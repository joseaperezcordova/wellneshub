/* ============================================================================
   Lo que hace la cabecera, en todas las páginas.

   Vivía dentro de index.php cuando la portada era el sitio entero. Al repartir
   el sitio en páginas, el menú de hamburguesa y el de cuenta tenían que seguir
   funcionando en buscar.php, en blog.php y en la ficha, así que salieron aquí.

   Sin dependencias y sin esperar a DOMContentLoaded: el archivo se carga al
   final del <body>, con lo que todo lo que busca ya existe.
   ========================================================================== */
(function () {
  'use strict';

  /* ---------- menú de hamburguesa ---------- */
  var burger = document.getElementById('burger');
  var menu   = document.getElementById('mainnav');

  if (burger && menu) {
    burger.addEventListener('click', function () {
      var abierto = menu.classList.toggle('open');
      burger.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
  }

  /* ---------- menú de cuenta ----------
     Es un <details>, así que abre y cierra sin ayuda. Lo único que hace falta
     es cerrarlo al pulsar fuera: si no, se queda abierto flotando sobre la
     página mientras se lee otra cosa. */
  var cuenta = document.querySelector('details.cuenta');

  if (cuenta) {
    document.addEventListener('click', function (ev) {
      if (cuenta.open && !cuenta.contains(ev.target)) cuenta.open = false;
    });

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && cuenta.open) {
        cuenta.open = false;
        var resumen = cuenta.querySelector('summary');
        if (resumen) resumen.focus();
      }
    });
  }

  /* ---------- selector de idioma (ES/EN) ----------
     Todavía no traduce nada: cambia el titular de la portada y poco más, que es
     lo que hacía en el prototipo. Se queda porque quitarlo sería decidir por
     nadie que el sitio no va a ser bilingüe, y esa decisión no es de aquí. */
  var i18n = {
    es: {
      h1:  'Encuentra tu próximo <em>retiro, festival o círculo</em> de bienestar',
      tag: 'Directorio de eventos · México'
    },
    en: {
      h1:  'Find your next <em>retreat, festival or wellness</em> circle',
      tag: 'Event directory · Mexico'
    }
  };

  var botonesIdioma = document.querySelectorAll('#langToggle button');

  botonesIdioma.forEach(function (b) {
    b.addEventListener('click', function () {
      botonesIdioma.forEach(function (x) { x.classList.remove('active'); });
      b.classList.add('active');

      // Solo la portada tiene ese titular. En las demás páginas no hay nada que
      // cambiar todavía, y el botón se limita a quedarse marcado.
      var t   = i18n[b.dataset.lang];
      var h1  = document.querySelector('.hero h1');
      var tag = document.querySelector('.hero .eyebrow');
      if (h1)  h1.innerHTML   = t.h1;
      if (tag) tag.textContent = t.tag;
    });
  });
})();
