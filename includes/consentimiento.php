<?php
/**
 * Consentimiento de cookies (REQ-00003).
 *
 * NADA QUE NO SEA NECESARIO SE CARGA ANTES DE QUE ALGUIEN ACEPTE
 *
 * Hasta ahora GA4, Clarity y el píxel de Meta se imprimían en la cabecera y se
 * ejecutaban en cuanto la página llegaba al navegador. Ya no se imprimen: lo
 * único que va en el HTML son los IDs y una lista de qué herramienta pertenece
 * a qué categoría. Quien decide si se cargan es assets/js/consentimiento.js,
 * después de leer la respuesta guardada.
 *
 * Se hace así, y no comprobando la cookie desde PHP, porque la respuesta se da
 * en la propia página: si el gate fuera de servidor, aceptar no encendería nada
 * hasta la siguiente página que visitaras. Además habría dos caminos que
 * imprimen los mismos scripts —el de PHP y el de después de aceptar— y dos
 * caminos que hacen lo mismo acaban haciéndolo distinto.
 *
 * LAS TRES CATEGORÍAS
 *
 *   necesarias  siempre. Hoy es una sola cookie: la de sesión.
 *   analiticas  Google Analytics 4 y Microsoft Clarity.
 *   marketing   Meta Pixel.
 *
 * SE PIDE SIEMPRE, NO SOLO EN LA UE
 *
 * El requerimiento dice «cuando éste sea requerido». Deducir cuándo lo es
 * significa geolocalizar por IP y acertar, y una IP mal clasificada es
 * exactamente el caso que se quería evitar. Pedirlo siempre no puede estar mal
 * en ningún país, y quien no quiera decidir tiene dos botones de un clic.
 */

declare(strict_types=1);

/** El nombre de la cookie donde vive la respuesta. */
const CONSENTIMIENTO_COOKIE = 'omdara_cookies';

/**
 * Versión de la respuesta guardada.
 *
 * Súbela cuando cambien las categorías o las herramientas que hay dentro de
 * cada una. Una respuesta de una versión anterior deja de valer y se vuelve a
 * preguntar: quien aceptó «analíticas» cuando eso era GA4 no aceptó una
 * herramienta que se añadió después.
 */
const CONSENTIMIENTO_VERSION = 1;

/** Cuánto dura la respuesta. Seis meses: lo habitual, y no «para siempre». */
const CONSENTIMIENTO_DIAS = 180;

/**
 * Las herramientas configuradas, agrupadas por la categoría que las cubre.
 *
 * Una herramienta sin ID no existe para nada de esto: ni se carga, ni se
 * anuncia, ni cuenta para decidir si hace falta banner.
 *
 * En local nunca hay analítica, ni con los IDs puestos, para que probar el
 * sitio en la máquina de quien programa no ensucie los datos reales. Es la
 * misma regla que había antes; lo único que cambia es dónde se aplica.
 *
 * @return array<string, array<string, string>>
 */
function herramientasAnalitica(): array
{
    $cfg = $GLOBALS['CONFIG']['analytics'] ?? [];

    if (!empty($GLOBALS['CONFIG']['es_local'])) return ['analiticas' => [], 'marketing' => []];

    $ga4     = trim((string) ($cfg['ga4_id'] ?? ''));
    $clarity = trim((string) ($cfg['clarity_id'] ?? ''));
    $pixel   = trim((string) ($cfg['meta_pixel_id'] ?? ''));

    $herramientas = ['analiticas' => [], 'marketing' => []];

    if ($ga4 !== '')     $herramientas['analiticas']['ga4']        = $ga4;
    if ($clarity !== '') $herramientas['analiticas']['clarity']    = $clarity;
    if ($pixel !== '')   $herramientas['marketing']['meta_pixel']  = $pixel;

    return $herramientas;
}

/**
 * ¿Hay algo que consentir?
 *
 * Sin ninguna herramienta configurada, el sitio solo pone la cookie de sesión,
 * que es necesaria y no requiere permiso. Un banner ahí no protege a nadie: solo
 * enseña un aviso que no controla nada, y acostumbra a la gente a darle a
 * «aceptar» sin leer.
 *
 * La excepción es 'probar_consentimiento' en config.local.php, que lo enciende
 * sin IDs para poder revisar el diálogo.
 */
function hayQueConsentir(): bool
{
    if (!empty($GLOBALS['CONFIG']['analytics']['probar_consentimiento'])) return true;

    foreach (herramientasAnalitica() as $herramientas) {
        if ($herramientas !== []) return true;
    }

    return false;
}

/**
 * La respuesta guardada, o null si no hay ninguna válida.
 *
 * Null significa «todavía no ha contestado»: no es lo mismo que «dijo que no».
 * La diferencia es la que decide si se enseña el banner.
 *
 * @return array{analiticas:bool, marketing:bool}|null
 */
function consentimientoGuardado(): ?array
{
    $crudo = (string) ($_COOKIE[CONSENTIMIENTO_COOKIE] ?? '');
    if ($crudo === '') return null;

    $datos = json_decode($crudo, true);
    if (!is_array($datos)) return null;

    // De una versión anterior: se vuelve a preguntar.
    if ((int) ($datos['v'] ?? 0) !== CONSENTIMIENTO_VERSION) return null;

    return [
        'analiticas' => !empty($datos['analiticas']),
        'marketing'  => !empty($datos['marketing']),
    ];
}

/**
 * ¿Se puede usar esta categoría?
 *
 * Para PHP, que no reparte cookies de terceros pero sí puede necesitar saberlo
 * —por ejemplo para no imprimir un iframe que las ponga—. 'necesarias' es
 * siempre sí; el resto, solo si consta que se aceptó.
 */
function consintio(string $categoria): bool
{
    if ($categoria === 'necesarias') return true;

    $respuesta = consentimientoGuardado();

    return $respuesta !== null && !empty($respuesta[$categoria]);
}
