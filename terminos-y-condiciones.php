<?php
/**
 * Términos y Condiciones.
 *
 * Existe porque el pie la enlaza (REQ-00001). El texto NO se redacta desde
 * aquí: son las condiciones bajo las que alguien publica una actividad y otro
 * la contrata, y de eso responde OMDARA. Un texto copiado de otra web puede
 * describir un servicio que no es este —y prometer cosas que este no hace—.
 *
 * Lo que sí puede aportar el código es la lista de abajo: son las reglas que
 * el sitio ya aplica de verdad, y los términos tienen que decir lo mismo que
 * hace el software.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$titulo      = 'Términos y Condiciones';
$descripcion = 'Condiciones de uso de OMDARA para visitantes y organizadores.';
$anchoLibre  = true;

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="block-head">
    <div>
      <div class="eyebrow">Legal</div>
      <h2 style="margin-top:6px;">Términos y Condiciones</h2>
    </div>
  </div>

  <?php $avisoPendiente = 'El texto legal debe redactarlo o revisarlo quien asesore legalmente a OMDARA: obliga a la empresa frente a organizadores y asistentes, y no puede copiarse de otro sitio.'; ?>
  <?php require __DIR__ . '/includes/aviso-pendiente.php'; ?>

  <div style="max-width:760px; font-size:15px; line-height:1.75;">
    <p style="opacity:.75;">Reglas que el sitio <strong>ya aplica</strong> hoy, para que el
       texto legal no diga algo distinto de lo que hace el software:</p>

    <ul style="opacity:.75; padding-left:20px; margin-top:16px;">
      <li style="margin-bottom:9px;">Las actividades se publican solas; no hay revisión previa.
          Lo que se revisa es lo que alguien reporta.</li>
      <li style="margin-bottom:9px;">El organizador puede corregir su actividad durante las 24
          horas siguientes a publicarla. Después, solo un administrador.</li>
      <li style="margin-bottom:9px;">Un administrador puede ocultar una actividad, y el
          organizador no puede volver a publicarla por su cuenta.</li>
      <li style="margin-bottom:9px;">OMDARA no cobra ni gestiona pagos: el cobro y las
          cancelaciones son entre el organizador y el asistente.</li>
      <li style="margin-bottom:9px;">Los enlaces de compra y reserva llevan a sitios de
          terceros que OMDARA no controla.</li>
      <li style="margin-bottom:9px;">Borrar una actividad solo es posible mientras se pueda
          editar, para que borrar no sea la puerta de atrás del plazo de 24 horas.</li>
    </ul>
  </div>
</section>

<?php pie(); ?>
