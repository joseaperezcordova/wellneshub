/* ============================================================================
   Lo que se mueve en la portada: el carrusel del banner, los dos carriles
   horizontales y el acuse del boletín.
   ========================================================================== */
(function () {
  'use strict';

  /* ---------- el carril de próximas actividades ---------- */
  // INICIO_T lo imprime index.php, en el idioma de la página.
  pintar('proximosRail', EVENTOS.map(evCardHTML), INICIO_T.vacio, INICIO_T.publicarPrimera);

  carril('catrail', 'catnext');
  carril('proximosRail', 'evnext');

  /* ---------- carrusel del banner ---------- */
  var raiz = document.getElementById('carrusel');
  if (raiz) {
    var slides = [].slice.call(raiz.querySelectorAll('.slide'));
    var puntos = [].slice.call(document.querySelectorAll('#cdots button'));
    var banner = raiz.parentNode;
    var heroTitulo = document.getElementById('heroTitulo');
    var heroSub = document.getElementById('heroSub');
    var i = 0, reloj = null;
    var ESPERA = 4000;

    /* Si el sistema pide menos movimiento, el carrusel no gira solo. Las flechas
       y los puntos siguen ahí: se quita el automatismo, no el control. */
    var quieto = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var ir = function (n) {
      i = (n + slides.length) % slides.length;
      slides.forEach(function (s, k) {
        s.classList.toggle('activa', k === i);
        s.setAttribute('aria-hidden', k === i ? 'false' : 'true');
      });
      puntos.forEach(function (p, k) {
        p.setAttribute('aria-current', k === i ? 'true' : 'false');
      });
      // El titular y el subtexto viven en la diapositiva (data-titulo/data-sub)
      // y aquí solo se copian: así cada idioma sale ya resuelto por PHP y este
      // archivo no necesita saber en qué idioma está la página.
      if (heroTitulo && heroSub) {
        heroTitulo.textContent = slides[i].dataset.titulo;
        heroSub.textContent = slides[i].dataset.sub;
      }
    };

    var parar    = function () { if (reloj) { clearInterval(reloj); reloj = null; } };
    var arrancar = function () {
      parar();
      if (!quieto) reloj = setInterval(function () { ir(i + 1); }, ESPERA);
    };

    document.getElementById('cprev').addEventListener('click', function () { ir(i - 1); arrancar(); });
    document.getElementById('cnext').addEventListener('click', function () { ir(i + 1); arrancar(); });
    puntos.forEach(function (p, k) {
      p.addEventListener('click', function () { ir(k); arrancar(); });
    });

    /* Se detiene con el ratón encima o con el foco dentro. Un carrusel que sigue
       girando mientras lees o mientras tabulas por sus botones es la queja
       clásica de este patrón. */
    banner.addEventListener('mouseenter', parar);
    banner.addEventListener('mouseleave', arrancar);
    banner.addEventListener('focusin',   parar);
    banner.addEventListener('focusout',  arrancar);

    // En una pestaña de fondo tampoco tiene sentido seguir contando.
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) parar(); else arrancar();
    });

    ir(0);
    arrancar();
  }

  /* ---------- boletín ----------
     Todavía no guarda nada: no hay lista de correo detrás. Enseña el acuse y
     limpia el campo, que es lo que hacía en el prototipo. */
  var boletin = document.getElementById('boletin');
  if (boletin) {
    boletin.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var t = boletin.parentElement.querySelector('.toast');
      if (t) t.classList.add('show');
      boletin.reset();
    });
  }
})();
