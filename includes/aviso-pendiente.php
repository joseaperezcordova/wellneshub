<?php
/**
 * El aviso de «esta página existe pero su texto todavía no».
 *
 * Está en un parcial y no copiado en cada página por lo de siempre: cinco
 * copias del mismo aviso acaban diciendo cinco cosas distintas, y la que se
 * queda vieja es justo la que nadie vuelve a mirar. El día que se rellene una
 * página, se borra su include y ya está.
 *
 * Espera definida: $avisoPendiente (string) — de dónde tiene que salir el
 * texto que falta. No es lo mismo «lo escribe marketing» que «lo tiene que
 * revisar un abogado», y quien abra la página merece saber cuál de las dos es.
 */

declare(strict_types=1);
?>
<div class="aviso aviso-info" style="max-width:760px; margin-bottom:26px;">
  <strong>Contenido pendiente.</strong>
  Esta página ya está publicada y enlazada desde el pie, pero su texto todavía
  no está escrito. <?= e($avisoPendiente ?? '') ?>
</div>
