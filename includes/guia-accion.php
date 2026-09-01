<?php
/**
 * Guía rápida para elegir la acción principal del evento.
 *
 * Compartida entre alta y edición, igual que includes/form-evento.php: es el
 * mismo panel en los dos sitios y solo cambia el formulario que tiene al lado.
 */
declare(strict_types=1);
?>
<aside class="guia-lateral">
  <h2><?= et('evento.guia.titulo') ?></h2>

  <div class="guia-item">
    <h3><?= et('evento.guia.contactar_titulo') ?></h3>
    <p><?= et('evento.guia.contactar_texto') ?></p>
    <div class="ejemplos"><?= et('evento.guia.contactar_ejemplos') ?></div>
  </div>

  <div class="guia-item">
    <h3><?= et('evento.guia.comprar_titulo') ?></h3>
    <p><?= et('evento.guia.comprar_texto') ?></p>
    <div class="ejemplos"><?= et('evento.guia.comprar_ejemplos') ?></div>
  </div>

  <div class="guia-item">
    <h3><?= et('evento.guia.reservar_titulo') ?></h3>
    <p><?= et('evento.guia.reservar_texto') ?></p>
    <div class="ejemplos"><?= et('evento.guia.reservar_ejemplos') ?></div>
  </div>

  <?php /* Aviso de traducción (REQ-00010). Va aquí y no en el campo de
           descripción porque es una decisión que se toma ANTES de escribirla:
           quien se entera de esto con el texto ya redactado tiene que volver a
           empezar. El sitio está en dos idiomas desde REQ-00002, pero lo que
           escribe cada organizador no se traduce solo —y traducirlo por su
           cuenta sería poner palabras en su boca. */ ?>
  <div class="guia-aviso">
    <h3><?= et('evento.guia.traduccion_titulo') ?></h3>
    <p><?= et('evento.guia.traduccion_texto1') ?></p>
    <p><?= et('evento.guia.traduccion_texto2') ?></p>
  </div>

  <div class="guia-nota">
    <?= t('evento.guia.nota') ?>
  </div>
</aside>
