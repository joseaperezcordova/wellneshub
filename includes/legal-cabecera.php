<?php
/**
 * La cabecera común de las tres páginas legales.
 *
 * Existe por un criterio de REQ-00014 —«el diseño es consistente con el resto
 * de las páginas legales»— y porque era verdad que no lo era: cada una traía su
 * propio bloque de título copiado, con sus márgenes escritos a mano, y la
 * tercera que se tocara habría acabado distinta de las dos primeras.
 *
 * LA FECHA VA AQUÍ, DEBAJO DEL TÍTULO
 *
 * Estuvo al pie hasta REQ-00016, y la movió el propio texto aprobado: la
 * cláusula 8 de la Política de Cookies dice que las modificaciones se reflejan
 * en «la fecha de "Última actualización" indicada al inicio de esta política».
 * Con la fecha al final, el documento mentía sobre sí mismo. Los otros dos
 * textos aprobados también la traen arriba.
 *
 * Se escribe a mano en cada página, y a propósito: no es la fecha en que se
 * tocó el archivo —cambiar una coma o un estilo no actualiza un documento
 * legal— sino aquella en que su texto quedó como está. Solo la sabe quien lo
 * redacta.
 *
 * Y no es adorno: un documento sin fecha no se puede comparar con el que
 * alguien aceptó. La fecha de aceptación sí se guarda —usuarios.acepto_legal_en,
 * migración 16—, así que esta es la otra mitad de ese par.
 *
 * Espera definidas:
 *   $legalTitulo      (string) — el nombre del documento.
 *   $legalActualizado (string) — la fecha, tal como se quiere leer.
 */

declare(strict_types=1);
?>
<div class="legal-cab">
  <div class="eyebrow">Legal</div>
  <h1><?= e($legalTitulo ?? '') ?></h1>
  <?php if (($legalActualizado ?? '') !== ''): ?>
    <p class="legal-fecha">Última actualización: <?= e($legalActualizado) ?></p>
  <?php endif; ?>
</div>
