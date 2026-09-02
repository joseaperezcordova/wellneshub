<?php
/**
 * Carga la configuración local y arranca la sesión.
 *
 * Todo punto de entrada empieza por aquí.
 */

declare(strict_types=1);

/*
 * INCLÚYELO SIEMPRE CON require_once. NUNCA CON require.
 *
 * Este archivo declara funciones, así que cargarlo dos veces es un error fatal
 * —«Cannot redeclare detectarUrlBase()»— y un 500 sin nada en pantalla.
 *
 * Y cargarlo dos veces es lo normal desde que hay enrutador: router.php empieza
 * por aquí para poder resolver la dirección, y después incluye la página, que
 * también empieza por aquí porque tiene que seguir funcionando cuando se la
 * llama directa (/buscar.php) o desde otra (evento.php desde el router). Con
 * `require` a secas, TODAS las direcciones limpias devolvían 500 y solo
 * funcionaban las que terminan en .php. Así estuvo desde que se introdujo el
 * enrutador hasta que se vio en el servidor.
 *
 * NO SE PUEDE ARREGLAR CON UNA GUARDA AQUÍ DENTRO. Se intentó:
 *
 *     if (defined('OMDARA_ARRANCADO')) return;
 *
 * y no sirve. PHP declara las funciones de un archivo al COMPILARLO, que es
 * antes de ejecutar su primera línea, así que el error fatal ya ha ocurrido
 * cuando ese `return` tendría su turno. La única defensa está en quien incluye,
 * y por eso hay una prueba que recorre todos los puntos de entrada y falla si
 * alguno vuelve a escribir `require`.
 */

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

/* Va antes que los demás: url(), t() y idiomaActual() los usan las plantillas
   de layout.php, y layout.php lo carga cualquier página. */
require_once __DIR__ . '/idioma.php';
/* Igual que el anterior: seccionVisible() lo consultan layout.php y la portada,
   así que tiene que estar cargado antes que cualquier plantilla. */
require_once __DIR__ . '/secciones.php';
/* Igual: layout.php lo consulta en la cabecera de todas las páginas. */
require_once __DIR__ . '/consentimiento.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/correo.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/subidas.php';
require_once __DIR__ . '/mapa.php';
require_once __DIR__ . '/captcha.php';
require_once __DIR__ . '/moderacion.php';
require_once __DIR__ . '/contacto.php';
require_once __DIR__ . '/metricas.php';
require_once __DIR__ . '/documentacion.php';

/** Escapa para HTML. Se usa en todas las plantillas, de ahí el nombre corto. */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * ¿Es una dirección privada, de bucle local o reservada?
 *
 * Sirve para reconocer al proxy que hay delante, que siempre se ve desde dentro
 * de la red.
 */
function esIpInterna(string $ip): bool
{
    return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

/**
 * La IP de quien visita, contando con el nginx que hay delante de Apache.
 *
 * Esto importa más de lo que parece. Con un proxy por medio, REMOTE_ADDR es la
 * dirección del proxy —127.0.0.1— para TODO el mundo. Cualquier límite "por IP"
 * pasa entonces a ser un límite para el sitio entero: el primero que pide un
 * código de acceso deja a los demás sin poder pedirlo, y un solo reporte de un
 * evento bloquearía los de todos los demás visitantes.
 *
 * Solo se hace caso a X-Forwarded-For si la petición viene de dentro. Creerla
 * siempre sería peor que no mirarla: cualquiera podría escribir la IP que
 * quisiera en la cabecera y saltarse todos los límites de golpe.
 *
 * Se lee de derecha a izquierda porque la última dirección de la lista la añade
 * el proxy más cercano —el nuestro, del que nos fiamos—, mientras que las
 * anteriores las puede haber escrito el propio cliente.
 */
function ipCliente(): string
{
    $remota = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    if (!esIpInterna($remota)) return $remota;

    $lista = explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));

    for ($i = count($lista) - 1; $i >= 0; $i--) {
        $ip = trim($lista[$i]);
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) && !esIpInterna($ip)) {
            return $ip;
        }
    }

    return $remota;
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

/**
 * La dirección pública de una actividad: /actividad/{slug} (REQ-00006).
 *
 * Antes era /evento.php?id=7. La limpia se comparte mejor, dice de qué va antes
 * de abrirla y sobrevive a que el archivo se renombre. La resuelve router.php.
 *
 * EL ID VA DENTRO DEL SLUG, Y ESO ES LO QUE HACE QUE ESTO SEA BARATO
 *
 * generarSlug() termina siempre en «-{id}», así que la dirección se puede leer
 * al revés: del slug se saca el id sin consultar la base, sin índice nuevo y sin
 * que dos actividades con el mismo título se estorben —«Yoga al amanecer» cada
 * sábado es un caso normal, no un error—.
 *
 * Si el título cambia, el slug cambia y la dirección vieja sigue llegando al
 * mismo sitio: evento.php la reconoce y redirige a la nueva con un 301.
 *
 * Vive aquí y no en eventos.php porque la usa includes/contacto.php, que se
 * carga siempre, y eventos.php solo lo cargan las páginas que lo necesitan.
 */
