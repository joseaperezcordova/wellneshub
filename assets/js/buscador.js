/* ============================================================================
   Los tres desplegables del buscador de la portada (REQ-00005).

   POR QUE HAY QUE SUSTITUIR LOS CONTROLES NATIVOS

   Los tres campos se abrian distinto porque los tres eran controles distintos,
   y ninguno de los tres se puede estilar:

     Donde   <input list> con <datalist>. El navegador dibuja la lista a su
             manera, estrecha y pegada al borde del campo: es la «nube de
             dialogo» del requerimiento.
     Cuando  <select> de cuatro opciones. Cabe debajo, asi que se abre hacia
             abajo y del ancho del campo — el comportamiento de referencia.
     Que     <select> de veintitres. No cabe debajo, asi que el navegador lo
             abre hacia arriba. No es un fallo: es lo que hace un <select>
             cuando no hay sitio, y no hay CSS que lo impida.

   La ventana de un <select> la pinta el sistema operativo, no la pagina. No
   admite ni ancho, ni color, ni direccion de apertura. Para que los tres se
   comporten igual hay que dejar de usarla, y eso obliga a construir la lista
   —con su teclado y su semantica— en vez de heredarla.

   LO QUE NO SE TIRA

   El <select> se queda en el formulario, oculto: sigue siendo el que viaja en
   el GET. Asi el formulario funciona igual si este archivo falla o tarda, y
   sin JavaScript los tres campos vuelven a ser lo que eran —feos y
   descoordinados, pero utiles—. Un buscador que deja de buscar porque no cargo
   un .js es peor que uno que se abre torcido.

   PATRON ARIA

   Es el «combobox» de la guia de accesibilidad de W3C: el disparador lleva
   role="combobox" y aria-expanded, la lista role="listbox" y cada fila
   role="option". El foco NO se mueve a la lista; se queda en el disparador y
   aria-activedescendant dice cual esta marcada. Es lo que permite que en
   «Donde» se pueda seguir escribiendo con la lista abierta.
   ========================================================================== */
