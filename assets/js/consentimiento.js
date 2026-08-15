/* ============================================================================
   Consentimiento de cookies (REQ-00003).

   Este archivo es el unico sitio del que salen GA4, Clarity y el pixel de Meta.
   Antes iban escritos en la cabecera y se ejecutaban solos; ahora no existen en
   el HTML: se inyectan aqui, y solo despues de que alguien haya dicho que si.

   POR QUE SE CARGA EN LA CABECERA Y NO AL FINAL

   Va sin defer, en el <head>, porque la activacion tiene que ocurrir ANTES de
   que la pagina dispare sus propios eventos. layout.php lanza whTrack() al
   principio del <body> para los eventos que vienen de una redireccion
   —publicar, editar—: si esto se cargara al final, gtag todavia no existiria y
   esos eventos se perderian en silencio, que es la peor forma de perderlos.

   El montaje de la interfaz si espera al DOM. La activacion no necesita HTML.
   ========================================================================== */
(function () {
  'use strict';

  var cfg = window.OMDARA_COOKIES;
  if (!cfg) return;

  var CATEGORIAS = ['analiticas', 'marketing'];
  var activadas = {};

  /* ---------- la cookie ---------- */

  function leerCookie(nombre) {
    var partes = document.cookie ? document.cookie.split('; ') : [];
    for (var i = 0; i < partes.length; i++) {
      var corte = partes[i].indexOf('=');
      if (corte > 0 && partes[i].slice(0, corte) === nombre) {
        return decodeURIComponent(partes[i].slice(corte + 1));
      }
    }
    return null;
  }

  function leerRespuesta() {
    var crudo = leerCookie(cfg.cookie);
    if (!crudo) return null;
    try {
      var d = JSON.parse(crudo);
      /* De una version anterior: no vale. Se vuelve a preguntar, porque quien
         acepto "analiticas" cuando eso era otra lista de herramientas no
         acepto las que se hayan añadido despues. */
      if (d.v !== cfg.version) return null;
      return { analiticas: !!d.analiticas, marketing: !!d.marketing };
    } catch (e) {
      return null;
    }
  }

  function guardarRespuesta(r) {
    var valor = JSON.stringify({
      v: cfg.version,
      analiticas: !!r.analiticas,
      marketing: !!r.marketing,
      fecha: new Date().toISOString().slice(0, 10)
    });

    document.cookie = cfg.cookie + '=' + encodeURIComponent(valor) +
      ';path=/;max-age=' + (cfg.dias * 86400) + ';samesite=Lax' +
      (cfg.seguro ? ';secure' : '');
  }

  /* ---------- activar cada herramienta ---------- */

  function inyectar(src) {
    var s = document.createElement('script');
    s.async = true;
    s.src = src;
    (document.head || document.documentElement).appendChild(s);
  }

  var arranques = {
    ga4: function (id) {
      /* El stub de gtag se define AQUI MISMO, sincrono, antes de que llegue el
         archivo de Google. Asi whTrack() ya encuentra la funcion y los eventos
         se encolan en dataLayer en vez de perderse esperando a la red. */
      window.dataLayer = window.dataLayer || [];
      window.gtag = function () { window.dataLayer.push(arguments); };
      window.gtag('js', new Date());
      window.gtag('config', id);
      inyectar('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id));
    },

    clarity: function (id) {
      window.clarity = window.clarity || function () {
        (window.clarity.q = window.clarity.q || []).push(arguments);
      };
      inyectar('https://www.clarity.ms/tag/' + encodeURIComponent(id));
    },

    meta_pixel: function (id) {
      /* El snippet oficial de Meta, con el mismo truco: la cola primero, el
         archivo despues. */
      var n = window.fbq = function () {
        n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
      };
      if (!window._fbq) window._fbq = n;
      n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
      inyectar('https://connect.facebook.net/en_US/fbevents.js');
      window.fbq('init', id);
      window.fbq('track', 'PageView');
    }
  };

  function activar(respuesta) {
    for (var i = 0; i < CATEGORIAS.length; i++) {
      var cat = CATEGORIAS[i];
      if (!respuesta[cat]) continue;

      var herramientas = (cfg.herramientas && cfg.herramientas[cat]) || {};
      for (var nombre in herramientas) {
        if (!Object.prototype.hasOwnProperty.call(herramientas, nombre)) continue;
        if (activadas[nombre] || !arranques[nombre]) continue;
        activadas[nombre] = true;
        arranques[nombre](herramientas[nombre]);
      }
    }
  }

  /* ---------- retirar el permiso ---------- */

  /* Un script ya cargado no se descarga. Si alguien retira un permiso que habia
     dado, lo unico honesto es borrar lo que dejaron y recargar la pagina para
     empezar limpio; dejarlo correr hasta la siguiente visita seria seguir
     midiendo a quien acaba de decir que no.

     Solo se pueden borrar las cookies de NUESTRO dominio: _ga, _clck, _fbp y
     compañia. Las que Meta o Microsoft ponen en los suyos —"fr" en
     facebook.com, "MUID" en clarity.ms— no se tocan desde aqui; eso se hace
     desde el navegador, y la Politica de Cookies lo dice. */
  var COOKIES_DE_TERCEROS = {
    analiticas: ['_ga', '_gid', '_gat', '_gcl_au', '_clck', '_clsk'],
    marketing: ['_fbp', '_fbc']
  };

  function borrarCookie(nombre) {
    var host = location.hostname;
    var dominios = ['', host, '.' + host];

    /* El dominio padre tambien, que es donde suele quedar _ga cuando el sitio
       vive en un subdominio: sin esto la cookie parece borrada y vuelve. */
    var partes = host.split('.');
    if (partes.length > 2) dominios.push('.' + partes.slice(-2).join('.'));

    for (var i = 0; i < dominios.length; i++) {
      document.cookie = nombre + '=;path=/;max-age=0' +
        (dominios[i] ? ';domain=' + dominios[i] : '');
    }
  }

  function limpiarCategoria(cat) {
    var prefijos = COOKIES_DE_TERCEROS[cat] || [];
    var actuales = document.cookie ? document.cookie.split('; ') : [];

    for (var i = 0; i < actuales.length; i++) {
      var nombre = actuales[i].split('=')[0];
      for (var j = 0; j < prefijos.length; j++) {
        /* Por prefijo y no por nombre exacto: GA4 pone "_ga_G-XXXXXXX", con el
           ID del flujo pegado detras. */
        if (nombre.indexOf(prefijos[j]) === 0) { borrarCookie(nombre); break; }
      }
    }
  }

  /* ---------- la interfaz ---------- */

  function montar() {
    var banner = document.getElementById('cookies-banner');
    var panel  = document.getElementById('cookies-panel');
    if (!banner || !panel) return;

    var previa = leerRespuesta();
    var ultimoFoco = null;

    function verBanner(si) { banner.hidden = !si; }

    function verPanel(si) {
      panel.hidden = !si;
      document.body.style.overflow = si ? 'hidden' : '';
      if (si) {
        ultimoFoco = document.activeElement;
        var casillas = panel.querySelectorAll('input[type=checkbox]');
        for (var i = 0; i < casillas.length; i++) {
          casillas[i].checked = previa ? !!previa[casillas[i].value] : false;
        }
        var primero = panel.querySelector('input, button');
        if (primero) primero.focus();
      } else if (ultimoFoco) {
        ultimoFoco.focus();
      }
    }

    function decidir(respuesta) {
      var antes = previa || { analiticas: false, marketing: false };
      var retiradas = [];

      guardarRespuesta(respuesta);
      previa = respuesta;

      /* Se recorren TODAS antes de recargar. Salir en la primera dejaria las
         cookies de la segunda categoria puestas, y despues de la recarga ya no
         hay ningun momento en el que se vuelvan a mirar. */
      for (var i = 0; i < CATEGORIAS.length; i++) {
        if (antes[CATEGORIAS[i]] && !respuesta[CATEGORIAS[i]]) retiradas.push(CATEGORIAS[i]);
      }

      if (retiradas.length) {
        for (var j = 0; j < retiradas.length; j++) limpiarCategoria(retiradas[j]);
        verPanel(false);
        location.reload();
        return;
      }

      activar(respuesta);
      verPanel(false);
      verBanner(false);
    }

    document.addEventListener('click', function (ev) {
      var boton = ev.target.closest ? ev.target.closest('[data-cookies]') : null;
      if (!boton) return;

      ev.preventDefault();

      switch (boton.getAttribute('data-cookies')) {
        case 'todas':
          decidir({ analiticas: true, marketing: true });
          break;

        case 'ninguna':
          decidir({ analiticas: false, marketing: false });
          break;

        case 'configurar':
          verPanel(true);
          break;

        case 'cerrar':
          verPanel(false);
          /* Cerrar sin guardar no es una respuesta: si nunca contesto, el
             banner tiene que seguir ahi. */
          if (!previa) verBanner(true);
          break;

        case 'guardar':
          var elegido = { analiticas: false, marketing: false };
          var casillas = panel.querySelectorAll('input[type=checkbox]');
          for (var i = 0; i < casillas.length; i++) {
            if (casillas[i].checked) elegido[casillas[i].value] = true;
          }
          decidir(elegido);
          break;
      }
    });

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && !panel.hidden) {
        verPanel(false);
        if (!previa) verBanner(true);
      }
    });

    if (!previa) verBanner(true);
  }

  /* Lo primero, y sincrono: si ya hay respuesta, las herramientas permitidas
     arrancan antes de que la pagina empiece a medir nada. */
  var respuestaPrevia = leerRespuesta();
  if (respuestaPrevia) activar(respuestaPrevia);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', montar);
  } else {
    montar();
  }
})();
