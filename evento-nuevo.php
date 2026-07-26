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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $errores['general'] = 'La sesión caducó. Vuelve a enviarlo.';
        $e = $_POST;
    } else {
        [$e, $errores] = validarEvento($_POST);

        if (!$errores) {
            $id = crearEvento($e, (int) $u['id']);
            redirigir('/evento.php?id=' . $id);
        }
    }
}

$titulo = 'Publicar un evento';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja caja-ancha">
  <h1>Publicar un evento</h1>
  <p class="sub">Rellena la ficha. Antes de publicarla la vas a ver tal como la verá la gente.</p>

  <?php if (!empty($errores['general'])): ?>
    <div class="aviso aviso-error"><?= e($errores['general']) ?></div>
  <?php elseif ($errores): ?>
    <div class="aviso aviso-error">Revisa los campos marcados.</div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <?php $textoBoton = 'Ver la vista previa'; require __DIR__ . '/includes/form-evento.php'; ?>
  </form>
</div>

<?php pie(); ?>
