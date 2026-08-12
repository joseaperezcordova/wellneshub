<?php
/**
 * Métricas de la plataforma: la versión con datos reales del panel que se
 * armó primero como maqueta para presentar OMDARA a organizadores e
 * inversionistas.
 *
 * Todo lo de aquí sale de las tablas propias —eventos, usuarios, contactos,
 * clics—. Tráfico y fuentes de adquisición sí existen desde que se instaló
 * GA4 (ver includes/layout.php), pero viven en Google Analytics, no aquí:
 * este panel no llama a su API, solo lee la base de datos. Ver
 * includes/metricas.php para de dónde sale cada número.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/eventos.php';

$u = exigirSesion();

if (!esAdmin($u)) {
    http_response_code(403);
    $titulo = 'Sin permiso';
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>Esta página no es para ti</h1>'
       . '<p class="sub">Solo los administradores ven este panel.</p></div>';
    pie();
    exit;
}

$rango = (int) ($_GET['rango'] ?? 30);
if (!in_array($rango, [7, 30, 90], true)) $rango = 30;

$publicadas   = contarActividadesPublicadas();
$organizadores = contarOrganizadoresActivos();
$mensajes     = contarMensajesContacto($rango);
$clicsBoletos = contarClics('boletos', $rango);
$clicsReserva = contarClics('reservar', $rango);

$crecimiento = actividadesPublicadasPorMes(8);
$categorias  = categoriasTop(6);
$ciudades    = ciudadesTop(6);

$titulo = 'Métricas de la plataforma';
require __DIR__ . '/includes/layout.php';
?>

<div class="admin-shell">
  <div class="wrap">
    <div class="admin-header">
      <div class="eyebrow">Panel administrador</div>
      <h1>Métricas de la plataforma</h1>
    </div>

    <a class="volver-admin" href="<?= URL_BASE ?>/admin.php">← Volver al dashboard</a>

    <div class="rango-filtros">
      <span class="rango-label">Periodo</span>
      <a class="rango-btn <?= $rango === 7  ? 'activo' : '' ?>" href="?rango=7">Últimos 7 días</a>
      <a class="rango-btn <?= $rango === 30 ? 'activo' : '' ?>" href="?rango=30">Últimos 30 días</a>
      <a class="rango-btn <?= $rango === 90 ? 'activo' : '' ?>" href="?rango=90">Últimos 90 días</a>
    </div>

    <div class="stat-grid met-stat-grid">
      <div class="stat-card">
        <div class="num"><?= number_format($publicadas) ?></div>
        <div class="lbl">Actividades publicadas <span class="tenue-met">(total)</span></div>
      </div>
      <div class="stat-card">
        <div class="num"><?= number_format($organizadores) ?></div>
        <div class="lbl">Organizadores activos <span class="tenue-met">(total)</span></div>
      </div>
      <div class="stat-card">
        <div class="num"><?= number_format($mensajes) ?></div>
        <div class="lbl">Mensajes de contacto <span class="tenue-met">(periodo)</span></div>
      </div>
      <div class="stat-card">
        <div class="num"><?= number_format($clicsBoletos + $clicsReserva) ?></div>
        <div class="lbl">Clics a boletos/reserva <span class="tenue-met">(periodo)</span></div>
      </div>
    </div>

    <div class="met-card">
      <h3>Crecimiento del directorio</h3>
      <p class="met-card-sub">Actividades publicadas acumuladas, por mes.</p>
      <div class="met-chart-wrap">
        <svg class="met-chart" id="metChart" viewBox="0 0 1080 260" preserveAspectRatio="none" role="img"
             aria-label="Actividades publicadas acumuladas por mes"></svg>
        <div class="met-tooltip" id="metTooltip"></div>
      </div>
    </div>

    <div class="met-card">
      <h3>A qué lleva cada ficha</h3>
      <p class="met-card-sub">Mensajes y clics por tipo de acción principal, en el periodo seleccionado arriba.</p>
      <div class="met-hbars">
        <?php
        $accionesData = [
            ['n' => 'Contactar al organizador', 'v' => $mensajes,     'c' => 'var(--met-cat-1)'],
            ['n' => 'Comprar boletos',          'v' => $clicsBoletos, 'c' => 'var(--met-cat-2)'],
            ['n' => 'Reservar lugar',           'v' => $clicsReserva, 'c' => 'var(--met-cat-3)'],
        ];
        $totalAcciones = max(1, array_sum(array_column($accionesData, 'v')));
        ?>
        <?php foreach ($accionesData as $a): $pct = round(($a['v'] / $totalAcciones) * 100); ?>
          <div class="met-hbar-row">
            <div class="met-hbar-name"><span class="met-hbar-key" style="background:<?= $a['c'] ?>;"></span><?= e($a['n']) ?></div>
            <div class="met-hbar-track" role="img" aria-label="<?= e($a['n']) ?>: <?= (int) $a['v'] ?>">
              <div class="met-hbar-fill" style="width:<?= $pct ?>%; background:<?= $a['c'] ?>;"></div>
            </div>
            <div class="met-hbar-value"><?= number_format($a['v']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($mensajes + $clicsBoletos + $clicsReserva === 0): ?>
        <div class="met-vacio">Todavía no hay mensajes ni clics registrados en este periodo.</div>
      <?php endif; ?>
    </div>

    <div class="twocol-admin">
      <div class="admin-card">
        <h4>Categorías con más actividades</h4>
        <?php if (!$categorias): ?>
          <p class="met-card-sub">Todavía no hay actividades publicadas.</p>
        <?php else: ?>
          <div class="met-rank-list">
            <?php $maxCat = max(array_column($categorias, 'v')); ?>
            <?php foreach ($categorias as $i => $c): ?>
              <div class="met-rank-row">
                <div class="met-rank-i"><?= $i + 1 ?>.</div>
                <div class="met-rank-name"><?= e($c['n']) ?></div>
                <div class="met-rank-track"><div class="met-rank-fill" style="width:<?= round(($c['v'] / $maxCat) * 100) ?>%;"></div></div>
                <div class="met-rank-value"><?= number_format($c['v']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="admin-card">
        <h4>Ciudades con más actividades</h4>
        <?php if (!$ciudades): ?>
          <p class="met-card-sub">Todavía no hay actividades presenciales publicadas.</p>
        <?php else: ?>
          <div class="met-rank-list">
            <?php $maxCiu = max(array_column($ciudades, 'v')); ?>
            <?php foreach ($ciudades as $i => $c): ?>
              <div class="met-rank-row">
                <div class="met-rank-i"><?= $i + 1 ?>.</div>
                <div class="met-rank-name"><?= e($c['n']) ?></div>
                <div class="met-rank-track"><div class="met-rank-fill" style="width:<?= round(($c['v'] / $maxCiu) * 100) ?>%;"></div></div>
                <div class="met-rank-value"><?= number_format($c['v']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="scope-banner">
      <b>Fuera de esta página</b> — tráfico y fuentes de adquisición viven en Google Analytics (analytics.google.com): este panel no se conecta a GA4, solo lee la base de datos de OMDARA. Todo lo de arriba sale directo de ahí.
    </div>

  </div>
</div>

<script>
(function(){
  'use strict';
  var datos = <?= json_encode(array_map(
      static fn(array $f): array => ['mes' => $f['mes'], 'total' => $f['total']],
      $crecimiento
  ), JSON_UNESCAPED_UNICODE) ?>;

  var meses = {1:'Ene',2:'Feb',3:'Mar',4:'Abr',5:'May',6:'Jun',7:'Jul',8:'Ago',9:'Sep',10:'Oct',11:'Nov',12:'Dic'};

  function construirChart(){
    var svgNS = 'http://www.w3.org/2000/svg';
    var svg = document.getElementById('metChart');
    if (!svg || !datos.length) return;
    svg.innerHTML = '';

    var W = 1080, H = 260;
    var padL = 12, padR = 12, padT = 20, padB = 26;
    var innerW = W - padL - padR, innerH = H - padT - padB;

    var valores = datos.map(function(d){ return d.total; });
    var max = Math.max.apply(null, valores) || 1;
    var stepX = datos.length > 1 ? innerW / (datos.length - 1) : 0;

    function xAt(i){ return padL + i * stepX; }
    function yAt(v){ return padT + innerH - (v / max) * innerH; }

    var ticks = 4;
    for (var t = 0; t <= ticks; t++){
      var yv = Math.round((max / ticks) * t);
      var y = yAt(yv);
      var line = document.createElementNS(svgNS,'line');
      line.setAttribute('class','met-grid-line');
      line.setAttribute('x1', padL); line.setAttribute('x2', W - padR);
      line.setAttribute('y1', y); line.setAttribute('y2', y);
      svg.appendChild(line);
      var lbl = document.createElementNS(svgNS,'text');
      lbl.setAttribute('class','met-axis-label');
      lbl.setAttribute('x', padL); lbl.setAttribute('y', y - 5);
      lbl.textContent = yv;
      svg.appendChild(lbl);
    }

    var pts = datos.map(function(d,i){ return xAt(i) + ',' + yAt(d.total); });
    var areaPath = 'M' + xAt(0) + ',' + yAt(0) + ' L' + pts.join(' L') + ' L' + xAt(datos.length-1) + ',' + yAt(0) + ' Z';
    var area = document.createElementNS(svgNS,'path');
    area.setAttribute('class','met-area-fill');
    area.setAttribute('d', areaPath);
    svg.appendChild(area);

    var line2 = document.createElementNS(svgNS,'path');
    line2.setAttribute('class','met-area-line');
    line2.setAttribute('d', 'M' + pts.join(' L'));
    svg.appendChild(line2);

    datos.forEach(function(d,i){
      var partes = d.mes.split('-');
      var lbl = document.createElementNS(svgNS,'text');
      lbl.setAttribute('class','met-axis-label');
      lbl.setAttribute('x', xAt(i));
      lbl.setAttribute('y', H - 6);
      lbl.setAttribute('text-anchor', i === 0 ? 'start' : (i === datos.length-1 ? 'end' : 'middle'));
      lbl.textContent = meses[parseInt(partes[1],10)] || d.mes;
      svg.appendChild(lbl);
    });

    var lastX = xAt(datos.length-1), lastY = yAt(datos[datos.length-1].total);
    var dot = document.createElementNS(svgNS,'circle');
    dot.setAttribute('class','met-area-dot');
    dot.setAttribute('cx', lastX); dot.setAttribute('cy', lastY); dot.setAttribute('r', 5);
    svg.appendChild(dot);
    var endLbl = document.createElementNS(svgNS,'text');
    endLbl.setAttribute('class','met-end-label');
    endLbl.setAttribute('x', lastX - 8); endLbl.setAttribute('y', lastY - 12);
    endLbl.setAttribute('text-anchor','end');
    endLbl.textContent = datos[datos.length-1].total + ' actividades';
    svg.appendChild(endLbl);

    var crosshair = document.createElementNS(svgNS,'line');
    crosshair.setAttribute('class','met-crosshair');
    crosshair.setAttribute('y1', padT); crosshair.setAttribute('y2', padT + innerH);
    svg.appendChild(crosshair);
    var hoverDot = document.createElementNS(svgNS,'circle');
    hoverDot.setAttribute('class','met-hover-dot');
    hoverDot.setAttribute('r', 5);
    svg.appendChild(hoverDot);

    var tooltip = document.getElementById('metTooltip');

    datos.forEach(function(d,i){
      var hit = document.createElementNS(svgNS,'rect');
      hit.setAttribute('class','met-hit-col');
      var x0 = i === 0 ? padL : xAt(i) - stepX/2;
      var w = (i === 0 || i === datos.length-1) ? stepX/2 + (i===0?padL:padR) : stepX;
      hit.setAttribute('x', x0); hit.setAttribute('y', padT);
      hit.setAttribute('width', Math.max(w,1)); hit.setAttribute('height', innerH);
      hit.tabIndex = 0;

      function mostrar(){
        var cx = xAt(i), cy = yAt(d.total);
        crosshair.setAttribute('x1', cx); crosshair.setAttribute('x2', cx);
        crosshair.style.opacity = 1;
        hoverDot.setAttribute('cx', cx); hoverDot.setAttribute('cy', cy);
        hoverDot.style.opacity = 1;
        var rect = svg.getBoundingClientRect();
        var scaleX = rect.width / W, scaleY = rect.height / H;
        tooltip.style.left = (cx * scaleX) + 'px';
        tooltip.style.top = (cy * scaleY - 10) + 'px';
        tooltip.innerHTML = '';
        var strong = document.createElement('strong');
        strong.textContent = d.total + ' actividades';
        var br = document.createElement('br');
        var span = document.createElement('span');
        var partes = d.mes.split('-');
        span.textContent = (meses[parseInt(partes[1],10)] || d.mes) + ' ' + partes[0] + ' · acumulado';
        tooltip.appendChild(strong); tooltip.appendChild(br); tooltip.appendChild(span);
        tooltip.style.opacity = 1;
      }
      function ocultar(){
        crosshair.style.opacity = 0; hoverDot.style.opacity = 0; tooltip.style.opacity = 0;
      }
      hit.addEventListener('pointerenter', mostrar);
      hit.addEventListener('pointermove', mostrar);
      hit.addEventListener('pointerleave', ocultar);
      hit.addEventListener('focus', mostrar);
      hit.addEventListener('blur', ocultar);
      svg.appendChild(hit);
    });
  }

  construirChart();
})();
</script>

<?php pie(); ?>
