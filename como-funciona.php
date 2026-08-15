<?php
/**
 * Cómo funciona OMDARA.
 *
 * Existe porque el pie la enlaza (REQ-00001) y un enlace que da 404 es peor
 * que no tenerlo. El texto está pendiente; el esqueleto de abajo marca qué
 * secciones va a llevar para que quien lo escriba no empiece de cero.
 *
 * Habla a los dos públicos del directorio —quien busca una actividad y quien
 * la publica— porque desde el pie se llega igual desde los dos lados.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$titulo      = t('pagina.como_funciona.titulo');
$descripcion = t('pagina.como_funciona.meta');
$anchoLibre  = true;

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="block-head">
    <div>
      <div class="eyebrow">Guía</div>
      <h2 style="margin-top:6px;">Cómo funciona</h2>
    </div>
  </div>

  <?php $avisoPendiente = 'El texto lo define quien lleva el producto.'; ?>
  <?php require __DIR__ . '/includes/aviso-pendiente.php'; ?>

  <div style="max-width:760px; font-size:15px; line-height:1.75;">
    <h3 style="margin-top:26px;">Si buscas una actividad</h3>
    <p style="opacity:.75;">Buscar por ciudad, categoría y fecha; qué información trae cada
       ficha; cómo contactar al organizador.</p>

    <h3 style="margin-top:26px;">Si organizas actividades</h3>
    <p style="opacity:.75;">Cómo publicar, qué se pide en el formulario, qué pasa después de
       enviarla y durante cuánto tiempo se puede corregir.</p>

    <h3 style="margin-top:26px;">Qué actividades entran</h3>
    <p style="opacity:.75;">Qué se considera bienestar aquí y qué no, y cómo se revisa lo que
       se publica.</p>
  </div>

  <div style="margin-top:34px; display:flex; gap:12px; flex-wrap:wrap;">
    <a class="btn-principal" style="text-decoration:none; display:inline-block; max-width:none;"
       href="<?= URL_BASE ?>/actividades">Ver actividades</a>
    <a class="btn-barra" href="<?= URL_BASE ?>/publicar-actividad">Publicar una actividad</a>
  </div>
</section>

<?php pie(); ?>