function urlEvento(array $ev, ?string $idioma = null): string
{
    $slug = trim((string) ($ev['slug'] ?? ''));

    // Actividades anteriores al slug, o guardadas a medias: la dirección sigue
    // siendo válida porque lo único que hace falta leer es el número del final.
    if ($slug === '') $slug = 'actividad-' . (int) $ev['id'];

    // El prefijo cambia con el idioma —'actividad' o 'activity' (REQ-00002
    // fase 5)—, pero el slug en sí se queda igual en los dos: es el título
    // en español, que es lo único que se guarda.
    $idioma   = $idioma ?? idiomaActual();
    $prefijo  = $idioma === 'en' ? 'activity' : 'actividad';

    return URL_BASE . '/' . $prefijo . '/' . rawurlencode($slug);
}

/**
 * Contactar al organizador, reportar una actividad o editarla: tres páginas
 * que actúan sobre una actividad por su id numérico (REQ-00002 fase con
 * rutas /en). A diferencia de urlEvento(), aquí no hace falta el slug —no son
 * direcciones para compartir ni indexar—, así que el número basta.
 */
function urlContactar(array $ev, ?string $idioma = null): string
{
    $idioma = $idioma ?? idiomaActual();

    return URL_BASE . '/' . ($idioma === 'en' ? 'contact-organizer' : 'contactar') . '/' . (int) $ev['id'];
}

function urlReportar(array $ev, ?string $idioma = null): string
{
    $idioma = $idioma ?? idiomaActual();

    return URL_BASE . '/' . ($idioma === 'en' ? 'report' : 'reportar') . '/' . (int) $ev['id'];
}

function urlEditarEvento(array $ev, ?string $idioma = null): string
{
    $idioma = $idioma ?? idiomaActual();

    return URL_BASE . '/' . ($idioma === 'en' ? 'edit-activity' : 'editar-actividad') . '/' . (int) $ev['id'];
}

/**
 * Redirige y corta la ejecución.
 *
 * Acepta una ruta relativa a la raíz del sitio —'/logout.php'— o una
 * dirección completa, que es lo que devuelven url() y urlEvento(). Sin esto,
 * cada sitio que manda a otra página tendría que volver a montar la
 * cabecera Location a mano.
 */
function redirigir(string $ruta): void
{
    $destino = preg_match('#^https?://#i', $ruta) ? $ruta : URL_BASE . $ruta;

    header('Location: ' . $destino);
    exit;
}

/**
 * Manda un 301 de la dirección .php directa a la limpia (punto 7 de
 * docs/pendientes.md). Los .php siguen existiendo y sirviendo la página —el
 * .htaccess solo reescribe lo que NO es un archivo real—, así que sin esto
 * /como-funciona.php y /como-funciona conviven indefinidamente como dos
 * direcciones para lo mismo.
 *
 * SOLO EN GET, igual que la misma redirección en evento.php: un POST
 * redirigido se convierte en GET y pierde lo enviado, así que un formulario
 * que postea contra su propia página —login.php, contacto.php, el alta de
 * actividad...— seguiría funcionando mientras se sirva por el .php.
 *
 * $urlCanonica ya viene resuelta —url('clave') para las páginas fijas,
 * urlContactar($ev) y compañía para las que dependen de un id— porque la
 * hace falta calcular distinto según la página, y esta función no tiene por
 * qué saber cómo.
 *
 * $conservarConsulta se apaga para las páginas cuya dirección .php recibe el
 * id por «?id=», como contactar.php o reportar.php: ese «?id=7» ya pasó a
 * formar parte de la ruta limpia (/contactar/7), y conservarlo pegaría un
 * «?id=7» sobrante detrás. Las páginas fijas si lo necesitan —un
 * /buscar.php?cat=Yoga tiene que llegar a /actividades?cat=Yoga con el
 * filtro puesto, no a secas—.
 */
function redirigirSiEsDirecto(string $urlCanonica, bool $conservarConsulta = true): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;

    $rutaPedida   = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $rutaCanonica = (string) parse_url($urlCanonica, PHP_URL_PATH);

    if ($rutaPedida === $rutaCanonica) return;

    $consulta = $conservarConsulta
        ? (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY)
        : '';

    header('Location: ' . $urlCanonica . ($consulta !== '' ? '?' . $consulta : ''), true, 301);
    exit;
}
