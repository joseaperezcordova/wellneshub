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
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/google.php';

/*
 * Dirección fija por Google Console (ver comentario de arriba), así que no
 * pasa por router.php: $GLOBALS['idioma'] no lo pone nadie más. Se recupera
 * el idioma que google-redirect.php dejó en sesión antes de salir del sitio,
 * de un solo uso, y se fija aquí para que tanto los redirigir() de abajo
 * como cualquier t()/et() que dispare resolverGoogle() —el mensaje que cae en
 * aviso_login, por ejemplo— salgan en el idioma correcto y no siempre en
 * español.
 */
$idiomaLogin = (string) ($_SESSION['idioma_login'] ?? '');
unset($_SESSION['idioma_login']);
if (!in_array($idiomaLogin, idiomasDisponibles(), true)) $idiomaLogin = IDIOMA_POR_DEFECTO;
$GLOBALS['idioma'] = $idiomaLogin;

// El usuario le dio a "Cancelar" en la pantalla de Google.
if (!empty($_GET['error'])) {
    unset($_SESSION['google_state']);
    redirigir(url('login', $idiomaLogin) . '?error=cancelado');
}

$estadoGuardado = $_SESSION['google_state'] ?? null;
unset($_SESSION['google_state']);   // de un solo uso, pase lo que pase

$estadoRecibido = (string) ($_GET['state'] ?? '');
$codigo         = (string) ($_GET['code'] ?? '');

// Si el "state" no coincide, esta vuelta no la inició este navegador.
if (!$estadoGuardado || !hash_equals($estadoGuardado, $estadoRecibido)) {
    redirigir(url('login', $idiomaLogin) . '?error=state');
}

if ($codigo === '') {
    redirigir(url('login', $idiomaLogin) . '?error=google');
}

$token = googleCanjearCodigo($codigo);
if (!$token) {
    redirigir(url('login', $idiomaLogin) . '?error=google');
}

$perfil = googlePerfil($token);
if (!$perfil) {
    redirigir(url('login', $idiomaLogin) . '?error=google');
}

[$estado, $resultado] = resolverGoogle($perfil);

if ($estado === 'error') {
    $_SESSION['aviso_login'] = $resultado;
    redirigir(url('login', $idiomaLogin) . '?error=google');
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
    redirigir(url('completar-registro', $idiomaLogin));
}

iniciarSesion((int) $resultado);
redirigir(destinoTrasLogin());
