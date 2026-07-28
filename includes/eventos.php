<?php
/**
 * Eventos: catálogo de categorías, consultas, validación y quién puede tocar qué.
 *
 * Todo lo que sabe la aplicación sobre eventos pasa por aquí. Las páginas se
 * limitan a pedir datos y a pintar; ninguna arma SQL por su cuenta ni decide
 * permisos a ojo, porque esa es la clase de regla que acaba escrita de tres
 * maneras distintas y con una de ellas mal.
 */

declare(strict_types=1);

/**
 * Margen que tiene el organizador para corregir su evento después de
 * publicarlo. Pasado ese plazo la ficha se congela y hay que pedirle el cambio
 * al administrador.
 */
const EVENTO_MARGEN_EDICION_H = 24;

/**
 * Las categorías, con el icono que usa el menú de la portada.
 *
 * Esta lista es la única fuente: el menú lineal de la portada se dibuja desde
 * aquí y el desplegable del formulario también. Cuando estaban escritas en dos
 * sitios, una categoría nueva aparecía en el menú y no se podía elegir al
 * publicar, o al revés.
 */
function categoriasMenu(): array
{
    // La clave es el nombre que se guarda en la base. La etiqueta es lo que se
    // lee en el menú, y va en plural porque ahí se está eligiendo un montón de
    // eventos, no uno. Guardar el plural sería peor: "Retiros" como categoría
    // de un solo evento chirría en la ficha.
    return [
        'Yoga'           => ['🧘',  'Yoga'],
        'Meditación'     => ['🌿',  'Meditación'],
        'Retiro'         => ['🏕️',  'Retiros'],
        'Breathwork'     => ['🌬️',  'Breathwork'],
        'Sound Healing'  => ['🎐',  'Sound Healing'],
        'Ceremonia'      => ['🔥',  'Ceremonias'],
        'Festival'       => ['☀️',  'Festivales'],
        'Temazcal'       => ['♨️',  'Temazcal'],
        'Cacao'          => ['🍫',  'Cacao'],
        'Ecstatic Dance' => ['💃',  'Ecstatic Dance'],
        'Ice Bath'       => ['🧊',  'Ice Bath'],
        'Pilates'        => ['🌀',  'Pilates'],
        'Biohacking'     => ['⚡',  'Biohacking'],
    ];
}

/** nombre => icono. Para el desplegable del formulario y para la ficha. */
function categorias(): array
{
    $r = [];
    foreach (categoriasMenu() as $nombre => $datos) {
        $r[$nombre] = $datos[0];
    }
    return $r;
}

/** Los colores de la paleta que puede llevar la tarjeta sin imagen. */
function coloresEvento(): array
{
    return ['#89A67D', '#C76E43', '#2F4E5D', '#496B52', '#E9DDC9', '#3E6375'];
}


// ------------------------------------------------------------- permisos ----

function esAdmin(?array $u): bool
{
    return $u !== null && $u['rol'] === 'admin';
}

/**
 * ¿Puede esta persona modificar este evento?
 *
 * El administrador, siempre. El dueño, mientras sea borrador o esté dentro del
 * margen de 24 horas desde que lo publicó.
 *
 * El plazo se calcula sobre publicado_en y no sobre creado_en a propósito: un
 * borrador que estuvo tres días a medias no debe llegar publicado y ya caducado.
 */
function puedeEditarEvento(array $ev, ?array $u): bool
{
    if ($u === null)   return false;
    if (esAdmin($u))   return true;

    if ((int) $ev['usuario_id'] !== (int) $u['id']) return false;
    if ($ev['situacion'] === 'borrador')            return true;

    return minutosRestantesEdicion($ev) > 0;
}

/**
 * Minutos que quedan de margen, o 0 si ya pasó.
 *
 * Sirve para dos cosas: decidir el permiso y avisar en pantalla de cuánto
 * queda, que es lo que evita que alguien descubra el plazo cuando ya expiró.
 */
