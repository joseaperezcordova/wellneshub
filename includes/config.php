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

/** @var array $CONFIG */
$CONFIG = require $rutaLocal;
$CONFIG['es_local'] = $esLocal;

define('URL_BASE', rtrim($CONFIG['url_base'], '/'));

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
require_once __DIR__ . '/auth.php';

/** Escapa para HTML. Se usa en todas las plantillas, de ahí el nombre corto. */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirige y corta la ejecución. */
function redirigir(string $ruta): void
{
    header('Location: ' . URL_BASE . $ruta);
    exit;
}
