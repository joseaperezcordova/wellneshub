/* Las pestañas del panel de administración. Solo cambian qué bloque se ve; no
   piden nada al servidor, porque los datos de los seis paneles ya vienen en la
   página. */
(function () {
  'use strict';

  var barra = document.getElementById('adminTabs');
  if (!barra) return;

  barra.addEventListener('click', function (ev) {
    var boton = ev.target.closest('button[data-panel]');
    if (!boton) return;

    barra.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
    document.querySelectorAll('.admin-panel').forEach(function (p) { p.classList.remove('active'); });

    boton.classList.add('active');
    var panel = document.getElementById('panel-' + boton.dataset.panel);
    if (panel) panel.classList.add('active');
  });
})();
