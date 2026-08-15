<?php
/**
 * Política de Cookies.
 *
 * Existe porque el pie la enlaza (REQ-00001). El texto lo redacta quien
 * asesore legalmente; lo que aporta el código es qué cookies pone el sitio de
 * verdad, que es lo único que una plantilla genérica no puede saber.
 *
 * Un detalle que conviene no perder de vista: hoy los scripts de analítica se
 * cargan sin pedir consentimiento. Mientras el sitio solo mire a México eso es
 * defendible, pero en cuanto haya visitantes de la UE hace falta un banner que
 * los bloquee hasta que alguien acepte. No es trabajo de esta página, pero es
 * aquí donde se descubre.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$titulo      = t('pagina.cookies.titulo');
$descripcion = t('pagina.cookies.meta');
$anchoLibre  = true;

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="block-head">
    <div>
      <div class="eyebrow">Legal</div>
      <h2 style="margin-top:6px;">Política de Cookies</h2>
    </div>
  </div>

  <?php $avisoPendiente = 'Debe redactarlo o revisarlo quien asesore legalmente a OMDARA.'; ?>
  <?php require __DIR__ . '/includes/aviso-pendiente.php'; ?>

  <div style="max-width:760px; font-size:15px; line-height:1.75;">
    <p style="opacity:.75;">Lo que el sitio pone hoy en el navegador:</p>

    <ul style="opacity:.75; padding-left:20px; margin-top:16px;">
      <li style="margin-bottom:9px;"><strong>wh_sesion</strong> — necesaria. Mantiene la
          sesión iniciada y protege los formularios. Sin ella no se puede entrar. Se borra al
          cerrar el navegador.</li>
      <li style="margin-bottom:9px;"><strong>Analítica</strong>, solo si están configuradas
          sus claves: Google Analytics, Microsoft Clarity y Meta Pixel ponen las suyas
          propias.</li>
    </ul>

    <p style="opacity:.75; margin-top:20px;">La política tiene que decir además cómo
       desactivarlas desde el navegador, y qué deja de funcionar al hacerlo — en el caso de
       la cookie de sesión, entrar a la cuenta.</p>
  </div>
</section>

<?php pie(); ?>
