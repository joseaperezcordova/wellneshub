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
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/eventos.php';

$ev = buscarEvento((int) ($_GET['id'] ?? 0));

// Solo se reporta lo que está publicado. Un borrador no lo ve nadie más que su
// dueño, así que no hay nada que denunciar.
if (!$ev || $ev['situacion'] !== 'publicado') {
    http_response_code(404);
    $titulo = 'Actividad no encontrada';
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>Esa actividad no existe</h1>'
       . '<p class="sub">Puede que ya se haya retirado.</p></div>';
    pie();
    exit;
}

$error   = '';
$enviado = false;
$motivo  = (string) ($_POST['motivo'] ?? '');
$comento = (string) ($_POST['comentario'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // El captcha se comprueba dentro de la cadena y no antes: verificarlo
    // implica una petición a Cloudflare, y no hay razón para gastarla en un
    // envío que ya se cayó por el token.
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'El formulario caducó. Vuelve a cargarlo.';

    } elseif (!($captcha = captchaValido($_POST))[0]) {
        $error = $captcha[1];

    } elseif (!isset(motivosReporte()[$motivo])) {
        $error = 'Ayúdanos a mantener una comunidad segura. Selecciona el motivo por el que deseas reportar esta actividad.';

    } elseif (reporteRepetido((int) $ev['id'])) {
        // Se dice claramente en vez de fingir que se aceptó. Quien ya reportó de
        // buena fe merece saber que su aviso llegó y que no hace falta insistir.
        $error = 'Ya reportaste esta actividad. La estamos revisando.';

    } else {
        crearReporte((int) $ev['id'], $motivo, $comento !== '' ? $comento : null);
        avisarAdministradores((int) $ev['id']);
        $enviado = true;
    }
}

$titulo = 'Reportar actividad';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja">

<?php if ($enviado): ?>

  <h1>Gracias por tu reporte</h1>
  <p class="sub">Lo revisaremos lo antes posible.</p>

  <div class="aviso aviso-ok">
    La actividad sigue publicada mientras tanto. No la retiramos por un aviso
    automático: lo revisa una persona.
  </div>

  <a class="btn-principal" style="display:block; text-align:center; text-decoration:none;"
     href="<?= e(urlEvento($ev)) ?>">Volver a la actividad</a>

<?php else: ?>

  <a class="volver" href="<?= e(urlEvento($ev)) ?>">← Volver a la actividad</a>

  <h1>Reportar actividad</h1>
  <p class="sub">«<?= e($ev['titulo']) ?>»</p>

  <?php if ($error): ?>
    <div class="aviso aviso-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <?= captchaCamposOcultos() ?>

    <div class="campo">
      <label>¿Qué le pasa a esta actividad?</label>
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
      <label for="comentario">Cuéntanos más <span class="opcional">opcional</span></label>
      <textarea id="comentario" name="comentario" rows="4" maxlength="1000"
                placeholder="Qué viste exactamente. Cuanto más concreto, más rápido se resuelve."><?= e($comento) ?></textarea>
    </div>

    <?= captchaHtml() ?>

    <button class="btn-principal" type="submit">Enviar reporte</button>
  </form>

  <div class="auth-pie">
    Tu aviso no oculta la actividad. Solo la pone en la lista de lo que hay que revisar.
  </div>

<?php endif; ?>

</div>

<?php pie(); ?>
