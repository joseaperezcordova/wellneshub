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

  /* Aquí vivía el selector de idioma, que traducía el titular de la portada
     con JavaScript y dejaba el resto de la página en español, con la misma
     dirección. Eso es justo lo que el REQ-00002 llama traducción parcial.

     Ahora el idioma lo decide la dirección —/actividades contra /activities—,
     el selector son dos enlaces normales y el servidor devuelve la página
     entera en el idioma que toca. Sin JavaScript de por medio: así funciona
     con el clic central, se puede compartir, y Google indexa las dos
     versiones por separado. Ver includes/idioma.php. */
})();
