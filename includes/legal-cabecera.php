<?php
/**
 * La cabecera común de las tres páginas legales.
 *
 * Existe por un criterio de REQ-00014 —«el diseño es consistente con el resto
 * de las páginas legales»— y porque era verdad que no lo era: cada una traía su
 * propio bloque de título copiado, con sus márgenes escritos a mano, y la
 * tercera que se tocara habría acabado distinta de las dos primeras.
 *
 * Espera definida:
 *   $legalTitulo (string) — el nombre del documento.
 */

declare(strict_types=1);
?>
<div class="legal-cab">
  <div class="eyebrow">Legal</div>
  <h1><?= e($legalTitulo ?? '') ?></h1>
</div>
