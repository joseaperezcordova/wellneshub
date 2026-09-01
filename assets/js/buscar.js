/* ============================================================================
   El buscador de actividades.

   El panel de la izquierda manda, y la dirección del navegador va detrás: cada
   cambio la reescribe —buscar.php?ciudad=Tulum&cat=Yoga— sin recargar. De ahí
   salen tres cosas:

     · Las tarjetas se llevan la búsqueda puesta, así que desde la ficha de una
       actividad se puede volver a estos mismos resultados.
     · Recargar, compartir o guardar la página conserva la búsqueda.
     · PHP deja los controles puestos al abrir, leyendo esos mismos parámetros,
       de modo que la página llega con la búsqueda hecha.

   El filtrado se resuelve en el servidor —buscar-datos.php—, no aquí: antes
   los eventos publicados venían todos incrustados en la página (hasta 60) y
   se filtraban en el navegador, y con más de 60 actividades esa lista dejaba
   de caber entera. Cargar la primera página y "Cargar más" son la misma
   función con distinto punto de partida, para que no se desincronicen.
   ========================================================================== */
(function () {
  'use strict';

  var $ = function (id) { return document.getElementById(id); };
  if (!$('resultsGrid')) return;

  // Lo imprime buscar.php, en el idioma de la página —ver BUSCAR_T ahí—.
  var T = window.BUSCAR_T;

  var PAGINA = 24;

  var estado    = {texto: '', entidad: '', ciudad: '', fecha: '', cats: [], gratis: false, orden: 'fecha'};
  var cargados  = 0;
  var total     = 0;
  var peticion  = 0; // se incrementa en cada cargar(): una respuesta vieja que
                      // llega tarde se descarta comparando contra este número.

  function vacioBusquedaHTML() {
    /* Sin actividades en la base y sin actividades que casen son dos vacíos
       distintos, y la salida de cada uno también: publicar la primera, o quitar
       filtros. Antes esto se sabía mirando el tamaño de la lista completa que
       ya estaba en el navegador; ahora lo dice hayFiltros(): sin ningún filtro
       puesto, cero resultados solo puede significar que el directorio está
       vacío. */
    if (!hayFiltros()) return vacioHTML(T.vacioDirectorio, T.publicarPrimera);

    return '<div class="rail-vacio">'
      + '<p>' + esc(T.sinResultados) + '</p>'
      + '<button type="button" class="btn-vacio" id="vaciarFiltros">' + esc(T.quitarFiltros) + '</button>'
      + '</div>';
  }

  /* La búsqueda como cadena de consulta. Tiene que dar lo mismo que
     consultaBusqueda() en includes/busqueda.php: PHP la lee al abrir la página
     y la ficha la usa para armar el enlace de vuelta. */
  function consulta() {
    var p = new URLSearchParams();

    if (estado.texto)        p.set('q',      estado.texto);
    if (estado.entidad)      p.set('estado', estado.entidad);
    if (estado.ciudad)       p.set('ciudad', estado.ciudad);
    if (estado.fecha)        p.set('fecha',  estado.fecha);
    if (estado.cats.length)  p.set('cat',    estado.cats.join(','));
    if (estado.gratis)       p.set('gratis', '1');
    /* El orden por defecto no se escribe en la direccion. Cual es lo dice PHP
       —ordenPorDefecto()—, no un literal aqui: escrito a mano en los dos sitios,
       cambiar el menu dejaria este a medias y la direccion se llenaria de un
       parametro que no hace nada. */
    if (estado.orden !== ORDEN_DEFECTO) p.set('orden', estado.orden);

    return p.toString();
  }

  function hayFiltros() {
    return !!(estado.texto || estado.entidad || estado.ciudad || estado.fecha
              || estado.cats.length || estado.gratis);
  }

  function pintarConteo() {
    if (total === 0) {
      $('resultsCount').textContent = T.sinCoincidencias;
    } else if (total === 1) {
      $('resultsCount').textContent = T.unaEncontrada;
    } else if (cargados < total) {
      $('resultsCount').textContent = cargados + T.deTotal + total + T.actividadesSufijo;
    } else {
      $('resultsCount').textContent = total + T.encontradasSufijo;
    }

    var boton = $('btnCargarMas');
    boton.hidden = cargados >= total;
  }

  /**
   * Pide una página de resultados al servidor.
   * @param {boolean} reiniciar true para una búsqueda nueva (reemplaza la
   *   grilla y vuelve a empezar en cero); false para "Cargar más" (añade).
   */
  function cargar(reiniciar) {
    var miPeticion = ++peticion;
    var offset     = reiniciar ? 0 : cargados;
    var qs         = consulta();
    var url        = 'buscar-datos.php' + (qs ? '?' + qs + '&' : '?') + 'offset=' + offset;

    if (reiniciar) {
      $('resultsGrid').innerHTML = '<div class="rail-cargando">' + esc(T.buscando) + '</div>';
      $('btnCargarMas').hidden = true;
    } else {
      $('btnCargarMas').disabled = true;
      $('btnCargarMas').textContent = T.cargando;
    }

    fetch(url)
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        if (miPeticion !== peticion) return; // llegó tarde: ya no aplica

        total = json.total;

        /* Cada tarjeta se lleva la búsqueda pegada a su dirección. Es lo que
           hace que la ficha sepa a dónde volver aunque se abra en otra
           pestaña, donde el «atrás» del navegador no serviría de nada. */
        var ida    = qs ? 'volver=' + encodeURIComponent(qs) : '';
        var trozos = json.eventos.map(function (ev) { return cardHTML(ev, ida); });

        if (reiniciar) {
          cargados = json.eventos.length;
          $('resultsGrid').innerHTML = trozos.length ? trozos.join('') : vacioBusquedaHTML();
          var vaciar = $('vaciarFiltros');
          if (vaciar) vaciar.addEventListener('click', limpiar);
        } else {
          cargados += json.eventos.length;
          $('resultsGrid').insertAdjacentHTML('beforeend', trozos.join(''));
        }

        pintarConteo();

        var boton = $('btnCargarMas');
        boton.disabled = false;
        boton.textContent = T.cargarMas;
      })
      .catch(function () {
        if (miPeticion !== peticion) return;
        if (reiniciar) {
          $('resultsGrid').innerHTML = '<div class="rail-vacio"><p>' + esc(T.error) + '</p></div>';
        }
      });

    /* replaceState y no pushState: con pushState cada casilla marcada dejaría
       una entrada en el historial y salir de la página costaría diez «atrás».
       Lo que interesa guardar es la dirección, no el camino. El offset no
       viaja en la URL: compartir una búsqueda tiene que empezar en la primera
       página, no donde se quedó quien la mandó. */
    /* location.pathname y no 'buscar.php' escrito a mano (REQ-00006): la
       direccion publica es /actividades, y reescribirla como buscar.php
       cambiaba la barra del navegador a la direccion interna en cuanto se
       tocaba un filtro. Lo que se copiara de ahi ya no era la direccion que se
       publica. Con pathname se conserva la que sea —tambien /activities el dia
       que el listado exista en ingles. */
    history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
  }

  function leer() {
    estado.texto   = $('fTexto').value.trim();
    estado.entidad = $('fEstado').value;
    estado.ciudad  = $('fCiudad').value;
    estado.fecha   = $('fFecha').value;
    estado.gratis  = $('fGratis').checked;
    estado.orden   = $('fOrden').value;
    estado.cats    = [].slice.call($('fCats').querySelectorAll('input:checked'))
                       .map(function (c) { return c.value; });
  }

  function escribir() {
    $('fTexto').value    = estado.texto;
    $('fEstado').value   = estado.entidad;
    $('fCiudad').value   = estado.ciudad;
    $('fFecha').value    = estado.fecha;
    $('fGratis').checked = estado.gratis;
    $('fOrden').value    = estado.orden;
    [].forEach.call($('fCats').querySelectorAll('input'), function (c) {
      c.checked = estado.cats.indexOf(c.value) !== -1;
    });
  }

  /* El título dice lo que se pidió. «Todas las actividades» encima de una lista
     que no son todas es la clase de detalle que hace dudar de la página entera. */
  function resumen() {
    if (!hayFiltros()) return T.todas;

    var que    = estado.cats.length === 1 ? estado.cats[0] : T.actividades;
    var donde  = estado.ciudad || estado.entidad;
    var cuando = {finde: T.findeSufijo, '7dias': T.sieteDiasSufijo, mes: T.mesSufijo};

    return (estado.gratis && que === T.actividades ? T.gratuitas : que)
         + (donde ? T.en + donde : '')
         + (cuando[estado.fecha] || '');
  }

  function encabezar() { $('resultsTitle').textContent = resumen(); }

  function limpiar() {
    estado.texto = estado.entidad = estado.ciudad = estado.fecha = '';
    estado.cats  = [];
    estado.gratis = false;
    escribir();
    encabezar();
    $('fLimpiar').hidden = true;
    cargar(true);
  }

  function refrescar() {
    leer();
    encabezar();
    $('fLimpiar').hidden = !hayFiltros();
    cargar(true);
  }

  // ---- enganches
  var temporizadorTexto = null;

  /* whTrack() la define includes/layout.php —de verdad si hay GA4, en blanco
     si no—, así que aquí no hace falta comprobar si existe. */
  ['fEstado', 'fCiudad', 'fFecha', 'fGratis', 'fOrden'].forEach(function (id) {
    $(id).addEventListener('change', function () {
      whTrack('filtro_aplicado', {campo: id});
      refrescar();
    });
  });
  $('fCats').addEventListener('change', function () {
    whTrack('filtro_aplicado', {campo: 'categoria'});
    refrescar();
  });

  /* El texto sí espera: cada tecla ya dispara una consulta al servidor, y sin
     esto una palabra de ocho letras mandaría ocho peticiones para quedarse
     solo con la última. peticion (arriba) descarta las respuestas que de
     todos modos lleguen fuera de orden. El evento de búsqueda se manda
     después de refrescar() —no en cada tecla— para no llenar GA4 de un
     evento por letra mientras alguien todavía está escribiendo. */
  $('fTexto').addEventListener('input', function () {
    clearTimeout(temporizadorTexto);
    temporizadorTexto = setTimeout(function () {
      refrescar();
      if (estado.texto) whTrack('buscar', {termino: estado.texto});
    }, 300);
  });

  $('fLimpiar').addEventListener('click', limpiar);
  $('btnCargarMas').addEventListener('click', function () { cargar(false); });

  /* Al abrir, los controles ya vienen puestos desde PHP con lo que traía la
     dirección. Se leen de ahí en vez de volver a interpretar la dirección aquí:
     una sola manera de entrar, y si PHP descartó una categoría inventada, aquí
     tampoco aparece. */
  leer();
  encabezar();
  $('fLimpiar').hidden = !hayFiltros();
  cargar(true);
})();
