<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/google.php';

if (haySesion()) redirigir('/');

$error = '';

// Si venía de escribir el correo y volvió con «Editar», que lo encuentre puesto.
$email = (string) ($_SESSION['codigo_email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = (string) ($_POST['email'] ?? '');

    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'La sesión caducó. Vuelve a intentarlo.';
    } else {
        /*
         * La casilla se recuerda para el paso siguiente (REQ-00008).
         *
         * No se exige AQUÍ, y es deliberado: este formulario es a la vez entrar
         * y crear cuenta —lo dice su propio título—, así que exigirla obligaría
         * a quien ya tiene cuenta a volver a aceptar los Términos en cada
         * inicio de sesión. Aceptar se hace una vez, al darse de alta.
         *
         * Comprobarlo aquí tendría además un efecto feo: como solo se podría
         * exigir a los correos sin cuenta, el propio mensaje de error revelaría
         * si un correo está registrado o no, a cualquiera que lo probara.
         *
         * Donde sí es obligatoria es donde se crea la cuenta, que es lo que el
         * requerimiento pide de verdad: completar-registro.php no deja pasar sin
         * ella. Marcarla aquí solo ahorra esa pantalla a quien ya la marcó.
         */
        $_SESSION['acepta_legal'] = !empty($_POST['acepto']);

        $normalizado    = mb_strtolower(trim($email));
        [$ok, $mensaje] = solicitarCodigo($email);

        if ($ok) {
            $_SESSION['codigo_email'] = $normalizado;
            redirigir('/codigo.php');
        }

        // Si ya se había mandado un código a este mismo correo, el fallo es
        // "espera un minuto", no "no se pudo". Dejarlo aquí sería un callejón
        // sin salida: el código está en su buzón y esta pantalla no tiene dónde
        // escribirlo. Se le manda al paso siguiente con el aviso.
        if (($_SESSION['codigo_email'] ?? '') === $normalizado) {
            $_SESSION['codigo_error'] = $mensaje;
            redirigir('/codigo.php');
        }

        $error = $mensaje;
    }
}

// Mensajes que llegan del callback de Google. Si el callback dejó un aviso más
// concreto en sesión (por ejemplo "ese correo ya está registrado"), gana ese.
if (!empty($_GET['error'])) {
    $mensajes = [
        'google'    => 'No se pudo completar el acceso con Google.',
        'state'     => 'La petición no se pudo verificar. Inténtalo otra vez.',
        'cancelado' => 'Cancelaste el acceso con Google.',
    ];
    $clave = (string) $_GET['error'];
    $error = isset($mensajes[$clave]) ? $mensajes[$clave] : 'Algo salió mal. Inténtalo otra vez.';

    if (!empty($_SESSION['aviso_login'])) {
        $error = (string) $_SESSION['aviso_login'];
        unset($_SESSION['aviso_login']);
    }
}

$titulo = 'Entrar';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja">
  <h1>Entrar o crear cuenta</h1>
  <p class="sub">Sin contraseñas: te mandamos un código al correo. Si es tu primera vez, la cuenta se crea sola.</p>

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
      <label for="email">Correo</label>
      <input id="email" name="email" type="email" autocomplete="email" required autofocus
             value="<?= e($email) ?>" placeholder="tucorreo@ejemplo.com">
    </div>

    <button class="btn-principal" type="submit">Continuar</button>

    <?php
    /*
     * Debajo del botón, como pide REQ-00008. Sin el atributo "required" del
     * navegador: aquí no se puede bloquear el envío sin bloquear también a
     * quien solo viene a entrar. La casilla es obligatoria para crear cuenta, y
     * eso lo garantiza completar-registro.php, que es donde la cuenta se crea.
     */
    $casillaMarcada     = !empty($_SESSION['acepta_legal']);
    $casillaObligatoria = false;
    ?>
    <?php require __DIR__ . '/includes/casilla-legal.php'; ?>
  </form>
</div>

<?php pie(); ?>
