<?php
/**
 * Preguntas frecuentes.
 *
 * Existe porque el pie la enlaza (REQ-00001). El texto está pendiente; las
 * preguntas de abajo son las que ya se contestan en otras partes del sitio
 * —el plazo de 24 horas para corregir, que no hace falta cuenta para
 * escribirle a un organizador, cómo se modera— así que son el punto de
 * partida natural.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$titulo      = t('pagina.faq.titulo');
$descripcion = t('pagina.faq.meta');
$anchoLibre  = true;

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="block-head">
    <div>
      <div class="eyebrow">Ayuda</div>
      <h2 style="margin-top:6px;">Preguntas frecuentes</h2>
    </div>
  </div>

  <?php $avisoPendiente = 'Las respuestas las define quien lleva el producto.'; ?>
  <?php require __DIR__ . '/includes/aviso-pendiente.php'; ?>

  <div style="max-width:760px; font-size:15px; line-height:1.75;">
    <p style="opacity:.75; margin-bottom:22px;">Preguntas que el sitio ya responde en
       otros sitios y que conviene reunir aquí:</p>

    <ul style="opacity:.75; padding-left:20px;">
      <li style="margin-bottom:9px;">¿Cuesta algo publicar una actividad?</li>
      <li style="margin-bottom:9px;">¿Necesito una cuenta para contactar a un organizador?</li>
      <li style="margin-bottom:9px;">Publiqué una actividad con un error, ¿puedo corregirla?</li>
      <li style="margin-bottom:9px;">¿Quién revisa las actividades antes de que se publiquen?</li>
      <li style="margin-bottom:9px;">Vi una actividad sospechosa, ¿cómo la reporto?</li>
      <li style="margin-bottom:9px;">¿Qué tipo de actividades se aceptan?</li>
      <li style="margin-bottom:9px;">¿Cómo se cobra a los asistentes?</li>
    </ul>
  </div>

  <div style="margin-top:34px;">
    <p style="font-size:14px; opacity:.75;">¿No está tu pregunta?
      <a href="<?= URL_BASE ?>/contacto" style="text-decoration:underline;">Escríbenos</a>.</p>
  </div>
</section>

<?php pie(); ?>
