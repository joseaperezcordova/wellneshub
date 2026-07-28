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

if ($puede && postDesbordado()) {
    $errores['general'] = 'La imagen pesa más de lo que admite el servidor. Prueba con una más ligera.';

} elseif ($puede && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // La que ya tiene guardada. No mandar archivo significa «déjala como está»,
    // no «bórrala».
    $imagenPrevia = $ev['imagen_url'];

    if (!csrfValido($_POST['csrf'] ?? null)) {
        $errores['general'] = 'La sesión caducó. Vuelve a enviarlo.';
        $e = $_POST;
        $e['imagen_url'] = imagenArrastrada($_POST['imagen_previa'] ?? null, $imagenPrevia);
    } else {
        [$e, $errores] = validarEvento($_POST);

        [$e['imagen_url'], $errorImagen] = imagenDelFormulario($_POST, $_FILES, $imagenPrevia);
        if ($errorImagen !== null) {
            $errores['imagen'] = $errorImagen;
        }

        if (!$errores) {
            actualizarEvento($e, (int) $ev['id']);
            olvidarImagenEnVuelo($e['imagen_url']);

            // La anterior se borra solo cuando el cambio ya está guardado. Al
            // revés, un fallo al actualizar dejaría la ficha apuntando a un
            // archivo que ya no existe.
            if ($imagenPrevia !== null && $e['imagen_url'] !== $imagenPrevia) {
                borrarImagenGuardada($imagenPrevia);
            }

            $_SESSION['evento_aviso'] = 'Cambios guardados.';

            // Si se llegó aquí desde el panel admin, esa procedencia se lleva
            // a la ficha para que su enlace de vuelta apunte al mismo sitio.
            $volver = (string) ($_GET['volver'] ?? '');
            redirigir('/evento.php?id=' . (int) $ev['id']
                . ($volver !== '' ? '&volver=' . urlencode($volver) : ''));
        }

        /*
         * Si algo falló, la foto recién subida SE QUEDA y el formulario vuelve a
         * salir con ella puesta. Antes se borraba y se volvía a enseñar la
         * antigua, con lo que el cambio de imagen se perdía sin avisar: parecía
         * que no se había llegado a elegir ninguna.
         *
         * La guardada del evento no se toca hasta que el cambio esté escrito.
         */
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

  <?php require __DIR__ . '/includes/aviso-errores.php'; ?>

  <form method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <?php $textoBoton = 'Guardar cambios'; require __DIR__ . '/includes/form-evento.php'; ?>
  </form>

  <?php /* Mismo permiso que habilitó esta página ($puede), y el mismo endpoint
           que ya borra desde la ficha —evento.php—: no hace falta duplicar
           aquí la comprobación de plazo ni la de quién manda. */ ?>
  <form method="post" action="<?= URL_BASE ?>/evento.php?id=<?= (int) $ev['id'] ?>"
        onsubmit="return confirm('¿Eliminar «<?= e(addslashes($ev['titulo'])) ?>»? No se puede deshacer.');"
        style="margin-top:18px;">
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <button class="btn-barra peligro" type="submit" name="eliminar" value="1">Eliminar evento</button>
  </form>

<?php endif; ?>

</div>

<?php pie(); ?>
