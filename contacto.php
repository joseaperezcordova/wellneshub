<?php
/**
 * Contacto general del sitio. Abierto a cualquiera, sin cuenta.
 *
 * Distinto de contactar.php: ese es "contactar al organizador de ESTA
 * actividad"; este es para quien escribe sin tener ninguna actividad en mente
 * —una alianza, una duda general, un problema con el sitio—.
 *
 * LOS ERRORES VAN JUNTO A SU CAMPO
 *
 * Antes había un solo aviso arriba y la validación se cortaba en el primer
 * fallo: quien mandaba el formulario con tres cosas mal lo enviaba tres veces,
 * enterándose de una cada vez. Ahora se comprueban todos y cada mensaje sale
 * pegado a su campo, con el mismo patrón que el formulario de publicar.
 *
 * VALIDAR EN LOS DOS LADOS, Y QUE MANDE EL SERVIDOR
 *
 * El navegador valida para avisar antes de gastar un viaje; el servidor valida
 * porque es el único que no se puede saltar. Quien desactive JavaScript, quien
 * mande el POST a mano o quien use un navegador que ignore «required» pasa
 * exactamente por las mismas comprobaciones.
 *
 * ANTI-SPAM: TRES CAPAS QUE YA ESTABAN
 *
 *   · captchaValido() — Turnstile o reCAPTCHA si hay claves, y por debajo el
 *     campo trampa y el reloj, que no dependen de nadie.
 *   · contactoSitioRepetido() — límite por IP, quince minutos entre mensajes.
 *   · csrfValido() — que el envío venga de este formulario y no de otro sitio.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

/** Lo que se considera un mensaje y no un tanteo. */
const CONTACTO_SITIO_MIN = 10;
const CONTACTO_SITIO_MAX = 1000;

$errores   = [];
$enviado   = false;
$nombre    = (string) ($_POST['nombre'] ?? '');
$email     = (string) ($_POST['email'] ?? '');
$motivo    = (string) ($_POST['motivo'] ?? '');
$actividad = (string) ($_POST['actividad'] ?? '');
$mensaje   = (string) ($_POST['mensaje'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /*
     * El token y el captcha se comprueban aparte y cortan: no son errores de
     * un campo, son motivos por los que este envío no cuenta. Y verificar el
     * captcha es una petición a Cloudflare, así que no se gasta en un formulario
     * que además viene incompleto.
     */
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $errores['general'] = 'El formulario caducó. Vuelve a cargarlo.';

    } elseif (!($captcha = captchaValido($_POST))[0]) {
        $errores['general'] = $captcha[1];

    } else {
        if (trim($nombre) === '') {
            $errores['nombre'] = 'Este campo es obligatorio.';
        }

        if (trim($email) === '') {
            $errores['email'] = 'Este campo es obligatorio.';
        } elseif (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'Introduce un correo electrónico válido.';
        }

        // No basta con que venga algo: tiene que ser uno de los seis. Un valor
        // inventado no debe acabar guardado como si fuera un motivo real.
        if (!isset(motivosContacto()[$motivo])) {
            $errores['motivo'] = 'Este campo es obligatorio.';
        }

        // Solo cuando el motivo lo pide. Si no, lo que venga se ignora: el campo
        // ni siquiera se ve, así que un valor ahí es basura o un intento.
        if (motivoPideActividad($motivo) && trim($actividad) === '') {
            $errores['actividad'] = 'Este campo es obligatorio.';
        }

        if (trim($mensaje) === '') {
            $errores['mensaje'] = 'Este campo es obligatorio.';
        } elseif (mb_strlen(trim($mensaje)) < CONTACTO_SITIO_MIN) {
            $errores['mensaje'] = 'Cuéntanos un poco más: con ' . CONTACTO_SITIO_MIN
                . ' caracteres no hay mucho a lo que responder.';
        }

        // El límite por IP se mira al final: es lo más caro de los cinco y no
        // tiene sentido preguntarlo por un formulario que ya se cayó.
        if (!$errores && contactoSitioRepetido()) {
            $errores['general'] = 'Ya nos escribiste hace un momento. Danos tiempo para responder antes de volver a escribir.';
        }

        if (!$errores) {
            $nombre    = trim($nombre);
            $email     = trim($email);
            $mensaje   = mb_substr(trim($mensaje), 0, CONTACTO_SITIO_MAX);
            $actividad = motivoPideActividad($motivo) ? trim($actividad) : null;

            crearContactoSitio($nombre, $email, $mensaje, $motivo, $actividad);
            avisarAdminsContactoSitio($nombre, $email, $mensaje, $motivo, $actividad);
            $enviado = true;
        }
    }
}

