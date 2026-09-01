<?php
/**
 * El sitio en dos idiomas: español (por defecto) e inglés.
 *
 * EL IDIOMA LO DICE LA DIRECCIÓN, Y NADA MÁS
 *
 * No hay cookie de idioma ni preferencia en sesión, y es a propósito. Si la
 * misma dirección pudiera devolver dos idiomas distintos según quién la pida:
 *
 *   · Google indexaría uno de los dos al azar, y el nginx que hay delante
 *     cachearía la primera versión que le llegara y se la serviría a todos.
 *   · Compartir un enlace dejaría de funcionar: quien lo abriera podría verlo
 *     en un idioma distinto del que vio quien lo mandó.
 *
 * Con /actividades y /activities como direcciones distintas, cada una dice lo
 * mismo siempre, a todo el mundo, y hreflang le explica a Google que son la
 * misma página en dos idiomas.
 *
 * DE DÓNDE SALEN LAS DIRECCIONES
 *
 * rutasSitio() es la única fuente: de ahí salen el enrutado (router.php), los
 * enlaces del menú y el pie (url()), el selector de idioma
 * (urlEquivalente()), las etiquetas hreflang y el sitemap. Cuando esto vivía
 * repartido, añadir una página significaba tocar cinco sitios y olvidarse de
 * dos.
 */

declare(strict_types=1);

const IDIOMA_POR_DEFECTO = 'es';

/** Los idiomas que sirve el sitio, en orden de importancia. */
function idiomasDisponibles(): array
{
    return ['es', 'en'];
}

/** El nombre de cada idioma, escrito en ese mismo idioma. */
function nombreIdioma(string $idioma): string
{
    $nombres = ['es' => 'Español', 'en' => 'English'];

    return $nombres[$idioma] ?? $idioma;
}

/**
 * El idioma de la petición en curso.
 *
 * Lo fija router.php al resolver la dirección. Si algo llega aquí sin pasar
 * por él —un .php abierto directamente, como /buscar.php— cae al idioma por
 * defecto, que es justo lo que se quiere: esas direcciones antiguas siguen
 * respondiendo en español.
 */
function idiomaActual(): string
{
    $idioma = $GLOBALS['idioma'] ?? IDIOMA_POR_DEFECTO;

    return in_array($idioma, idiomasDisponibles(), true) ? $idioma : IDIOMA_POR_DEFECTO;
}

/**
 * El mapa de direcciones.
 *
 * clave interna => [archivo que la sirve, y su dirección en cada idioma]
 *
 * Las direcciones en inglés usan palabras reales del idioma y no la traducción
 * literal de la española: "frequently-asked-questions" y no "faq" porque el
 * requerimiento pide direcciones legibles, y tampoco "preguntas-frequently"
 * ni mezclas por el estilo.
 *
 * Las páginas que aún no tienen versión inglesa sencillamente no traen clave
 * 'en'. El selector de idioma lo sabe y manda al inicio en inglés en vez de a
 * una dirección que daría 404 —es el único caso en que el requerimiento
 * permite no conservar la página.
 */
function rutasSitio(): array
{
    return [
        'inicio' => [
            'archivo' => 'index.php',
            'es' => '',
            'en' => 'en',
        ],
        'actividades' => [
            'archivo' => 'buscar.php',
            'es' => 'actividades',
            'en' => 'activities',
        ],
        'publicar' => [
            'archivo' => 'evento-nuevo.php',
            'es' => 'publicar-una-actividad',
            'en' => 'publish-an-activity',
        ],
        'como-funciona' => [
            'archivo' => 'como-funciona.php',
            'es' => 'como-funciona',
            'en' => 'how-it-works',
        ],
        'faq' => [
            'archivo' => 'preguntas-frecuentes.php',
            'es' => 'preguntas-frecuentes',
            'en' => 'frequently-asked-questions',
        ],
        'contacto' => [
            'archivo' => 'contacto.php',
            'es' => 'contacto',
            'en' => 'contact',
        ],
        'terminos' => [
            'archivo' => 'terminos-y-condiciones.php',
            'es' => 'terminos-y-condiciones',
            'en' => 'terms-and-conditions',
        ],
        'privacidad' => [
            'archivo' => 'aviso-de-privacidad.php',
            'es' => 'aviso-de-privacidad',
            'en' => 'privacy-notice',
        ],
        'cookies' => [
            'archivo' => 'politica-de-cookies.php',
            'es' => 'politica-de-cookies',
            'en' => 'cookie-policy',
        ],
        'blog' => [
            'archivo' => 'blog.php',
            'es' => 'blog',
            'en' => 'blog',   // se escribe igual en los dos idiomas
        ],
    ];
}