function minutosRestantesEdicion(array $ev): int
{
    if (empty($ev['publicado_en'])) return 0;

    $limite = strtotime($ev['publicado_en']) + EVENTO_MARGEN_EDICION_H * 3600;
    $quedan = (int) ceil(($limite - time()) / 60);

    return max(0, $quedan);
}

/** ¿Se puede ver esta ficha? Las no publicadas, solo su dueño y el admin. */
function puedeVerEvento(array $ev, ?array $u): bool
{
    if ($ev['situacion'] === 'publicado') return true;

    return $u !== null
        && (esAdmin($u) || (int) $ev['usuario_id'] === (int) $u['id']);
}


// ------------------------------------------------------------- consultas ----

function buscarEvento(int $id): ?array
{
    $st = db()->prepare(
        'SELECT e.*, u.nombre AS organizador, u.email AS organizador_email
           FROM eventos e
           JOIN usuarios u ON u.id = e.usuario_id
          WHERE e.id = ? LIMIT 1'
    );
    $st->execute([$id]);

    return $st->fetch() ?: null;
}

/**
 * La agenda pública: publicados y que no hayan terminado todavía.
 *
 * El corte usa COALESCE(fecha_fin, fecha_inicio) para que un retiro de cinco
 * días siga en cartel mientras dure, en vez de desaparecer el mismo día que
 * empieza, que es cuando más gente lo busca.
 */
function eventosPublicados(?string $categoria = null, int $limite = 60): array
{
    $sql = 'SELECT e.*, u.nombre AS organizador
              FROM eventos e
              JOIN usuarios u ON u.id = e.usuario_id
             WHERE e.situacion = "publicado"
               AND COALESCE(e.fecha_fin, e.fecha_inicio) >= NOW()';

    $params = [];

    if ($categoria !== null && $categoria !== '') {
        $sql .= ' AND e.categoria = ?';
        $params[] = $categoria;
    }

    $sql .= ' ORDER BY e.fecha_inicio ASC LIMIT ' . (int) $limite;

    $st = db()->prepare($sql);
    $st->execute($params);

    return $st->fetchAll();
}

/** Los eventos de una persona, incluidos borradores y pasados. */
function eventosDeUsuario(int $usuarioId): array
{
    $st = db()->prepare(
        'SELECT * FROM eventos WHERE usuario_id = ? ORDER BY fecha_inicio DESC'
    );
    $st->execute([$usuarioId]);

    return $st->fetchAll();
}

/** Todos, para el panel de administración. */
function eventosTodos(int $limite = 200): array
{
    $st = db()->query(
        'SELECT e.*, u.nombre AS organizador
           FROM eventos e
           JOIN usuarios u ON u.id = e.usuario_id
       ORDER BY e.creado_en DESC
          LIMIT ' . (int) $limite
    );

    return $st->fetchAll();
}


// ------------------------------------------------------------ validación ----

/**
 * El nombre visible de cada campo, para poder decir cuál falló.
 *
 * Vive aquí y no en la plantilla porque lo usan las dos páginas —alta y
 * edición— y porque tiene que cuadrar con las claves que devuelve
 * validarEvento(): si se separan, el aviso acaba nombrando un campo que no es.
 */
function etiquetasCampos(): array
{
    return [
        'titulo'       => 'Título del evento',
        'categoria'    => 'Categoría',
        'descripcion'  => 'Descripción',
        'ciudad'       => 'Ciudad',
        'entidad'      => 'Estado',
        'mapa_url'     => 'Enlace de Google Maps',
        'fecha_inicio' => 'Fecha de inicio',
        'fecha_fin'    => 'Fecha de fin',
        'precio'       => 'Precio',
        'url_boletos'  => 'Enlace para comprar o reservar',
        'imagen'       => 'Imagen',
    ];
}

/**
 * Revisa y normaliza lo que llega del formulario.
 *
 * @return array{0:array,1:array} [datos limpios, errores por campo]
 */
