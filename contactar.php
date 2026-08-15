<?php
/**
 * Contactar al organizador. Abierto a cualquiera, sin cuenta.
 *
 * Mismo criterio que reportar.php: quien tiene una pregunta antes de
 * apuntarse no se va a registrar solo para preguntarla. El mensaje llega al
 * correo del organizador con el Reply-To puesto a quien escribe, así que
 * responder es tan simple como contestar ese correo.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/eventos.php';

$ev = buscarEvento((int) ($_GET['id'] ?? 0));

if (!$ev || $ev['situacion'] !== 'publicado') {
    http_response_code(404);
    $titulo = 'Actividad no encontrada';
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>Esa actividad no existe</h1>'
       . '<p class="sub">Puede que ya se haya retirado.</p></div>';
    pie();
    exit;
}

// Este formulario es para la acción "Contactar al organizador". Si el
// organizador eligió otra, aquí no hay nada que hacer: se manda de vuelta a
// la ficha, que es donde está el enlace que sí corresponde.
if ($ev['accion_principal'] !== 'informacion') {
    redirigir(urlEvento($ev));
}

$error   = '';
$enviado = false;
$nombre  = (string) ($_POST['nombre'] ?? '');
$email   = (string) ($_POST['email'] ?? '');
$mensaje = (string) ($_POST['mensaje'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // El captcha se comprueba dentro de la cadena y no antes: verificarlo
    // implica una petición a Cloudflare, y no hay razón para gastarla en un
    // envío que ya se cayó por el token.
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'El formulario caducó. Vuelve a cargarlo.';

    } elseif (!($captcha = captchaValido($_POST))[0]) {
        $error = $captcha[1];

    } elseif (trim($nombre) === '') {
        $error = 'Escribe tu nombre para que el organizador sepa quién pregunta.';

    } elseif (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
        $error = 'Ese correo no parece válido.';

    } elseif (contactoRepetido((int) $ev['id'])) {
        $error = 'Ya le escribiste a este organizador hace un momento. Dale tiempo a responder antes de volver a escribir.';

    } else {
        $nombre  = trim($nombre);
        $email   = trim($email);
        $mensaje = trim($mensaje) !== '' ? trim($mensaje) : null;

        crearContacto((int) $ev['id'], $nombre, $email, $mensaje);
        avisarOrganizador($ev, $nombre, $email, $mensaje);
        $enviado = true;
    }
}

$titulo = 'Contactar al organizador';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja">

<?php if ($enviado): ?>

  <h1>Mensaje enviado</h1>
  <p class="sub">Le llegó al organizador por correo, con tu dirección para que pueda responderte directo.</p>

  <div class="aviso aviso-ok">
    Si no tienes noticias en unos días, revisa tu carpeta de spam o intenta con otro medio si la actividad lo ofrece.
  </div>

  <a class="btn-principal" style="display:block; text-align:center; text-decoration:none;"
     href="<?= e(urlEvento($ev)) ?>">Volver a la actividad</a>

<?php else: ?>

  <a class="volver" href="<?= e(urlEvento($ev)) ?>">← Volver a la actividad</a>

  <h1>Contactar al organizador</h1>
  <p class="sub">«<?= e($ev['titulo']) ?>» · <?= e($ev['organizador']) ?></p>

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
      <div class="pista">Aquí te va a responder el organizador.</div>
    </div>

    <div class="campo">
      <label for="mensaje">Tu mensaje <span class="opcional">opcional</span></label>
      <textarea id="mensaje" name="mensaje" rows="4" maxlength="1000"
                placeholder="Cuéntale al organizador qué quieres saber."><?= e($mensaje) ?></textarea>
    </div>

    <?= captchaHtml() ?>

    <button class="btn-principal" type="submit">Enviar mensaje</button>
  </form>

  <div class="auth-pie">
    Tu correo solo lo recibe el organizador de esta actividad. No se hace pública en ningún lado.
  </div>

<?php endif; ?>

</div>

<?php pie(); ?>