$titulo      = 'Contacta a OMDARA';
$descripcion = 'Escríbenos: dudas, ayuda, problemas con una actividad o cualquier cosa que necesites consultarnos.';
require __DIR__ . '/includes/layout.php';

/** El mensaje de error de un campo, si lo hay. Mismo patrón que form-evento.php. */
$err = function (string $campo) use ($errores): string {
    return isset($errores[$campo])
        ? '<div class="campo-error">' . e($errores[$campo]) . '</div>'
        : '';
};

/** La clase del contenedor cuando el campo falló, para que se vea marcado. */
$mal = function (string $campo) use ($errores): string {
    return isset($errores[$campo]) ? ' con-error' : '';
};
?>

<div class="auth-caja">

<?php if ($enviado): ?>

  <script>whTrack('mensaje_contacto', <?= json_encode(['motivo' => $motivo]) ?>);</script>

  <h1>Hemos recibido tu mensaje.</h1>
  <p class="sub">Gracias por contactarnos. Te responderemos a la brevedad.</p>

  <div class="aviso aviso-ok">
    Te responderemos al correo que nos dejaste. Si no tienes noticias en unos días,
    revisa tu carpeta de spam.
  </div>

  <a class="btn-principal" style="display:block; text-align:center; text-decoration:none;"
     href="<?= URL_BASE ?>/">Volver al inicio</a>

