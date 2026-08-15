<?php
/**
 * Envío de correo con mail().
 *
 * Sin librería y sin SMTP autenticado: el hosting no tiene SSH para instalar
 * nada, y cPanel ya trae un MTA local que mail() usa directamente. Para un
 * código de acceso cada pocos minutos sobra.
 *
 * El día que esto se quede corto —cuando haya avisos a organizadores, boletines
 * o cualquier cosa con volumen— el cambio es meter un proveedor de envío
 * (Postmark, Resend, Brevo) por HTTPS. Toda la aplicación manda correo por las
 * funciones de este archivo, así que ese cambio se hace aquí y en ningún otro
 * sitio.
 */

declare(strict_types=1);

/**
 * Dirección y nombre desde los que se manda.
 *
 * Tiene que ser una dirección DEL PROPIO DOMINIO. Gmail y Outlook comprueban
 * que quien firma el correo tenga permiso sobre el dominio del remitente
 * (SPF/DKIM); mandar desde un @gmail.com a través del servidor del hosting es
 * exactamente el patrón de la suplantación, y acaba en spam o rechazado.
 *
 * @return array{0:string,1:string} [dirección, nombre]
 */
function correoRemitente(): array
{
    global $CONFIG;

    $host      = parse_url(URL_BASE, PHP_URL_HOST) ?: 'localhost';
    $direccion = trim((string) ($CONFIG['correo']['remitente'] ?? ''));
    $nombre    = trim((string) ($CONFIG['correo']['nombre'] ?? ''));

    return [
        $direccion !== '' ? $direccion : 'no-responder@' . $host,
        $nombre    !== '' ? $nombre    : 'OMDARA',
    ];
}

/**
 * La dirección PÚBLICA de contacto, la que se imprime en las páginas legales.
 *
 * No es correoRemitente(). Aquella es de sistema —no-responder@—, y publicar un
 * buzón que nadie lee en un documento que promete atender consultas es peor que
 * no publicar ninguno.
 *
 * Nace vacía a propósito. La piden dos textos legales aprobados: el Aviso de
 * Privacidad manda ejercer los derechos ARCO «al correo electrónico de contacto
 * de omdara» y la Política de Cookies deja un hueco literal donde va. Ninguno de
 * los dos dice cuál es, así que hasta que se decida y se ponga en
 * config.local.php, quien tenga que escribir usa el formulario de /contacto, que
 * sí llega a alguien. Anotado en docs/pendientes.md.
 *
 * @return string La dirección, o cadena vacía si todavía no hay ninguna.
 */
function correoContacto(): string
{
    global $CONFIG;

    $direccion = trim((string) ($CONFIG['correo']['contacto'] ?? ''));

    return filter_var($direccion, FILTER_VALIDATE_EMAIL) ? $direccion : '';
}

/**
 * Envía un correo de texto plano.
 *
 * De texto plano y no HTML a propósito: un correo con maquetación pesa más,
 * pasa peor los filtros y aquí no aporta nada — el contenido es un número de
 * seis cifras.
 *
 * $responderA cambia el Reply-To de $de (el remitente del sistema) a otra
 * dirección. Sirve para que, por ejemplo, un organizador que recibe un aviso
 * de contacto pueda simplemente darle "Responder" y llegarle a quien
 * escribió, sin tener que copiar su correo a mano. Se valida aquí también
 * —no solo en quien llama— porque una cabecera mal formada es la puerta de
 * entrada clásica a la inyección de cabeceras de correo.
 *
 * Ojo con lo que significa "true": mail() solo confirma que el servidor de
 * correo local aceptó el mensaje. No dice que llegara, ni que no acabara en
 * spam. Eso solo se comprueba mirando el buzón.
 */
function enviarCorreo(string $para, string $asunto, string $cuerpo, ?string $responderA = null): bool
{
    global $CONFIG;

    [$de, $nombreDe] = correoRemitente();

    $replyTo = ($responderA !== null && filter_var($responderA, FILTER_VALIDATE_EMAIL)) ? $responderA : $de;

    // En local no hay MTA: XAMPP en Windows no manda nada y mail() devolvería
    // false sin más. En vez de dejar el desarrollo bloqueado, el mensaje entero
    // va al log de errores de PHP, así que el código se lee ahí y el flujo se
    // puede probar de principio a fin sin servidor de correo.
    if (!empty($CONFIG['es_local'])) {
        error_log("=== CORREO (no enviado, entorno local) ===\nPara: $para\nAsunto: $asunto\nReply-To: $replyTo\n\n$cuerpo\n===");
        return true;
    }

    // El asunto lleva acentos y el estándar de cabeceras solo admite ASCII.
    $asuntoCodificado = mb_encode_mimeheader($asunto, 'UTF-8', 'B', "\r\n");

    // El nombre del remitente va por el mismo camino: "Código" sin codificar
    // llega como texto roto en algunos clientes.
    $nombreCodificado = mb_encode_mimeheader($nombreDe, 'UTF-8', 'B', "\r\n");

    $cabeceras = implode("\r\n", [
        'From: ' . $nombreCodificado . ' <' . $de . '>',
        'Reply-To: ' . $replyTo,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',

        // Que ningún filtro de vacaciones ni autorespondedor conteste a esto.
        'Auto-Submitted: auto-generated',
        'X-Auto-Response-Suppress: All',
    ]);

    // El quinto parámetro fija el remitente del sobre (el "return-path"). Sin
    // él, el MTA de cPanel manda desde el usuario de la cuenta —algo como
    // jpcorela@servidor.hostname.com— que no coincide con el From y hace
    // saltar las comprobaciones de alineación de SPF.
    $ok = @mail($para, $asuntoCodificado, $cuerpo, $cabeceras, '-f' . $de);

    if (!$ok) {
        error_log("mail() rechazó el envío a $para (asunto: $asunto)");
    }

    return $ok;
}

/**
 * El correo con el código de un solo uso.
 *
 * El código va también en el asunto para que se lea en la notificación del
 * móvil sin abrir nada. Es deliberado: quien ve el mensaje ya lo tiene, y quien
 * no tiene acceso al buzón no ve ni la notificación.
 */
function enviarCodigoAcceso(string $para, string $codigo, int $minutos): bool
{
    // El nombre sale de la configuración y no escrito a mano aquí. Si el
    // remitente se llama de una manera y el texto del mensaje de otra, el
    // correo parece suplantado —a la persona que lo lee y a los filtros de
    // spam, que comparan justo eso.
    [, $marca] = correoRemitente();

    $asunto = $codigo . ' es tu código para entrar en ' . $marca;

    $cuerpo = <<<TEXTO
Tu código para entrar en $marca es:

    $codigo

Caduca en $minutos minutos y sirve una sola vez.

Si no has pedido este código, no hagas nada: sin él nadie entra, y el
código deja de valer solo. Nadie de $marca te lo va a pedir por teléfono,
por WhatsApp ni por correo.

--
$marca · Directorio de actividades wellness en México
TEXTO;

    return enviarCorreo($para, $asunto, $cuerpo);
}
