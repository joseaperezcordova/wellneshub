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

  // Sub-pestañas de Actividades (Todas/Publicadas/Canceladas/Ocultas, Req.
  // 17092026 punto 4): mismo patrón, pero filtrando filas de una sola tabla
  // en vez de mostrar/ocultar paneles enteros.
  var subbarra = document.getElementById('eventosSubtabs');
  if (!subbarra) return;

  subbarra.addEventListener('click', function (ev) {
    var boton = ev.target.closest('button[data-situacion]');
    if (!boton) return;

    subbarra.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
    boton.classList.add('active');

    var situacion = boton.dataset.situacion;
    document.querySelectorAll('#panel-eventos tbody tr[data-situacion]').forEach(function (fila) {
      fila.hidden = situacion !== '' && fila.dataset.situacion !== situacion;
    });
  });
})();
