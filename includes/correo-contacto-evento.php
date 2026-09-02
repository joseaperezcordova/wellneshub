<?php
/**
 * El correo de contacto de UNA actividad (migración 24, requerimiento del
 * cliente 2026-09-02): fuera del formulario grande a propósito, en su propio
 * <form> — un <form> no puede ir dentro de otro <form>, y evento-editar.php
 * ya abre uno para el resto de campos.
 *
 * Dos pantallas, según haya o no un código pendiente:
 *   · sin pendiente: el correo que usa ahora (el de la cuenta, o el que ya
 *     confirmó) + un campo para escribir uno nuevo y pedirle un código.
 *   · con pendiente: "te mandamos un código a X" + el campo para escribirlo,
 *     y un botón para cancelar sin esperar a que caduque.
 *
 * Espera definidos: $ev, $correoPendiente, $correoEfectivo, $tieneOverride,
 * $avisoCorreoContacto, $errorCorreoContacto (evento-editar.php).
 */

declare(strict_types=1);
?>

<div class="auth-caja caja-ancha" style="margin-top:18px;">
  <h2 style="font-size:19px;"><?= et('evento.correo_contacto.titulo') ?></h2>
  <p class="sub" style="margin-top:4px;"><?= et('evento.correo_contacto.explicacion') ?></p>

  <?php if ($avisoCorreoContacto !== ''): ?>
    <div class="aviso aviso-ok" style="margin-top:14px;"><?= e($avisoCorreoContacto) ?></div>
  <?php endif; ?>
  <?php if ($errorCorreoContacto !== ''): ?>
    <div class="aviso aviso-error" style="margin-top:14px;"><?= e($errorCorreoContacto) ?></div>
  <?php endif; ?>

  <?php if ($correoPendiente !== null): ?>

    <form method="post" style="margin-top:14px;">
      <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
      <p style="font-size:14.5px; line-height:1.6;">
        <?= e(sprintf(t('evento.correo_contacto.pendiente_texto'), $correoPendiente)) ?>
      </p>
      <div class="campo">
        <label for="codigo_correo_contacto"><?= et('evento.correo_contacto.codigo_label') ?></label>
        <input id="codigo_correo_contacto" name="codigo_correo_contacto" type="text" inputmode="numeric"
               autocomplete="one-time-code" maxlength="6"
               placeholder="<?= et('evento.correo_contacto.codigo_placeholder') ?>">
      </div>
      <div class="barra-acciones" style="margin-top:10px;">
        <button class="btn-barra destacado" type="submit" name="confirmar_codigo_correo" value="1">
          <?= et('evento.correo_contacto.confirmar_btn') ?>
        </button>
        <button class="btn-barra" type="submit" name="cancelar_codigo_correo" value="1">
          <?= et('evento.correo_contacto.cancelar_btn') ?>
        </button>
      </div>
    </form>

  <?php else: ?>

    <p style="font-size:14.5px; line-height:1.6; margin-top:14px;">
      <?= et($tieneOverride ? 'evento.correo_contacto.actual_propio' : 'evento.correo_contacto.actual_cuenta') ?>
      <strong><?= e($correoEfectivo) ?></strong>
    </p>

    <?php if ($tieneOverride): ?>
      <form method="post" style="margin-top:6px;">
        <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
        <button class="actionbtn" type="submit" name="quitar_codigo_correo" value="1">
          <?= et('evento.correo_contacto.quitar_btn') ?>
        </button>
      </form>
    <?php endif; ?>

    <form method="post" style="margin-top:14px;">
      <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
      <div class="campo">
        <label for="correo_contacto_nuevo"><?= et('evento.correo_contacto.nuevo_label') ?></label>
        <input id="correo_contacto_nuevo" name="correo_contacto_nuevo" type="email" maxlength="190"
               placeholder="<?= et('evento.correo_contacto.nuevo_placeholder') ?>">
      </div>
      <div class="barra-acciones" style="margin-top:10px;">
        <button class="btn-barra" type="submit" name="enviar_codigo_correo" value="1">
          <?= et('evento.correo_contacto.enviar_btn') ?>
        </button>
      </div>
    </form>

  <?php endif; ?>
</div>