function validarEvento(array $in): array
{
    $e = [];
    $errores = [];

    $e['titulo'] = trim((string) ($in['titulo'] ?? ''));
    if (mb_strlen($e['titulo']) < 5) {
        $errores['titulo'] = 'El título necesita al menos 5 caracteres.';
    } elseif (mb_strlen($e['titulo']) > 160) {
        $errores['titulo'] = 'El título no puede pasar de 160 caracteres.';
    }

    $e['descripcion'] = trim((string) ($in['descripcion'] ?? ''));
    if (mb_strlen($e['descripcion']) < 40) {
        $errores['descripcion'] = 'Cuenta un poco más: al menos 40 caracteres.';
    }

    $e['categoria'] = (string) ($in['categoria'] ?? '');
    if (!isset(categorias()[$e['categoria']])) {
        $errores['categoria'] = 'Elige una categoría de la lista.';
    }

    $e['ciudad'] = trim((string) ($in['ciudad'] ?? ''));
    if ($e['ciudad'] === '') $errores['ciudad'] = 'Falta la ciudad.';

    $e['entidad'] = trim((string) ($in['entidad'] ?? ''));
    if ($e['entidad'] === '') $errores['entidad'] = 'Falta el estado.';

    $e['lugar'] = trim((string) ($in['lugar'] ?? '')) ?: null;

    /*
     * El punto en el mapa. Opcional, pero si se pone un enlace tiene que poder
     * leerse: guardarlo sin coordenadas dejaría una ficha con un mapa vacío y
     * nadie se enteraría hasta que alguien fuera a buscar el sitio.
     *
     * El mensaje de error explica el camino entero. Es más largo de lo normal a
     * propósito: aquí falla justo quien no sabe de dónde sacar el enlace bueno,
     * y un «enlace no válido» a secas le deja igual de perdido.
     */
    $mapa = trim((string) ($in['mapa_url'] ?? ''));

    $e['mapa_url'] = null;
    $e['latitud']  = null;
    $e['longitud'] = null;

    if ($mapa !== '') {
        if (mb_strlen($mapa) > 500) {
            $errores['mapa_url'] = 'Ese enlace es larguísimo. Copia el que da el botón «Compartir» de Google Maps.';
        } else {
            $e['mapa_url'] = $mapa;
            $punto = coordenadasDeEnlace($mapa);

            if ($punto === null) {
                $errores['mapa_url'] = 'No pudimos sacar la ubicación de ahí. '
                    . 'Abre Google Maps, busca el sitio, pulsa «Compartir» y pega el enlace que te dé.';
            } else {
                [$e['latitud'], $e['longitud']] = $punto;
            }
        }
    }

    // Los campos datetime-local llegan como "2026-08-16T19:30".
    $e['fecha_inicio'] = normalizarFecha((string) ($in['fecha_inicio'] ?? ''));
    if ($e['fecha_inicio'] === null) {
        $errores['fecha_inicio'] = 'Pon la fecha y la hora de inicio.';
    }

    $e['fecha_fin'] = normalizarFecha((string) ($in['fecha_fin'] ?? ''));
    if ($e['fecha_fin'] !== null && $e['fecha_inicio'] !== null
        && strtotime($e['fecha_fin']) < strtotime($e['fecha_inicio'])) {
        $errores['fecha_fin'] = 'El final no puede ser anterior al principio.';
    }

    // Un evento que ya terminó se puede guardar, pero no aparecería en el
    // listado —la agenda corta por COALESCE(fecha_fin, fecha_inicio) >= NOW()—
    // y quien lo publicara se quedaría esperando a verlo. Mejor decirlo aquí.
    //
    // Se mira el final y no el principio: un retiro de cinco días que empezó
    // ayer sigue vigente, y rechazarlo por la fecha de inicio sería un error.
    if (!isset($errores['fecha_inicio']) && !isset($errores['fecha_fin'])) {
        $termina = $e['fecha_fin'] ?? $e['fecha_inicio'];

        if ($termina !== null && strtotime($termina) < time()) {
            $campo = $e['fecha_fin'] !== null ? 'fecha_fin' : 'fecha_inicio';
            $errores[$campo] = 'Esa fecha ya pasó, así que el evento no aparecería en el listado.';
        }
    }

    $e['gratuito'] = !empty($in['gratuito']) ? 1 : 0;

    if ($e['gratuito']) {
        $e['precio'] = null;
    } else {
        $precio = str_replace([',', ' '], '', (string) ($in['precio'] ?? ''));

        if ($precio === '') {
            $errores['precio'] = 'Pon el precio, o marca que es gratuito.';
            $e['precio'] = null;
        } elseif (!is_numeric($precio) || (float) $precio < 0) {
            $errores['precio'] = 'El precio tiene que ser un número.';
            $e['precio'] = null;
        } else {
            $e['precio'] = round((float) $precio, 2);
        }
    }

    $e['url_boletos'] = urlValida((string) ($in['url_boletos'] ?? ''));
    if ($e['url_boletos'] === false) {
        $errores['url_boletos'] = 'Esa dirección no parece válida. Empieza por https://';
        $e['url_boletos'] = null;
    }

    // imagen_url no se valida aquí: no viene del formulario de texto sino de la
    // subida, que la comprueba en includes/subidas.php. La página la asigna
    // después de llamar a esta función.
    $e['imagen_url'] = null;

    $color = (string) ($in['color'] ?? '');
    $e['color'] = in_array($color, coloresEvento(), true) ? $color : coloresEvento()[0];

    return [$e, $errores];
}

