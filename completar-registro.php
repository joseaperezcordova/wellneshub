<?php
/**
 * Completa tu registro: la casilla legal antes de crear la cuenta (REQ-00008).
 *
 * ESTA PÁGINA ES LA PUERTA, Y ES UNA SOLA PARA LOS DOS CAMINOS
 *
 * Se llega aquí después de haber demostrado quién eres —con un código que ya se
 * gastó, o con la vuelta de Google ya verificada— y ANTES de que exista ninguna
 * cuenta. Es la única función del sitio que crea usuarios a partir de este
 * requerimiento.
 *
 * Que sea una sola para el correo y para Google no es por ahorrar un archivo:
 * es lo que impide que la puerta quede puesta en un camino y olvidada en el
 * otro, que es exactamente lo que el requerimiento se molesta en prohibir
 * ("La autenticación de Google por sí sola no debe considerarse como aceptación
 * de los documentos").
 *
 * QUÉ PRUEBA QUE ALGUIEN PUEDE ESTAR AQUÍ
 *
 * $_SESSION['alta_pendiente'], que solo lo escriben codigo.php —tras verificar
 * el código— y google-callback.php —tras validar el "state" y el token—. No
 * viaja nada por la URL ni por un campo oculto: si el correo llegara en el
 * formulario, cualquiera podría cambiarlo por el de otra persona y crear una
 * cuenta a su nombre. Es el mismo motivo por el que codigo.php lleva el correo
 * en sesión.
 *
 * Y caduca. Media hora es de sobra para leer dos documentos y marcar una
 * casilla; más allá, lo que hay es una pestaña olvidada.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/google.php';

if (haySesion()) redirigir('/');

/** Cuánto vale una alta a medias, en minutos. */
const ALTA_PENDIENTE_MIN = 30;

$pendiente = $_SESSION['alta_pendiente'] ?? null;

if (!is_array($pendiente)
    || (time() - (int) ($pendiente['en'] ?? 0)) > ALTA_PENDIENTE_MIN * 60) {
    unset($_SESSION['alta_pendiente']);
    redirigir('/login.php');
}

$viaGoogle = ($pendiente['via'] ?? '') === 'google';
$email     = (string) ($pendiente['email'] ?? '');
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'La sesión caducó. Vuelve a empezar.';

    } elseif (empty($_POST['acepto'])) {
        // El mensaje es el del requerimiento, palabra por palabra. Y se
        // comprueba aquí y no solo con el "required" del navegador: ese se
        // salta desactivando JavaScript o mandando el POST a mano, y entonces
        // la aceptación no habría existido nunca.
        $error = 'Para crear tu cuenta debes aceptar los Términos y Condiciones y el Aviso de Privacidad.';

    } else {
        /*
         * Se vuelve a resolver antes de crear. Entre que empezó esto y le dio
         * al botón pudo crearse la cuenta por el otro camino —dos pestañas,
         * o un código pedido antes de irse a Google—, y un INSERT a ciegas
         * chocaría contra el índice único del correo y acabaría en un error
         * que no explica nada.
         */
        [$estado, $dato] = $viaGoogle
            ? resolverGoogle($pendiente['perfil'] ?? [])
            : resolverPorCorreo($email);

        if ($estado === 'error') {
            $error = $dato;

        } elseif ($estado === 'entra') {
            // Ya existía. No se crea nada; se entra, y se deja constancia de que
            // aceptó, que es lo que acaba de hacer.
            unset($_SESSION['alta_pendiente']);
            registrarAceptacionLegal((int) $dato);
            iniciarSesion((int) $dato);
            redirigir(destinoTrasLogin());

        } else {
            $nuevoId = $viaGoogle
                ? crearUsuarioConGoogle($pendiente['perfil'] ?? [])
                : crearUsuarioPorCorreo($email);

            if ($nuevoId === null) {
                $error = 'No se pudo crear tu cuenta. Inténtalo otra vez.';
            } else {
                unset($_SESSION['alta_pendiente']);
                registrarAceptacionLegal((int) $nuevoId);
                iniciarSesion((int) $nuevoId);
                redirigir(destinoTrasLogin());
            }
        }
    }
}

$titulo = 'Completa tu registro';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja">
  <h1>Completa tu registro</h1>
  <p class="sub">
    <?php if ($viaGoogle): ?>
      Google confirmó tu correo. Falta un paso para crear tu cuenta.
    <?php else: ?>
      Verificamos tu correo. Falta un paso para crear tu cuenta.
    <?php endif; ?>
  </p>

  <?php if ($error): ?>
    <div class="aviso aviso-error"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if ($email !== ''): ?>
    <?php /* Qué correo va a quedar en la cuenta. Con Google sobre todo: quien
             tiene varias cuentas de Google abiertas no siempre sabe con cuál
             acaba de entrar, y esto se descubre semanas después. */ ?>
    <div class="alta-correo">
      <span>Se creará la cuenta de</span>
      <strong><?= e($email) ?></strong>
    </div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">

    <?php require __DIR__ . '/includes/casilla-legal.php'; ?>

    <button class="btn-principal" type="submit">Crear mi cuenta</button>
  </form>

  <div class="auth-pie">
    ¿No eras tú? <a href="<?= URL_BASE ?>/logout.php">Cancelar y salir</a>.
  </div>
</div>

<?php pie(); ?>
