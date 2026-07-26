<?php
/**
 * El recuadro de errores que va sobre el formulario del evento.
 *
 * Antes decía «Revisa los campos marcados» y no marcaba ninguno: había que
 * repasar el formulario entero buscando una línea de texto pequeña, del mismo
 * tamaño y color que las pistas de ayuda de al lado. En un formulario de doce
 * campos eso es una búsqueda a ciegas.
 *
 * Ahora nombra los campos y enlaza a cada uno. El enlace importa más de lo que
 * parece en móvil, donde el campo que falla puede estar tres pantallas más
 * abajo.
 *
 * Espera definido: $errores.
 */

declare(strict_types=1);

if (!empty($errores['general'])): ?>
  <div class="aviso aviso-error"><?= e($errores['general']) ?></div>

<?php elseif ($errores):
    $etiquetas = etiquetasCampos();
    $fallidos  = [];

    foreach ($errores as $campo => $_) {
        if (isset($etiquetas[$campo])) {
            $fallidos[$campo] = $etiquetas[$campo];
        }
    }
?>
  <div class="aviso aviso-error">
    <?php if (count($fallidos) === 1): ?>
      Falta revisar <strong><a href="#<?= e(array_key_first($fallidos)) ?>"><?= e(reset($fallidos)) ?></a></strong>.
    <?php else: ?>
      Faltan por revisar
      <?php $i = 0; foreach ($fallidos as $campo => $etiqueta): $i++; ?>
        <?php if ($i > 1): ?><?= $i === count($fallidos) ? ' y ' : ', ' ?><?php endif; ?>
        <strong><a href="#<?= e($campo) ?>"><?= e($etiqueta) ?></a></strong>
      <?php endforeach; ?>.
    <?php endif; ?>
  </div>
<?php endif; ?>
