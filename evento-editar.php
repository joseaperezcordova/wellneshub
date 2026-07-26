<?php
/**
 * Edición de un evento.
 *
 * El permiso lo decide puedeEditarEvento(): el administrador siempre, y el
 * organizador mientras sea borrador o esté dentro de las 24 horas siguientes a
 * publicarlo. Pasado el plazo esta página no enseña el formulario, y lo explica
 * en vez de limitarse a un "no puedes".
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/eventos.php';

$u  = exigirSesion();
$ev = buscarEvento((int) ($_GET['id'] ?? 0));

// Se comprueba VER antes que editar. Sin esto, quien abriera el editor de un
// borrador ajeno recibía la pantalla de "ya no se puede editar" con el título
// dentro, que es filtrar el contenido de un evento que nadie ha publicado.
if ($ev && !puedeVerEvento($ev, $u)) {
    $ev = null;
}

if (!$ev) {
    http_response_code(404);
    $titulo = 'Evento no encontrado';
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>Ese evento no existe</h1>'
       . '<p class="sub">Puede que se haya borrado.</p>'
       . '<a class="btn-principal" style="text-decoration:none; display:block; text-align:center;" href="' . URL_BASE . '/">Volver al inicio</a></div>';
    pie();
    exit;
}

$puede = puedeEditarEvento($ev, $u);

$e       = $ev;
$errores = [];

if ($puede && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $errores['general'] = 'La sesión caducó. Vuelve a enviarlo.';
        $e = $_POST;
    } else {
        [$e, $errores] = validarEvento($_POST);

        if (!$errores) {
            actualizarEvento($e, (int) $ev['id']);
            $_SESSION['evento_aviso'] = 'Cambios guardados.';
            redirigir('/evento.php?id=' . (int) $ev['id']);
        }
    }
}

$titulo = 'Editar evento';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja caja-ancha">

<?php if (!$puede): ?>

  <h1>Ya no se puede editar</h1>
  <p class="sub">«<?= e($ev['titulo']) ?>»</p>

  <div class="aviso aviso-error">
    El plazo para corregir un evento es de <?= EVENTO_MARGEN_EDICION_H ?> horas desde que se publica<?php
      // publicado_en puede ser NULL si el evento nunca llegó a publicarse; en
      // ese caso no hay fecha límite que enseñar.
      if (!empty($ev['publicado_en'])):
        $limite = date('Y-m-d H:i:s', strtotime($ev['publicado_en']) + EVENTO_MARGEN_EDICION_H * 3600);
    ?>, y el de este terminó el <?= e(fechaLarga($limite)) ?><?php endif; ?>.
  </div>

  <p style="font-size:14px; line-height:1.6;">
    Es a propósito: una ficha que cambia después de que la gente ya se apuntó
    —otra fecha, otro precio, otro lugar— deja tirado a quien contaba con lo que
    leyó. A partir de aquí los cambios pasan por el administrador, que ve qué se
    modificó y puede avisar si hace falta.
  </p>

  <p style="font-size:14px; line-height:1.6;">
    Escríbele contando qué hay que cambiar y en qué evento.
  </p>

  <a class="btn-principal" style="text-decoration:none; display:block; text-align:center;"
     href="<?= URL_BASE ?>/evento.php?id=<?= (int) $ev['id'] ?>">Volver a la ficha</a>

<?php else: ?>

  <h1>Editar evento</h1>
  <?php if ($ev['situacion'] === 'borrador'): ?>
    <p class="sub">Es un borrador: no lo ve nadie más que tú hasta que lo publiques.</p>
  <?php else: ?>
    <?php $quedan = minutosRestantesEdicion($ev); ?>
    <p class="sub">
      Publicado. Te quedan
      <strong><?= $quedan >= 60 ? intdiv($quedan, 60) . ' h ' . ($quedan % 60) . ' min' : $quedan . ' min' ?></strong>
      de margen para corregirlo<?= esAdmin($u) && (int) $ev['usuario_id'] !== (int) $u['id'] ? ' (tú eres administrador: puedes editarlo siempre)' : '' ?>.
    </p>
  <?php endif; ?>

  <?php if (!empty($errores['general'])): ?>
    <div class="aviso aviso-error"><?= e($errores['general']) ?></div>
  <?php elseif ($errores): ?>
    <div class="aviso aviso-error">Revisa los campos marcados.</div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <?php $textoBoton = 'Guardar cambios'; require __DIR__ . '/includes/form-evento.php'; ?>
  </form>

<?php endif; ?>

</div>

<?php pie(); ?>