/**
 * "2026-08-16T19:30" -> "2026-08-16 19:30:00". null si viene vacío o no cuadra.
 */
function normalizarFecha(string $valor): ?string
{
    $valor = trim($valor);
    if ($valor === '') return null;

    $ts = strtotime(str_replace('T', ' ', $valor));

    return $ts === false ? null : date('Y-m-d H:i:s', $ts);
}

/**
 * @return string|null|false null si viene vacío, false si no vale, la URL si sí.
 */
function urlValida(string $url)
{
    $url = trim($url);
    if ($url === '') return null;

    // Solo http y https. Sin esta comprobación, un "javascript:..." acabaría
    // dentro de un href de la ficha y se ejecutaría al pulsarlo.
    $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($esquema, ['http', 'https'], true)) return false;

    return filter_var($url, FILTER_VALIDATE_URL) ? mb_substr($url, 0, 500) : false;
}

/**
 * Un slug único para la URL. El id va al final porque dos eventos pueden
 * llamarse igual con toda la razón —"Yoga al amanecer" cada sábado— y sin él
 * el segundo no podría guardarse.
 */
function generarSlug(string $titulo, int $id): string
{
    $s = mb_strtolower(trim($titulo), 'UTF-8');
    $s = strtr($s, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ç'=>'c',
    ]);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string) $s, '-');

    return mb_substr($s === '' ? 'evento' : $s, 0, 170) . '-' . $id;
}


// -------------------------------------------------------------- escritura ----

/** Crea el evento como borrador y devuelve su id. */
function crearEvento(array $e, int $usuarioId): int
{
    $pdo = db();

    $pdo->prepare(
        'INSERT INTO eventos
           (usuario_id, titulo, slug, descripcion, categoria, ciudad, entidad,
            lugar, mapa_url, latitud, longitud, fecha_inicio, fecha_fin,
            gratuito, precio, url_boletos, imagen_url, color, situacion)
         VALUES (?, ?, "", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "borrador")'
    )->execute([
        $usuarioId, $e['titulo'], $e['descripcion'], $e['categoria'],
        $e['ciudad'], $e['entidad'], $e['lugar'],
        $e['mapa_url'], $e['latitud'], $e['longitud'],
        $e['fecha_inicio'], $e['fecha_fin'], $e['gratuito'], $e['precio'],
        $e['url_boletos'], $e['imagen_url'], $e['color'],
    ]);

    // El slug lleva el id dentro, así que solo puede calcularse después de
    // insertar. Se guarda vacío y se completa aquí.
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE eventos SET slug = ? WHERE id = ?')
        ->execute([generarSlug($e['titulo'], $id), $id]);

    return $id;
}

