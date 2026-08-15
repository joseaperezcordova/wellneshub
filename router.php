<?php
/**
 * Resuelve las direcciones limpias y bilingües.
 *
 * El .htaccess manda aquí todo lo que NO sea un archivo que exista de verdad,
 * asi que /actividades y /activities llegan a este punto mientras que
 * /buscar.php o /assets/css/app.css se sirven directos y no lo pisan.
 *
 * POR QUÉ UN ENRUTADOR Y NO UNA REGLA POR PÁGINA
 *
 * Antes había una RewriteRule por dirección. Con dos idiomas serían veinte, y
 * el mapa de direcciones acabaría escrito dos veces: una en .htaccess y otra
 * en PHP para poder generar los enlaces y el hreflang. Dos copias que hay que
 * acordarse de cambiar a la vez son dos copias que un día dicen cosas
 * distintas.
 *
 * Con esto, rutasSitio() es la única fuente y el .htaccess solo tiene una
 * regla que no vuelve a tocarse.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

/*
 * La ruta pedida, sin la carpeta desde la que se sirve el sitio.
 *
 * En el hosting el sitio cuelga de la raíz y esto no quita nada; bajo XAMPP
 * cuelga de /wellneshub y hay que descontarlo, o ninguna dirección coincidiría
 * en local. Sale de URL_BASE para no escribir la carpeta a mano.
 */
$ruta = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$base = (string) parse_url(URL_BASE, PHP_URL_PATH);

if ($base !== '' && $base !== '/' && strpos($ruta, $base) === 0) {
    $ruta = substr($ruta, strlen($base));
}

$ruta = trim($ruta, '/');

/*
 * Buscar la dirección en el mapa. Se recorre entero porque una misma cadena
 * puede ser de un idioma o de otro —"blog" vale para los dos— y hay que saber
 * en cuál se encontró: de ahí sale el idioma de toda la petición.
 */
$claveEncontrada = null;
$idiomaEncontrado = IDIOMA_POR_DEFECTO;

foreach (rutasSitio() as $clave => $destino) {
    /*
     * Una sección oculta (REQ-00004) deja de resolver: /blog da 404 igual que
     * cualquier dirección inventada. Quitar el enlace del menú y dejar la
     * dirección abierta no es ocultar —se sigue compartiendo, se sigue
     * indexando—, y la clave de ruta y la de sección son la misma palabra a
     * propósito, para que esto no necesite una tabla de equivalencias.
     */
    if (!seccionVisible($clave)) continue;

    foreach (idiomasDisponibles() as $idioma) {
        if (isset($destino[$idioma]) && $destino[$idioma] === $ruta) {
            $claveEncontrada  = $clave;
            $idiomaEncontrado = $idioma;
            break 2;
        }
    }
}

if ($claveEncontrada === null) {
    /*
     * Nada coincide. Un 404 de verdad —código incluido— y no una redirección
     * al inicio: mandar al inicio le dice a Google que esa dirección existe y
     * la deja en el índice para siempre.
     */
    http_response_code(404);

    $titulo = 'Página no encontrada';
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>Esta página no existe</h1>'
       . '<p class="sub">Puede que el enlace esté mal escrito o que la página se haya movido.</p>'
       . '<a class="btn-principal" style="text-decoration:none; display:block; text-align:center;"'
       . ' href="' . e(URL_BASE) . '/">Ir al inicio</a></div>';
    pie();
    exit;
}

/*
 * Estas dos variables las lee todo lo demás: idiomaActual() para elegir
 * catálogo, y urlEquivalente() para saber a qué página lleva el selector de
 * idioma. Van en $GLOBALS y no como argumentos porque las consultan funciones
 * llamadas desde plantillas, a las que no se les puede pasar contexto.
 */
$GLOBALS['idioma']     = $idiomaEncontrado;
$GLOBALS['rutaActual'] = $claveEncontrada;

require __DIR__ . '/' . rutasSitio()[$claveEncontrada]['archivo'];
