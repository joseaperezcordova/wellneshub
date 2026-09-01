<?php
/**
 * Segundo paso del acceso: escribir el código que llegó por correo.
 *
 * El correo se lleva en sesión y no en un campo del formulario ni en la URL.
 * Si viajara con la petición, cualquiera podría cambiarlo por el de otra
 * persona y usar su propio código para entrar en la cuenta ajena.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

if (haySesion()) redirigir('/');

$email = (string) ($_SESSION['codigo_email'] ?? '');
if ($email === '') redirigir('/login.php');

$error = '';
$aviso = '';

// Avisos que dejó el reenvío antes de redirigir (patrón POST-redirect-GET: sin
// él, recargar la página volvería a mandar otro código).
if (!empty($_SESSION['codigo_error'])) {
    $error = (string) $_SESSION['codigo_error'];
    unset($_SESSION['codigo_error']);
}
if (!empty($_SESSION['codigo_aviso'])) {
    $aviso = (string) $_SESSION['codigo_aviso'];
    unset($_SESSION['codigo_aviso']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = t('codigo.error.sesion_caducada');

    } elseif (isset($_POST['reenviar'])) {
        [$ok, $mensaje] = solicitarCodigo($email);
        $_SESSION[$ok ? 'codigo_aviso' : 'codigo_error'] = $mensaje;
        redirigir('/codigo.php');

    } else {
        [$estado, $resultado] = verificarCodigo($email, (string) ($_POST['codigo'] ?? ''));

        if ($estado === 'entra') {
            // Ya tenía cuenta: esto es entrar, no darse de alta, así que no se
            // le vuelve a pedir la casilla. Si la marcó en login.php y su cuenta
            // es de antes de REQ-00008, se aprovecha para dejar constancia.
            if (!empty($_SESSION['acepta_legal'])) {
                registrarAceptacionLegal((int) $resultado);
            }
            unset($_SESSION['acepta_legal']);

            iniciarSesion((int) $resultado);
            redirigir(destinoTrasLogin());

        } elseif ($estado === 'nueva') {
            /*
             * Aquí NO se crea la cuenta (REQ-00008). El código ya se gastó y el
             * correo está demostrado; lo que falta es aceptar los documentos.
             *
             * Si ya marcó la casilla en login.php no se le pregunta dos veces:
             * se crea aquí mismo con la aceptación registrada. Si no la marcó,
             * la pantalla de completar-registro.php la exige.
             */
            if (!empty($_SESSION['acepta_legal'])) {
                unset($_SESSION['acepta_legal'], $_SESSION['codigo_email']);

                $nuevoId = crearUsuarioPorCorreo($email);
                registrarAceptacionLegal($nuevoId);
                iniciarSesion($nuevoId);
                redirigir(destinoTrasLogin());
            }

            $_SESSION['alta_pendiente'] = ['via' => 'correo', 'email' => $email, 'en' => time()];
            unset($_SESSION['codigo_email']);
            redirigir('/completar-registro.php');
        }

        $error = $resultado;
    }
}

$titulo = t('codigo.pagina.titulo');
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja">
  <h1><?= et('codigo.pagina.titulo') ?></h1>
  <p class="sub"><?= sprintf(et('codigo.sub'), CODIGO_VIGENCIA_MIN) ?></p>

  <?php if ($error): ?>
    <div class="aviso aviso-error"><?= e($error) ?></div>
  <?php endif; ?>
  <?php if ($aviso): ?>
    <div class="aviso aviso-ok"><?= e($aviso) ?></div>
  <?php endif; ?>

  <div class="correo-fijo">
    <span><?= e($email) ?></span>
    <a href="<?= URL_BASE ?>/login.php"><?= et('codigo.editar') ?></a>
  </div>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">

    <div class="campo">
      <label for="codigo"><?= et('codigo.campo.codigo') ?></label>
      <!-- inputmode numeric saca el teclado de números en el móvil sin impedir
           pegar el código; one-time-code deja que el sistema lo autocomplete. -->
      <input id="codigo" name="codigo" class="codigo" type="text" required autofocus
             inputmode="numeric" autocomplete="one-time-code" maxlength="7"
             pattern="[0-9 ]*" placeholder="······">
    </div>

    <button class="btn-principal" type="submit"><?= et('codigo.enviar_btn') ?></button>
  </form>

  <form method="post" class="reenviar-form">
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <button type="submit" name="reenviar" value="1" class="enlace-boton"><?= et('codigo.reenviar_btn') ?></button>
  </form>

  <div class="auth-pie">
    <?= et('codigo.pie') ?>
  </div>
</div>

<?php pie(); ?>
