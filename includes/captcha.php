<?php
/**
 * Comprobación de que quien envía un formulario público es una persona.
 *
 * DOS CAPAS, Y LA PRIMERA FUNCIONA HOY
 *
 * Turnstile de Cloudflare —o reCAPTCHA— es lo mejor contra bots, pero exige
 * darse de alta y pegar dos claves en la configuración. Mientras esas claves no
 * estén, el formulario tiene que seguir defendido: entregar un formulario
 * público sin ninguna protección "hasta que alguien cree la cuenta" es dejarlo
 * abierto justo el tiempo que tarda un bot en encontrarlo.
 *
 * Así que hay dos capas:
 *
 *   1. Siempre: campo trampa y reloj. Ni una ni otra molestan a nadie ni
 *      dependen de terceros.
 *   2. Si hay claves: Turnstile o reCAPTCHA encima.
 *
 * Se elige Turnstile por delante de reCAPTCHA porque no rastrea a quien lo usa
 * y no obliga a poner un aviso de cookies de Google en un formulario donde
 * alguien solo quiere denunciar un evento.
 */

declare(strict_types=1);

/** Segundos que, como mínimo, tarda una persona en leer y rellenar. */
const CAPTCHA_SEGUNDOS_MINIMOS = 3;

const TURNSTILE_VERIFY  = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
const RECAPTCHA_VERIFY  = 'https://www.google.com/recaptcha/api/siteverify';

/** 'turnstile', 'recaptcha' o '' si no hay ninguno configurado. */
function captchaProveedor(): string
{
    global $CONFIG;

    foreach (['turnstile', 'recaptcha'] as $p) {
        if (!empty($CONFIG['captcha'][$p]['site_key']) && !empty($CONFIG['captcha'][$p]['secret'])) {
            return $p;
        }
    }

    return '';
}

function captchaSiteKey(): string
{
    global $CONFIG;
    $p = captchaProveedor();

    return $p === '' ? '' : (string) $CONFIG['captcha'][$p]['site_key'];
}

/** El script y el widget que van dentro del formulario. */
function captchaHtml(): string
{
    $p = captchaProveedor();
    if ($p === '') return '';

    $clave = htmlspecialchars(captchaSiteKey(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    if ($p === 'turnstile') {
        return '<div class="cf-turnstile" data-sitekey="' . $clave . '"></div>'
             . '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }

    return '<div class="g-recaptcha" data-sitekey="' . $clave . '"></div>'
         . '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
}

/**
 * Los campos invisibles de la primera capa.
 *
 * El campo trampa se llama "sitio_web" porque los bots rellenan cualquier cosa
 * que parezca un campo conocido, y se esconde con CSS en vez de con type=hidden:
 * un campo oculto de verdad lo ignoran, uno visible para ellos y no para la
 * persona es el que muerden.
 */
function captchaCamposOcultos(): string
{
    $ahora = time();
    $firma = hash_hmac('sha256', (string) $ahora, sesionSemilla());

    return '<div class="trampa" aria-hidden="true">'
         . '<label>No rellenes esto<input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label>'
         . '</div>'
         . '<input type="hidden" name="ts" value="' . $ahora . '">'
         . '<input type="hidden" name="ts_firma" value="' . $firma . '">';
}

/**
 * Una clave estable por sesión para firmar la marca de tiempo.
 *
 * Sin firma, el reloj no sirve de nada: bastaría con mandar una hora vieja.
 */
function sesionSemilla(): string
{
    if (empty($_SESSION['semilla'])) {
        $_SESSION['semilla'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['semilla'];
}

/**
 * ¿Pasa el envío?
 *
 * @return array{0:bool,1:string} [ok, mensaje de error]
 */
function captchaValido(array $post): array
{
    // --- capa 1: trampa
    if (trim((string) ($post['sitio_web'] ?? '')) !== '') {
        // No se le dice que le hemos pillado: un bot que sabe por qué falla se
        // arregla; uno que solo ve un error genérico, no.
        error_log('Campo trampa relleno desde ' . ipCliente());
        return [false, 'No se pudo enviar el formulario. Inténtalo otra vez.'];
    }

    // --- capa 1: reloj
    $ts    = (string) ($post['ts'] ?? '');
    $firma = (string) ($post['ts_firma'] ?? '');

    if ($ts === '' || !hash_equals(hash_hmac('sha256', $ts, sesionSemilla()), $firma)) {
        return [false, 'El formulario caducó. Vuelve a cargarlo.'];
    }

    if (time() - (int) $ts < CAPTCHA_SEGUNDOS_MINIMOS) {
        return [false, 'Tómate un momento para revisarlo y vuelve a enviarlo.'];
    }

    // --- capa 2: el proveedor, si está configurado
    $p = captchaProveedor();
    if ($p === '') return [true, ''];

    global $CONFIG;

    $campo    = $p === 'turnstile' ? 'cf-turnstile-response' : 'g-recaptcha-response';
    $respuesta = (string) ($post[$campo] ?? '');

    if ($respuesta === '') {
        return [false, 'Confirma que no eres un robot.'];
    }

    [$http, $cuerpo] = googleHttp(
        $p === 'turnstile' ? TURNSTILE_VERIFY : RECAPTCHA_VERIFY,
        [
            'secret'   => $CONFIG['captcha'][$p]['secret'],
            'response' => $respuesta,
            'remoteip' => ipCliente(),
        ]
    );

    if ($http !== 200) {
        // Si el servicio no contesta, no se castiga a quien está delante: se
        // deja pasar y queda anotado. Bloquear un formulario de denuncias
        // porque Cloudflare tiene un mal día es peor que colar algún bot.
        error_log("Captcha $p no respondió (HTTP $http): $cuerpo");
        return [true, ''];
    }

    $datos = json_decode($cuerpo, true);

    if (empty($datos['success'])) {
        return [false, 'No se pudo verificar que seas una persona. Inténtalo otra vez.'];
    }

    return [true, ''];
}
