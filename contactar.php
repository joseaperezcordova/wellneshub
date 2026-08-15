<?php
/**
 * Contactar al organizador (REQ-00007). Abierto a cualquiera, sin cuenta.
 *
 * Mismo criterio que reportar.php: quien tiene una pregunta antes de apuntarse
 * no se va a registrar solo para preguntarla. El mensaje llega al correo del
 * organizador con el Reply-To puesto a quien escribe, así que responder es tan
 * simple como contestar ese correo.
 *
 * POR QUÉ ES UNA PÁGINA Y NO UNA VENTANA FLOTANTE
 *
 * El diseño lo dibuja como un cuadro encima de la ficha, y la tarjeta se ve
 * exactamente así. Lo que no hace es flotar: es una página con su propia
 * dirección. La diferencia se nota en tres sitios, y en los tres a favor:
 *
 *   · El formulario se envía y se valida en el servidor, como todo lo demás
 *     del sitio. Una ventana flotante que envía sin recargar necesita una
 *     capa de JavaScript por encima del CSRF, del captcha y del límite por IP,
 *     y esa capa es exactamente donde se cuelan los envíos que no se validan.
 *   · Sin JavaScript sigue funcionando. Ahora mismo no hace falta ninguno.
 *   · Un error de validación tiene dónde volver. En una ventana flotante hay
 *     que decidir a mano cómo se reabre con lo escrito dentro.
 *
 * Si producto quiere la ventana de verdad —que la ficha se quede detrás,
 * difuminada—, se puede montar encima de esto sin tirar nada: el mismo
 * formulario, cargado en un contenedor. Está anotado en docs/pendientes.md.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';

$ev = buscarEvento((int) ($_GET['id'] ?? 0));

if (!$ev || $ev['situacion'] !== 'publicado') {
    http_response_code(404);
    $titulo = 'Actividad no encontrada';
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>Esa actividad no existe</h1>'
       . '<p class="sub">Puede que ya se haya retirado.</p></div>';
    pie();
    exit;
}

// Este formulario es para la acción "Contactar al organizador". Si el
// organizador eligió otra, aquí no hay nada que hacer: se manda de vuelta a
// la ficha, que es donde está el enlace que sí corresponde.
if ($ev['accion_principal'] !== 'informacion') {
    redirigir(urlEvento($ev));
}

/** Tope del mensaje. El contador de debajo del campo lee de aquí. */
const CONTACTO_MENSAJE_MAX = 500;

$error    = '';
$enviado  = false;
$nombre   = (string) ($_POST['nombre'] ?? '');
$email    = (string) ($_POST['email'] ?? '');
$telefono = (string) ($_POST['telefono'] ?? '');
$mensaje  = (string) ($_POST['mensaje'] ?? '');
$acepta   = !empty($_POST['privacidad']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // El captcha se comprueba dentro de la cadena y no antes: verificarlo
    // implica una petición a Cloudflare, y no hay razón para gastarla en un
    // envío que ya se cayó por el token.
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'El formulario caducó. Vuelve a cargarlo.';

    } elseif (!($captcha = captchaValido($_POST))[0]) {
        $error = $captcha[1];

    } elseif (trim($nombre) === '') {
        $error = 'Escribe tu nombre para que el organizador sepa quién pregunta.';

    } elseif (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
        $error = 'Ese correo no parece válido.';

    } elseif (trim($mensaje) === '') {
        // Desde REQ-00007 el mensaje es obligatorio. Un aviso de "alguien
        // quiere contactarte" sin nada dentro obliga al organizador a escribir
        // primero para averiguar qué le preguntan, y ahí se pierde la mitad.
        $error = 'Escribe tu mensaje: es lo que el organizador va a leer.';

    } elseif (!$acepta) {
        // Se comprueba en el servidor y no solo con el "required" del
        // navegador: ese se salta desactivando JavaScript o mandando el POST a
        // mano, y entonces el consentimiento no habría existido nunca.
        $error = 'Marca la casilla del Aviso de Privacidad para poder enviar tu mensaje.';

    } elseif (contactoRepetido((int) $ev['id'])) {
        $error = 'Ya le escribiste a este organizador hace un momento. Dale tiempo a responder antes de volver a escribir.';

    } else {
        $nombre   = trim($nombre);
        $email    = trim($email);
        $telefono = trim($telefono) !== '' ? mb_substr(trim($telefono), 0, 30) : null;
        $mensaje  = mb_substr(trim($mensaje), 0, CONTACTO_MENSAJE_MAX);

        crearContacto((int) $ev['id'], $nombre, $email, $telefono, $mensaje);
        avisarOrganizador($ev, $nombre, $email, $telefono, $mensaje);
        $enviado = true;
    }
}

$titulo = 'Contactar al organizador';
require __DIR__ . '/includes/layout.php';
?>

<div class="tarjeta-contacto">

<?php if ($enviado): ?>

  <?php /* Interfaz 2 del requerimiento. Sin la × de cerrar: aquí ya no hay nada
           a medias que abandonar, y el único camino es volver a la actividad,
           que es justo lo que ofrece el botón. */ ?>
  <div class="contacto-hecho">
    <div class="contacto-tick" aria-hidden="true">
      <svg viewBox="0 0 48 48" width="66" height="66" fill="none" stroke="currentColor"
           stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="24" cy="24" r="21"/>
        <path d="M15 24.5l6.5 6.5L33 19"/>
      </svg>
    </div>

    <h1>¡Mensaje enviado!</h1>
    <p>Hemos recibido tu mensaje y lo enviaremos al organizador de esta actividad.</p>
    <p>La respuesta dependerá del organizador.</p>

    <a class="btn-contorno" href="<?= e(urlEvento($ev)) ?>">← Volver a la actividad</a>
  </div>

