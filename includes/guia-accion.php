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
  <h2>Guía rápida</h2>

  <div class="guia-item">
    <h3>Contactar al organizador</h3>
    <p>Centraliza las solicitudes de tus interesados. Los usuarios completarán un formulario con sus dudas y recibirás la información directamente en tu correo electrónico.</p>
    <div class="ejemplos">Ejemplos: certificaciones, programas, retiros, actividades gratuitas.</div>
  </div>

  <div class="guia-item">
    <h3>Comprar boletos</h3>
    <p>Utilízalo cuando exista un enlace externo donde el participante pueda realizar el pago o la inscripción.</p>
    <div class="ejemplos">Ejemplos: Eventbrite, Boletia, Ticketmaster, sitio web propio, landing page de pago.</div>
  </div>

  <div class="guia-item">
    <h3>Reservar lugar</h3>
    <p>Ideal para actividades con cupo limitado o registro previo, aunque no exista compra directa de boletos.</p>
    <div class="ejemplos">Ejemplos: Google Forms, WhatsApp, Calendly, formulario propio, landing page de registro.</div>
  </div>

  <?php /* Aviso de traducción (REQ-00010). Va aquí y no en el campo de
           descripción porque es una decisión que se toma ANTES de escribirla:
           quien se entera de esto con el texto ya redactado tiene que volver a
           empezar. El sitio está en dos idiomas desde REQ-00002, pero lo que
           escribe cada organizador no se traduce solo —y traducirlo por su
           cuenta sería poner palabras en su boca. */ ?>
  <div class="guia-aviso">
    <h3>Importante sobre la traducción</h3>
    <p>La traducción de OMDARA no modifica ni traduce automáticamente los textos que tú ingreses
       en la descripción de tu actividad. La descripción, instrucciones, condiciones y demás
       contenido personalizado permanecerán tal como los hayas escrito.</p>
    <p>Si tu actividad está dirigida a participantes en español e inglés, considera proporcionar
       la información relevante en ambos idiomas dentro de la descripción.</p>
  </div>

  <div class="guia-nota">
    Con esto en mente, elige la <strong>acción principal</strong> más abajo: decide qué verán quienes quieran dar el siguiente paso.
  </div>
</aside>
