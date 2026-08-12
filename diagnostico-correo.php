<?php
/**
 * Diagnóstico del correo saliente.
 *
 * Existe porque este hosting no tiene SSH: sin consola no hay forma de mirar la
 * configuración de PHP ni de probar un envío a mano, y sin eso "no llega el
 * correo" se convierte en adivinar a ciegas.
 *
 * Solo para administradores. Enseña detalles del servidor —rutas, versiones,
 * registros DNS— que no tienen por qué ser públicos, y manda correo, que es
 * justo lo que no se le regala a un desconocido.
 *
 * Cuando el correo funcione, este archivo se puede borrar. No hace falta para
 * nada más.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$u = exigirSesion();

if ($u['rol'] !== 'admin') {
    http_response_code(403);
    exit('Solo para administradores.');
}

[$remitente, $nombreRemitente] = correoRemitente();
$dominioEnvio = substr((string) strrchr($remitente, '@'), 1);

$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValido($_POST['csrf'] ?? null)) {
    $destino = $u['email'];

    // Se manda solo a la dirección de quien está mirando la página. Un campo
    // libre aquí convierte esto en un formulario para mandar correo a
    // cualquiera desde nuestro dominio.
    $ok = enviarCorreo(
        $destino,
        'Prueba de envío de OMDARA',
        "Si lees esto, el correo saliente funciona.\n\nEnviado el " . date('d/m/Y H:i:s') . "."
    );

    $resultado = [$ok, $destino];
}

/** Un registro DNS, o null si no se puede consultar. */
function dnsSeguro(string $dominio, int $tipo): ?array
{
    if (!function_exists('dns_get_record')) return null;

    $r = @dns_get_record($dominio, $tipo);
    return is_array($r) ? $r : null;
}

$titulo = 'Diagnóstico de correo';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja" style="max-width:680px;">
  <h1>Diagnóstico de correo</h1>
  <p class="sub">Qué ve PHP y qué dice el DNS del dominio desde el que mandamos.</p>

  <?php if ($resultado !== null): ?>
    <div class="aviso <?= $resultado[0] ? 'aviso-ok' : 'aviso-error' ?>">
      <?php if ($resultado[0]): ?>
        El servidor de correo <strong>aceptó</strong> el mensaje para <?= e($resultado[1]) ?>.
        Eso no garantiza que llegue: mira la bandeja, el spam, y «Rastrear entrega» en cPanel.
      <?php else: ?>
        <strong>mail() devolvió false</strong> — el servidor ni siquiera aceptó el mensaje.
        El problema está en el servidor, no en la entrega. Mira la tabla de abajo.
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <h2 style="font-size:16px; margin:22px 0 10px;">PHP</h2>
  <table style="width:100%; border-collapse:collapse; font-size:13.5px;">
    <tr><td style="padding:7px 0; opacity:.8;">Versión</td>
        <td><?= e(PHP_VERSION) ?></td></tr>

    <tr><td style="padding:7px 0; opacity:.8;">¿Existe mail()?</td>
        <td><?= function_exists('mail') ? 'Sí' : '<strong>NO — está desactivada</strong>' ?></td></tr>

    <tr><td style="padding:7px 0; opacity:.8;">disable_functions</td>
        <td><?= e(ini_get('disable_functions') ?: '(ninguna)') ?></td></tr>

    <tr><td style="padding:7px 0; opacity:.8;">sendmail_path</td>
        <td><?= e(ini_get('sendmail_path') ?: '(vacío)') ?></td></tr>
  </table>

  <h2 style="font-size:16px; margin:22px 0 10px;">Remitente</h2>
  <table style="width:100%; border-collapse:collapse; font-size:13.5px;">
    <tr><td style="padding:7px 0; opacity:.8;">Dirección</td>
        <td><?= e($remitente) ?></td></tr>
    <tr><td style="padding:7px 0; opacity:.8;">Nombre</td>
        <td><?= e($nombreRemitente) ?></td></tr>
    <tr><td style="padding:7px 0; opacity:.8;">Dominio que se comprueba</td>
        <td><strong><?= e($dominioEnvio) ?></strong></td></tr>
  </table>

  <h2 style="font-size:16px; margin:22px 0 10px;">DNS de <?= e($dominioEnvio) ?></h2>
  <?php
  $txt = dnsSeguro($dominioEnvio, DNS_TXT);
  $mx  = dnsSeguro($dominioEnvio, DNS_MX);

  if ($txt === null):
  ?>
    <p style="font-size:13.5px; opacity:.8;">No se puede consultar el DNS desde aquí
      (<code>dns_get_record</code> no está disponible). Compruébalo en cPanel → Editor de zona.</p>
  <?php else:
      $spf = '';
      foreach ($txt as $r) {
          if (isset($r['txt']) && stripos($r['txt'], 'v=spf1') === 0) $spf = $r['txt'];
      }
  ?>
    <table style="width:100%; border-collapse:collapse; font-size:13.5px;">
      <tr><td style="padding:7px 0; opacity:.8; width:120px;">SPF</td>
          <td><?= $spf !== ''
                ? '<code>' . e($spf) . '</code>'
                : '<strong>No hay registro SPF en este dominio.</strong>' ?></td></tr>
      <tr><td style="padding:7px 0; opacity:.8;">MX</td>
          <td><?= $mx ? e(implode(', ', array_column($mx, 'target'))) : '<strong>(ninguno)</strong>' ?></td></tr>
    </table>

    <?php if (!$mx): ?>
      <div class="aviso aviso-error" style="margin-top:16px;">
        <strong>Sin MX no se puede enviar desde este dominio.</strong>
        Los filtros de salida hacen <em>callout verification</em>: antes de aceptar el mensaje
        se conectan al servidor de correo del remitente para comprobar que la dirección existe.
        Sin registro MX no hay a quién preguntar, y el correo se rechaza con
        <code>550 Invalid sender</code> sin llegar a salir.
        <br><br>
        Manda desde un dominio que sí tenga MX y con el buzón creado en cPanel → Email Accounts.
        Y ten en cuenta que el filtro <strong>cachea el fallo</strong>: una dirección que ya
        rebotó sigue rechazándose un buen rato aunque crees el buzón después.
      </div>
    <?php endif; ?>

    <?php if ($spf === ''): ?>
      <div class="aviso aviso-error" style="margin-top:16px;">
        <strong>Aquí está el problema, probablemente.</strong>
        SPF no se hereda del dominio padre: se busca en el dominio exacto del remitente.
        Aunque <code>jpcorelab.com</code> tenga SPF, <code><?= e($dominioEnvio) ?></code> no lo usa.
        Gmail ve un correo sin autorizar y lo manda a spam o lo rechaza.
        <br><br>
        Lo más rápido: manda desde el dominio principal, poniendo
        <code>'remitente' =&gt; 'no-responder@jpcorelab.com'</code> en
        <code>includes/config.local.php</code>.
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <form method="post" style="margin-top:26px;">
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <button class="btn-principal" type="submit">Enviarme un correo de prueba a <?= e($u['email']) ?></button>
  </form>
</div>

<?php pie(); ?>
