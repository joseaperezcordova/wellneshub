<?php
/**
 * Alta de un evento.
 *
 * Guarda como BORRADOR y manda a la vista previa. Nada se publica desde este
 * formulario: quien escribe una ficha larga quiere verla antes de enseñarla, y
 * un botón "publicar" al final del formulario no deja hacerlo.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/eventos.php';

$u = exigirSesion();

$e       = ['color' => coloresEvento()[0]];
$errores = [];

if (postDesbordado()) {
    // Se comprueba antes que el CSRF: con el cuerpo descartado tampoco llega el
    // token, y el mensaje "la sesión caducó" mandaría a buscar el problema al
    // sitio equivocado.
    $errores['general'] = 'La imagen pesa más de lo que admite el servidor. Prueba con una más ligera.';

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $errores['general'] = 'La sesión caducó. Vuelve a enviarlo.';
        $e = $_POST;
    } else {
        [$e, $errores] = validarEvento($_POST);

        [$okImagen, $imagen] = guardarImagenSubida($_FILES['imagen'] ?? []);
        if (!$okImagen) {
            $errores['imagen'] = (string) $imagen;
        } else {
            $e['imagen_url'] = $imagen;
        }

        if (!$errores) {
            $id = crearEvento($e, (int) $u['id']);
            redirigir('/evento.php?id=' . $id);
        }

        // Si algo falló, la imagen ya guardada no se queda de okupa en el disco:
        // el formulario vuelve a salir y se subirá otra vez.
        if ($errores && $okImagen && $imagen !== null) {
            borrarImagenGuardada($imagen);
            $e['imagen_url'] = null;
        }
    }
}

$titulo = 'Publicar un evento';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja caja-ancha">
  <h1>Publicar un evento</h1>
  <p class="sub">Rellena la ficha. Antes de publicarla la vas a ver tal como la verá la gente.</p>

  <?php require __DIR__ . '/includes/aviso-errores.php'; ?>

  <form method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <?php $textoBoton = 'Ver la vista previa'; require __DIR__ . '/includes/form-evento.php'; ?>
  </form>
</div>

<?php pie(); ?>
