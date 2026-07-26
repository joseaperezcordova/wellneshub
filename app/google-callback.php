<?php
/**
 * Vuelta desde Google.
 *
 * Esta URL es la que hay que registrar en Google Cloud Console como "URI de
 * redireccionamiento autorizado", tal cual, sin barra final:
 *
 *   http://localhost/wellneshub/app/google-callback.php
 *   https://wellnesshubmx.jpcorelab.com/app/google-callback.php
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

[$ok, $resultado] = entrarConGoogle($perfil);

if (!$ok) {
    $_SESSION['aviso_login'] = $resultado;
    redirigir('/login.php?error=google');
}

iniciarSesion((int) $resultado);
redirigir('/');
