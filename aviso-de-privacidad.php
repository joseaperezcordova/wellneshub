<?php
/**
 * Aviso de Privacidad.
 *
 * Existe porque el pie la enlaza (REQ-00001), pero aquí hay algo más que un
 * enlace roto: en México el aviso de privacidad no es cortesía. La LFPDPPP lo
 * exige a quien trate datos personales, y este sitio los trata desde el primer
 * día —correos, identificadores de Google, direcciones IP—.
 *
 * El texto lo redacta quien asesore legalmente. Lo que sí sale del código, y
 * es lo que suele faltar cuando se copia un aviso de otra web, es el
 * inventario real de qué se guarda y por qué. Va abajo.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$titulo      = 'Aviso de Privacidad';
$descripcion = 'Qué datos personales trata OMDARA, con qué finalidad y durante cuánto tiempo.';
$anchoLibre  = true;

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="block-head">
    <div>
      <div class="eyebrow">Legal</div>
      <h2 style="margin-top:6px;">Aviso de Privacidad</h2>
    </div>
  </div>

  <?php $avisoPendiente = 'En México lo exige la LFPDPPP a quien trate datos personales, y este sitio los trata. Debe redactarlo o revisarlo quien asesore legalmente a OMDARA, e incluir al responsable, sus datos de contacto y cómo se ejercen los derechos ARCO.'; ?>
  <?php require __DIR__ . '/includes/aviso-pendiente.php'; ?>

  <div style="max-width:760px; font-size:15px; line-height:1.75;">
    <p style="opacity:.75;">Inventario de lo que el sitio guarda hoy. Es el punto de partida
       del aviso, y lo que suele quedar mal cuando se copia de otra web:</p>

    <ul style="opacity:.75; padding-left:20px; margin-top:16px;">
      <li style="margin-bottom:9px;"><strong>Nombre y correo</strong> de quien se registra,
          para poder entrar y para avisar al organizador de sus mensajes.</li>
      <li style="margin-bottom:9px;"><strong>Identificador de Google</strong> de quien entra
          con esa cuenta. Se guarda el «sub», no el correo, porque el «sub» no cambia.</li>
      <li style="margin-bottom:9px;"><strong>Códigos de acceso</strong> enviados por correo.
          Se guardan cifrados y caducan solos.</li>
      <li style="margin-bottom:9px;"><strong>Dirección IP</strong> de quien reporta una
          actividad o escribe a un organizador, para limitar envíos repetidos.</li>
      <li style="margin-bottom:9px;"><strong>Nombre, correo y mensaje</strong> de quien usa
          los formularios de contacto.</li>
      <li style="margin-bottom:9px;"><strong>Datos de la actividad</strong> que publica un
          organizador, que son públicos por definición.</li>
    </ul>

    <p style="opacity:.75; margin-top:20px;">Y servicios de terceros que reciben datos de
       navegación cuando están configurados: Google Analytics, Microsoft Clarity, Meta Pixel,
       más OpenStreetMap para los mapas. El aviso tiene que nombrarlos.</p>
  </div>
</section>

<?php pie(); ?>
