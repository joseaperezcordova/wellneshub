<?php
/**
 * Vuelta desde Google.
 *
 * Esta URL es la que hay que registrar en Google Cloud Console como "URI de
 * redireccionamiento autorizado", tal cual, sin barra final:
 *
 *   http://localhost/wellneshub/google-callback.php
 *   https://wellnesshubmx.jpcorelab.com/google-callback.php
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/google.php';

// El usuario le dio a "Cancelar" en la pantalla de Google.
if (!empty($_GET['error'])) {
    unset($_SESSION['google_state']);
    redirigir('/login.php?error=cancelado');
}

$estadoGuardado = $_SESSION['google_state'] ?? null;
unset($_SESSION['google_state']);   // de un solo uso, pase lo que pase

$estadoRecibido = (string) ($_GET['state'] ?? '');
$codigo         = (string) ($_GET['code'] ?? '');

// Si el "state" no coincide, esta vuelta no la inició este navegador.
if (!$estadoGuardado || !hash_equals($estadoGuardado, $estadoRecibido)) {
    redirigir('/login.php?error=state');
}

if ($codigo === '') {
    redirigir('/login.php?error=google');
}

$token = googleCanjearCodigo($codigo);
if (!$token) {
    redirigir('/login.php?error=google');
}

$perfil = googlePerfil($token);
if (!$perfil) {
    redirigir('/login.php?error=google');
}

[$estado, $resultado] = resolverGoogle($perfil);

if ($estado === 'error') {
    $_SESSION['aviso_login'] = $resultado;
    redirigir('/login.php?error=google');
}

/*
 * Cuenta nueva: aquí NO se crea (REQ-00008).
 *
 * Que Google confirme el correo demuestra quién eres, no que hayas aceptado los
 * Términos ni el Aviso de Privacidad —el requerimiento lo dice con todas las
 * letras—. Antes de esto, pulsar «Continuar con Google» y aceptar la pantalla
 * de Google creaba la cuenta, de modo que autenticarse equivalía a aceptar unos
 * documentos que nadie había visto.
 *
 * El perfil se guarda en sesión, no en la URL ni en un campo oculto: es el
 * único sitio donde no lo puede tocar quien lo manda. Sin esa cautela, cambiar
 * el correo del formulario daría de alta a otra persona.
 */
if ($estado === 'nueva') {
    $_SESSION['alta_pendiente'] = ['via' => 'google', 'perfil' => $perfil, 'en' => time(),
                                   'email' => (string) ($perfil['email'] ?? '')];
    redirigir('/completar-registro.php');
}

iniciarSesion((int) $resultado);
redirigir(destinoTrasLogin());
