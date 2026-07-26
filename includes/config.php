<?php
/**
 * Carga la configuración local y arranca la sesión.
 *
 * Todo punto de entrada empieza por aquí.
 */

declare(strict_types=1);

/*
 * Código escrito para PHP 7.4, que es lo que trae el XAMPP de esta máquina.
 * Corre igual en 8.x, así que no depende de qué versión tenga el hosting; si
 * usara sintaxis de 8 (match, str_starts_with, never) reventaría en local.
 */

// En producción los errores no se enseñan al visitante: un aviso de PHP puede
// revelar rutas del servidor y nombres de tabla. Se registran, no se pintan.
$host    = $_SERVER['HTTP_HOST'] ?? '';
$esLocal = in_array($host, ['localhost', '127.0.0.1'], true)
        || strpos($host, 'localhost:') === 0
        || strpos($host, '127.0.0.1:') === 0;

error_reporting(E_ALL);
ini_set('display_errors', $esLocal ? '1' : '0');
ini_set('log_errors', '1');

$rutaLocal = __DIR__ . '/config.local.php';
if (!is_file($rutaLocal)) {
    http_response_code(500);
    exit('Falta includes/config.local.php. Copia config.local.example.php y rellénalo.');
}

/*
 * El require va envuelto en un búfer de salida a propósito.
 *
 * Si config.local.php tiene aunque sea un espacio o una línea suelta antes de
 * "<?php" —lo típico al copiar y pegar desde un editor web—, ese texto se
 * imprime al principio de TODAS las páginas. Y como sale antes que cualquier
 * cabecera, session_start() y las redirecciones dejan de funcionar sin decir
 * por qué: el sitio se queda sin sesión y nada indica la causa.
 *
 * Aquí esa salida accidental se traga y se registra, en vez de romper el sitio.
 */
ob_start();
/** @var array $CONFIG */
$CONFIG = require $rutaLocal;
$basura = ob_get_clean();

if ($basura !== '' && trim($basura) !== '') {
    error_log('config.local.php imprime texto antes de <?php (se ha ignorado): '
        . substr(trim($basura), 0, 200));
}

$CONFIG['es_local'] = $esLocal;

/**
 * Deduce la URL base de la propia petición.
 *
 * Existe porque copiar el config.local.php de local al servidor y olvidar
 * cambiar 'url_base' deja el sitio publicado generando enlaces a localhost:
 * el CSS no carga, los enlaces no van a ningún sitio y la página parece rota
 * sin ningún error que lo explique. Con esto, dejarlo vacío es la opción
 * correcta y no hay nada que olvidar.
 */
function detectarUrlBase(): string
{
    // Detrás de un proxy que termina el TLS (aquí hay nginx delante de Apache),
    // $_SERVER['HTTPS'] llega vacío aunque el visitante venga por https.
    $esquema = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $esquema = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
    } elseif (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        $esquema = 'https';
    } elseif (($_SERVER['SERVER_PORT'] ?? '') === '443') {
        $esquema = 'https';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Carpeta desde la que se sirve la aplicación: '' en la raíz del dominio,
    // '/wellneshub' bajo XAMPP.
    $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    $dir = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');

    return $esquema . '://' . $host . $dir;
}

$urlBase = trim((string) ($CONFIG['url_base'] ?? ''));
define('URL_BASE', $urlBase !== '' ? rtrim($urlBase, '/') : detectarUrlBase());

/**
 * Sesión.
 *
 * httponly  → el JavaScript de la página no puede leer la cookie, así que un
 *             XSS no se lleva la sesión de regalo.
 * samesite  → 'Lax' y no 'Strict' porque el callback de Google llega desde otro
 *             dominio: con 'Strict' el navegador no manda la cookie de vuelta y
 *             se pierde el "state", que es justo lo que valida el callback.
 * secure    → solo bajo HTTPS. En local (http) activarlo impediría entrar.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_name('wh_sesion');
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/correo.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/subidas.php';

/** Escapa para HTML. Se usa en todas las plantillas, de ahí el nombre corto. */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * La URL de un archivo estático, con la fecha del archivo pegada detrás.
 *
 * Delante de este Apache hay un nginx que cachea, y un CSS servido desde su
 * caché puede quedarse semanas obsoleto: la página llega con el HTML nuevo y
 * los estilos viejos, que es lo peor de los dos mundos —parece un fallo de
 * diseño y no de caché, así que se busca donde no está.
 *
 * Con la marca de tiempo en la URL, publicar un cambio cambia la dirección, y
 * ni nginx ni el navegador tienen nada que reutilizar. Y como la dirección solo
 * cambia cuando cambia el archivo, se puede seguir cacheando todo lo demás.
 */
function assetUrl(string $ruta): string
{
    $absoluta = dirname(__DIR__) . '/' . ltrim($ruta, '/');
    $version  = is_file($absoluta) ? (string) filemtime($absoluta) : '0';

    return URL_BASE . '/' . ltrim($ruta, '/') . '?v=' . $version;
}

/** Redirige y corta la ejecución. */
function redirigir(string $ruta): void
{
    header('Location: ' . URL_BASE . $ruta);
    exit;
}
