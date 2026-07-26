<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/google.php';

if (haySesion()) redirigir('/');

$error  = '';
$nombre = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = (string) ($_POST['nombre'] ?? '');
    $email  = (string) ($_POST['email'] ?? '');

    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'La sesión caducó. Vuelve a intentarlo.';
    } else {
        [$ok, $resultado] = registrarUsuario($nombre, $email, (string) ($_POST['password'] ?? ''));
        if ($ok) {
            // Se entra directamente tras registrarse: pedir un login inmediato
            // después de escribir la contraseña es fricción sin ninguna ganancia.
            iniciarSesion((int) $resultado);
            redirigir('/');
        }
        $error = $resultado;
    }
}

$titulo = 'Crear cuenta';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja">
  <h1>Crear cuenta</h1>
  <p class="sub">Guarda los eventos que te interesan y publica los tuyos.</p>

  <?php if ($error): ?>
    <div class="aviso aviso-error"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (googleConfigurado()): ?>
    <a class="btn-google" href="<?= URL_BASE ?>/google-redirect.php">
      <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
        <path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h11.8c-.5 2.8-2 5.1-4.4 6.7v5.5h7.1c4.2-3.8 6.6-9.5 6.6-16.2z"/>
        <path fill="#34A853" d="M24 46c6 0 11-2 14.5-5.3l-7.1-5.5c-2 1.3-4.5 2.1-7.4 2.1-5.7 0-10.6-3.9-12.3-9.1H4.3v5.7C7.8 41.1 15.3 46 24 46z"/>
        <path fill="#FBBC05" d="M11.7 28.2c-.4-1.3-.7-2.7-.7-4.2s.3-2.9.7-4.2v-5.7H4.3C2.8 17.1 2 20.5 2 24s.8 6.9 2.3 9.9l7.4-5.7z"/>
        <path fill="#EA4335" d="M24 10.7c3.2 0 6.1 1.1 8.4 3.3l6.3-6.3C34.9 4.1 30 2 24 2 15.3 2 7.8 6.9 4.3 14.1l7.4 5.7c1.7-5.2 6.6-9.1 12.3-9.1z"/>
      </svg>
      Continuar con Google
    </a>
    <div class="separador">o con tu correo</div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">

    <div class="campo">
      <label for="nombre">Nombre</label>
      <input id="nombre" name="nombre" type="text" autocomplete="name" required
             value="<?= e($nombre) ?>" placeholder="Cómo quieres que te llamemos">
    </div>

    <div class="campo">
      <label for="email">Correo</label>
      <input id="email" name="email" type="email" autocomplete="email" required
             value="<?= e($email) ?>" placeholder="tucorreo@ejemplo.com">
    </div>

    <div class="campo">
      <label for="password">Contraseña</label>
      <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8">
      <div class="pista">Mínimo 8 caracteres.</div>
    </div>

    <button class="btn-principal" type="submit">Crear cuenta</button>
  </form>

  <div class="auth-pie">
    ¿Ya tienes cuenta? <a href="<?= URL_BASE ?>/login.php">Entrar</a>
  </div>
</div>

<?php pie(); ?>
