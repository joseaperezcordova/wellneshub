<?php
/**
 * El pie de una página legal: cuándo se actualizó por última vez.
 *
 * Lo pide REQ-00014 y no es adorno: unas condiciones sin fecha no se pueden
 * comparar con las que alguien aceptó. La fecha de aceptación sí se guarda
 * —usuarios.acepto_legal_en, migración 16—, así que esta es la otra mitad de
 * ese par.
 *
 * Espera definida:
 *   $legalActualizado (string) — la fecha, tal como se quiere leer.
 *
 * Se escribe a mano en cada página, y a propósito: no es la fecha en que se
 * tocó el archivo —cambiar una coma o un estilo no actualiza un documento
 * legal— sino aquella en que su texto quedó como está. Solo la sabe quien lo
 * redacta.
 */

declare(strict_types=1);
?>
<div class="legal-pie">
  Última actualización: <?= e($legalActualizado ?? '') ?>
</div>
