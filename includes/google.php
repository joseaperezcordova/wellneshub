<?php
/**
 * OAuth 2.0 con Google, a mano.
 *
 * Sin librería y sin Composer: el hosting no tiene SSH, así que no hay forma de
 * ejecutar `composer install` allí. Subir un vendor/ entero por FTP para usar
 * tres peticiones HTTP no compensa.
 *
 * El flujo es el de "authorization code":
 *   1. Mandamos al usuario a Google con un "state" aleatorio.
 *   2. Google lo devuelve a nuestro callback con un código.
 *   3. Cambiamos ese código por un token, servidor contra servidor.
 *   4. Con el token pedimos el perfil.
 *
 * El perfil se pide al endpoint userinfo en vez de decodificar el id_token que
 * viene en el paso 3. Un JWT hay que verificarlo con las claves públicas de
 * Google, que rotan y habría que cachear; si no se verifica la firma, el
 * id_token no vale nada. La llamada a userinfo llega por TLS directamente de
 * Google, así que no hay nada que verificar a mano.
 */

declare(strict_types=1);

const GOOGLE_AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_TOKEN_URL    = 'https://oauth2.googleapis.com/token';
const GOOGLE_USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

function googleConfigurado(): bool
{
    global $CONFIG;
    return !empty($CONFIG['google']['client_id']) && !empty($CONFIG['google']['client_secret']);
}

function googleRedirectUri(): string
{
    return URL_BASE . '/google-callback.php';
}

/**
 * Permisos que se le piden a Google.
 *
 * Lo natural sería 'openid email profile', que trae también nombre y foto. Pero
 * este hosting tiene una regla de mod_security que devuelve 403 ante cualquier
 * URL que contenga la cadena ".profile" —es un archivo de shell de Unix y el
 * WAF lo trata como intento de acceder a él—. Y Google, al volver del login,
 * incluye siempre en el callback:
 *
 *     scope=...https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.profile...
 *
 * O sea que pedir 'profile' hace que el callback sea bloqueado por el servidor
 * antes de llegar a PHP, y el login con Google es imposible. Comprobado: la URL
 * "…/login.php?x=.profile" da 403, y "…?x=.email" da 200.
 *
 * Sin 'profile' seguimos recibiendo el identificador estable y el correo, que
 * es lo que de verdad hace falta para tener cuenta. El nombre se deriva del
 * correo hasta que la persona lo cambie.
 *
 * Cuando el hosting desactive esa regla, basta con poner 'pedir_perfil' => true
 * en config.local.php y se recuperan nombre y avatar sin tocar código.
 */
function googleScope(): string
{
    global $CONFIG;
    return !empty($CONFIG['google']['pedir_perfil'])
        ? 'openid email profile'
        : 'openid email';
}

/** URL a la que mandamos al usuario para que elija su cuenta. */
function googleUrlAutorizacion(): string
{
    global $CONFIG;

    // El "state" viaja a Google y vuelve. Si al volver no coincide con el que
    // guardamos en sesión, la petición no la inició este navegador: es un CSRF
    // sobre el login y se descarta.
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_state'] = $state;

    return GOOGLE_AUTH_URL . '?' . http_build_query([
        'client_id'     => $CONFIG['google']['client_id'],
        'redirect_uri'  => googleRedirectUri(),
        'response_type' => 'code',
        'scope'         => googleScope(),
        'state'         => $state,
        'access_type'   => 'online',

        // Sin esto, quien tenga varias cuentas de Google entra siempre con la
        // última y no hay manera de cambiar sin cerrar sesión en Google.
        'prompt'        => 'select_account',
    ]);
}

/**
 * Cambia el código por un access_token.
 */
function googleCanjearCodigo(string $codigo): ?string
{
    global $CONFIG;

    [$http, $cuerpo] = peticionHttp(GOOGLE_TOKEN_URL, [
        'code'          => $codigo,
        'client_id'     => $CONFIG['google']['client_id'],
        'client_secret' => $CONFIG['google']['client_secret'],
        'redirect_uri'  => googleRedirectUri(),
        'grant_type'    => 'authorization_code',
    ]);

    if ($http !== 200) {
        error_log("Google /token respondió $http: $cuerpo");
        return null;
    }

    $datos = json_decode($cuerpo, true);
    return $datos['access_token'] ?? null;
}

/**
 * Perfil del usuario. Devuelve sub, email, email_verified, name y picture.
 */
function googlePerfil(string $accessToken): ?array
{
    [$http, $cuerpo] = peticionHttp(GOOGLE_USERINFO_URL, null, [
        'Authorization: Bearer ' . $accessToken,
    ]);

    if ($http !== 200) {
        error_log("Google /userinfo respondió $http: $cuerpo");
        return null;
    }

    $perfil = json_decode($cuerpo, true);
    return is_array($perfil) && !empty($perfil['sub']) ? $perfil : null;
}
