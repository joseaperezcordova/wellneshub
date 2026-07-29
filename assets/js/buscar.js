/* ============================================================================
   El buscador de actividades.

   El panel de la izquierda manda, y la dirección del navegador va detrás: cada
   cambio la reescribe —buscar.php?ciudad=Tulum&cat=Yoga— sin recargar. De ahí
   salen tres cosas:

     · Las tarjetas se llevan la búsqueda puesta, así que desde la ficha de una
       actividad se puede volver a estos mismos resultados.
     · Recargar, compartir o guardar la página conserva la búsqueda.
     · PHP deja los controles puestos al abrir, leyendo esos mismos parámetros,
       de modo que la página llega con la búsqueda hecha y no parpadea.

   Se filtra aquí y no en el servidor a propósito: los eventos ya están todos en
   la página —eventosPublicados() trae hasta 60— y una recarga por cada casilla
   que se marca se nota más que cualquier ahorro. El día que la lista no quepa,
   esto se cambia por una consulta y el panel se queda igual.
   ========================================================================== */
(function () {
  'use strict';

  var $ = function (id) { return document.getElementById(id); };
  if (!$('resultsGrid')) return;

  var estado = {texto: '', entidad: '', ciudad: '', fecha: '', cats: [], gratis: false, orden: 'fecha'};

  /* Sin acentos y en minúsculas por los dos lados. Buscar «meditacion» tiene
     que encontrar «Meditación»: nadie escribe el acento en un buscador. */
  function llano(s) {
    s = String(s == null ? '' : s).toLowerCase();
    return s.normalize ? s.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : s;
  }

  function finDeDia(d) { return new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23, 59, 59); }

  /* El tramo de días que cubre cada opción de «Fecha». */
  function rango(clave) {
    if (!clave) return null;

    var ahora = new Date();
    var hoy   = new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate());

    if (clave === 'finde') {
      /* 0 es domingo y 6 sábado. En sábado o en domingo «este fin de semana» es
         el de hoy, no el de dentro de una semana: quien lo pulsa un sábado por
         la mañana está buscando planes para hoy. */
      var dia    = hoy.getDay();
      var inicio = new Date(hoy);
      if (dia !== 0 && dia !== 6) inicio.setDate(hoy.getDate() + (6 - dia));
      var fin = new Date(inicio);
      if (inicio.getDay() === 6) fin.setDate(inicio.getDate() + 1);
      return [inicio, finDeDia(fin)];
    }

    if (clave === '7dias') {
      // Siete días contando hoy, de ahí el +6. Con +7 el tramo cubre ocho
      // fechas distintas y el evento del final no cuadra con la etiqueta.
      var semana = new Date(hoy);
      semana.setDate(hoy.getDate() + 6);
      return [hoy, finDeDia(semana)];
    }

    // Lo que queda de mes. El día 0 del mes siguiente es el último de este.
    return [hoy, finDeDia(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0))];
  }

  /* Un retiro de cinco días que cruza el sábado cuenta como «este fin de
     semana», aunque empiece el miércoles. Por eso se comparan los dos extremos
     y no solo la fecha de inicio. */
  function enRango(ev, r) {
    if (!r) return true;
    var ini = ev.ini ? new Date(ev.ini) : null;
    if (!ini || isNaN(ini)) return false;
    var fin = ev.fin ? new Date(ev.fin) : ini;
    if (isNaN(fin)) fin = ini;
    return ini <= r[1] && fin >= r[0];
  }

  function pasa(ev, r) {
    if (estado.entidad && ev.entidad !== estado.entidad) return false;
    if (estado.ciudad  && ev.ciudad  !== estado.ciudad)  return false;
    if (estado.gratis  && !ev.free)                      return false;
    if (estado.cats.length && estado.cats.indexOf(ev.cat) === -1) return false;
    if (!enRango(ev, r)) return false;

    if (estado.texto) {
      /* Mira también categoría, lugar y organizador. Es lo que hace que el
         «Dónde» de la portada funcione: llega aquí como texto, y escribir
         «Tulum» encuentra Tulum sin necesitar un campo aparte. */
      var pajar = llano(ev.t + ' ' + ev.cat + ' ' + ev.city + ' ' + ev.org);
      if (pajar.indexOf(llano(estado.texto)) === -1) return false;
    }
    return true;
  }

  function porFecha(a, b) { return String(a.ini || '').localeCompare(String(b.ini || '')); }

  var ordenes = {
    fecha: porFecha,
    precio: function (a, b) {
      /* «Por confirmar» es null y se va al final: no es ni caro ni barato, y
         colarlo entre los precios reales rompe la lectura de la columna. */
      if (a.pnum === null && b.pnum === null) return porFecha(a, b);
      if (a.pnum === null) return 1;
      if (b.pnum === null) return -1;
      return a.pnum - b.pnum || porFecha(a, b);
    },
    nuevos: function (a, b) {
      return String(b.pub || '').localeCompare(String(a.pub || '')) || porFecha(a, b);
    }
  };

  function hayFiltros() {
    return !!(estado.texto || estado.entidad || estado.ciudad || estado.fecha
              || estado.cats.length || estado.gratis);
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
    if (estado.orden !== 'fecha') p.set('orden', estado.orden);

    return p.toString();
  }

  function vacioBusquedaHTML() {
    /* Sin actividades en la base y sin actividades que casen son dos vacíos
       distintos, y la salida de cada uno también: publicar la primera, o quitar
       filtros. */
    if (!EVENTOS.length) return vacioHTML('Todavía no hay actividades publicadas.');

    return '<div class="rail-vacio">'
      + '<p>Ninguna actividad coincide con lo que buscas.</p>'
      + '<button type="button" class="btn-vacio" id="vaciarFiltros">Quitar filtros</button>'
      + '</div>';
  }

  function aplicar() {
    var r     = rango(estado.fecha);
    var lista = EVENTOS.filter(function (ev) { return pasa(ev, r); });
    lista.sort(ordenes[estado.orden] || ordenes.fecha);

    /* Cada tarjeta se lleva la búsqueda pegada a su dirección. Es lo que hace
       que la ficha sepa a dónde volver aunque se abra en otra pestaña, donde el
       «atrás» del navegador no serviría de nada. */
    var cola = consulta();
    var ida  = cola ? 'volver=' + encodeURIComponent(cola) : '';

    $('resultsGrid').innerHTML = lista.length
      ? lista.map(function (ev) { return cardHTML(ev, ida); }).join('')
      : vacioBusquedaHTML();

    var vaciar = $('vaciarFiltros');
    if (vaciar) vaciar.addEventListener('click', limpiar);

    $('resultsCount').textContent =
      lista.length === 0 ? 'Ninguna actividad coincide'
      : lista.length === 1 ? '1 actividad encontrada'
      : lista.length + ' actividades encontradas';

    $('fLimpiar').hidden = !hayFiltros();

    /* replaceState y no pushState: con pushState cada casilla marcada dejaría
       una entrada en el historial y salir de la página costaría diez «atrás».
       Lo que interesa guardar es la dirección, no el camino. */
    var url = 'buscar.php' + (cola ? '?' + cola : '');
    history.replaceState(null, '', url);
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
    if (!hayFiltros()) return 'Todas las actividades';

    var que    = estado.cats.length === 1 ? estado.cats[0] : 'Actividades';
    var donde  = estado.ciudad || estado.entidad;
    var cuando = {finde: ' este fin de semana', '7dias': ' en los próximos 7 días', mes: ' este mes'};

    return (estado.gratis && que === 'Actividades' ? 'Actividades gratuitas' : que)
         + (donde ? ' en ' + donde : '')
         + (cuando[estado.fecha] || '');
  }

  function encabezar() { $('resultsTitle').textContent = resumen(); }

  function limpiar() {
    estado.texto = estado.entidad = estado.ciudad = estado.fecha = '';
    estado.cats  = [];
    estado.gratis = false;
    escribir();
    encabezar();
    aplicar();
  }

  function refrescar() { leer(); encabezar(); aplicar(); }

  // ---- enganches
  ['fTexto', 'fEstado', 'fCiudad', 'fFecha', 'fGratis', 'fOrden'].forEach(function (id) {
    // «input» y no «change» en el texto: la lista se va estrechando al teclear.
    $(id).addEventListener(id === 'fTexto' ? 'input' : 'change', refrescar);
  });

  $('fCats').addEventListener('change', refrescar);
  $('fLimpiar').addEventListener('click', limpiar);

  /* Al abrir, los controles ya vienen puestos desde PHP con lo que traía la
     dirección. Se leen de ahí en vez de volver a interpretar la dirección aquí:
     una sola manera de entrar, y si PHP descartó una categoría inventada, aquí
     tampoco aparece. */
  leer();
  encabezar();
  aplicar();
})();
