<?php
/**
 * Bandeja de moderación. Solo administradores.
 *
 * Aquí llega lo que alguien señaló: reportes de visitantes y los que crea solo
 * el filtro de palabras al publicar. Los eventos siguen publicados; en esta
 * página se decide qué hacer con ellos.
 *
 * Tres salidas, y ninguna es automática:
 *
 *   · Descartar  — el aviso no tenía razón. El evento sigue igual.
 *   · Ocultar    — desaparece del listado y se puede volver a publicar.
 *   · Eliminar   — se va del todo, con su confirmación delante.
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
       . '<p class="sub">Solo los administradores moderan eventos.</p></div>';
    pie();
    exit;
}

$aviso = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventoId = (int) ($_POST['evento_id'] ?? 0);

    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'La sesión caducó. Vuelve a intentarlo.';

    } elseif (!buscarEvento($eventoId)) {
        $error = 'Ese evento ya no existe.';

    } elseif (isset($_POST['descartar'])) {
        marcarReportesRevisados($eventoId, (int) $u['id']);
        $aviso = 'Reportes descartados. El evento sigue publicado.';

    } elseif (isset($_POST['ocultar'])) {
        cambiarSituacionEvento($eventoId, 'oculto');
        marcarReportesRevisados($eventoId, (int) $u['id']);
        $aviso = 'Evento oculto. Ya no aparece en el listado.';

    } elseif (isset($_POST['publicar'])) {
        cambiarSituacionEvento($eventoId, 'publicado');
        marcarReportesRevisados($eventoId, (int) $u['id']);
        $aviso = 'Evento publicado otra vez.';

    } elseif (isset($_POST['eliminar'])) {
        // Los reportes se van con él por la clave foránea en cascada.
        eliminarEvento($eventoId);
        $aviso = 'Evento eliminado.';
    }
}

$pendientes = reportesPendientes();

$titulo = 'Moderación';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja caja-ancha">
  <h1>Moderación</h1>
  <p class="sub">
    <?php if ($pendientes): ?>
      <?= count($pendientes) ?> evento<?= count($pendientes) === 1 ? '' : 's' ?> con avisos sin revisar.
    <?php else: ?>
      Nada pendiente.
    <?php endif; ?>
  </p>

  <?php if ($aviso): ?><div class="aviso aviso-ok"><?= e($aviso) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="aviso aviso-error"><?= e($error) ?></div><?php endif; ?>

  <?php if (!$pendientes): ?>

    <div class="aviso aviso-ok" style="margin-bottom:0;">
      No hay nada que revisar. Es lo normal: los eventos se publican solos y aquí
      solo aparece lo que alguien señala.
    </div>

  <?php else: foreach ($pendientes as $p): ?>

    <div class="caso">
      <div class="caso-cab">
        <div>
          <h2><a href="<?= URL_BASE ?>/evento.php?id=<?= (int) $p['id'] ?>"><?= e($p['titulo']) ?></a></h2>
          <div class="caso-meta">
            <?= e($p['categoria']) ?> · <?= e($p['ciudad']) ?> ·
            organiza <?= e($p['organizador']) ?> (<?= e($p['organizador_email']) ?>)
          </div>
        </div>
        <div class="caso-cifras">
          <span class="cuenta"><?= (int) $p['total'] ?></span>
          <span class="cuenta-lbl">aviso<?= (int) $p['total'] === 1 ? '' : 's' ?></span>
        </div>
      </div>

      <?php if (!empty($p['tiene_automatico'])): ?>
        <div class="marca-auto">Lo levantó el filtro automático de palabras, no una persona.</div>
      <?php endif; ?>

      <?php if ($p['situacion'] !== 'publicado'): ?>
        <div class="marca-auto">Ahora mismo está <strong><?= e($p['situacion']) ?></strong>.</div>
      <?php endif; ?>

      <ul class="caso-reportes">
        <?php foreach (reportesDeEvento((int) $p['id']) as $r): ?>
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
          <input type="hidden" name="evento_id" value="<?= (int) $p['id'] ?>">
          <button class="btn-barra" type="submit" name="descartar" value="1">Descartar avisos</button>
        </form>

        <?php if ($p['situacion'] === 'publicado'): ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
            <input type="hidden" name="evento_id" value="<?= (int) $p['id'] ?>">
            <button class="btn-barra" type="submit" name="ocultar" value="1">Ocultar</button>
          </form>
        <?php else: ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
            <input type="hidden" name="evento_id" value="<?= (int) $p['id'] ?>">
            <button class="btn-barra" type="submit" name="publicar" value="1">Volver a publicar</button>
          </form>
        <?php endif; ?>

        <a class="btn-barra" href="<?= URL_BASE ?>/evento-editar.php?id=<?= (int) $p['id'] ?>">Editar</a>

        <form method="post" onsubmit="return confirm('¿Eliminar «<?= e(addslashes($p['titulo'])) ?>»? No se puede deshacer.');">
          <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
          <input type="hidden" name="evento_id" value="<?= (int) $p['id'] ?>">
          <button class="btn-barra peligro" type="submit" name="eliminar" value="1">Eliminar</button>
        </form>
      </div>
    </div>

  <?php endforeach; endif; ?>
</div>

<?php pie(); ?>
