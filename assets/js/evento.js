/* Compartir la ficha del evento.
   Con Web Share API (móvil, sobre todo) se abre la hoja nativa del sistema.
   Sin ella —la mayoría de navegadores de escritorio— se copia el enlace al
   portapapeles y se avisa aquí mismo, sin depender de permisos de
   notificaciones ni de nada que haya que conceder antes. */
(function () {
  'use strict';

  var boton = document.getElementById('btnCompartir');
  if (!boton) return;

  var aviso = document.getElementById('avisoCopiado');

  boton.addEventListener('click', function () {
    var url    = boton.dataset.url;
    var titulo = boton.dataset.titulo;

    if (navigator.share) {
      navigator.share({ title: titulo, url: url }).catch(function () {});
      return;
    }

    if (!navigator.clipboard) return;

    navigator.clipboard.writeText(url).then(function () {
      if (!aviso) return;
      aviso.classList.add('show');
      setTimeout(function () { aviso.classList.remove('show'); }, 2500);
    });
  });
})();
