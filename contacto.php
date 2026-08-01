<?php
/**
 * Contacto general del sitio. Abierto a cualquiera, sin cuenta.
 *
 * Distinto de contactar.php: ese es "contactar al organizador de ESTA
 * actividad"; este es para quien escribe sin tener ninguna actividad en
 * mente —una alianza, una duda general, un problema con el sitio—. Mismo
 * patrón de siempre: límite por IP, captcha si hay claves puestas, y el
 * mensaje llega por correo con el Reply-To puesto a quien escribe.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$error   = '';
$enviado = false;
$nombre  = (string) ($_POST['nombre'] ?? '');
$email   = (string) ($_POST['email'] ?? '');
$mensaje = (string) ($_POST['mensaje'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'El formulario caducó. Vuelve a cargarlo.';

    } elseif (!($captcha = captchaValido($_POST))[0]) {
        $error = $captcha[1];

    } elseif (trim($nombre) === '') {
        $error = 'Escribe tu nombre.';

    } elseif (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
        $error = 'Ese correo no parece válido.';

    } elseif (trim($mensaje) === '') {
        $error = 'Escribe tu mensaje.';

    } elseif (contactoSitioRepetido()) {
        $error = 'Ya nos escribiste hace un momento. Danos tiempo para responder antes de volver a escribir.';

    } else {
        $nombre  = trim($nombre);
        $email   = trim($email);
        $mensaje = trim($mensaje);

        crearContactoSitio($nombre, $email, $mensaje);
        avisarAdminsContactoSitio($nombre, $email, $mensaje);
        $enviado = true;
    }
}

$titulo      = 'Contacto';
$descripcion = 'Escríbenos: alianzas, dudas generales o cualquier cosa que no sea sobre una actividad en particular.';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja">

<?php if ($enviado): ?>

  <h1>Mensaje enviado</h1>
  <p class="sub">Nos llegó por correo, con tu dirección para poder responderte directo.</p>

  <div class="aviso aviso-ok">
    Si no tienes noticias en unos días, revisa tu carpeta de spam.
  </div>

  <a class="btn-principal" style="display:block; text-align:center; text-decoration:none;"
     href="<?= URL_BASE ?>/">Volver al inicio</a>

<?php else: ?>

  <h1>Contacto</h1>
  <p class="sub">¿Tienes una actividad publicada y quieres preguntar algo sobre ella? Ve a su ficha y usa «Contactar al organizador» — a nosotros solo escríbenos por lo demás.</p>

  <?php if ($error): ?>
    <div class="aviso aviso-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <?= captchaCamposOcultos() ?>

    <div class="campo">
      <label for="nombre">Tu nombre</label>
      <input id="nombre" name="nombre" type="text" maxlength="120"
             value="<?= e($nombre) ?>" required>
    </div>

    <div class="campo">
      <label for="email">Tu correo</label>
      <input id="email" name="email" type="email" maxlength="190"
             value="<?= e($email) ?>" placeholder="tucorreo@ejemplo.com" required>
      <div class="pista">Aquí te vamos a responder.</div>
    </div>

    <div class="campo">
      <label for="mensaje">Tu mensaje</label>
      <textarea id="mensaje" name="mensaje" rows="5" maxlength="1000" required
                placeholder="Cuéntanos qué necesitas."><?= e($mensaje) ?></textarea>
    </div>

    <?= captchaHtml() ?>

    <button class="btn-principal" type="submit">Enviar mensaje</button>
  </form>

<?php endif; ?>

</div>

<?php pie(); ?>
