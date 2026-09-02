<?php
/**
 * Reportar una actividad. Abierto a cualquiera, sin cuenta.
 *
 * Sin cuenta a propósito: quien se topa con una actividad que es una estafa no
 * se va a registrar para avisarnos. Pedir cuenta aquí no filtra bots —esos sí se
 * registran—, filtra personas.
 *
 * Lo que se manda NO oculta nada. La actividad sigue publicada hasta que un
 * administrador decide otra cosa.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';

$ev = buscarEvento((int) ($_GET['id'] ?? 0));

// Solo se reporta lo que está publicado. Un borrador no lo ve nadie más que su
// dueño, así que no hay nada que denunciar.
if (!$ev || $ev['situacion'] !== 'publicado') {
    http_response_code(404);
    $titulo = t('ficha.no_encontrada.titulo');
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>' . et('ficha.no_encontrada.h1') . '</h1>'
       . '<p class="sub">' . et('reportar.no_encontrada.texto') . '</p></div>';
    pie();
    exit;
}

// Punto 7 de docs/pendientes.md: ver el porqué en redirigirSiEsDirecto().
// Sin conservar la consulta: ver la nota igual en contactar.php.
redirigirSiEsDirecto(urlReportar($ev), false);

// Para que el selector de idioma de la cabecera se quede en este mismo
// formulario en vez de mandar al inicio (urlEquivalente(), includes/idioma.php).
$GLOBALS['urlEquivalente'] = [
    'es' => urlReportar($ev, 'es'),
    'en' => urlReportar($ev, 'en'),
];

$error   = '';
$enviado = false;
$motivo  = (string) ($_POST['motivo'] ?? '');
$comento = (string) ($_POST['comentario'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // El captcha se comprueba dentro de la cadena y no antes: verificarlo
    // implica una petición a Cloudflare, y no hay razón para gastarla en un
    // envío que ya se cayó por el token.
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = t('captcha.error.caducado');

    } elseif (!($captcha = captchaValido($_POST))[0]) {
        $error = $captcha[1];

    } elseif (!isset(motivosReporte()[$motivo])) {
        $error = t('reportar.error.motivo_falta');

    } elseif (reporteRepetido((int) $ev['id'])) {
        // Se dice claramente en vez de fingir que se aceptó. Quien ya reportó de
        // buena fe merece saber que su aviso llegó y que no hace falta insistir.
        $error = t('reportar.error.repetido');

    } else {
        crearReporte((int) $ev['id'], $motivo, $comento !== '' ? $comento : null);
        avisarAdministradores((int) $ev['id']);
        $enviado = true;
    }
}

$titulo = t('reportar.pagina.titulo');
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja">

<?php if ($enviado): ?>

  <h1><?= et('reportar.enviado.h1') ?></h1>
  <p class="sub"><?= et('reportar.enviado.sub') ?></p>

  <div class="aviso aviso-ok">
    <?= et('reportar.enviado.aviso') ?>
  </div>

  <a class="btn-principal" style="display:block; text-align:center; text-decoration:none;"
     href="<?= e(urlEvento($ev)) ?>"><?= et('reportar.enviado.volver') ?></a>

<?php else: ?>

  <a class="volver" href="<?= e(urlEvento($ev)) ?>"><?= et('reportar.volver_actividad') ?></a>

  <h1><?= et('reportar.pagina.titulo') ?></h1>
  <p class="sub">«<?= e($ev['titulo']) ?>»</p>

  <?php if ($error): ?>
    <div class="aviso aviso-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <?= captchaCamposOcultos() ?>

    <div class="campo">
      <label><?= et('reportar.form.pregunta') ?></label>
      <div class="motivos">
        <?php foreach (motivosReporte() as $clave => $etiqueta): ?>
          <label class="motivo">
            <input type="radio" name="motivo" value="<?= e($clave) ?>"
                   <?= $motivo === $clave ? 'checked' : '' ?> required>
            <span><?= e($etiqueta) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="campo">
      <label for="comentario"><?= et('reportar.form.comentario') ?> <span class="opcional"><?= et('campo.opcional') ?></span></label>
      <textarea id="comentario" name="comentario" rows="4" maxlength="1000"
                placeholder="<?= et('reportar.form.comentario_placeholder') ?>"><?= e($comento) ?></textarea>
    </div>

    <?= captchaHtml() ?>

    <button class="btn-principal" type="submit"><?= et('reportar.enviar_btn') ?></button>
  </form>

  <div class="auth-pie">
    <?= et('reportar.pie') ?>
  </div>

<?php endif; ?>

</div>

<?php pie(); ?>
