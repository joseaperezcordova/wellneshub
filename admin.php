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
 * Las cinco pestañas salen de la base de datos. Hubo una sexta, «Newsletter»,
 * que enseñaba una cifra inventada de la maqueta del prototipo: se quitó
 * entera en vez de dejarla a medias, porque no existe ningún mecanismo que
 * capture correos todavía. Vuelve el día que haya una decisión real sobre esa
 * funcionalidad —tabla de suscriptores, formulario público, etc.—.
 *
 * Las seis cifras de arriba SÍ son reales —ver includes/metricas.php—; el
 * detalle completo, con gráfica de crecimiento y desglose por acción
 * principal, vive en metricas.php.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';

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

$eventosAdmin      = eventosTodos();
$organizadoresAdmin = organizadoresConConteo();
$categoriasAdmin    = categoriasConConteo();
$estadosAdmin       = estadosConConteo();
$ciudadesAdmin      = ciudadesConConteo();
$usuariosAdmin      = usuariosTodos();
$mensajesAdmin      = mensajesContactoRecientes();

$cifras = [
    'publicadas'    => contarActividadesPublicadas(),
    'proximas'      => contarActividadesProximas(7),
    'reportes'      => contarReportesPendientes(),
    'expiradas'     => contarActividadesExpiradas(),
    'organizadores' => contarOrganizadoresActivos(),
    'contactos30'   => contarMensajesContacto(30),
];

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

    <div class="stat-grid">
      <div class="stat-card"><div class="num"><?= number_format($cifras['publicadas']) ?></div><div class="lbl">Actividades publicadas</div></div>
      <div class="stat-card"><div class="num"><?= number_format($cifras['proximas']) ?></div><div class="lbl">Próximas (7 días)</div></div>
      <div class="stat-card"><div class="num"><?= number_format($cifras['reportes']) ?></div><div class="lbl">Reportes pendientes</div></div>
      <div class="stat-card"><div class="num"><?= number_format($cifras['expiradas']) ?></div><div class="lbl">Actividades expiradas</div></div>
      <div class="stat-card"><div class="num"><?= number_format($cifras['organizadores']) ?></div><div class="lbl">Organizadores activos</div></div>
      <div class="stat-card"><div class="num"><?= number_format($cifras['contactos30']) ?></div><div class="lbl">Mensajes (30 días)</div></div>
    </div>

    <a class="actionbtn" href="<?= URL_BASE ?>/metricas.php" style="display:inline-block; margin-bottom:10px;">Ver métricas completas →</a>

    <div class="scope-banner">
      <b>Fuera de alcance del MVP</b> — se diseña la arquitectura para permitirlo después, no se construye ahora.
      <div class="scope-list">
        <span>Procesamiento de pagos</span>·<span>Venta de boletos</span>·<span>App móvil</span>·<span>Chat</span>·<span>Reseñas</span>·<span>Afiliados</span>·<span>Automatizaciones de marketing</span>·<span>Integraciones externas</span>·<span>IA de recomendaciones</span>·<span>Notificaciones push</span>·<span>Favoritos</span>·<span>Calendario personal</span>·<span>Marketplace</span>·<span>Directorio de profesionales / hoteles</span>
      </div>
    </div>

    <div class="admin-tabs" id="adminTabs">
      <button data-panel="eventos" class="active">Actividades</button>
      <button data-panel="organizadores">Organizadores</button>
      <button data-panel="categorias">Categorías</button>
      <button data-panel="ciudades">Ciudades y estados</button>
      <button data-panel="usuarios">Usuarios</button>
      <button data-panel="mensajes">Mensajes<?php if ($mensajesAdmin): ?> <span class="pendientes"><?= count($mensajesAdmin) ?></span><?php endif; ?></button>
    </div>

    <!-- ACTIVIDADES — la única pestaña con datos de verdad -->
    <div class="admin-panel active" id="panel-eventos">
      <div class="panel-toolbar">
        <a class="btn-add" href="<?= URL_BASE ?>/evento-nuevo.php">+ Nueva actividad</a>
      </div>
      <table class="admtable">
        <thead><tr><th>Título</th><th>Organiza</th><th>Ciudad</th><th>Fecha</th><th>Situación</th><th></th></tr></thead>
        <tbody>
          <?php if (!$eventosAdmin): ?>
            <tr><td colspan="6" style="opacity:.8;">Todavía no hay actividades.</td></tr>
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
                <a class="actionbtn" href="<?= e(urlEvento($ea)) ?>?volver=admin">Ver</a>
                <a class="actionbtn" href="<?= e(urlEditarEvento($ea) . '?volver=admin') ?>">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="evergreen-note" style="margin-top:18px;">
        Ocultar y eliminar se hacen desde la ficha de la actividad, con la confirmación delante.
        Un botón «Eliminar» en una fila de tabla se pulsa por error con demasiada facilidad.
      </div>
    </div>

    <!-- ORGANIZADORES — quien ya publicó al menos una actividad (ver publicarEvento()) -->
    <div class="admin-panel" id="panel-organizadores">
      <table class="admtable">
        <?php /* El teléfono es el que cada organizador pone en «Mi cuenta»
                 (REQ-00009). Es el único sitio donde se lee: no se publica en
                 ninguna ficha, y está aquí para poder localizar a alguien
                 cuando hay algo que resolver con una actividad suya. */ ?>
        <thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Actividades publicadas</th><th>Último acceso</th><th>Cuenta</th></tr></thead>
        <tbody>
          <?php if (!$organizadoresAdmin): ?>
            <tr><td colspan="6" style="opacity:.8;">Todavía no hay organizadores con actividades publicadas.</td></tr>
          <?php endif; ?>
          <?php foreach ($organizadoresAdmin as $org): ?>
            <tr>
              <td><?= e($org['nombre']) ?></td>
              <td><?= e($org['email']) ?></td>
              <td><?= !empty($org['telefono']) ? e($org['telefono']) : '—' ?></td>
              <td><?= number_format((int) $org['publicadas']) ?></td>
              <td><?= $org['ultimo_acceso_en'] ? e(date('d M Y', strtotime($org['ultimo_acceso_en']))) : '—' ?></td>
              <td><span class="badge <?= $org['estado'] === 'activo' ? 'on' : 'off' ?>"><?= e(ucfirst($org['estado'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="evergreen-note" style="margin-top:18px;">
        No hay «editar» ni «eliminar» aquí todavía: suspender una cuenta o cambiar un rol es una acción con
        consecuencias —le corta el acceso a alguien— y no existe aún la pantalla de confirmación que eso merece.
      </div>
    </div>

    <!-- CATEGORIAS — catálogo fijo de categoriasMenu(), con conteo real de actividades publicadas -->
    <div class="admin-panel" id="panel-categorias">
      <?php /* No hay «+ Nueva categoría»: el catálogo es un array fijo en
               includes/eventos.php (categoriasMenu()), no algo que se cree
               desde aquí. Agregar una implica tocar código, no un formulario. */ ?>
      <div>
        <?php foreach ($categoriasAdmin as $cat): ?>
          <span class="catchip-admin"><?= e($cat['icono'] . ' ' . $cat['nombre']) ?> <span class="n"><?= number_format($cat['total']) ?></span></span>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- CIUDADES / ESTADOS — agrupado real desde eventos publicados -->
    <div class="admin-panel" id="panel-ciudades">
      <div class="twocol-admin">
        <div class="admin-card">
          <h4>Estados</h4>
          <ul>
            <?php if (!$estadosAdmin): ?><li style="opacity:.8;">Sin actividades publicadas todavía.</li><?php endif; ?>
            <?php foreach ($estadosAdmin as $est): ?>
              <li><?= e($est['nombre']) ?> <span class="mono" style="opacity:.8;"><?= (int) $est['ciudades'] ?> <?= $est['ciudades'] === 1 ? 'ciudad' : 'ciudades' ?></span></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="admin-card">
          <h4>Ciudades</h4>
          <ul>
            <?php if (!$ciudadesAdmin): ?><li style="opacity:.8;">Sin actividades publicadas todavía.</li><?php endif; ?>
            <?php foreach ($ciudadesAdmin as $ciu): ?>
              <li><?= e($ciu['nombre']) ?> <span class="mono" style="opacity:.8;"><?= number_format($ciu['actividades']) ?> <?= $ciu['actividades'] === 1 ? 'actividad' : 'actividades' ?></span></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- USUARIOS — toda la tabla usuarios -->
    <div class="admin-panel" id="panel-usuarios">
      <table class="admtable">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Último acceso</th></tr></thead>
        <tbody>
          <?php foreach ($usuariosAdmin as $us): ?>
            <tr>
              <td><?= e($us['nombre']) ?></td>
              <td><?= e($us['email']) ?></td>
              <td><span class="badge <?= $us['rol'] === 'admin' ? 'on' : 'off' ?>"><?= e(ucfirst($us['rol'])) ?></span></td>
              <td><?= $us['ultimo_acceso_en'] ? e(date('d M Y', strtotime($us['ultimo_acceso_en']))) : 'Nunca entró' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- MENSAJES — lo que llega por /contacto. Solo lectura: el requerimiento
         pide guardarlos "para tener un historial", y un historial que nadie
         puede leer no es un historial. Cambiar el estado de un mensaje es otra
         cosa y no está hecha (ver docs/pendientes.md). -->
    <div class="admin-panel" id="panel-mensajes">
      <table class="admtable">
        <thead><tr><th>Cuándo</th><th>Quién</th><th>Motivo</th><th>Actividad</th><th>Mensaje</th><th>Estado</th></tr></thead>
        <tbody>
          <?php if (!$mensajesAdmin): ?>
            <tr><td colspan="6" style="opacity:.8;">Todavía no ha escrito nadie.</td></tr>
          <?php endif; ?>
          <?php foreach ($mensajesAdmin as $ms): ?>
            <tr>
              <td style="white-space:nowrap;"><?= e(date('d M Y H:i', strtotime($ms['creado_en']))) ?></td>
              <td>
                <?= e($ms['nombre']) ?><br>
                <a href="mailto:<?= e($ms['email']) ?>" style="font-size:12px; opacity:.85;"><?= e($ms['email']) ?></a>
              </td>
              <?php /* Los tres campos de la migración 19 pueden no existir
                       todavía: el panel tiene que aguantar los dos casos. */ ?>
              <td><?= e(motivosContacto()[$ms['motivo'] ?? ''] ?? '—') ?></td>
              <td><?= !empty($ms['actividad_nombre']) ? e($ms['actividad_nombre']) : '—' ?></td>
              <td style="max-width:340px;"><?= e($ms['mensaje']) ?></td>
              <td>
                <span class="badge <?= ($ms['estado'] ?? 'nuevo') === 'nuevo' ? 'on' : 'off' ?>">
                  <?= e(estadosContacto()[$ms['estado'] ?? 'nuevo'] ?? 'Nuevo') ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="evergreen-note" style="margin-top:18px;">
        Solo se leen. El estado de cada mensaje se queda en «Nuevo» porque todavía no hay
        pantalla para cambiarlo: el requerimiento pedía un registro básico, no un sistema de
        tickets. Responder se hace desde el correo, que llega con el Reply-To puesto.
      </div>
    </div>

  </div>
</div>

<?php pie(); ?>
