<?php
/**
 * Subida de imágenes de eventos.
 *
 * Un formulario que acepta archivos es la puerta más grande que tiene un sitio,
 * así que aquí no se da nada por bueno:
 *
 *   · El tipo no se cree lo que dice el navegador. $_FILES['type'] lo manda el
 *     cliente y se pone lo que quiera; se comprueba abriendo el archivo.
 *   · El nombre no se reutiliza. Un "foto.php.jpg" o un nombre con ../ dentro
 *     son el camino clásico para escribir donde no se debe. Se inventa uno.
 *   · La carpeta de destino no ejecuta nada, por si algo se colara igualmente
 *     (ver el .htaccess que hay dentro).
 */

declare(strict_types=1);

const IMAGEN_MAX_BYTES  = 4 * 1024 * 1024;  // 4 MB
const IMAGEN_MAX_ANCHO  = 1600;             // se reduce lo más grande
const IMAGEN_CARPETA    = 'assets/eventos';

/** Tipos que aceptamos, y la extensión con la que se guardan. */
function imagenTiposAceptados(): array
{
    return [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
}

/**
 * Valida y guarda el archivo. Devuelve la ruta relativa para la base de datos.
 *
 * @return array{0:bool,1:?string} [ok, ruta relativa o mensaje de error]
 */
function guardarImagenSubida(array $archivo): array
{
    $codigo = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($codigo === UPLOAD_ERR_NO_FILE) {
        return [true, null];   // no subió nada, y no pasa nada
    }

    if ($codigo === UPLOAD_ERR_INI_SIZE || $codigo === UPLOAD_ERR_FORM_SIZE) {
        return [false, 'La imagen pesa demasiado. El máximo son ' . round(IMAGEN_MAX_BYTES / 1048576) . ' MB.'];
    }

    if ($codigo !== UPLOAD_ERR_OK) {
        error_log('Subida fallida, código ' . $codigo);
        return [false, 'No se pudo subir la imagen. Inténtalo otra vez.'];
    }

    $tmp = (string) ($archivo['tmp_name'] ?? '');

    // Que el archivo venga de verdad de una subida HTTP y no sea una ruta del
    // servidor colada en el formulario.
    if (!is_uploaded_file($tmp)) {
        return [false, 'Archivo no válido.'];
    }

    if (filesize($tmp) > IMAGEN_MAX_BYTES) {
        return [false, 'La imagen pesa demasiado. El máximo son ' . round(IMAGEN_MAX_BYTES / 1048576) . ' MB.'];
    }

    // getimagesize abre el archivo y mira su contenido. Un .php renombrado a
    // .jpg no pasa de aquí.
    $info = @getimagesize($tmp);
    if ($info === false) {
        return [false, 'Ese archivo no es una imagen.'];
    }

    $tipos = imagenTiposAceptados();
    $tipo  = (int) $info[2];

    if (!isset($tipos[$tipo])) {
        return [false, 'Formato no admitido. Usa JPG, PNG o WebP.'];
    }

    $carpeta = dirname(__DIR__) . '/' . IMAGEN_CARPETA;

    if (!is_dir($carpeta) && !@mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
        error_log('No se pudo crear ' . $carpeta);
        return [false, 'No se pudo guardar la imagen en el servidor.'];
    }

    if (!is_writable($carpeta)) {
        error_log('Carpeta de imágenes sin permiso de escritura: ' . $carpeta);
        return [false, 'El servidor no tiene permiso para guardar imágenes. Avisa al administrador.'];
    }

    // Nombre inventado: ni rastro del original. Además evita que dos personas
    // que suban "portada.jpg" se pisen la una a la otra.
    $nombre  = date('Ym') . '-' . bin2hex(random_bytes(10)) . '.' . $tipos[$tipo];
    $destino = $carpeta . '/' . $nombre;

    if (!move_uploaded_file($tmp, $destino)) {
        error_log('move_uploaded_file falló hacia ' . $destino);
        return [false, 'No se pudo guardar la imagen.'];
    }

    @chmod($destino, 0644);
    reducirImagen($destino, $tipo);

    return [true, IMAGEN_CARPETA . '/' . $nombre];
}

/**
 * Reduce la imagen si es más ancha de la cuenta.
 *
 * Una foto de móvil son 4000 píxeles y varios megas para enseñarse en una
 * tarjeta de 300. Reducirla ahorra ancho de banda a quien visita —que a menudo
 * está en datos móviles— y espacio en un hosting compartido.
 *
 * Si el servidor no trae GD, la imagen se queda tal cual: mejor grande que
 * ninguna, y el tamaño ya está limitado por IMAGEN_MAX_BYTES.
 */
function reducirImagen(string $ruta, int $tipo): void
{
    if (!function_exists('imagecreatetruecolor')) return;

    $lector = [
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG  => 'imagecreatefrompng',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
    ][$tipo] ?? null;

    if ($lector === null || !function_exists($lector)) return;

    $info = @getimagesize($ruta);
    if ($info === false || $info[0] <= IMAGEN_MAX_ANCHO) return;

    $origen = @$lector($ruta);
    if (!$origen) return;

    $ancho = IMAGEN_MAX_ANCHO;
    $alto  = (int) round($info[1] * ($ancho / $info[0]));

    $destino = imagecreatetruecolor($ancho, $alto);

    // Sin esto, un PNG con transparencia sale con el fondo negro.
    if ($tipo === IMAGETYPE_PNG || $tipo === IMAGETYPE_WEBP) {
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
    }

    imagecopyresampled($destino, $origen, 0, 0, 0, 0, $ancho, $alto, $info[0], $info[1]);

    switch ($tipo) {
        case IMAGETYPE_JPEG: imagejpeg($destino, $ruta, 82); break;
        case IMAGETYPE_PNG:  imagepng($destino, $ruta, 6);   break;
        case IMAGETYPE_WEBP: imagewebp($destino, $ruta, 82); break;
    }

    imagedestroy($origen);
    imagedestroy($destino);
}

/* ---------------------------------------------------------------------------
 * Imágenes «en vuelo»: subidas que todavía no son de ningún evento.
 *
 * Cuando alguien rellena la ficha, elige una foto y el formulario falla por otra
 * cosa —la descripción corta, una fecha pasada—, la foto se perdía y había que
 * buscarla otra vez en el disco. El navegador no puede volver a poner un archivo
 * en un <input type="file"> por seguridad, así que la única manera de conservarla
 * es guardarla ya y que el formulario se acuerde de ella.
 *
 * El formulario la recuerda en un campo oculto, y ese campo lo escribe el
 * navegador: no se puede creer sin más. Si solo se comprobara que la ruta está
 * dentro de la carpeta, cualquiera podría poner ahí la imagen de OTRO evento y
 * conseguir que se la borráramos al subir la suya.
 *
 * Por eso la lista de lo que está en vuelo vive en la sesión. Solo se acepta
 * —y solo se borra— lo que subió esta misma persona en este mismo rato.
 * ------------------------------------------------------------------------- */

const IMAGENES_EN_VUELO_MAX = 20;

function recordarImagenEnVuelo(string $relativa): void
{
    $_SESSION['imagenes_en_vuelo'][$relativa] = time();

    // Sin tope, un rato largo peleándose con el formulario llena la sesión.
    while (count($_SESSION['imagenes_en_vuelo']) > IMAGENES_EN_VUELO_MAX) {
        array_shift($_SESSION['imagenes_en_vuelo']);
    }
}

function esImagenEnVuelo(?string $relativa): bool
{
    return $relativa !== null && isset($_SESSION['imagenes_en_vuelo'][$relativa]);
}

function olvidarImagenEnVuelo(?string $relativa): void
{
    if ($relativa !== null) unset($_SESSION['imagenes_en_vuelo'][$relativa]);
}

/**
 * La imagen que el formulario dice traer puesta, si de verdad puede traerla.
 *
 * Vale si es la que ya tiene el evento, o si la subió esta persona en un intento
 * anterior. Cualquier otra cosa se ignora en silencio: no es un ataque que haya
 * que contarle a nadie, es un campo manipulado.
 */
function imagenArrastrada($valor, ?string $propia): ?string
{
    if (!is_string($valor) || $valor === '') return null;
    if ($valor === $propia)                  return $propia;
    if (!esImagenEnVuelo($valor))            return null;

    // Pudo borrarla la limpieza de huérfanas mientras el formulario estaba abierto.
    return is_file(dirname(__DIR__) . '/' . $valor) ? $valor : null;
}

/** Suelta una subida de un intento anterior. Nunca la que ya tiene el evento. */
function soltarImagenTemporal(?string $ruta, ?string $propia): void
{
    if ($ruta === null || $ruta === $propia || !esImagenEnVuelo($ruta)) return;

    borrarImagenGuardada($ruta);
    olvidarImagenEnVuelo($ruta);
}

/**
 * Con qué imagen se queda la ficha en este envío.
 *
 * Lo comparten el alta y la edición porque las reglas son las mismas, y cuando
 * estaban escritas dos veces no coincidían: en el alta la imagen se borraba al
 * fallar y en la edición se volvía a la anterior.
 *
 * @param  ?string $propia  la que ya tiene guardada el evento (null si es un alta)
 * @return array{0:?string,1:?string} [ruta para la ficha, error o null]
 */
function imagenDelFormulario(array $post, array $files, ?string $propia): array
{
    $arrastrada = imagenArrastrada($post['imagen_previa'] ?? null, $propia);

    [$ok, $nueva] = guardarImagenSubida($files['imagen'] ?? []);

    // El archivo nuevo no valía. Se conserva lo que hubiera antes: el mensaje ya
    // dice qué pasó, y quitarle además la foto anterior sería castigar dos veces.
    if (!$ok) return [$arrastrada ?? $propia, (string) $nueva];

    if ($nueva !== null) {
        recordarImagenEnVuelo($nueva);
        soltarImagenTemporal($arrastrada, $propia);   // la tentativa anterior sobra
        return [$nueva, null];
    }

    if (!empty($post['quitar_imagen'])) {
        soltarImagenTemporal($arrastrada, $propia);
        return [null, null];
    }

    // No mandó archivo: se queda como estaba.
    return [$arrastrada ?? $propia, null];
}

/**
 * Barre las imágenes que ya no son de nadie.
 *
 * Salen de dos sitios: fichas que se empezaron con foto y nunca se terminaron, y
 * —hasta que eliminarEvento() se encargó— eventos borrados. Nada volvía a
 * apuntar a esos archivos y nadie sabía que estaban ahí.
 *
 * Solo se miran archivos de más de $horas para no pisar una subida que está en
 * mitad de un formulario abierto, y como mucho 200 por pasada, que es de sobra
 * para el ritmo de este sitio y acota la consulta.
 */
function limpiarImagenesHuerfanas(int $horas = 24): int
{
    $carpeta = dirname(__DIR__) . '/' . IMAGEN_CARPETA;
    if (!is_dir($carpeta)) return 0;

    $limite    = time() - $horas * 3600;
    $candidatas = [];

    foreach ((array) @scandir($carpeta) as $nombre) {
        if ($nombre === '.' || $nombre === '..' || $nombre[0] === '.') continue;

        $ruta = $carpeta . '/' . $nombre;
        if (!is_file($ruta) || @filemtime($ruta) > $limite) continue;

        $candidatas[IMAGEN_CARPETA . '/' . $nombre] = $ruta;
        if (count($candidatas) >= 200) break;
    }

    if (!$candidatas) return 0;

    try {
        $marcas = implode(',', array_fill(0, count($candidatas), '?'));
        $st = db()->prepare('SELECT imagen_url FROM eventos WHERE imagen_url IN (' . $marcas . ')');
        $st->execute(array_keys($candidatas));

        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $enUso) {
            unset($candidatas[$enUso]);
        }
    } catch (Throwable $ex) {
        // Si la consulta falla no se borra nada: mejor dejar basura que llevarse
        // por delante la foto de un evento publicado.
        error_log('No se pudieron listar las imágenes en uso: ' . $ex->getMessage());
        return 0;
    }

    foreach ($candidatas as $ruta) @unlink($ruta);

    return count($candidatas);
}

/** Borra una imagen guardada. Se le pasa la ruta relativa de la base. */
function borrarImagenGuardada(?string $relativa): void
{
    if ($relativa === null || strpos($relativa, IMAGEN_CARPETA . '/') !== 0) return;

    // Ni rastro de ../ antes de borrar nada.
    if (strpos($relativa, '..') !== false) return;

    $absoluta = dirname(__DIR__) . '/' . $relativa;
    if (is_file($absoluta)) @unlink($absoluta);
}

/**
 * ¿Se pasó el POST del límite del servidor?
 *
 * Cuando la subida excede post_max_size, PHP descarta el cuerpo entero: $_POST
 * y $_FILES llegan vacíos y el formulario parece haberse enviado en blanco. Sin
 * detectarlo, el usuario ve "la sesión caducó" —porque tampoco llega el CSRF— y
 * no hay manera de que adivine que la culpa era del peso de la foto.
 */
function postDesbordado(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && empty($_POST)
        && empty($_FILES)
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}
