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

/*
 * Reportes (antes moderacion.php, página aparte): Req. 17092026 punto 4 pide
 * las Actividades reportadas integradas en el mismo dashboard, no en su
 * propia puerta. moderacion.php ahora solo redirige aquí —ver ese archivo—,
 * así que su lógica de POST vive aquí en vez de ahí. Misma lógica de
 * siempre: Descartar / Ocultar / Volver a publicar, sin borrado real (ver
 * retirarEvento(), migración 26).
 */
$avisoReportes = '';
$errorReportes  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evento_id'])) {
    $eventoId = (int) $_POST['evento_id'];

    if (!csrfValido($_POST['csrf'] ?? null)) {
        $errorReportes = 'La sesión caducó. Vuelve a intentarlo.';

    } elseif (!buscarEvento($eventoId)) {
        $errorReportes = 'Esa actividad ya no existe.';

    } elseif (isset($_POST['descartar'])) {
        marcarReportesRevisados($eventoId, (int) $u['id']);
        $avisoReportes = 'Reportes descartados. La actividad sigue publicada.';

    } elseif (isset($_POST['ocultar_reporte'])) {
        retirarEvento($eventoId, (int) $u['id']);
        marcarReportesRevisados($eventoId, (int) $u['id']);
        $avisoReportes = 'Actividad oculta. Ya no aparece en el listado.';

    } elseif (isset($_POST['publicar_reporte'])) {
        cambiarSituacionEvento($eventoId, 'publicado');
        marcarReportesRevisados($eventoId, (int) $u['id']);
        $avisoReportes = 'Actividad publicada otra vez.';

    } elseif (isset($_POST['procesar_retiro'])) {
        // Req. 17092026 punto 8, "revisión administrativa": la solicitud
        // llegó pasadas las EVENTO_MARGEN_RETIRO_H horas del propio dueño.
        // Aprobarla oculta la actividad y cierra la fila de eventos_retiros
        // —no crea una nueva, por eso no pasa por retirarEvento()—.
        procesarSolicitudRetiro((int) ($_POST['retiro_id'] ?? 0), (int) $u['id']);
        $avisoReportes = 'Solicitud procesada: la actividad quedó oculta.';

    } elseif (isset($_POST['descartar_retiro'])) {
        descartarSolicitudRetiro((int) ($_POST['retiro_id'] ?? 0), (int) $u['id']);
        $avisoReportes = 'Solicitud descartada. La actividad sigue como estaba.';
    }
}

$pendientesReportes  = [];
$sinTablaReportes    = false;
$reportesPorEvento   = [];

try {
    $pendientesReportes = reportesPendientes();
    $reportesPorEvento  = reportesDeEventos(array_column($pendientesReportes, 'id'));
} catch (Throwable $ex) {
    error_log('Panel admin, pestaña Reportes: ' . $ex->getMessage());
    $sinTablaReportes = true;
}

// Deep-link (Req. 17092026 punto 4): moderacion.php redirige a
// admin.php?panel=reportes, y el enlace "Moderación" del menú de cabecera
// (includes/layout.php) ahora apunta aquí directo. Sin esto, cada visita
// caía siempre en la pestaña Actividades y había que hacer un clic de más.
$panelesValidos = ['eventos', 'reportes', 'retiros', 'organizadores', 'categorias', 'ciudades', 'usuarios', 'mensajes'];
$panelActivo    = in_array($_GET['panel'] ?? '', $panelesValidos, true) ? $_GET['panel'] : 'eventos';

