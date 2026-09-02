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
        $nombre    !== '' ? $nombre    : 'Omdara',
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
 * El buzón de soporte técnico: problemas para entrar, con la cuenta, para
 * publicar o editar una actividad, errores dentro de la plataforma.
 *
 * Es OTRO buzón, no correoContacto(): ese es para dudas generales, propuestas
 * y alianzas —lo maneja quien lleva la relación con el público—; este es para
 * quien resuelve fallos del sitio. Mezclarlos en uno solo habría significado
 * que un «no puedo iniciar sesión» se pierde entre mensajes de alianzas, o al
 * revés.
 *
 * Requerimiento del cliente, «Configuración y uso de correos de Omdara»
 * (2026-09-02): PÚBLICO, a diferencia de admin@ —ver el aviso en
 * config.local.example.php—.
 *
 * @return string La dirección, o cadena vacía si todavía no hay ninguna.
 */
function correoSoporte(): string
{
    global $CONFIG;

    $direccion = trim((string) ($CONFIG['correo']['soporte'] ?? ''));

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
 *
 * A DIFERENCIA de los avisos a organizadores y administradores (motivosContacto()
 * y compañía, que fijan español explícito porque van siempre a la misma persona
 * del negocio), este correo va a cualquier visitante que intente entrar —así
 * que SÍ sigue su idioma. Se puede porque login.php y codigo.php, que son
 * quienes disparan este correo, ya tienen ruta /en propia: idiomaActual() aquí
 * refleja de verdad el idioma de la página que lo pidió.
 */
function enviarCodigoAcceso(string $para, string $codigo, int $minutos): bool
{
    // El nombre sale de la configuración y no escrito a mano aquí. Si el
    // remitente se llama de una manera y el texto del mensaje de otra, el
    // correo parece suplantado —a la persona que lo lee y a los filtros de
    // spam, que comparan justo eso.
    [, $marca] = correoRemitente();

    $reemplazos = ['{codigo}' => $codigo, '{minutos}' => (string) $minutos, '{marca}' => $marca];

    $asunto = strtr(t('correo.codigo.asunto'), $reemplazos);
    $cuerpo = strtr(t('correo.codigo.cuerpo'), $reemplazos);

    return enviarCorreo($para, $asunto, $cuerpo);
}

/**
 * El código para confirmar el correo de contacto de una actividad (migración
 * 24, REQ del cliente 2026-09-02).
 *
 * A DIFERENCIA del código de acceso, este correo puede llegarle a alguien que
 * nunca pidió nada —quien edita puede escribir cualquier dirección—, así que
 * el cuerpo lo dice de frente y explica que sin ese código no pasa nada.
 *
 * Sigue el idioma de quien lo pide (idiomaActual()), igual que
 * enviarCodigoAcceso(): quien está editando una actividad ya tiene sesión y
 * una página con idioma real, no hace falta fijar español aquí.
 */
function enviarCodigoCorreoContacto(string $para, string $codigo, int $minutos, string $tituloEvento): bool
{
    [, $marca] = correoRemitente();

    $reemplazos = [
        '{codigo}'    => $codigo,
        '{minutos}'   => (string) $minutos,
        '{marca}'     => $marca,
        '{actividad}' => $tituloEvento,
    ];

    $asunto = strtr(t('correo.confirmar_contacto.asunto'), $reemplazos);
    $cuerpo = strtr(t('correo.confirmar_contacto.cuerpo'), $reemplazos);

    return enviarCorreo($para, $asunto, $cuerpo);
}

/**
 * El código para confirmar el correo NUEVO de la cuenta (punto 18 de
 * docs/pendientes.md, migración 25).
 *
 * Español fijo y no idiomaActual(), a diferencia del código de acceso:
 * quien lo dispara es mi-cuenta.php, y esa página —como el resto de «Mi
 * cuenta»— no tiene mecanismo de idioma propio todavía.
 */
function enviarCodigoCambioCorreo(string $para, string $codigo, int $minutos): bool
{
    [, $marca] = correoRemitente();

    $asunto = "$codigo es tu código para confirmar tu correo nuevo en $marca";
    $cuerpo = "Alguien —esperamos que tú— quiere cambiar el correo con el que entra a su cuenta de "
            . "$marca a esta dirección.\n\n"
            . "Tu código es:\n\n    $codigo\n\n"
            . "Escríbelo en la página donde se pidió el cambio. Caduca en $minutos minutos y sirve una "
            . "sola vez.\n\n"
            . "Si no reconoces esto, no hagas nada: sin ese código no se activa nada, y el código deja "
            . "de valer solo.\n\n"
            . "--\n$marca · Directorio de actividades wellness en México";

    return enviarCorreo($para, $asunto, $cuerpo);
}

/**
 * Avisa al correo VIEJO de que el cambio ya se hizo. Es el único de los
 * cuatro pasos que detecta un secuestro —alguien con acceso al buzón nuevo,
 * pero no al viejo, pidiendo el cambio—, así que llega DESPUÉS de que el
 * cambio ya es un hecho, no antes: antes de eso no hay nada que avisar.
 */
function enviarAvisoCambioCorreo(string $correoViejo, string $correoNuevo): bool
{
    [, $marca] = correoRemitente();

    $asunto = "El correo de tu cuenta de $marca cambió";
    $cuerpo = "El correo con el que entras a tu cuenta de $marca cambió de esta dirección a "
            . "$correoNuevo.\n\n"
            . "Si fuiste tú, no hace falta que hagas nada más.\n\n"
            . "Si no reconoces este cambio, escríbenos de inmediato" . (correoSoporte() !== '' ? " a " . correoSoporte() : '')
            . " para recuperar tu cuenta.\n\n"
            . "--\n$marca · Directorio de actividades wellness en México";

    return enviarCorreo($correoViejo, $asunto, $cuerpo);
}
