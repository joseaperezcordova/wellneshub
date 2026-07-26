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

/**
 * ¿Es esto una clave de verdad o el hueco de la plantilla sin rellenar?
 *
 * Existe por un tropiezo real: se copió el ejemplo de la documentación tal cual
 * y quedó 'TU_CLAVE' en la configuración. Como no estaba vacía, el widget se
 * pintaba, Cloudflare rechazaba la clave inventada y el formulario de reportes
 * quedaba imposible de enviar para todo el mundo.
 *
 * Una clave a medio poner tiene que comportarse como una clave ausente, no como
 * una rota: el formulario sigue defendido por la trampa y el reloj, y el aviso
 * queda en el log para quien administre.
 */
function claveUtilizable(?string $valor): bool
{
    $valor = trim((string) $valor);

    if ($valor === '') return false;

    // Las claves de Turnstile y reCAPTCHA pasan de 30 caracteres con holgura.
    // Cualquier cosa corta es un marcador de posición o una copia a medias.
    if (strlen($valor) < 20) return false;

    return !preg_match('/^(tu[_ -]|your[_ -]|cambia|pon[_ -]|xxx|clave|secreto)/i', $valor);
}

/** 'turnstile', 'recaptcha' o '' si no hay ninguno configurado. */
function captchaProveedor(): string
{
    global $CONFIG;

    foreach (['turnstile', 'recaptcha'] as $p) {
        $clave   = $CONFIG['captcha'][$p]['site_key'] ?? null;
        $secreto = $CONFIG['captcha'][$p]['secret']   ?? null;

        if (claveUtilizable($clave) && claveUtilizable($secreto)) {
            return $p;
        }

        // Puesto a medias: se ignora, pero que no pase en silencio.
        if (trim((string) $clave) !== '' || trim((string) $secreto) !== '') {
            error_log("Captcha $p configurado a medias o con un valor de ejemplo: se ignora.");
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

    // Si el widget no llega a cargar, Cloudflare pinta un enlace «Troubleshoot»
    // que a quien está delante no le dice nada. Este aviso sí: explica qué pasó
    // y qué hacer, sin destapar nada de la configuración.
    $fallo = '<div id="captchaFallo" class="captcha-fallo" hidden>'
           . 'No se pudo cargar la comprobación de seguridad. Recarga la página; '
           . 'si vuelve a pasar, avísanos y lo revisamos.'
           . '</div>'
           . '<script>function captchaError(){var a=document.getElementById("captchaFallo");'
           . 'if(a)a.hidden=false;return true;}</script>';

    if ($p === 'turnstile') {
        return '<div class="cf-turnstile" data-sitekey="' . $clave . '"'
             . ' data-error-callback="captchaError" data-theme="light"></div>'
             . $fallo
             . '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }

    return '<div class="g-recaptcha" data-sitekey="' . $clave . '"'
         . ' data-error-callback="captchaError"></div>'
         . $fallo
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
        // Se corta aquí a propósito. Dejar pasar los envíos sin token haría que
        // el captcha no sirviera de nada: a un bot le basta con no mandarlo.
        //
        // El otro caso —que el servicio no conteste— sí se deja pasar, más
        // abajo. La diferencia es quién falla: ahí falla un tercero y se
        // comprueba desde el servidor, donde nadie puede fingirlo; aquí falta
        // algo que tenía que mandar el navegador, y eso sí se puede omitir a
        // voluntad.
        return [false, 'Confirma que no eres un robot. Si no ves la casilla, recarga la página.'];
    }

    [$http, $cuerpo] = peticionHttp(
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