function actualizarEvento(array $e, int $id): void
{
    db()->prepare(
        'UPDATE eventos SET
            titulo = ?, slug = ?, descripcion = ?, categoria = ?, ciudad = ?,
            entidad = ?, lugar = ?, mapa_url = ?, latitud = ?, longitud = ?,
            fecha_inicio = ?, fecha_fin = ?,
            gratuito = ?, precio = ?, url_boletos = ?, imagen_url = ?, color = ?
          WHERE id = ?'
    )->execute([
        $e['titulo'], generarSlug($e['titulo'], $id), $e['descripcion'],
        $e['categoria'], $e['ciudad'], $e['entidad'], $e['lugar'],
        $e['mapa_url'], $e['latitud'], $e['longitud'],
        $e['fecha_inicio'], $e['fecha_fin'], $e['gratuito'], $e['precio'],
        $e['url_boletos'], $e['imagen_url'], $e['color'], $id,
    ]);
}

/**
 * Publica el evento y arranca el reloj de las 24 horas.
 *
 * publicado_en solo se pone la primera vez: si un administrador oculta y vuelve
 * a publicar una ficha, eso no le regala al organizador otras 24 horas de
 * edición sobre algo que lleva semanas en cartel.
 */
function publicarEvento(int $id, int $usuarioId): void
{
    $pdo = db();

    $pdo->prepare(
        'UPDATE eventos
            SET situacion = "publicado",
                publicado_en = COALESCE(publicado_en, NOW())
          WHERE id = ?'
    )->execute([$id]);

    // Quien publica su primer evento deja de ser un visitante. El rol de
    // administrador no se toca: se asciende desde visitante y nada más.
    $pdo->prepare(
        'UPDATE usuarios SET rol = "organizador" WHERE id = ? AND rol = "visitante"'
    )->execute([$usuarioId]);
}

function cambiarSituacionEvento(int $id, string $situacion): void
{
    if (!in_array($situacion, ['borrador', 'publicado', 'oculto'], true)) return;

    db()->prepare('UPDATE eventos SET situacion = ? WHERE id = ?')
        ->execute([$situacion, $id]);
}

function eliminarEvento(int $id): void
{
    /*
     * La imagen se va con él. Antes se quedaba en el disco para siempre: nadie
     * volvía a apuntar a ese archivo y nadie sabía que estaba ahí, así que en un
     * hosting compartido solo podía crecer.
     *
     * Se lee ANTES de borrar la fila, que es la última vez que se sabe cuál era,
     * y se borra el archivo DESPUÉS, cuando la fila ya no está: al revés, un
     * fallo al borrar dejaría un evento apuntando a un archivo que ya no existe.
     */
    $st = db()->prepare('SELECT imagen_url FROM eventos WHERE id = ?');
    $st->execute([$id]);
    $imagen = $st->fetchColumn();

    db()->prepare('DELETE FROM eventos WHERE id = ?')->execute([$id]);

    if (is_string($imagen) && $imagen !== '') {
        borrarImagenGuardada($imagen);
    }
}


// --------------------------------------------------------------- formato ----

/** "16 AGO", en partes, para la tarjeta y la ficha. */
function fechaPartes(string $fecha): array
{
    static $meses = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];

    $ts = strtotime($fecha);

    return [
        'd'    => date('d', $ts),
        'm'    => $meses[(int) date('n', $ts) - 1],
        'hora' => date('H:i', $ts),
    ];
}