<?php else: ?>

  <div class="contacto-cab">
    <div>
      <h1>Contacta al organizador</h1>
      <p class="sub">Completa el formulario y enviaremos tu mensaje al organizador.</p>
    </div>
    <?php /* La × es un enlace y no un botón con JavaScript: cerrar aquí es
             volver a la actividad, y siendo enlace funciona el clic central,
             el «abrir en pestaña nueva» y el teclado sin nada más. */ ?>
    <a class="contacto-cerrar" href="<?= e(urlEvento($ev)) ?>"
       aria-label="Cerrar y volver a la actividad">&times;</a>
  </div>

  <?php /* Qué actividad se está consultando. El requerimiento lo pide, y hace
           falta: a este formulario se llega desde una ficha, pero también desde
           un enlace guardado o desde otra pestaña, y sin esto no hay forma de
           saber a quién se le está escribiendo. */ ?>
  <div class="contacto-actividad">
    <div class="contacto-actividad-tit">Actividad</div>
    <strong><?= e($ev['titulo']) ?></strong>
    <div class="contacto-actividad-dato">
      <span aria-hidden="true">◎</span>
      <?php
      $donde = array_filter([$ev['lugar'] ?? '', trim(($ev['ciudad'] ?? '') . ', ' . ($ev['entidad'] ?? ''), ', ')]);
      echo e(implode(', ', $donde));
      ?>
    </div>
    <div class="contacto-actividad-dato">
      <span aria-hidden="true">▤</span> <?= e(fechaResumen($ev)) ?>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="aviso aviso-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
    <?= captchaCamposOcultos() ?>

    <div class="campo">
      <label for="nombre">Tu nombre <span class="obligatorio">*</span></label>
      <input id="nombre" name="nombre" type="text" maxlength="120" autocomplete="name"
             placeholder="Escribe tu nombre" value="<?= e($nombre) ?>" required>
    </div>

    <div class="campo">
      <label for="email">Tu correo electrónico <span class="obligatorio">*</span></label>
      <input id="email" name="email" type="email" maxlength="190" autocomplete="email"
             placeholder="ejemplo@correo.com" value="<?= e($email) ?>" required>
      <div class="pista">Aquí te va a responder el organizador.</div>
    </div>

    <div class="campo">
      <label for="telefono">Tu teléfono / WhatsApp <span class="opcional">opcional</span></label>
      <?php /* type="tel" y no "text": en el teléfono abre el teclado numérico.
               Sin patrón de validación a propósito — un número mexicano, uno
               con prefijo internacional y uno con extensión se escriben de
               formas distintas, y todas son correctas para quien va a marcar. */ ?>
      <input id="telefono" name="telefono" type="tel" maxlength="30" autocomplete="tel"
             placeholder="Ej. +52 612 123 4567" value="<?= e($telefono) ?>">
    </div>

    <div class="campo">
      <label for="mensaje">Tu mensaje <span class="obligatorio">*</span></label>
      <textarea id="mensaje" name="mensaje" rows="4" maxlength="<?= CONTACTO_MENSAJE_MAX ?>"
                placeholder="Escribe aquí tu mensaje..." required><?= e($mensaje) ?></textarea>
      <?php /* El contador sale del propio maxlength, así que cambiar el tope se
               hace en un sitio. Sin JavaScript no aparece, y no pasa nada: el
               navegador ya impide pasarse y el servidor recorta. */ ?>
      <div class="contador" id="contadorMensaje" hidden>0/<?= CONTACTO_MENSAJE_MAX ?></div>
    </div>

    <div class="contacto-nota">
      <strong>¿Qué pasa con tu mensaje?</strong>
      Recibiremos tu mensaje y lo enviaremos al organizador de esta actividad.
      La respuesta dependerá del organizador.
    </div>

    <?= captchaHtml() ?>

    <label class="contacto-privacidad">
      <input type="checkbox" name="privacidad" value="1"<?= $acepta ? ' checked' : '' ?> required>
      <span>He leído y acepto el
        <a href="<?= e(url('privacidad')) ?>" target="_blank" rel="noopener">Aviso de Privacidad</a>.</span>
    </label>

    <button class="btn-principal btn-enviar" type="submit">✈ Enviar mensaje</button>
  </form>

  <div class="auth-pie">
    Tu correo y tu teléfono solo los recibe el organizador de esta actividad. No se hacen públicos en ningún lado.
  </div>

<?php endif; ?>

</div>

<?php if (!$enviado): ?>
<script>
(function () {
  var campo = document.getElementById('mensaje');
  var marca = document.getElementById('contadorMensaje');
  if (!campo || !marca) return;

  var tope = campo.getAttribute('maxlength');

  function pintar() { marca.textContent = campo.value.length + '/' + tope; }

  marca.hidden = false;
  campo.addEventListener('input', pintar);
  pintar();   /* al volver de un error el campo ya trae texto */
})();
</script>
<?php endif; ?>

<?php pie(); ?>