<?php else: ?>

  <h1>Contacta a OMDARA</h1>
  <p class="sub">¿Tienes alguna pregunta, necesitas ayuda o quieres reportar un problema?
     Completa el formulario y nos pondremos en contacto contigo.</p>

  <?php if (isset($errores['general'])): ?>
    <div class="aviso aviso-error"><?= e($errores['general']) ?></div>
  <?php elseif ($errores): ?>
    <div class="aviso aviso-error">Revisa los campos marcados.</div>
  <?php endif; ?>

  <form method="post" novalidate id="formContacto">
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <?= captchaCamposOcultos() ?>

    <div class="campo<?= $mal('nombre') ?>">
      <label for="nombre">Nombre <span class="obligatorio">*</span></label>
      <input id="nombre" name="nombre" type="text" maxlength="120" autocomplete="name"
             placeholder="Tu nombre" value="<?= e($nombre) ?>" required>
      <?= $err('nombre') ?>
    </div>

    <div class="campo<?= $mal('email') ?>">
      <label for="email">Correo electrónico <span class="obligatorio">*</span></label>
      <input id="email" name="email" type="email" maxlength="190" autocomplete="email"
             placeholder="tu@email.com" value="<?= e($email) ?>" required>
      <div class="pista">Aquí te vamos a responder.</div>
      <?= $err('email') ?>
    </div>

    <div class="campo<?= $mal('motivo') ?>">
      <label for="motivo">Motivo del contacto <span class="obligatorio">*</span></label>
      <select id="motivo" name="motivo" required>
        <option value="">Elige uno…</option>
        <?php foreach (motivosContacto() as $clave => $etiqueta): ?>
          <option value="<?= e($clave) ?>"
                  data-actividad="<?= motivoPideActividad($clave) ? '1' : '0' ?>"
                  <?= $motivo === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $err('motivo') ?>
    </div>

    <?php
    /*
     * Solo sale con los dos motivos que hablan de una actividad concreta.
     *
     * Se pinta SIEMPRE y lo esconde el script de abajo, no al revés: sin
     * JavaScript el campo tiene que poder verse y rellenarse, o esos dos
     * motivos se volverían imposibles de enviar —el servidor los exige—.
     *
     * El "hidden" inicial lo pone PHP solo cuando ya sabe que no toca, para
     * que al recargar tras un fallo no dé un salto: aparecer y desaparecer en
     * el primer parpadeo se lee como un error de la página.
     */
    $mostrarActividad = motivoPideActividad($motivo) || isset($errores['actividad']);
    ?>
    <div class="campo<?= $mal('actividad') ?>" id="campoActividad"<?= $mostrarActividad ? '' : ' hidden' ?>>
      <label for="actividad">¿Qué actividad está relacionada? <span class="obligatorio">*</span></label>
      <input id="actividad" name="actividad" type="text" maxlength="200"
             placeholder="Nombre de la actividad" value="<?= e($actividad) ?>">
      <div class="pista">Indica el nombre de la actividad sobre la que nos escribes.</div>
      <?= $err('actividad') ?>
    </div>

    <div class="campo<?= $mal('mensaje') ?>">
      <label for="mensaje">Mensaje <span class="obligatorio">*</span></label>
      <textarea id="mensaje" name="mensaje" rows="5" maxlength="<?= CONTACTO_SITIO_MAX ?>" required
                placeholder="Cuéntanos cómo podemos ayudarte..."><?= e($mensaje) ?></textarea>
      <?= $err('mensaje') ?>
    </div>

    <?= captchaHtml() ?>

    <button class="btn-principal" type="submit" id="botonEnviar">Enviar mensaje</button>
  </form>

  <div class="auth-pie">
    ¿Es sobre una actividad publicada y quieres hablar con quien la organiza? Ve a su ficha
    y usa «Contactar al organizador»: llega directo a esa persona y te responde antes.
  </div>

<?php endif; ?>

</div>

<?php if (!$enviado): ?>
<script>
(function () {
  'use strict';

  var motivo    = document.getElementById('motivo');
  var campo     = document.getElementById('campoActividad');
  var actividad = document.getElementById('actividad');
  var formulario= document.getElementById('formContacto');
  var boton     = document.getElementById('botonEnviar');

  /* ---------- el campo que aparece y desaparece ---------- */
  if (motivo && campo && actividad) {
    motivo.addEventListener('change', function () {
      var opcion = motivo.options[motivo.selectedIndex];
      var toca   = opcion && opcion.getAttribute('data-actividad') === '1';

      campo.hidden        = !toca;
      actividad.required  = toca;

      /* Al dejar de tocar se BORRA el valor, no solo se esconde. Un campo
         oculto que sigue lleno manda un dato que quien escribe cree haber
         quitado —y que ademas ya no tiene nada que ver con el motivo. */
      if (!toca) {
        actividad.value = '';
        campo.classList.remove('con-error');
      }
    });

    /* Al cargar, el estado que corresponda: el navegador puede restaurar el
       motivo elegido al volver con el boton «atras». */
    actividad.required = !campo.hidden;
  }

  /* ---------- que no se mande dos veces ---------- */
  if (formulario && boton) {
    formulario.addEventListener('submit', function () {
      /* checkValidity antes de bloquear: si el propio navegador va a frenar el
         envio por un campo vacio, deshabilitar el boton dejaria el formulario
         sin forma de reintentarlo. */
      if (typeof formulario.checkValidity === 'function' && !formulario.checkValidity()) return;

      /* En el siguiente ciclo, no ahora: un <button type="submit"> deshabilitado
         durante su propio evento no llega a enviar el formulario. */
      setTimeout(function () {
        boton.disabled    = true;
        boton.textContent = 'Enviando…';
      }, 0);
    });
  }
})();
</script>
<?php endif; ?>

<?php pie(); ?>