/**
 * La dirección completa de una página, en el idioma que se pida.
 *
 * Se usa en TODOS los enlaces internos del menú y el pie. Sin argumento de
 * idioma devuelve el de la página actual, que es lo que hace que navegar en
 * inglés no te devuelva al español a la primera.
 */
function url(string $clave, ?string $idioma = null): string
{
    $idioma = $idioma ?? idiomaActual();
    $ruta   = rutasSitio()[$clave] ?? null;

    if ($ruta === null) return URL_BASE . '/';

    // Sin versión en este idioma: al inicio de ese idioma, que es lo más
    // cercano que existe.
    if (!isset($ruta[$idioma])) {
        return URL_BASE . '/' . ($idioma === IDIOMA_POR_DEFECTO ? '' : $idioma);
    }

    $trozo = $ruta[$idioma];

    return URL_BASE . '/' . $trozo;
}

/**
 * La misma página en la que estamos, pero en el otro idioma.
 *
 * Es lo que necesita el selector: el requerimiento pide expresamente que
 * cambiar de idioma NO devuelva al inicio mientras exista la página
 * equivalente.
 */
function urlEquivalente(string $idioma): string
{
    $clave = $GLOBALS['rutaActual'] ?? null;

    // Fuera del enrutado —un .php abierto directo, una ficha de actividad—
    // no hay equivalencia declarada. Al inicio del idioma pedido.
    if ($clave === null || !isset(rutasSitio()[$clave])) {
        return URL_BASE . '/' . ($idioma === IDIOMA_POR_DEFECTO ? '' : $idioma);
    }

    return url($clave, $idioma);
}

/**
 * El texto de una clave, en el idioma actual —o en el que se le pida—.
 *
 * Si falta en inglés cae al español en vez de enseñar la clave en crudo. Un
 * "pie.legal" suelto en mitad del pie es peor que la frase en español: el
 * visitante no entiende qué pasó, y quien programa tampoco se entera si no
 * mira esa página. El aviso va al log, que es donde sí se busca.
 *
 * El segundo argumento es para lo poco que necesita un idioma fijo en vez del
 * de la página actual —hoy, los correos: no tienen mecanismo de idioma
 * propio, así que van siempre en español pase lo que pase con la petición
 * que los dispara. Ver motivosContacto() en includes/contacto.php.
 */
function t(string $clave, ?string $idiomaForzado = null): string
{
    static $catalogos = [];

    $idioma = $idiomaForzado ?? idiomaActual();

    if (!isset($catalogos[$idioma])) {
        $archivo = __DIR__ . '/idiomas/' . $idioma . '.php';
        $catalogos[$idioma] = is_file($archivo) ? require $archivo : [];
    }

    if (isset($catalogos[$idioma][$clave])) {
        return $catalogos[$idioma][$clave];
    }

    if ($idioma !== IDIOMA_POR_DEFECTO) {
        if (!isset($catalogos[IDIOMA_POR_DEFECTO])) {
            $catalogos[IDIOMA_POR_DEFECTO] = require __DIR__ . '/idiomas/' . IDIOMA_POR_DEFECTO . '.php';
        }
        if (isset($catalogos[IDIOMA_POR_DEFECTO][$clave])) {
            error_log("Falta la traducción de «{$clave}» en $idioma");
            return $catalogos[IDIOMA_POR_DEFECTO][$clave];
        }
    }

    error_log("Clave de traducción inexistente: «{$clave}»");

    return $clave;
}

/** Igual que t(), pero ya escapado para HTML. Ahorra e(t('...')) por todas partes. */
function et(string $clave, ?string $idiomaForzado = null): string
{
    return e(t($clave, $idiomaForzado));
}