// Solicitudes de retiro pendientes (Req. 17092026 punto 8): mismo patrón
// defensivo que Reportes, por si la migración 26 todavía no corrió aquí.
$retirosAdmin    = [];
$sinTablaRetiros = false;
try {
    $retirosAdmin = retirosPendientes();
} catch (Throwable $ex) {
    error_log('Panel admin, pestaña Retiros: ' . $ex->getMessage());
    $sinTablaRetiros = true;
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

    <div class="admin-tabs" id="adminTabs">
      <button data-panel="eventos" class="<?= $panelActivo === 'eventos' ? 'active' : '' ?>">Actividades</button>
      <button data-panel="reportes" class="<?= $panelActivo === 'reportes' ? 'active' : '' ?>">Reportes<?php if ($cifras['reportes'] > 0): ?> <span class="pendientes"><?= $cifras['reportes'] ?></span><?php endif; ?></button>
      <?php $pendRetiros = contarRetirosPendientes(); ?>
      <button data-panel="retiros" class="<?= $panelActivo === 'retiros' ? 'active' : '' ?>">Retiros<?php if ($pendRetiros > 0): ?> <span class="pendientes"><?= $pendRetiros ?></span><?php endif; ?></button>
      <button data-panel="organizadores" class="<?= $panelActivo === 'organizadores' ? 'active' : '' ?>">Organizadores</button>
      <button data-panel="categorias" class="<?= $panelActivo === 'categorias' ? 'active' : '' ?>">Categorías</button>
      <button data-panel="ciudades" class="<?= $panelActivo === 'ciudades' ? 'active' : '' ?>">Ciudades y estados</button>
      <button data-panel="usuarios" class="<?= $panelActivo === 'usuarios' ? 'active' : '' ?>">Usuarios</button>
      <button data-panel="mensajes" class="<?= $panelActivo === 'mensajes' ? 'active' : '' ?>">Mensajes<?php if ($mensajesAdmin): ?> <span class="pendientes"><?= count($mensajesAdmin) ?></span><?php endif; ?></button>
    </div>

    <!-- ACTIVIDADES — la única pestaña con datos de verdad -->
    <div class="admin-panel <?= $panelActivo === 'eventos' ? 'active' : '' ?>" id="panel-eventos">
      <div class="panel-toolbar">
        <a class="btn-add" href="<?= URL_BASE ?>/evento-nuevo.php">+ Nueva actividad</a>
      </div>

      <?php /* Sub-pestañas por estado (Req. 17092026 punto 4): filtran las
               mismas filas en el navegador —mismo criterio que admin.js para
               las pestañas grandes, sin pedir nada al servidor otra vez—. */ ?>
      <div class="admin-tabs admin-subtabs" id="eventosSubtabs">
        <button data-situacion="" class="active">Todas</button>
        <button data-situacion="publicado">Publicadas</button>
        <button data-situacion="cancelado">Canceladas</button>
        <button data-situacion="oculto">Ocultas</button>
      </div>

      <table class="admtable">
        <thead><tr><th>Título</th><th>Organiza</th><th>Ciudad</th><th>Fecha</th><th>Situación</th><th></th></tr></thead>
        <tbody>
          <?php if (!$eventosAdmin): ?>
            <tr><td colspan="6" style="opacity:.8;">Todavía no hay actividades.</td></tr>
          <?php endif; ?>
          <?php foreach ($eventosAdmin as $ea): $p = fechaPartes($ea['fecha_inicio']); ?>
            <tr data-situacion="<?= e($ea['situacion']) ?>">
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
                <?php if (!empty($ea['organizador_email'])): ?>
                  <a class="actionbtn" href="mailto:<?= e($ea['organizador_email']) ?>">Contactar organizador</a>
                <?php endif; ?>
                <?php /* «Ocultar» pega directo contra la ficha —mismo botón,
                         mismo permiso (esAdmin), misma retirarEvento() con su
                         auditoría en eventos_retiros— para no duplicar esa
                         lógica aquí. Solo tiene sentido sobre algo publicado:
                         lo demás ya está oculto, cancelado o ni publicado. */ ?>
                <?php if ($ea['situacion'] === 'publicado'): ?>
                  <form method="post" action="<?= e(urlEvento($ea)) ?>?volver=admin" style="display:inline;">
                    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
                    <button class="actionbtn" type="submit" name="ocultar" value="1">Ocultar</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="evergreen-note" style="margin-top:18px;">
        Eliminar de verdad ya no existe en ningún lado del sitio (Req. 17092026): ni el
        organizador ni un administrador vuelven a borrar una actividad. «Ocultar» de aquí arriba
        es la única acción, y conserva la fila.
      </div>
    </div>

    <!-- REPORTES — antes moderacion.php, página aparte. Lo que alguien señaló:
         reportes de visitantes y los que crea solo el filtro de palabras al
         publicar. Las actividades siguen publicadas; aquí se decide qué
         hacer con ellas. Misma lógica de siempre (ver el POST handling más
         arriba): Descartar / Ocultar / Volver a publicar, nunca borrado. -->
    <div class="admin-panel <?= $panelActivo === 'reportes' ? 'active' : '' ?>" id="panel-reportes">
      <?php if ($avisoReportes): ?><div class="aviso aviso-ok"><?= e($avisoReportes) ?></div><?php endif; ?>
      <?php if ($errorReportes): ?><div class="aviso aviso-error"><?= e($errorReportes) ?></div><?php endif; ?>

      <?php if ($sinTablaReportes): ?>
        <div class="aviso aviso-error" style="margin-bottom:0;">
          <strong>Falta la tabla de reportes en la base de datos.</strong>
          Entra a phpMyAdmin, selecciona tu base y ejecuta
          <code>database/migracion-03-reportes.sql</code>. Hasta entonces nadie puede
          reportar actividades y esta pestaña no funciona.
        </div>

      <?php elseif (!$pendientesReportes): ?>
        <div class="aviso aviso-ok" style="margin-bottom:0;">
          No hay nada que revisar. Es lo normal: las actividades se publican solas y
          aquí solo aparece lo que alguien señala.
        </div>

      <?php else: foreach ($pendientesReportes as $rep): ?>
        <div class="caso">
          <div class="caso-cab">
            <div>
              <h2><a href="<?= e(urlEvento($rep)) ?>?volver=admin"><?= e($rep['titulo']) ?></a></h2>
              <div class="caso-meta">
                <?= e($rep['categoria']) ?> · <?= e($rep['ciudad']) ?> ·
                organiza <?= e($rep['organizador']) ?> (<?= e($rep['organizador_email']) ?>)
              </div>
            </div>
            <div class="caso-cifras">
              <span class="cuenta"><?= (int) $rep['total'] ?></span>
              <span class="cuenta-lbl">aviso<?= (int) $rep['total'] === 1 ? '' : 's' ?></span>
            </div>
          </div>

          <?php if (!empty($rep['tiene_automatico'])): ?>
            <div class="marca-auto">Lo levantó el filtro automático de palabras, no una persona.</div>
          <?php endif; ?>

          <?php if ($rep['situacion'] !== 'publicado'): ?>
            <div class="marca-auto">Ahora mismo está <strong><?= e($rep['situacion']) ?></strong>.</div>
          <?php endif; ?>

          <ul class="caso-reportes">
            <?php foreach ($reportesPorEvento[(int) $rep['id']] ?? [] as $r): ?>
              <li>
                <span class="motivo-tag"><?= e(motivosReporte()[$r['motivo']] ?? $r['motivo']) ?></span>
                <?php if (!empty($r['comentario'])): ?>
                  <span class="comentario"><?= e($r['comentario']) ?></span>
                <?php endif; ?>
                <span class="cuando"><?= e(fechaLarga($r['creado_en'])) ?><?= $r['situacion'] === 'revisado' ? ' · ya revisado' : '' ?></span>
              </li>
            <?php endforeach; ?>
          </ul>

          <div class="caso-acciones">
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
              <input type="hidden" name="evento_id" value="<?= (int) $rep['id'] ?>">
              <button class="btn-barra" type="submit" name="descartar" value="1">Descartar avisos</button>
            </form>

            <?php if ($rep['situacion'] === 'publicado'): ?>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
                <input type="hidden" name="evento_id" value="<?= (int) $rep['id'] ?>">
                <button class="btn-barra" type="submit" name="ocultar_reporte" value="1">Ocultar</button>
              </form>
            <?php else: ?>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
                <input type="hidden" name="evento_id" value="<?= (int) $rep['id'] ?>">
                <button class="btn-barra" type="submit" name="publicar_reporte" value="1">Volver a publicar</button>
              </form>
            <?php endif; ?>

            <a class="btn-barra" href="<?= e(urlEditarEvento($rep) . '?volver=admin') ?>">Editar</a>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- RETIROS — solicitudes de quien ya no puede retirar por su cuenta
         (pasadas EVENTO_MARGEN_RETIRO_H horas), Req. 17092026 punto 8. Dos
         salidas, ninguna borra nada: Aprobar oculta la actividad; Descartar
         la deja como estaba. Las dos cierran la solicitud. -->
    <div class="admin-panel <?= $panelActivo === 'retiros' ? 'active' : '' ?>" id="panel-retiros">
      <?php if ($sinTablaRetiros): ?>
        <div class="aviso aviso-error" style="margin-bottom:0;">
          <strong>Falta la tabla de retiros en la base de datos.</strong>
          Entra a phpMyAdmin, selecciona tu base y ejecuta
          <code>database/migracion-26-cancelar-retirar-actividad.sql</code>.
        </div>

      <?php elseif (!$retirosAdmin): ?>
        <div class="aviso aviso-ok" style="margin-bottom:0;">
          No hay solicitudes de retiro esperando respuesta.
        </div>

      <?php else: foreach ($retirosAdmin as $ret): ?>
        <div class="caso">
          <div class="caso-cab">
            <div>
              <h2><a href="<?= e(urlEvento($ret)) ?>?volver=admin"><?= e($ret['titulo']) ?></a></h2>
              <div class="caso-meta">
                <?= e($ret['categoria']) ?> · <?= e($ret['ciudad']) ?> ·
                organiza <?= e($ret['organizador']) ?> (<?= e($ret['organizador_email']) ?>) ·
                solicitado <?= e(fechaLarga($ret['solicitado_en'])) ?>
              </div>
            </div>
          </div>

          <?php if (!empty($ret['motivo'])): ?>
            <div class="marca-auto"><?= e($ret['motivo']) ?></div>
          <?php endif; ?>

          <div class="caso-acciones">
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
              <input type="hidden" name="evento_id" value="<?= (int) $ret['id'] ?>">
              <input type="hidden" name="retiro_id" value="<?= (int) $ret['retiro_id'] ?>">
              <button class="btn-barra" type="submit" name="procesar_retiro" value="1">Aprobar y ocultar</button>
            </form>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
              <input type="hidden" name="evento_id" value="<?= (int) $ret['id'] ?>">
              <input type="hidden" name="retiro_id" value="<?= (int) $ret['retiro_id'] ?>">
              <button class="btn-barra" type="submit" name="descartar_retiro" value="1">Descartar</button>
            </form>
            <a class="btn-barra" href="mailto:<?= e($ret['organizador_email']) ?>">Contactar organizador</a>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- ORGANIZADORES — quien ya publicó al menos una actividad (ver publicarEvento()) -->
    <div class="admin-panel <?= $panelActivo === 'organizadores' ? 'active' : '' ?>" id="panel-organizadores">
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
    <div class="admin-panel <?= $panelActivo === 'categorias' ? 'active' : '' ?>" id="panel-categorias">
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
    <div class="admin-panel <?= $panelActivo === 'ciudades' ? 'active' : '' ?>" id="panel-ciudades">
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
    <div class="admin-panel <?= $panelActivo === 'usuarios' ? 'active' : '' ?>" id="panel-usuarios">
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
    <div class="admin-panel <?= $panelActivo === 'mensajes' ? 'active' : '' ?>" id="panel-mensajes">
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
