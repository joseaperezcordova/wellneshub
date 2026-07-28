<?php
/**
 * Panel de administración.
 *
 * Era la vista «admin» dentro de la portada, a la que se llegaba por /#admin
 * porque no tenía dirección propia — y a la que, mientras vivió ahí, no había
 * enlace público ninguno: cualquiera que supiera el ancla la abría, porque el
 * HTML de las siete vistas se le mandaba entero a todo el mundo. Ahora es una
 * página con su puerta delante.
 *
 * ATENCIÓN: de las seis pestañas, solo «Eventos» tiene datos de verdad. Las
 * otras cinco —organizadores, categorías, ciudades, usuarios, newsletter— son
 * la maqueta del prototipo, con nombres y cifras inventados, igual que las seis
 * cifras de arriba. Se mantienen tal cual estaban al repartir el sitio en
 * páginas: cambiarlas era otro trabajo, y mezclarlo con este habría hecho
 * imposible revisar ninguno de los dos.
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

$eventosAdmin = eventosTodos();

$titulo        = 'Panel admin';
$anchoLibre    = true;
$scriptsPagina = ['assets/js/admin.js'];

require __DIR__ . '/includes/layout.php';
?>

<div class="admin-shell">
  <div class="wrap">
    <div class="admin-header">
      <div class="eyebrow">Panel administrador</div>
      <h1>Dashboard</h1>
    </div>

    <?php /* Cifras del prototipo: no salen de la base. Ver la nota de arriba. */ ?>
    <div class="stat-grid">
      <div class="stat-card"><div class="num">142</div><div class="lbl">Eventos publicados</div></div>
      <div class="stat-card"><div class="num">19</div><div class="lbl">Próximos (7 días)</div></div>
      <div class="stat-card"><div class="num">6</div><div class="lbl">Pendientes de aprobación</div></div>
      <div class="stat-card"><div class="num">31</div><div class="lbl">Eventos expirados</div></div>
      <div class="stat-card"><div class="num">58</div><div class="lbl">Organizadores</div></div>
      <div class="stat-card"><div class="num">1,207</div><div class="lbl">Suscriptores newsletter</div></div>
    </div>

    <div class="scope-banner">
      <b>Fuera de alcance del MVP</b> — se diseña la arquitectura para permitirlo después, no se construye ahora.
      <div class="scope-list">
        <span>Procesamiento de pagos</span>·<span>Venta de boletos</span>·<span>App móvil</span>·<span>Chat</span>·<span>Reseñas</span>·<span>Afiliados</span>·<span>Automatizaciones de marketing</span>·<span>Integraciones externas</span>·<span>IA de recomendaciones</span>·<span>Notificaciones push</span>·<span>Favoritos</span>·<span>Calendario personal</span>·<span>Marketplace</span>·<span>Directorio de profesionales / hoteles</span>
      </div>
    </div>

    <div class="admin-tabs" id="adminTabs">
      <button data-panel="eventos" class="active">Eventos</button>
      <button data-panel="organizadores">Organizadores</button>
      <button data-panel="categorias">Categorías</button>
      <button data-panel="ciudades">Ciudades y estados</button>
      <button data-panel="usuarios">Usuarios</button>
      <button data-panel="newsletter">Newsletter</button>
    </div>

    <!-- EVENTOS — la única pestaña con datos de verdad -->
    <div class="admin-panel active" id="panel-eventos">
      <div class="panel-toolbar">
        <a class="btn-add" href="<?= URL_BASE ?>/evento-nuevo.php">+ Nuevo evento</a>
      </div>
      <table class="admtable">
        <thead><tr><th>Título</th><th>Organiza</th><th>Ciudad</th><th>Fecha</th><th>Situación</th><th></th></tr></thead>
        <tbody>
          <?php if (!$eventosAdmin): ?>
            <tr><td colspan="6" style="opacity:.6;">Todavía no hay eventos.</td></tr>
          <?php endif; ?>
          <?php foreach ($eventosAdmin as $ea): $p = fechaPartes($ea['fecha_inicio']); ?>
            <tr>
              <td><?= e($ea['titulo']) ?></td>
              <td><?= e($ea['organizador']) ?></td>
              <td><?= e($ea['ciudad']) ?></td>
              <td><?= e($p['d'] . ' ' . $p['m'] . ' ' . date('Y', strtotime($ea['fecha_inicio']))) ?></td>
              <td>
                <span class="badge <?= $ea['situacion'] === 'publicado' ? 'on' : 'off' ?>">
                  <?= e(ucfirst($ea['situacion'])) ?>
                </span>
              </td>
              <td>
                <a class="actionbtn" href="<?= URL_BASE ?>/evento.php?id=<?= (int) $ea['id'] ?>">Ver</a>
                <a class="actionbtn" href="<?= URL_BASE ?>/evento-editar.php?id=<?= (int) $ea['id'] ?>">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="evergreen-note" style="margin-top:18px;">
        Ocultar y eliminar se hacen desde la ficha del evento, con la confirmación delante.
        Un botón «Eliminar» en una fila de tabla se pulsa por error con demasiada facilidad.
      </div>
    </div>

    <!-- ORGANIZADORES — maqueta -->
    <div class="admin-panel" id="panel-organizadores">
      <div class="panel-toolbar">
        <input type="text" placeholder="Buscar organizador…">
        <button class="btn-add">+ Nuevo organizador</button>
      </div>
      <table class="admtable">
        <thead><tr><th>Nombre</th><th>Contacto</th><th>Eventos</th><th>Redes</th><th></th></tr></thead>
        <tbody>
          <tr><td>Raíz Colectivo</td><td>hola@raizcolectivo.mx</td><td>12</td><td>IG · FB · WA</td><td><button class="actionbtn">Editar</button><button class="actionbtn">Eliminar</button></td></tr>
          <tr><td>Circulo Vivo</td><td>circulovivo@gmail.com</td><td>7</td><td>IG · Web</td><td><button class="actionbtn">Editar</button><button class="actionbtn">Eliminar</button></td></tr>
          <tr><td>Sana Selva</td><td>contacto@sanaselva.mx</td><td>5</td><td>IG · FB</td><td><button class="actionbtn">Editar</button><button class="actionbtn">Eliminar</button></td></tr>
          <tr><td>Amara Wellness</td><td>amara.wellness@gmail.com</td><td>9</td><td>IG · WA · Web</td><td><button class="actionbtn">Editar</button><button class="actionbtn">Eliminar</button></td></tr>
        </tbody>
      </table>
    </div>

    <!-- CATEGORIAS — maqueta -->
    <div class="admin-panel" id="panel-categorias">
      <div class="panel-toolbar">
        <input type="text" placeholder="Buscar categoría…">
        <button class="btn-add">+ Nueva categoría</button>
      </div>
      <div>
        <span class="catchip-admin">Yoga <span class="n">38</span></span>
        <span class="catchip-admin">Meditación <span class="n">21</span></span>
        <span class="catchip-admin">Breathwork <span class="n">14</span></span>
        <span class="catchip-admin">Pilates <span class="n">9</span></span>
        <span class="catchip-admin">Retreat <span class="n">17</span></span>
        <span class="catchip-admin">Festival <span class="n">6</span></span>
        <span class="catchip-admin">Sound Healing <span class="n">12</span></span>
        <span class="catchip-admin">Ice Bath <span class="n">5</span></span>
        <span class="catchip-admin">Biohacking <span class="n">4</span></span>
        <span class="catchip-admin">Nutrición <span class="n">8</span></span>
        <span class="catchip-admin">Conferencia <span class="n">3</span></span>
        <span class="catchip-admin">Networking <span class="n">2</span></span>
        <span class="catchip-admin">Otro <span class="n">3</span></span>
      </div>
    </div>

    <!-- CIUDADES / ESTADOS — maqueta -->
    <div class="admin-panel" id="panel-ciudades">
      <div class="twocol-admin">
        <div class="admin-card">
          <h4>Estados</h4>
          <ul>
            <li>Quintana Roo <span class="mono" style="opacity:.5;">2 ciudades</span></li>
            <li>Ciudad de México <span class="mono" style="opacity:.5;">1 ciudad</span></li>
            <li>Oaxaca <span class="mono" style="opacity:.5;">1 ciudad</span></li>
            <li>Jalisco <span class="mono" style="opacity:.5;">2 ciudades</span></li>
            <li>Guanajuato <span class="mono" style="opacity:.5;">1 ciudad</span></li>
            <li>Nuevo León <span class="mono" style="opacity:.5;">1 ciudad</span></li>
          </ul>
          <button class="btn-add" style="margin-top:14px;">+ Nuevo estado</button>
        </div>
        <div class="admin-card">
          <h4>Ciudades</h4>
          <ul>
            <li>Tulum <span class="mono" style="opacity:.5;">24 eventos</span></li>
            <li>CDMX <span class="mono" style="opacity:.5;">41 eventos</span></li>
            <li>Oaxaca de Juárez <span class="mono" style="opacity:.5;">18 eventos</span></li>
            <li>San Miguel de Allende <span class="mono" style="opacity:.5;">15 eventos</span></li>
            <li>Guadalajara <span class="mono" style="opacity:.5;">12 eventos</span></li>
            <li>Puerto Vallarta <span class="mono" style="opacity:.5;">9 eventos</span></li>
          </ul>
          <button class="btn-add" style="margin-top:14px;">+ Nueva ciudad</button>
        </div>
      </div>
    </div>

    <!-- USUARIOS — maqueta -->
    <div class="admin-panel" id="panel-usuarios">
      <div class="panel-toolbar">
        <input type="text" placeholder="Buscar usuario…">
        <button class="btn-add">+ Nuevo usuario</button>
      </div>
      <table class="admtable">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th></th></tr></thead>
        <tbody>
          <tr><td>José P.</td><td>jose@jpcorelab.com</td><td><span class="badge on">Administrador</span></td><td><button class="actionbtn">Editar</button></td></tr>
          <tr><td>Mariana R.</td><td>mariana@directoriowellness.mx</td><td><span class="badge off">Editor</span></td><td><button class="actionbtn">Editar</button><button class="actionbtn">Eliminar</button></td></tr>
        </tbody>
      </table>
    </div>

    <!-- NEWSLETTER — maqueta -->
    <div class="admin-panel" id="panel-newsletter">
      <div class="admin-card" style="max-width:420px;">
        <h4>Suscriptores</h4>
        <div class="stat-card" style="margin-bottom:14px;"><div class="num">1,207</div><div class="lbl">Correos capturados</div></div>
        <button class="btn-add">Exportar CSV</button>
      </div>
    </div>

  </div>
</div>

<?php pie(); ?>