/** "16 de agosto de 2026, 19:30" */
function fechaLarga(string $fecha): string
{
    static $meses = ['enero','febrero','marzo','abril','mayo','junio','julio',
                     'agosto','septiembre','octubre','noviembre','diciembre'];

    $ts = strtotime($fecha);

    return (int) date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1]
         . ' de ' . date('Y', $ts) . ', ' . date('H:i', $ts);
}

/**
 * La dirección con la que se pinta la imagen de un evento.
 *
 * En la base se guarda la ruta relativa ("assets/eventos/2607-ab12.jpg") y no
 * la dirección completa: si el dominio cambia, o el sitio se mueve de carpeta,
 * las imágenes siguen apareciendo sin tocar una sola fila.
 *
 * Sigue admitiendo direcciones completas porque antes de que hubiera subidas la
 * imagen se pegaba como URL, y esas fichas tienen que seguir viéndose.
 */
function urlImagen(?string $valor): ?string
{
    if ($valor === null || $valor === '') return null;

    if (preg_match('#^https?://#i', $valor)) return $valor;

    return URL_BASE . '/' . ltrim($valor, '/');
}

function precioTexto(array $ev): string
{
    if (!empty($ev['gratuito']))    return 'Gratis';
    if ($ev['precio'] === null)     return 'Por confirmar';

    return '$' . number_format((float) $ev['precio'], 0, '.', ',') . ' MXN';
}

/**
 * Un evento en la forma que espera el JavaScript de la portada.
 *
 * La portada se diseñó como prototipo con un array de ejemplo dentro del
 * script. En vez de reescribir el render entero, se le pasan los datos reales
 * con las mismas claves: el diseño no se toca y las tarjetas salen de la base.
 */
function eventoParaTarjeta(array $ev): array
{
    $p = fechaPartes($ev['fecha_inicio']);

    return [
        'id'    => (int) $ev['id'],
        't'     => $ev['titulo'],
        'cat'   => $ev['categoria'],
        'city'  => $ev['ciudad'] . ', ' . $ev['entidad'],
        'org'   => $ev['organizador'] ?? '',
        'date'  => $p['d'] . ' ' . $p['m'],
        'd'     => $p['d'],
        'm'     => $p['m'],
        'price' => $ev['precio'] !== null ? number_format((float) $ev['precio'], 0, '.', ',') : '',
        'free'  => (bool) $ev['gratuito'],
        'color' => $ev['color'],
        'img'   => urlImagen($ev['imagen_url']),
        'url'   => URL_BASE . '/evento.php?id=' . (int) $ev['id'],

        /*
         * De aquí para abajo no se pinta nada: es lo que necesita el buscador
         * de la portada para filtrar y ordenar.
         *
         * Van aparte de lo que ya se enseña porque las claves de arriba están
         * pensadas para leerse, no para compararse. Filtrar por «city»
         * obligaría a partir «Tulum, Quintana Roo» en el navegador, y ordenar
         * por «price» compararía «2,450» como texto: 2,450 saldría antes que
         * 900.
         */
        'ciudad'  => $ev['ciudad'],
        'entidad' => $ev['entidad'],

        // Con la T en medio, que es lo que sabe leer new Date() en todos los
        // navegadores. Con el espacio de MySQL, Safari devuelve fecha inválida.
        'ini' => fechaIso($ev['fecha_inicio'] ?? null),
        'fin' => fechaIso($ev['fecha_fin'] ?? null),
        'pub' => fechaIso($ev['publicado_en'] ?? null),

        // Gratis es cero, y «todavía no sé el precio» es null: son cosas
        // distintas y al ordenar tienen que caer en sitios distintos.
        'pnum' => !empty($ev['gratuito'])
            ? 0.0
            : ($ev['precio'] !== null ? (float) $ev['precio'] : null),
    ];
}

/** Una fecha de MySQL en la forma que entiende new Date() sin sorpresas. */
function fechaIso(?string $fecha): ?string
{
    return ($fecha === null || $fecha === '') ? null : str_replace(' ', 'T', $fecha);
}