(function () {
  'use strict';

  var barra = document.querySelector('.buscador');
  if (!barra) return;

  var TECLEO_MS = 700;   /* cuanto se acumulan las letras del salto por teclado */
  var contador = 0;

  function nuevoId(prefijo) { contador++; return prefijo + contador; }

  function opcionDe(ev) {
    return ev.target && ev.target.closest ? ev.target.closest('.bopcion') : null;
  }

  /* ------------------------------------------------------------------ */

  function Desplegable(campo, disparador, panel, alElegir, esTexto) {
    var yo = this;

    this.campo = campo;
    this.disparador = disparador;
    this.panel = panel;
    this.alElegir = alElegir;
    this.esTexto = !!esTexto;
    this.activa = null;
    this.tecleo = '';
    this.relojTecleo = null;

    /* mousedown y no click: con click, el navegador ya ha movido el foco fuera
       del campo y el blur habria cerrado el panel antes de que llegue el
       evento. preventDefault ademas evita que el foco se mueva siquiera. */
    panel.addEventListener('mousedown', function (ev) {
      var op = opcionDe(ev);
      if (!op) return;
      ev.preventDefault();
      yo.elegir(op);
    });

    panel.addEventListener('mousemove', function (ev) {
      var op = opcionDe(ev);
      if (op) yo.marcar(op, false);
    });

    disparador.addEventListener('keydown', function (ev) { yo.tecla(ev); });
    disparador.addEventListener('blur', function () { yo.cerrar(false); });
  }

  /** Las opciones que se ven ahora mismo. En «Donde» la lista se filtra segun
      lo que haya escrito, asi que las ocultas no cuentan para nada. */
  Desplegable.prototype.opciones = function () {
    var todas = this.panel.querySelectorAll('.bopcion');
    var vivas = [];
    for (var i = 0; i < todas.length; i++) {
      if (!todas[i].hidden) vivas.push(todas[i]);
    }
    return vivas;
  };

  Desplegable.prototype.abierto = function () {
    return !this.panel.hidden;
  };

  Desplegable.prototype.abrir = function () {
    if (this.abierto()) return;

    cerrarOtros(this);

    this.panel.hidden = false;
    this.campo.classList.add('abierto');
    this.disparador.setAttribute('aria-expanded', 'true');

    /* Se marca la que ya estuviera elegida, para que las flechas empiecen
       desde donde esta el valor actual y no desde el principio.

       En «Donde» NO se marca nada por defecto. Si se marcara la primera
       sugerencia, pulsar Intro despues de escribir sustituiria lo escrito por
       ella —teclear «oax» y buscar «Oaxaca de Juarez» sin haberlo pedido—, y
       ese campo tambien busca por titulo y por quien organiza, asi que lo
       escrito vale por si mismo. */
    var elegida = this.panel.querySelector('.bopcion[aria-selected="true"]');
    if (elegida && elegida.hidden) elegida = null;

    this.marcar(elegida || (this.esTexto ? null : this.opciones()[0] || null), true);
  };

  Desplegable.prototype.cerrar = function (devolverFoco) {
    if (!this.abierto()) return;

    this.panel.hidden = true;
    this.campo.classList.remove('abierto');
    this.disparador.setAttribute('aria-expanded', 'false');
    this.disparador.removeAttribute('aria-activedescendant');

    if (this.activa) this.activa.classList.remove('activa');
    this.activa = null;

    if (devolverFoco) this.disparador.focus();
  };

  Desplegable.prototype.alternar = function () {
    if (this.abierto()) this.cerrar(true); else this.abrir();
  };

  /** Marca una opcion como «la que se elegiria ahora». No la elige. */
  Desplegable.prototype.marcar = function (op, desplazar) {
    if (this.activa) this.activa.classList.remove('activa');
    this.activa = op;

    if (!op) {
      this.disparador.removeAttribute('aria-activedescendant');
      return;
    }

    op.classList.add('activa');
    this.disparador.setAttribute('aria-activedescendant', op.id);

    if (!desplazar) return;

    /* scrollIntoView a secas desplaza tambien la pagina cuando el panel esta
       parcialmente fuera de la pantalla. Aqui solo interesa mover el panel. */
    var alto = this.panel.clientHeight;
    var arriba = op.offsetTop;
    var abajo = arriba + op.offsetHeight;

    if (arriba < this.panel.scrollTop) this.panel.scrollTop = arriba;
    else if (abajo > this.panel.scrollTop + alto) this.panel.scrollTop = abajo - alto;
  };

  Desplegable.prototype.mover = function (salto) {
    var vivas = this.opciones();
    if (!vivas.length) return;

    var i = this.activa ? vivas.indexOf(this.activa) : -1;
    i = i < 0 ? (salto > 0 ? 0 : vivas.length - 1) : i + salto;

    /* Sin dar la vuelta: llegar al final y volver al principio de golpe hace
       perder de vista donde estabas. */
    if (i < 0) i = 0;
    if (i > vivas.length - 1) i = vivas.length - 1;

    this.marcar(vivas[i], true);
  };

  Desplegable.prototype.elegir = function (op) {
    if (!op) return;

    var todas = this.panel.querySelectorAll('.bopcion');
    for (var i = 0; i < todas.length; i++) {
      todas[i].setAttribute('aria-selected', todas[i] === op ? 'true' : 'false');
    }

    this.alElegir(op.getAttribute('data-valor'), op.textContent);
    this.cerrar(true);
  };

  /** Salto por letra: escribir «tem» con la lista abierta lleva a Temazcal.
      Con veintitres practicas, bajar a flechazos es una lista de la compra. */
  Desplegable.prototype.saltarPorLetra = function (letra) {
    var yo = this;

    this.tecleo += letra.toLowerCase();
    clearTimeout(this.relojTecleo);
    this.relojTecleo = setTimeout(function () { yo.tecleo = ''; }, TECLEO_MS);

    var vivas = this.opciones();
    for (var i = 0; i < vivas.length; i++) {
      if (vivas[i].textContent.toLowerCase().indexOf(this.tecleo) === 0) {
        this.marcar(vivas[i], true);
        return;
      }
    }
  };

  Desplegable.prototype.tecla = function (ev) {
    if (!ev.key) return;   /* navegador sin KeyboardEvent.key: se deja pasar */

    var abierto = this.abierto();

    switch (ev.key) {
      case 'ArrowDown':
        ev.preventDefault();
        if (abierto) this.mover(1); else this.abrir();
        return;

      case 'ArrowUp':
        ev.preventDefault();
        if (abierto) this.mover(-1); else this.abrir();
        return;

      case 'Home':
        if (!abierto || this.esTexto) return;   /* en un texto, Inicio es del cursor */
        ev.preventDefault();
        this.mover(-999);
        return;

      case 'End':
        if (!abierto || this.esTexto) return;
        ev.preventDefault();
        this.mover(999);
        return;

      case 'Enter':
        if (!abierto || !this.activa) return;   /* cerrado: que el formulario se envie */
        ev.preventDefault();
        this.elegir(this.activa);
        return;

      case ' ':
        /* En un campo de texto el espacio es un espacio. */
        if (this.esTexto) return;
        ev.preventDefault();
        if (abierto) this.elegir(this.activa); else this.abrir();
        return;

      case 'Escape':
        if (!abierto) return;
        ev.preventDefault();
        this.cerrar(true);
        return;

      case 'Tab':
        this.cerrar(false);
        return;
    }

    /* Una letra suelta sobre un desplegable cerrado o abierto: salto por letra.
       En «Donde» no, porque ahi escribir filtra. */
    if (!this.esTexto && ev.key.length === 1 && !ev.ctrlKey && !ev.metaKey && !ev.altKey) {
      if (!abierto) this.abrir();
      this.saltarPorLetra(ev.key);
    }
  };

  /* ------------------------------------------------------------------ */

  var todos = [];

  function cerrarOtros(salvo) {
    for (var i = 0; i < todos.length; i++) {
      if (todos[i] !== salvo) todos[i].cerrar(false);
    }
  }

  function nuevoPanel() {
    var panel = document.createElement('div');
    panel.className = 'bdrop';
    panel.id = nuevoId('bdrop');
    panel.setAttribute('role', 'listbox');
    panel.hidden = true;
    return panel;
  }

  function nuevaOpcion(valor, texto, elegida) {
    var op = document.createElement('div');
    op.className = 'bopcion';
    op.id = nuevoId('bopcion');
    op.setAttribute('role', 'option');
    op.setAttribute('aria-selected', elegida ? 'true' : 'false');
    op.setAttribute('data-valor', valor);
    op.textContent = texto;
    return op;
  }

  /* ---------- «Cuándo» y «Qué»: encima de un <select> ---------- */

  function montarSelect(campo) {
    var select = campo.querySelector('select');
    var etiqueta = campo.querySelector('label');
    if (!select || !etiqueta) return;

    var panel = nuevoPanel();
    for (var i = 0; i < select.options.length; i++) {
      var o = select.options[i];
      panel.appendChild(nuevaOpcion(o.value, o.text, o.selected));
    }

    var boton = document.createElement('button');
    boton.type = 'button';                 /* sin esto envia el formulario */
    boton.className = 'bvalor';
    boton.id = nuevoId('bvalor');
    boton.setAttribute('role', 'combobox');
    boton.setAttribute('aria-expanded', 'false');
    boton.setAttribute('aria-controls', panel.id);
    boton.innerHTML = '<span class="btexto"></span><span class="bflecha" aria-hidden="true"></span>';
    boton.querySelector('.btexto').textContent =
      (select.options[select.selectedIndex] || select.options[0]).text;

    /* El nombre que anuncia un lector de pantalla: «Cuándo, Cualquier fecha».
       Con <label for> a secas diria solo «Cuándo» —la etiqueta gana al
       contenido del boton— y se perderia el valor, que es justo lo que hay que
       saber antes de abrirlo. */
    if (!etiqueta.id) etiqueta.id = nuevoId('blabel');
    boton.setAttribute('aria-labelledby', etiqueta.id + ' ' + boton.id);
    etiqueta.htmlFor = boton.id;

    /* El <select> se queda: es el que lleva el name y por tanto el que viaja
       en el GET. Oculto no se tabula ni lo lee nadie, pero sigue enviandose. */
    select.hidden = true;
    select.setAttribute('tabindex', '-1');
    select.setAttribute('aria-hidden', 'true');

    campo.insertBefore(boton, select);
    campo.appendChild(panel);

    var desplegable = new Desplegable(campo, boton, panel, function (valor, texto) {
      select.value = valor;
      boton.querySelector('.btexto').textContent = texto;
    }, false);

    /* click y no mousedown: asi pulsar la etiqueta «Cuándo» tambien abre el
       desplegable —el for del <label> genera un click, no un mousedown— y
       Enter con el panel cerrado hace lo mismo sin codigo aparte. El focus()
       explicito es por Safari en macOS, que no da el foco a un boton al
       pulsarlo. */
    boton.addEventListener('click', function () {
      boton.focus();
      desplegable.alternar();
    });

    todos.push(desplegable);
  }

  /* ---------- «Dónde»: encima de un <input> con sugerencias ---------- */

  function montarTexto(campo) {
    var input = campo.querySelector('input[type=text]');
    if (!input) return;

    var origen = document.getElementById(input.getAttribute('list') || '');
    if (!origen) return;

    var panel = nuevoPanel();
    var opciones = origen.querySelectorAll('option');
    for (var i = 0; i < opciones.length; i++) {
      panel.appendChild(nuevaOpcion(opciones[i].value, opciones[i].value, false));
    }
    if (!panel.children.length) return;   /* sin actividades no hay nada que proponer */

    /* Se quita el list para que el navegador no dibuje ADEMAS su propia lista
       nativa: se verian las dos, una encima de otra. El <datalist> se queda en
       el documento por si este archivo no llega a ejecutarse. */
    input.removeAttribute('list');

    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-controls', panel.id);
    input.setAttribute('aria-autocomplete', 'list');

    campo.appendChild(panel);

    var desplegable = new Desplegable(campo, input, panel, function (valor) {
      input.value = valor;
    }, true);

    function filtrar() {
      var buscado = input.value.trim().toLowerCase();
      var vivas = 0;
      var todasLas = panel.querySelectorAll('.bopcion');

      for (var i = 0; i < todasLas.length; i++) {
        /* Por «contiene» y no por «empieza por»: quien escribe «juarez» busca
           Oaxaca de Juarez, y quien escribe «paz» busca La Paz. */
        var cabe = buscado === '' ||
                   todasLas[i].textContent.toLowerCase().indexOf(buscado) !== -1;
        todasLas[i].hidden = !cabe;
        if (cabe) vivas++;
      }

      return vivas;
    }

    input.addEventListener('input', function () {
      if (filtrar() === 0) {
        /* Cero coincidencias: se cierra en vez de enseñar un panel vacio. Lo
           que hay escrito sigue siendo una busqueda valida —«Donde» busca
           tambien en el titulo y en quien organiza—, asi que no es un error. */
        desplegable.cerrar(false);
        return;
      }
      if (!desplegable.abierto()) desplegable.abrir();
      /* Si al filtrar desaparece la que estaba marcada, se desmarca: dejarla
         marcada haria que Intro eligiera algo que ya no se ve. */
      else if (desplegable.activa && desplegable.activa.hidden) desplegable.marcar(null, false);
    });

    input.addEventListener('mousedown', function () {
      /* Sin preventDefault: en un campo de texto el clic tiene que poder
         colocar el cursor. */
      if (!desplegable.abierto() && filtrar() > 0) desplegable.abrir();
    });

    input.addEventListener('focus', function () {
      if (filtrar() > 0) desplegable.abrir();
    });

    todos.push(desplegable);
  }

  /* ------------------------------------------------------------------ */

  var campos = barra.querySelectorAll('.bcampo');
  for (var i = 0; i < campos.length; i++) {
    if (campos[i].querySelector('select')) montarSelect(campos[i]);
    else montarTexto(campos[i]);
  }

  /* Un clic fuera de la barra cierra lo que hubiera abierto. En mousedown para
     que ocurra antes de que el clic haga cualquier otra cosa. */
  document.addEventListener('mousedown', function (ev) {
    if (!barra.contains(ev.target)) cerrarOtros(null);
  });

  /* Al enviar, ningun panel debe quedarse abierto sobre la pagina siguiente
     mientras esta carga. */
  barra.addEventListener('submit', function () { cerrarOtros(null); });
})();
