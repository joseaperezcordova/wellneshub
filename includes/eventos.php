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
 * Margen que tiene el organizador para ELIMINAR su evento después de
 * publicarlo. Pasado ese plazo solo el administrador puede retirarlo.
 *
 * Hasta REQ-000-XX esta misma constante también limitaba EDITAR, y la
 * función se llamaba distinto (ver puedeEditarEvento() más abajo). Editar ya
 * no tiene plazo; borrar sigue teniéndolo, porque quitar la ficha y no volver
 * a subirla es la puerta de atrás para saltarse esa misma protección —dejar
 * tirado a quien ya contaba con lo que leyó—.
 */
const EVENTO_MARGEN_ELIMINACION_H = 24;

/**
 * Cuántas categorías puede llevar una actividad a la vez.
 *
 * Sin techo, "elegir categorías" se convierte en "marcarlas todas" para salir
 * en el mayor número de filtros posible, y el catálogo deja de servir para
 * nada. Tres alcanza para una actividad de verdad mixta —Yoga y Sonido, un
 * retiro de Temazcal y Meditación— sin llegar a eso.
 */
const EVENTO_CATEGORIAS_MAX = 3;

/**
 * El correo de contacto por actividad (migración 24, requerimiento del
 * cliente 2026-09-02): mismos números que CODIGO_* en includes/auth.php, la
 * verificación del correo de la cuenta —es el mismo riesgo (un código de un
 * solo uso, seis cifras, mandado por correo) y no hay motivo para que la
 * ventana de intentos o de espera sea distinta aquí.
 */
const CORREO_CONTACTO_VIGENCIA_MIN = 15;  // minutos que vale un código
const CORREO_CONTACTO_MAX_INTENTOS = 5;   // fallos sobre un mismo código antes de anularlo
const CORREO_CONTACTO_ESPERA_SEG   = 60;  // entre dos códigos para la misma actividad
const CORREO_CONTACTO_MAX_POR_HORA = 5;   // códigos por actividad y hora
const CORREO_CONTACTO_MAX_IP_HORA  = 15;  // códigos por IP y hora, sea cual sea la actividad o el correo

/**
 * Las categorías, con el icono que usa el menú de la portada.
 *
 * Esta lista es la única fuente: el menú lineal de la portada se dibuja desde
 * aquí y el desplegable del formulario también. Cuando estaban escritas en dos
 * sitios, una categoría nueva aparecía en el menú y no se podía elegir al
 * publicar, o al revés.
 */
function categoriasMenu(?string $idioma = null): array
{
    // La clave es el nombre que se guarda en la base —siempre en español, en
    // las dos versiones del sitio: traducir lo que hay guardado en miles de
    // filas está fuera de alcance (docs/pendientes.md, REQ-00002 fase 5)—; la
    // etiqueta es lo que se lee en el menú, y esa sí cambia con el idioma.
    //
    // El orden no es alfabético a propósito: el carril de la portada se lee de
    // izquierda a derecha y casi nadie llega al final, así que va agrupado por
    // parentesco —práctica, ceremonia, aire libre, cuidado del cuerpo— y lo más
    // buscado primero.
    $idioma = $idioma ?? idiomaActual();

    $es = [
        // Práctica
        'Yoga'               => ['🧘',  'Yoga'],
        'Meditación'         => ['🌿',  'Meditación'],
        'Pilates'            => ['🌀',  'Pilates'],
        'Breathwork'         => ['🌬️',  'Breathwork'],
        'Sound Healing'      => ['🎐',  'Sound Healing'],
        'Tai Chi'            => ['☯️',  'Tai Chi'],
        'Qi Gong'            => ['🍃',  'Qi Gong'],

        // Ceremonia
        'Temazcal'           => ['♨️',  'Temazcal'],
        'Ceremonia de Cacao' => ['🍫',  'Ceremonia de Cacao'],
        'Ecstatic Dance'     => ['💃',  'Ecstatic Dance'],

        // Aire libre
        'Senderismo'         => ['🥾',  'Senderismo'],
        'Running'            => ['👟',  'Running'],
        'Carreras'           => ['🏃',  'Carreras'],
        'Ciclismo'           => ['🚴',  'Ciclismo'],
        'Triatlón'           => ['🏊',  'Triatlón'],
        'Surf'               => ['🏄',  'Surf'],

        // Cuidado del cuerpo
        'Nutrición'          => ['🥗',  'Nutrición'],
        'Ayurveda'           => ['🌱',  'Ayurveda'],
        'Spa'                => ['💆',  'Spa'],
        'Cold Plunge'        => ['🧊',  'Cold Plunge'],
        'Biohacking'         => ['⚡',  'Biohacking'],
        'Longevidad'         => ['⏳',  'Longevidad'],
    ];

    if ($idioma !== 'en') return $es;

    // Solo la etiqueta cambia; la clave —lo que se guarda y lo que llega en
    // ?cat=— se queda en español siempre, arriba.
    $etiquetasEn = [
        'Yoga' => 'Yoga', 'Meditación' => 'Meditation', 'Pilates' => 'Pilates',
        'Breathwork' => 'Breathwork', 'Sound Healing' => 'Sound Healing',
        'Tai Chi' => 'Tai Chi', 'Qi Gong' => 'Qi Gong', 'Temazcal' => 'Temazcal',
        'Ceremonia de Cacao' => 'Cacao Ceremony', 'Ecstatic Dance' => 'Ecstatic Dance',
        'Senderismo' => 'Hiking', 'Running' => 'Running', 'Carreras' => 'Races',
        'Ciclismo' => 'Cycling', 'Triatlón' => 'Triathlon', 'Surf' => 'Surf',
        'Nutrición' => 'Nutrition', 'Ayurveda' => 'Ayurveda', 'Spa' => 'Spa',
        'Cold Plunge' => 'Cold Plunge', 'Biohacking' => 'Biohacking',
        'Longevidad' => 'Longevity',
    ];

    $en = [];
    foreach ($es as $nombre => $datos) {
        $en[$nombre] = [$datos[0], $etiquetasEn[$nombre]];
    }
    return $en;
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

/**
 * Los iconos de línea del carril de categorías de la portada —el diseño
 * original antes de que el emoji entrara como marcador de posición—.
 *
 * SOLO para ese carril: el desplegable del formulario, la ficha y el filtro
 * del buscador siguen leyendo el emoji de categoriasMenu(), que ahí funciona
 * bien —es un solo carácter, cabe en una etiqueta de texto— y un SVG entero
 * no. Mezclar los dos formatos en una sola lista habría obligado a tratar el
 * emoji como HTML en sitios donde hoy se escapa como texto plano.
 *
 * Cada trazo es un <path>/<circle> sencillo, mismo grosor y estilo que ya usan
 * los iconos de las secciones del formulario (viewBox 24×24, stroke-width
 * 1.8, remates redondeados): no es un juego de iconos importado, es dibujado
 * a mano con la misma regla visual que el resto del sitio.
 *
 * @return array<string, string> nombre de categoría => SVG completo
 */
function iconosLineaCategoria(): array
{
    $svg = static function (string $trazos): string {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" '
             . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $trazos . '</svg>';
    };

    return [
        'Yoga'               => $svg('<circle cx="12" cy="5" r="2"/><path d="M12 7.2v4.3"/>'
            . '<path d="M7 19c0-4 2-6 5-6.5M17 19c0-4-2-6-5-6.5"/><path d="M7 19h10"/>'),
        'Meditación'         => $svg('<path d="M6 18c0-7 4-11 11-11-1 8-4 12-11 11z"/><path d="M8 16c3-3 5-6 8-8"/>'),
        'Pilates'            => $svg('<circle cx="12" cy="12" r="7"/><path d="M12 12a3 3 0 1 0 3-3"/>'),
        'Breathwork'         => $svg('<path d="M4 9c2-2 4-2 6 0s4 2 6 0 4-2 4 0"/>'
            . '<path d="M4 15c2-2 4-2 6 0s4 2 6 0 4-2 4 0"/>'),
        'Sound Healing'      => $svg('<path d="M6 15c0 3 3 4.5 6 4.5s6-1.5 6-4.5"/><path d="M4 15h16"/>'
            . '<path d="M9 9c1-1.5 5-1.5 6 0M7.5 6.5c1.8-2.3 7.2-2.3 9 0"/>'),
        'Tai Chi'            => $svg('<circle cx="12" cy="12" r="7.5"/>'
            . '<path d="M12 4.5a3.75 3.75 0 0 1 0 7.5 3.75 3.75 0 0 0 0 7.5"/>'
            . '<circle cx="12" cy="8.25" r="1" fill="currentColor" stroke="none"/>'
            . '<circle cx="12" cy="15.75" r="1" fill="currentColor" stroke="none"/>'),
        'Qi Gong'            => $svg('<path d="M8 5c4 2 4 6 0 8s-4 6 0 8"/><path d="M14 5c4 2 4 6 0 8s-4 6 0 8"/>'),

        'Temazcal'           => $svg('<path d="M4 18a8 8 0 0 1 16 0"/><path d="M4 18h16"/>'
            . '<path d="M10 8c-1 1-1 2 0 3M14 6c-1 1-1 2 0 3"/>'),
        'Ceremonia de Cacao' => $svg('<path d="M12 4c4 2 6 6 6 10a6 6 0 0 1-12 0c0-4 2-8 6-10z"/><path d="M12 4v16"/>'),
        'Ecstatic Dance'     => $svg('<circle cx="12" cy="5.5" r="2"/>'
            . '<path d="M12 7.5v6M12 9l-5-3M12 9l5-3M12 13.5l-4 5.5M12 13.5l4 5.5"/>'),

        'Senderismo'         => $svg('<path d="M3 18l6-10 4 6 2-3 6 7z"/><circle cx="9" cy="6" r="1.4"/>'),
        'Running'            => $svg('<circle cx="15" cy="5.5" r="1.8"/>'
            . '<path d="M8 19l3-4 2-3-1.5-4-3.5 1.5M13 12l3 2 3-1M9 15l-3.5 4"/>'),
        'Carreras'           => $svg('<path d="M6 20V4"/><path d="M6 5h5l-1.5 2.5L11 10H6"/>'),
        'Ciclismo'           => $svg('<circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/>'
            . '<path d="M6 17l5-9 7 9M11 8h3"/>'),
        'Triatlón'           => $svg('<path d="M3 18c1.5-1.5 3-1.5 4.5 0s3 1.5 4.5 0 3-1.5 4.5 0 3 1.5 4.5 0"/>'
            . '<circle cx="12" cy="8" r="2"/><path d="M9 12l3-2 3 2"/>'),
        'Surf'               => $svg('<path d="M3 15c3-4 6-4 9 0s6 4 9 0"/><path d="M6 19l12-10"/>'),

        'Nutrición'          => $svg('<path d="M12 8c3-2 6 0 6 4.5S15 20 12 20s-6-3-6-7.5S9 6 12 8z"/>'
            . '<path d="M12 8V5.5M12 5.5c0-1 .8-1.5 2-1.5"/>'),
        'Ayurveda'           => $svg('<path d="M12 20v-9"/>'
            . '<path d="M12 11c0-4 3-6 6-6 0 4-2 6-6 6zM12 14c0-3-2.5-5-5-5 0 3 2 5 5 5z"/>'),
        'Spa'                => $svg('<path d="M12 4c3 4.5 5 8 5 10.5a5 5 0 0 1-10 0C7 12 9 8.5 12 4z"/>'),
        'Cold Plunge'        => $svg('<path d="M12 3v18M4.5 7.5l15 9M19.5 7.5l-15 9"/>'
            . '<path d="M12 3l-1.5 2M12 3l1.5 2M12 21l-1.5-2M12 21l1.5-2"/>'),
        'Biohacking'         => $svg('<path d="M13 3L6 13h5l-1 8 7-10h-5z"/>'),
        'Longevidad'         => $svg('<path d="M6 3h12M6 21h12"/>'
            . '<path d="M7 3c0 4 3 6 5 8 2-2 5-4 5-8M7 21c0-4 3-6 5-8 2 2 5 4 5 8"/>'),
    ];
}

/**
 * Los colores de la paleta que puede llevar la tarjeta sin imagen.
 *
 * Van escritos y no como var(--x) porque el valor elegido se guarda en la
 * columna eventos.color y se pinta con style="background-color:…": ahí no hay
 * hoja de estilos que resuelva una variable.
 *
 * SIN NARANJA, a diferencia del resto de la interfaz. El naranja de OMDARA es
 * el único acento —para los CTA, y debe rondar el 5% de la superficie—; un
 * carril de tarjetas en la portada es de lo más grande que se ve de una vez, y
 * bastaría con que tres organizadores lo eligieran para convertirlo en el
 * color dominante del sitio, compitiendo con los botones por la atención.
 *
 * Los mismos seis papeles de siempre —negro de marca, gris medio, gris
 * oscuro, gris muy oscuro, gris claro, negro cálido de texto—, ahora en la
 * escala de grises de la paleta nueva en vez de verdes.
 *
 * Las fichas publicadas antes del cambio de paleta conservan su hex viejo
 * hasta que alguien las vuelva a guardar: validarEvento() solo acepta valores
 * de esta lista, así que al reeditarlas caen al primero.
 */
function coloresEvento(): array
{
    return ['#161616', '#4A4A47', '#3A3A37', '#2A2A27', '#EDEDE9', '#221F1B'];
}

/** clave => etiqueta. Con qué frecuencia se repite una actividad recurrente. */
function frecuenciasRecurrencia(): array
{
    return [
        'diaria'    => t('evento.frecuencia.diaria'),
        'semanal'   => t('evento.frecuencia.semanal'),
        'quincenal' => t('evento.frecuencia.quincenal'),
        'mensual'   => t('evento.frecuencia.mensual'),
    ];
}

/**
 * Qué se espera que haga quien ve la ficha, con el mismo texto que ya usa
 * includes/guia-accion.php: los dos hablan de las mismas tres opciones y
 * tienen que decir lo mismo con las mismas palabras.
 */
function accionesPrincipales(): array
{
    return [
        'informacion' => t('evento.form.accion_contactar'),
        'boletos'     => t('evento.form.accion_comprar'),
        'reservar'    => t('evento.form.accion_reservar'),
    ];
}

/** Las 32 entidades federativas. Para el select de "Estado": aquí no hace
 * falta texto libre, la lista es corta y no cambia. */
function estadosMexico(): array
{
    return [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche',
        'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima',
        'Durango', 'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'México',
        'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla',
        'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora',
        'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
    ];
}

/**
 * estado => municipios oficiales. A diferencia del texto libre que tenía
 * Ciudad antes, este es un catálogo cerrado —los ~2,478 municipios de
 * México según el INEGI—: quien esté en una localidad pequeña que no
 * aparece con su propio nombre elige el municipio al que pertenece, igual
 * que ya hace con el "Estado".
 *
 * Vive en un JSON aparte y no en un array de PHP porque son casi 2,500
 * líneas de datos, no de lógica: mezclarlas aquí habría hecho ilegible el
 * resto del archivo.
 */
function municipiosPorEstado(): array
{
    static $datos = null;

    if ($datos === null) {
        $json  = file_get_contents(__DIR__ . '/datos/municipios.json');
        $datos = $json !== false ? (json_decode($json, true) ?: []) : [];
    }

    return $datos;
}


// ------------------------------------------------------------- permisos ----

function esAdmin(?array $u): bool
{
    return $u !== null && $u['rol'] === 'admin';
}

/**
 * ¿Puede esta persona EDITAR este evento?
 *
 * El administrador, siempre. El dueño, siempre —sin límite de tiempo desde
 * REQ-000-XX—, mientras siga siendo suyo: borrador, publicada u oculta por
 * moderación entran los tres aquí. Una actividad retirada no tiene fila que
 * editar —eliminarEvento() la borra de verdad—, así que ese caso no hace
 * falta comprobarlo aparte.
 *
 * Que el dueño pueda editar una oculta no le devuelve la visibilidad: eso
 * sigue siendo decisión del administrador (ver el "Volver a publicar" de
 * evento.php, que solo él puede pulsar). Editar y publicar son permisos
 * distintos.
 */
function puedeEditarEvento(array $ev, ?array $u): bool
{
    if ($u === null) return false;
    if (esAdmin($u)) return true;

    return (int) $ev['usuario_id'] === (int) $u['id'];
}

/**
 * ¿Puede esta persona ELIMINAR este evento?
 *
 * A diferencia de editar, esto SÍ sigue teniendo plazo: el administrador,
 * siempre, y el dueño mientras sea borrador o esté dentro del margen de
 * EVENTO_MARGEN_ELIMINACION_H horas desde que lo publicó.
 *
 * El plazo se calcula sobre publicado_en y no sobre creado_en a propósito: un
 * borrador que estuvo tres días a medias no debe llegar publicado y ya caducado.
 */
function puedeEliminarEvento(array $ev, ?array $u): bool
{
    if ($u === null)   return false;
    if (esAdmin($u))   return true;

    if ((int) $ev['usuario_id'] !== (int) $u['id']) return false;
    if ($ev['situacion'] === 'borrador')            return true;

    return minutosRestantesEliminacion($ev) > 0;
}

/**
 * Minutos que quedan para poder ELIMINAR, o 0 si ya pasó.
 *
 * Sirve para dos cosas: decidir el permiso y avisar en pantalla de cuánto
 * queda, que es lo que evita que alguien descubra el plazo cuando ya expiró.
 */
function minutosRestantesEliminacion(array $ev): int
{
    if (empty($ev['publicado_en'])) return 0;

    $limite = strtotime($ev['publicado_en']) + EVENTO_MARGEN_ELIMINACION_H * 3600;
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
 * El título de la actividad en el idioma actual, con reserva al español.
 *
 * REQ-00002 fase 5: la versión en inglés la escribe el organizador —campo
 * `titulo_en`, opcional—, no se traduce sola. Si no la rellenó, quien vea la
 * ficha en inglés sigue leyendo el título en español: es mejor eso que un
 * campo vacío o un título a medias.
 */
function tituloEvento(array $ev, ?string $idioma = null): string
{
    $idioma = $idioma ?? idiomaActual();

    return ($idioma === 'en' && !empty($ev['titulo_en'])) ? $ev['titulo_en'] : $ev['titulo'];
}

/** Igual que tituloEvento(), para `descripcion`/`descripcion_en`. */
function descripcionEvento(array $ev, ?string $idioma = null): string
{
    $idioma = $idioma ?? idiomaActual();

    return ($idioma === 'en' && !empty($ev['descripcion_en'])) ? $ev['descripcion_en'] : $ev['descripcion'];
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

/**
 * Actividades publicadas y todavía vigentes, para sitemap.xml. Solo id y
 * fecha de actualización: es lo único que necesita un mapa del sitio, y
 * evita traer la ficha completa —descripción, imagen…— de cada una.
 *
 * @return array<int, array{id:int, actualizado_en:string}>
 */
function eventosPublicadosParaSitemap(): array
{
    $st = db()->query(
        "SELECT id, slug, actualizado_en
           FROM eventos
          WHERE situacion = 'publicado'
            AND COALESCE(fecha_fin, fecha_inicio) >= NOW()
       ORDER BY actualizado_en DESC"
    );

    return array_map(
        static fn(array $f): array => [
            'id'             => (int) $f['id'],
            'slug'           => (string) ($f['slug'] ?? ''),
            'actualizado_en' => (string) $f['actualizado_en'],
        ],
        $st->fetchAll()
    );
}

/**
 * El tramo de fechas de cada opción de "Cuándo" del buscador.
 *
 * Tiene que dar exactamente el mismo resultado que rango() en
 * assets/js/buscar.js —ahí decide qué tarjeta se pinta cuando el filtrado
 * era en el navegador; aquí decide qué fila trae la consulta—. Si los dos se
 * desincronizan, el número de "resultados encontrados" no cuadraría con lo
 * que de verdad se está pidiendo.
 *
 * @return array{0:?string,1:?string} [desde, hasta] como DATETIME, o [null,null]
 */
function rangoFechaBusqueda(string $clave): array
{
    if ($clave === '') return [null, null];

    $hoy = new DateTimeImmutable('today');

    if ($clave === 'finde') {
        // 0 domingo .. 6 sábado, igual que Date.getDay() en JS.
        $dia    = (int) $hoy->format('w');
        $inicio = ($dia !== 0 && $dia !== 6) ? $hoy->modify('+' . (6 - $dia) . ' days') : $hoy;
        $fin    = ((int) $inicio->format('w') === 6) ? $inicio->modify('+1 day') : $inicio;

        return [$inicio->format('Y-m-d 00:00:00'), $fin->format('Y-m-d 23:59:59')];
    }

    if ($clave === '7dias') {
        return [$hoy->format('Y-m-d 00:00:00'), $hoy->modify('+6 days')->format('Y-m-d 23:59:59')];
    }

    // 'mes': lo que queda del mes. El día 0 del mes siguiente es el último de este.
    $finMes = $hoy->modify('last day of this month');
    return [$hoy->format('Y-m-d 00:00:00'), $finMes->format('Y-m-d 23:59:59')];
}

/**
 * Busca actividades publicadas con los filtros de includes/busqueda.php,
 * resueltos en SQL y no en el navegador —para que la lista no tenga techo—.
 *
 * @return array{total:int, eventos:array}
 */
function eventosBuscar(array $f, int $limite, int $offset): array
{
    // Igual que eventosPublicados(): una actividad ya terminada sigue
    // "publicada" —la ficha se puede seguir viendo—, pero no aparece al
    // buscar ni al explorar.
    $where  = ["e.situacion = 'publicado'", "COALESCE(e.fecha_fin, e.fecha_inicio) >= NOW()"];
    $params = [];

    if ($f['entidad'] !== '') {
        $where[]  = 'e.entidad = ?';
        $params[] = $f['entidad'];
    }
    if ($f['ciudad'] !== '') {
        $where[]  = 'e.ciudad = ?';
        $params[] = $f['ciudad'];
    }
    if ($f['gratis']) {
        $where[] = 'e.gratuito = 1';
    }
    if ($f['cats']) {
        // EXISTS contra eventos_categorias y no "e.categoria IN (...)": una
        // actividad marcada Yoga y Meditación tiene que aparecer al filtrar
        // por cualquiera de las dos, no solo por la que quedó como principal.
        $marcadores = implode(',', array_fill(0, count($f['cats']), '?'));
        $where[]    = "EXISTS (SELECT 1 FROM eventos_categorias ec
                                 WHERE ec.evento_id = e.id AND ec.categoria IN ($marcadores))";
        array_push($params, ...$f['cats']);
    }
    if ($f['texto'] !== '') {
        $comodin = '%' . $f['texto'] . '%';
        $where[] = '(e.titulo LIKE ? OR e.categoria LIKE ? OR e.ciudad LIKE ? OR u.nombre LIKE ?)';
        array_push($params, $comodin, $comodin, $comodin, $comodin);
    }

    [$desde, $hasta] = rangoFechaBusqueda($f['fecha']);
    if ($desde !== null) {
        $where[]  = 'e.fecha_inicio <= ? AND COALESCE(e.fecha_fin, e.fecha_inicio) >= ?';
        $params[] = $hasta;
        $params[] = $desde;
    }

    $whereSql = implode(' AND ', $where);
    $pdo      = db();

    $stTotal = $pdo->prepare(
        "SELECT COUNT(*) FROM eventos e JOIN usuarios u ON u.id = e.usuario_id WHERE $whereSql"
    );
    $stTotal->execute($params);
    $total = (int) $stTotal->fetchColumn();

    $ordenes = [
        // gratuito primero deja las de precio 0 antes que "por confirmar"
        // (precio NULL), igual que pnum=0 vence a pnum=null en el JS de antes.
        'precio' => 'e.gratuito DESC, (e.precio IS NULL) ASC, e.precio ASC, e.fecha_inicio ASC',
        'nuevos' => 'e.publicado_en DESC, e.fecha_inicio ASC',
        'fecha'  => 'e.fecha_inicio ASC',
    ];
    $orderBy = $ordenes[$f['orden']] ?? $ordenes['fecha'];

    $st = $pdo->prepare(
        "SELECT e.*, u.nombre AS organizador
           FROM eventos e
           JOIN usuarios u ON u.id = e.usuario_id
          WHERE $whereSql
       ORDER BY $orderBy
          LIMIT " . (int) $limite . ' OFFSET ' . (int) $offset
    );
    $st->execute($params);

    return ['total' => $total, 'eventos' => $st->fetchAll()];
}

/** Estados y ciudades con al menos una actividad publicada, para el panel de filtros. */
function ubicacionesConActividad(): array
{
    $vigente = "situacion = 'publicado' AND COALESCE(fecha_fin, fecha_inicio) >= NOW()";

    $entidades = db()->query(
        "SELECT DISTINCT entidad FROM eventos WHERE $vigente AND entidad != ''"
    )->fetchAll(PDO::FETCH_COLUMN);

    $ciudades = db()->query(
        "SELECT DISTINCT ciudad FROM eventos WHERE $vigente AND ciudad != ''"
    )->fetchAll(PDO::FETCH_COLUMN);

    sort($entidades, SORT_NATURAL | SORT_FLAG_CASE);
    sort($ciudades, SORT_NATURAL | SORT_FLAG_CASE);

    return ['entidades' => $entidades, 'ciudades' => $ciudades];
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

/**
 * Quienes ya publicaron al menos una actividad, con cuántas llevan.
 *
 * "Organizador" no es una etiqueta que alguien elija: es el rol que
 * publicarEvento() pone solo la primera vez que a alguien se le publica algo
 * (ver ahí el porqué). Esta lista es ese mismo criterio, para el panel.
 */
function organizadoresConConteo(): array
{
    /*
     * Subconsulta para el conteo en vez de JOIN + GROUP BY, y u.* en vez de la
     * lista de columnas. Las dos cosas por el mismo motivo: desde la migración
     * 17 hay una columna más —el teléfono—, y una lista explícita habría que
     * mantenerla aquí Y en el GROUP BY, con el añadido de que reventaría en el
     * rato que pasa entre publicar el código y aplicar la migración a mano.
     *
     * HAVING y no WHERE sobre el alias: WHERE se evalúa antes que la
     * subconsulta. El resultado es el mismo que el del JOIN interno de antes
     * —solo organizadores con al menos una actividad publicada—.
     */
    $st = db()->query(
        "SELECT u.*,
                (SELECT COUNT(*) FROM eventos e
                  WHERE e.usuario_id = u.id AND e.situacion = 'publicado') AS publicadas
           FROM usuarios u
          WHERE u.rol = 'organizador'
         HAVING publicadas > 0
       ORDER BY publicadas DESC, u.nombre ASC"
    );

    return $st->fetchAll();
}

/** Todas las cuentas, para el panel de administración. */
function usuariosTodos(): array
{
    $st = db()->query(
        'SELECT id, nombre, email, rol, estado, ultimo_acceso_en, creado_en
           FROM usuarios
       ORDER BY COALESCE(ultimo_acceso_en, creado_en) DESC'
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
        'titulo'          => t('evento.form.titulo_label'),
        'titulo_en'       => t('evento.form.titulo_en_label'),
        'categorias'      => t('evento.form.categorias_label'),
        'descripcion'     => t('evento.form.descripcion_label'),
        'descripcion_en'  => t('evento.form.descripcion_en_label'),
        'ciudad'          => t('evento.form.ciudad_label'),
        'entidad'         => t('evento.form.estado_label'),
        'lugar'           => t('evento.form.lugar_label'),
        'direccion'       => t('evento.form.direccion_label'),
        'mapa_url'        => t('evento.campo.mapa_url'),

        // No es un campo de la actividad —vive en la cuenta del organizador,
        // ver guardarContactoOrganizador() en includes/auth.php— pero
        // REQ-000-XX lo volvió obligatorio en el formulario, así que necesita
        // su propia etiqueta aquí igual que cualquier otro campo requerido.
        'org_nombre'      => t('evento.form.organizador_nombre_label'),

        // Actividad de un día.
        'fecha_unica'        => t('evento.form.fecha_label'),
        'hora_inicio_unica'  => t('evento.form.hora_inicio_label'),
        'hora_fin_unica'     => t('evento.form.hora_fin_label'),
        'fecha_fin_unica'    => t('evento.campo.fecha_fin_unica'),

        // Actividad recurrente.
        'fecha_inicio_rec'   => t('evento.form.fecha_inicio_label'),
        'fecha_fin_rec'      => t('evento.form.fecha_fin_label'),
        'frecuencia'         => t('evento.form.frecuencia_label'),
        'hora_recurrente'    => t('evento.form.hora_inicio_label'),
        'hora_fin_recurrente' => t('evento.form.hora_fin_label'),

        'precio'          => t('evento.campo.precio'),
        'forma_pago'      => t('evento.form.forma_pago_label'),
        'cupo_maximo'     => t('evento.campo.cupo_maximo'),
        'url_boletos'     => t('evento.form.url_compra_label'),
        'url_reserva'     => t('evento.form.url_reserva_label'),
        'sitio_web'       => t('evento.campo.sitio_web'),
        'accion_principal' => t('evento.campo.accion_principal'),
        'imagen'          => t('evento.campo.imagen'),
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
        $errores['titulo'] = t('evento.valida.titulo_corto');
    } elseif (mb_strlen($e['titulo']) > 160) {
        $errores['titulo'] = t('evento.valida.titulo_largo');
    }

    $e['descripcion'] = trim((string) ($in['descripcion'] ?? ''));
    if (mb_strlen($e['descripcion']) < 50) {
        $errores['descripcion'] = t('evento.valida.descripcion_corta');
    } elseif (mb_strlen($e['descripcion']) > 2000) {
        $errores['descripcion'] = t('evento.valida.descripcion_larga');
    }

    /*
     * Versión en inglés, opcional (REQ-00002 fase 5): la escribe el
     * organizador, no se traduce sola. Sin mínimo —a diferencia de arriba—,
     * porque dejarla vacía es una opción válida y no un error.
     */
    $e['titulo_en'] = trim((string) ($in['titulo_en'] ?? ''));
    if (mb_strlen($e['titulo_en']) > 160) {
        $errores['titulo_en'] = t('evento.valida.titulo_en_largo');
    }
    if ($e['titulo_en'] === '') $e['titulo_en'] = null;

    $e['descripcion_en'] = trim((string) ($in['descripcion_en'] ?? ''));
    if (mb_strlen($e['descripcion_en']) > 2000) {
        $errores['descripcion_en'] = t('evento.valida.descripcion_en_larga');
    }
    if ($e['descripcion_en'] === '') $e['descripcion_en'] = null;

    /*
     * Una o varias categorías (checkboxes "categorias[]"), no ya un único
     * <select>. Se cotejan con el catálogo y se ordenan como en él —no como
     * llegaron los checkboxes— para que "la principal" sea siempre
     * determinista: la primera del catálogo entre las que se marcaron.
     */
    $categoriasEntrada = $in['categorias'] ?? [];
    if (!is_array($categoriasEntrada)) $categoriasEntrada = [];
    $categoriasEntrada = array_unique(array_map('strval', $categoriasEntrada));

    $catalogoCategorias = array_keys(categorias());
    $categoriasValidas  = array_values(array_intersect($catalogoCategorias, $categoriasEntrada));

    if (!$categoriasValidas) {
        $errores['categorias'] = t('evento.valida.categoria_falta');
    } elseif (count($categoriasValidas) > EVENTO_CATEGORIAS_MAX) {
        $errores['categorias'] = sprintf(t('evento.valida.categoria_max'), EVENTO_CATEGORIAS_MAX);
    }

    $e['categorias'] = array_slice($categoriasValidas, 0, EVENTO_CATEGORIAS_MAX);
    $e['categoria']  = $e['categorias'][0] ?? '';

    $e['entidad'] = trim((string) ($in['entidad'] ?? ''));
    if (!in_array($e['entidad'], estadosMexico(), true)) {
        $errores['entidad'] = t('evento.valida.estado_falta');
    }

    $e['ciudad'] = trim((string) ($in['ciudad'] ?? ''));
    $municipiosDelEstado = municipiosPorEstado()[$e['entidad']] ?? [];
    if (!in_array($e['ciudad'], $municipiosDelEstado, true)) {
        $errores['ciudad'] = $e['entidad'] === ''
            ? t('evento.valida.ciudad_sin_estado')
            : t('evento.valida.ciudad_falta');
    }

    $e['lugar'] = trim((string) ($in['lugar'] ?? ''));
    if ($e['lugar'] === '') $errores['lugar'] = t('evento.valida.lugar_falta');

    // Aparte del nombre del lugar: la calle y número, para quien quiera
    // corregir o completar lo que puso el geocoding automático.
    $e['direccion'] = trim((string) ($in['direccion'] ?? ''));
    if (mb_strlen($e['direccion']) > 255) {
        $errores['direccion'] = t('evento.valida.direccion_larga');
    }
    if ($e['direccion'] === '') $e['direccion'] = null;

    /*
     * El punto en el mapa ya no sale de un enlace pegado: sale del pin que
     * se arrastra en el mapa interactivo, y esos hidden inputs son
     * exactamente lo que ese JavaScript escribe. Opcional a propósito —no
     * toda actividad necesita el pin exacto para publicarse—, pero si
     * llega algo tiene que ser un punto real y no basura de un POST armado
     * a mano.
     */
    $e['mapa_url'] = null;
    $e['latitud']  = null;
    $e['longitud'] = null;

    $lat = trim((string) ($in['latitud'] ?? ''));
    $lng = trim((string) ($in['longitud'] ?? ''));

    if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
        $punto = coordenadasValidas((float) $lat, (float) $lng);
        if ($punto !== null) {
            [$e['latitud'], $e['longitud']] = $punto;
        }
    }

    /*
     * Única o recurrente cambia de dónde salen fecha_inicio y fecha_fin, pero
     * no lo que significan una vez guardadas: el resto del sitio —agenda,
     * buscador, ficha— sigue leyendo las mismas dos columnas DATETIME de
     * siempre y no necesita saber cuál de los dos formularios las llenó.
     */
    $e['tipo_actividad'] = ($in['tipo_actividad'] ?? '') === 'recurrente' ? 'recurrente' : 'unico';

    $horaValida = static function (string $hora): ?string {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) ? $hora : null;
    };

    if ($e['tipo_actividad'] === 'recurrente') {
        $e['frecuencia'] = (string) ($in['frecuencia'] ?? '');
        if (!isset(frecuenciasRecurrencia()[$e['frecuencia']])) {
            $errores['frecuencia'] = t('evento.valida.frecuencia_falta');
            $e['frecuencia'] = null;
        }

        $e['hora_recurrente'] = $horaValida(trim((string) ($in['hora_recurrente'] ?? '')));
        if ($e['hora_recurrente'] === null) {
            $errores['hora_recurrente'] = t('evento.valida.hora_inicio_sesion_falta');
        }

        $e['hora_fin_recurrente'] = $horaValida(trim((string) ($in['hora_fin_recurrente'] ?? '')));
        if ($e['hora_fin_recurrente'] === null) {
            $errores['hora_fin_recurrente'] = t('evento.valida.hora_fin_sesion_falta');
        } elseif ($e['hora_recurrente'] !== null && $e['hora_fin_recurrente'] <= $e['hora_recurrente']) {
            $errores['hora_fin_recurrente'] = t('evento.valida.hora_fin_antes_inicio');
        }

        $inicioRec = trim((string) ($in['fecha_inicio_rec'] ?? ''));
        $inicioRecValido = ($inicioRec !== '' && strtotime($inicioRec) !== false) ? $inicioRec : null;
        if ($inicioRecValido === null) {
            $errores['fecha_inicio_rec'] = t('evento.valida.fecha_inicio_rec_falta');
        }

        $finRec = trim((string) ($in['fecha_fin_rec'] ?? ''));
        $finRecValido = ($finRec !== '' && strtotime($finRec) !== false) ? $finRec : null;
        if ($finRecValido === null) {
            $errores['fecha_fin_rec'] = t('evento.valida.fecha_fin_rec_falta');
        }

        $e['fecha_inicio'] = ($inicioRecValido !== null && $e['hora_recurrente'] !== null)
            ? normalizarFecha($inicioRecValido . 'T' . $e['hora_recurrente']) : null;

        $e['fecha_fin'] = ($finRecValido !== null && $e['hora_fin_recurrente'] !== null)
            ? normalizarFecha($finRecValido . 'T' . $e['hora_fin_recurrente']) : null;

        if ($e['fecha_fin'] !== null && $e['fecha_inicio'] !== null
            && strtotime($e['fecha_fin']) < strtotime($e['fecha_inicio'])) {
            $errores['fecha_fin_rec'] = t('evento.valida.fecha_fin_antes_inicio');
        }
    } else {
        $e['frecuencia']          = null;
        $e['hora_recurrente']     = null;
        $e['hora_fin_recurrente'] = null;

        $fechaUnica = trim((string) ($in['fecha_unica'] ?? ''));
        $fechaUnicaValida = ($fechaUnica !== '' && strtotime($fechaUnica) !== false) ? $fechaUnica : null;
        if ($fechaUnicaValida === null) {
            $errores['fecha_unica'] = t('evento.valida.fecha_falta');
        }

        $horaIniValida = $horaValida(trim((string) ($in['hora_inicio_unica'] ?? '')));
        if ($horaIniValida === null) {
            $errores['hora_inicio_unica'] = t('evento.valida.hora_inicio_falta');
        }

        $horaFinValida = $horaValida(trim((string) ($in['hora_fin_unica'] ?? '')));
        if ($horaFinValida === null) {
            $errores['hora_fin_unica'] = t('evento.valida.hora_fin_falta');
        }

        // El día de término es opcional: sin él, se asume el mismo día de inicio.
        $fechaFinInput = trim((string) ($in['fecha_fin_unica'] ?? ''));
        $fechaFinUnicaValida = ($fechaFinInput !== '' && strtotime($fechaFinInput) !== false)
            ? $fechaFinInput : $fechaUnicaValida;

        $e['fecha_inicio'] = ($fechaUnicaValida !== null && $horaIniValida !== null)
            ? normalizarFecha($fechaUnicaValida . 'T' . $horaIniValida) : null;

        $e['fecha_fin'] = ($fechaFinUnicaValida !== null && $horaFinValida !== null)
            ? normalizarFecha($fechaFinUnicaValida . 'T' . $horaFinValida) : null;

        if ($e['fecha_fin'] !== null && $e['fecha_inicio'] !== null
            && strtotime($e['fecha_fin']) < strtotime($e['fecha_inicio'])) {
            $errores['hora_fin_unica'] = t('evento.valida.fecha_fin_antes_inicio');
        }
    }

    // Un evento que ya terminó se puede guardar, pero no aparecería en el
    // listado —la agenda corta por COALESCE(fecha_fin, fecha_inicio) >= NOW()—
    // y quien lo publicara se quedaría esperando a verlo. Mejor decirlo aquí.
    //
    // Se mira el final y no el principio: un retiro de cinco días que empezó
    // ayer sigue vigente, y rechazarlo por la fecha de inicio sería un error.
    $recurrente    = $e['tipo_actividad'] === 'recurrente';
    $huboErrorFecha = $recurrente
        ? (isset($errores['fecha_inicio_rec']) || isset($errores['fecha_fin_rec'])
            || isset($errores['hora_recurrente']) || isset($errores['hora_fin_recurrente']))
        : (isset($errores['fecha_unica']) || isset($errores['hora_inicio_unica'])
            || isset($errores['hora_fin_unica']));

    if (!$huboErrorFecha) {
        $termina = $e['fecha_fin'] ?? $e['fecha_inicio'];

        if ($termina !== null && strtotime($termina) < time()) {
            $campo = $recurrente ? 'fecha_fin_rec' : 'fecha_unica';
            $errores[$campo] = t('evento.valida.fecha_pasada');
        }
    }

    $e['gratuito'] = ($in['precio_modo'] ?? '') === 'de_pago' ? 0 : 1;

    if ($e['gratuito']) {
        $e['precio']     = null;
        $e['forma_pago'] = null;
    } else {
        $precio = str_replace([',', ' '], '', (string) ($in['precio'] ?? ''));

        if ($precio === '') {
            $errores['precio'] = t('evento.valida.precio_falta');
            $e['precio'] = null;
        } elseif (!is_numeric($precio) || (float) $precio < 0) {
            $errores['precio'] = t('evento.valida.precio_invalido');
            $e['precio'] = null;
        } else {
            $e['precio'] = round((float) $precio, 2);
        }

        $e['forma_pago'] = (string) ($in['forma_pago'] ?? '');
        if (!in_array($e['forma_pago'], ['completa', 'sesion'], true)) {
            $errores['forma_pago'] = t('evento.valida.forma_pago_falta');
            $e['forma_pago'] = null;
        }
    }

    // Cupo máximo: opcional, y solo tiene sentido como entero positivo. No se
    // le pone techo —quién sabe cuánta gente cabe en un festival— más allá
    // de lo que ya limita la columna (INT UNSIGNED).
    $cupo = trim((string) ($in['cupo_maximo'] ?? ''));
    $e['cupo_maximo'] = null;
    if ($cupo !== '') {
        if (!ctype_digit($cupo) || (int) $cupo < 1) {
            $errores['cupo_maximo'] = t('evento.valida.cupo_invalido');
        } else {
            $e['cupo_maximo'] = (int) $cupo;
        }
    }

    $e['accion_principal'] = (string) ($in['accion_principal'] ?? '');
    if (!isset(accionesPrincipales()[$e['accion_principal']])) {
        $errores['accion_principal'] = t('evento.valida.accion_falta');
    }

    // Cada acción principal pide su propio enlace. Solo es obligatorio el de
    // la acción elegida: el otro puede quedar vacío sin que bloquee el envío.
    $e['url_boletos'] = urlValida((string) ($in['url_boletos'] ?? ''));
    if ($e['url_boletos'] === false) {
        $errores['url_boletos'] = t('evento.valida.url_invalida');
        $e['url_boletos'] = null;
    } elseif ($e['url_boletos'] === null && $e['accion_principal'] === 'boletos') {
        $errores['url_boletos'] = t('evento.valida.boletos_falta');
    }

    $e['url_reserva'] = urlValida((string) ($in['url_reserva'] ?? ''));
    if ($e['url_reserva'] === false) {
        $errores['url_reserva'] = t('evento.valida.url_invalida');
        $e['url_reserva'] = null;
    } elseif ($e['url_reserva'] === null && $e['accion_principal'] === 'reservar') {
        $errores['url_reserva'] = t('evento.valida.reserva_falta');
    }

    // Igual que url_boletos, pero sin obligación ninguna de rellenarlo: es
    // informativo y puede llevarse aunque los otros enlaces también estén llenos.
    $e['sitio_web'] = urlValida((string) ($in['sitio_web'] ?? ''));
    if ($e['sitio_web'] === false) {
        $errores['sitio_web'] = t('evento.valida.url_invalida');
        $e['sitio_web'] = null;
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

/**
 * ¿Ya tiene este organizador otra actividad de la misma categoría, en el
 * mismo estado y ciudad, el mismo día? No compara por título —dos nombres
 * distintos para lo mismo no se detectarían, y dos actividades reales que
 * coincidan en nombre por casualidad sí deberían poder coexistir— así que
 * se usa la combinación que de verdad describe "es la misma actividad
 * repetida sin querer": dónde, cuándo y de qué tipo.
 */
function eventoDuplicado(
    int $usuarioId,
    string $entidad,
    string $ciudad,
    string $categoria,
    string $fechaInicio,
    ?int $excluirId = null
): bool {
    $sql = 'SELECT COUNT(*) FROM eventos
             WHERE usuario_id = ? AND entidad = ? AND ciudad = ? AND categoria = ?
               AND DATE(fecha_inicio) = DATE(?)';
    $params = [$usuarioId, $entidad, $ciudad, $categoria, $fechaInicio];

    if ($excluirId !== null) {
        $sql .= ' AND id != ?';
        $params[] = $excluirId;
    }

    $st = db()->prepare($sql);
    $st->execute($params);

    return (int) $st->fetchColumn() > 0;
}

/** Crea el evento como borrador y devuelve su id. */
function crearEvento(array $e, int $usuarioId): int
{
    $pdo = db();

    $pdo->prepare(
        'INSERT INTO eventos
           (usuario_id, titulo, titulo_en, slug, descripcion, descripcion_en, categoria, tipo_actividad,
            frecuencia, hora_recurrente, hora_fin_recurrente,
            ciudad, entidad, lugar, direccion, mapa_url, latitud, longitud, fecha_inicio, fecha_fin,
            gratuito, precio, forma_pago, cupo_maximo,
            url_boletos, url_reserva, sitio_web, accion_principal,
            imagen_url, color, situacion)
         VALUES (?, ?, ?, "", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "borrador")'
    )->execute([
        $usuarioId, $e['titulo'], $e['titulo_en'], $e['descripcion'], $e['descripcion_en'], $e['categoria'],
        $e['tipo_actividad'], $e['frecuencia'], $e['hora_recurrente'], $e['hora_fin_recurrente'],
        $e['ciudad'], $e['entidad'], $e['lugar'], $e['direccion'],
        $e['mapa_url'], $e['latitud'], $e['longitud'],
        $e['fecha_inicio'], $e['fecha_fin'], $e['gratuito'], $e['precio'],
        $e['forma_pago'], $e['cupo_maximo'],
        $e['url_boletos'], $e['url_reserva'], $e['sitio_web'], $e['accion_principal'],
        $e['imagen_url'], $e['color'],
    ]);

    // El slug lleva el id dentro, así que solo puede calcularse después de
    // insertar. Se guarda vacío y se completa aquí.
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE eventos SET slug = ? WHERE id = ?')
        ->execute([generarSlug($e['titulo'], $id), $id]);

    sincronizarCategoriasEvento($id, $e['categorias']);

    return $id;
}

/**
 * Avisa a los administradores de que se creó una actividad nueva.
 *
 * Se dispara al CREAR, no al publicar: crearEvento() siempre guarda como
 * borrador, así que esto puede avisar de algo que su organizador nunca
 * llegue a publicar. Es a propósito —es la única señal temprana que hay de
 * que alguien está usando el formulario, y publicarEvento() no manda ningún
 * correo (ver revisarAlPublicar() en moderacion.php: el filtro de palabras sí
 * avisa, pero solo si algo suena mal)—.
 *
 * Un correo por actividad, sin agrupar ni espaciar como sí hace
 * avisarAdministradores() con los reportes: crear una actividad no es algo
 * que una misma persona repita en ráfaga como sí pasa cuando varias reportan
 * la misma ficha.
 */
function avisarAdminsNuevaActividad(array $ev): void
{
    $admins = db()->query(
        'SELECT email FROM usuarios WHERE rol = "admin" AND estado = "activo"'
    )->fetchAll();

    if (!$admins) {
        error_log('Actividad ' . $ev['id'] . ' creada y no hay ningún administrador a quien avisar.');
        return;
    }

    $cuerpo = "Se creó una actividad nueva en OMDARA.\n\n"
            . 'Título:     ' . $ev['titulo'] . "\n"
            . 'Categoría:  ' . $ev['categoria'] . "\n"
            . 'Organiza:   ' . ($ev['organizador'] ?? '') . "\n"
            . 'Ciudad:     ' . $ev['ciudad'] . ', ' . $ev['entidad'] . "\n\n"
            . "Todavía es un borrador: nadie más la ve hasta que su organizador la publique.\n\n"
            // Español fijo: el resto del correo también lo está, y el enlace
            // no debería cambiar de prefijo solo porque quien publicó lo hizo
            // desde el formulario en inglés (evento-nuevo.php sí tiene ruta
            // propia en /en; este correo no tiene mecanismo de idioma propio).
            . urlEvento($ev, 'es') . "\n";

    foreach ($admins as $a) {
        enviarCorreo($a['email'], 'Nueva actividad creada: ' . $ev['titulo'], $cuerpo);
    }
}

function actualizarEvento(array $e, int $id): void
{
    db()->prepare(
        'UPDATE eventos SET
            titulo = ?, titulo_en = ?, slug = ?, descripcion = ?, descripcion_en = ?, categoria = ?,
            tipo_actividad = ?, frecuencia = ?, hora_recurrente = ?, hora_fin_recurrente = ?,
            ciudad = ?,
            entidad = ?, lugar = ?, direccion = ?, mapa_url = ?, latitud = ?, longitud = ?,
            fecha_inicio = ?, fecha_fin = ?,
            gratuito = ?, precio = ?, forma_pago = ?, cupo_maximo = ?,
            url_boletos = ?, url_reserva = ?, sitio_web = ?, accion_principal = ?,
            imagen_url = ?, color = ?
          WHERE id = ?'
    )->execute([
        $e['titulo'], $e['titulo_en'], generarSlug($e['titulo'], $id), $e['descripcion'], $e['descripcion_en'],
        $e['categoria'], $e['tipo_actividad'], $e['frecuencia'], $e['hora_recurrente'], $e['hora_fin_recurrente'],
        $e['ciudad'], $e['entidad'], $e['lugar'], $e['direccion'],
        $e['mapa_url'], $e['latitud'], $e['longitud'],
        $e['fecha_inicio'], $e['fecha_fin'], $e['gratuito'], $e['precio'],
        $e['forma_pago'], $e['cupo_maximo'],
        $e['url_boletos'], $e['url_reserva'], $e['sitio_web'], $e['accion_principal'],
        $e['imagen_url'], $e['color'], $id,
    ]);

    sincronizarCategoriasEvento($id, $e['categorias']);
}

/**
 * Reescribe el conjunto de categorías guardado para un evento.
 *
 * Borra y vuelve a insertar en vez de comparar qué cambió: son como mucho
 * EVENTO_CATEGORIAS_MAX filas, y calcular la diferencia costaría más código
 * del que ahorra.
 */
function sincronizarCategoriasEvento(int $eventoId, array $categorias): void
{
    $pdo = db();
    $pdo->prepare('DELETE FROM eventos_categorias WHERE evento_id = ?')->execute([$eventoId]);

    if (!$categorias) return;

    $marcadores = implode(',', array_fill(0, count($categorias), '(?, ?)'));
    $params = [];
    foreach ($categorias as $categoria) {
        $params[] = $eventoId;
        $params[] = $categoria;
    }

    $pdo->prepare("INSERT INTO eventos_categorias (evento_id, categoria) VALUES $marcadores")
        ->execute($params);
}

/**
 * Las categorías guardadas de un evento, en el orden del catálogo.
 *
 * array_intersect() con el catálogo primero, y no al revés, descarta de paso
 * cualquier categoría huérfana que ya no exista —el mismo caso que resuelve
 * migracion-05-categorias.sql para eventos.categoria— sin que haga falta otra
 * comprobación aparte.
 */
function categoriasDeEvento(int $eventoId): array
{
    $st = db()->prepare('SELECT categoria FROM eventos_categorias WHERE evento_id = ?');
    $st->execute([$eventoId]);
    $guardadas = $st->fetchAll(PDO::FETCH_COLUMN);

    return array_values(array_intersect(array_keys(categoriasMenu()), $guardadas));
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
 * "16 de agosto de 2026", sin hora.
 *
 * Para una actividad recurrente: fecha_inicio/fecha_fin son la primera y
 * última ocurrencia, y su hora es la de esa sesión —no la de todas—, así
 * que mezclarla en el rango de fechas confundiría más de lo que aclara.
 */
function fechaCorta(string $fecha): string
{
    static $meses = ['enero','febrero','marzo','abril','mayo','junio','julio',
                     'agosto','septiembre','octubre','noviembre','diciembre'];

    $ts = strtotime($fecha);

    return (int) date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

/**
 * Cuándo es, en una sola línea: "Sábado 17 de agosto, 7:00".
 *
 * Para sitios donde la actividad no es la protagonista sino el contexto —el
 * formulario de contactar al organizador, por ejemplo—, donde hacen falta las
 * tres formas de fecha resueltas en un renglón y no el bloque de tres líneas
 * de la ficha.
 *
 * El día de la semana va escrito aquí y no con setlocale: la traducción del
 * sistema depende de qué locales tenga instalado el servidor, y un hosting
 * compartido sin es_MX devuelve "Saturday" sin avisar de nada.
 */
function fechaResumen(array $ev): string
{
    static $dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    static $meses = ['enero','febrero','marzo','abril','mayo','junio','julio',
                     'agosto','septiembre','octubre','noviembre','diciembre'];

    if (($ev['tipo_actividad'] ?? '') === 'recurrente') {
        return (frecuenciasRecurrencia()[$ev['frecuencia']] ?? t('ficha.varias_fechas'))
             . ', ' . substr((string) $ev['hora_recurrente'], 0, 5);
    }

    $ts = (int) strtotime((string) $ev['fecha_inicio']);

    if (terminaOtroDia($ev)) {
        return t('ficha.del') . ' ' . fechaCorta((string) $ev['fecha_inicio'])
             . ' ' . t('ficha.al') . ' ' . fechaCorta((string) $ev['fecha_fin']);
    }

    $dia = mb_convert_case($dias[(int) date('w', $ts)], MB_CASE_TITLE, 'UTF-8');

    return $dia . ' ' . (int) date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1]
         . ', ' . date('H:i', $ts);
}

/**
 * ¿Termina esta actividad en un día distinto del que empieza?
 *
 * NO vale preguntar si fecha_fin está puesta, que es lo que hacía la ficha.
 * Esa pregunta fue fiable mientras la hora de fin no existía: entonces
 * fecha_fin solo se llenaba al marcar "Termina otro día", así que tenerla
 * puesta y durar varios días eran lo mismo.
 *
 * Desde migracion-08 la hora de fin es obligatoria y validarEvento() cae por
 * defecto al día de inicio cuando no se marca "Termina otro día", de modo que
 * fecha_fin viene llena SIEMPRE. La condición no cambió, cambió lo que mide: la
 * ficha pasó a anunciar «16 de agosto de 2026, 19:30 / hasta el 16 de agosto de
 * 2026, 21:00» —la misma fecha dos veces, presentada con un "hasta el" que
 * promete otro día—.
 *
 * Lo que separa un retiro de una clase de tarde es el día del calendario, no
 * que el dato esté relleno.
 */
function terminaOtroDia(array $ev): bool
{
    if (empty($ev['fecha_fin'])) return false;

    return date('Y-m-d', (int) strtotime((string) $ev['fecha_inicio']))
        !== date('Y-m-d', (int) strtotime((string) $ev['fecha_fin']));
}

/**
 * "de 19:30 a 21:00" para una actividad que empieza y acaba el mismo día.
 *
 * Devuelve solo la hora de inicio en las fichas anteriores a migracion-08, que
 * se guardaron sin hora de fin y tienen fecha_fin en NULL. Siguen teniendo que
 * poder leerse.
 */
function horarioDelDia(array $ev): string
{
    $desde = date('H:i', (int) strtotime((string) $ev['fecha_inicio']));

    if (empty($ev['fecha_fin'])) return $desde;

    return t('ficha.horario_de') . ' ' . $desde . ' ' . t('ficha.horario_a') . ' '
        . date('H:i', (int) strtotime((string) $ev['fecha_fin']));
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

/**
 * La descripción de una actividad, recortada para meta description/Open
 * Graph: sin saltos de línea, a un tamaño que Google y WhatsApp no cortan a
 * media palabra.
 */
function resumenParaMeta(string $texto, int $limite = 160): string
{
    $plano = trim(preg_replace('/\s+/u', ' ', $texto) ?? '');
    if (mb_strlen($plano) <= $limite) return $plano;

    $corte = mb_substr($plano, 0, $limite);
    $ultimoEspacio = mb_strrpos($corte, ' ');
    if ($ultimoEspacio !== false) $corte = mb_substr($corte, 0, $ultimoEspacio);

    return rtrim($corte, '.,;:') . '…';
}

function precioTexto(array $ev): string
{
    if (!empty($ev['gratuito']))    return t('ficha.precio.gratis');
    if ($ev['precio'] === null)     return t('ficha.precio.por_confirmar');

    $texto = '$' . number_format((float) $ev['precio'], 0, '.', ',') . ' MXN';

    // Importa sobre todo en una recurrente: "$400" solo, sin aclarar, no dice
    // si es por toda la serie de sesiones o por cada una.
    if ($ev['forma_pago'] === 'sesion') {
        $texto .= t('ficha.precio.por_sesion');
    }

    return $texto;
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
        't'     => tituloEvento($ev),
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
        'url'   => urlEvento($ev),

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


// ------------------------------------------------------- datos estructurados ----

/**
 * El marcado Schema.org/Event de una actividad, para que Google pueda
 * mostrar fecha, lugar y precio directamente en el resultado de búsqueda
 * (rich result) en vez de solo un enlace y una descripción.
 *
 * SIN HORA LOCAL EXPLÍCITA A PROPÓSITO: fecha_inicio/fecha_fin no guardan
 * zona horaria —México tiene varias—, así que en vez de adivinar un offset
 * se manda la fecha "pelada" (2026-08-29T10:00:00). Es justo lo que Google
 * documenta como válido cuando no se conoce el offset: la interpreta como
 * hora local del lugar del evento, que es exactamente lo que es.
 *
 * SIEMPRE PRESENCIAL: este directorio no tiene actividades en línea —todas
 * piden ciudad, estado y lugar—, así que eventAttendanceMode es siempre
 * "Offline" y no hace falta comprobar nada para decidirlo.
 *
 * SIEMPRE "programado": no hay una acción de "cancelar" o "posponer" en el
 * modelo de datos —lo que existe es ocultar, que ya deja de mostrar la
 * ficha entera—, así que eventStatus es siempre EventScheduled: si algo se
 * cancela de verdad, se oculta, y una ficha oculta no llega a llamar a esta
 * función porque nunca se le pide a un visitante.
 *
 * "offers" SOLO SI EL PRECIO ES UN DATO REAL: "gratuito" cuenta como precio
 * cero, pero "de pago, precio por confirmar" (precio NULL) no es un número
 * que Google pueda anunciar sin mentir, así que ahí se omite el bloque
 * entero en vez de inventar un valor.
 */
function datosEstructuradosEvento(array $ev): array
{
    $datos = [
        '@context'            => 'https://schema.org',
        '@type'               => 'Event',
        'name'                => tituloEvento($ev),
        'description'         => descripcionEvento($ev),
        'startDate'           => fechaIso($ev['fecha_inicio']),
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendedMode',
        'eventStatus'         => 'https://schema.org/EventScheduled',
        'url'                 => urlEvento($ev),
        'location'            => [
            '@type'   => 'Place',
            'name'    => $ev['lugar'],
            'address' => array_filter([
                '@type'           => 'PostalAddress',
                'streetAddress'   => $ev['direccion'] ?: null,
                'addressLocality' => $ev['ciudad'],
                'addressRegion'   => $ev['entidad'],
                'addressCountry'  => 'MX',
            ]),
        ],
        'organizer' => [
            '@type' => 'Organization',
            'name'  => $ev['organizador'] ?? '',
        ],
    ];

    if (!empty($ev['fecha_fin'])) {
        $datos['endDate'] = fechaIso($ev['fecha_fin']);
    }

    $imagen = urlImagen($ev['imagen_url'] ?? null);
    if ($imagen !== null) {
        $datos['image'] = [$imagen];
    }

    if (eventoTienePunto($ev)) {
        $datos['location']['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $ev['latitud'],
            'longitude' => (float) $ev['longitud'],
        ];
    }

    $precioConocido = !empty($ev['gratuito']) || $ev['precio'] !== null;
    if ($precioConocido) {
        $urlOferta = null;
        if ($ev['accion_principal'] === 'boletos' && !empty($ev['url_boletos'])) {
            $urlOferta = URL_BASE . '/salida.php?id=' . (int) $ev['id'] . '&tipo=boletos';
        } elseif ($ev['accion_principal'] === 'reservar' && !empty($ev['url_reserva'])) {
            $urlOferta = URL_BASE . '/salida.php?id=' . (int) $ev['id'] . '&tipo=reservar';
        }
        if ($urlOferta === null) $urlOferta = urlEvento($ev);

        $datos['offers'] = [
            '@type'         => 'Offer',
            'url'           => $urlOferta,
            'price'         => !empty($ev['gratuito']) ? '0' : (string) $ev['precio'],
            'priceCurrency' => 'MXN',
            'availability'  => 'https://schema.org/InStock',
        ];
    }

    return $datos;
}


// --------------------------------------- correo de contacto por actividad --
// Migración 24, requerimiento del cliente (2026-09-02). Ver el comentario de
// arriba de CORREO_CONTACTO_VIGENCIA_MIN y el de la migración para el porqué
// de una tabla aparte de codigos_acceso.

/**
 * El correo que de verdad recibe "Contactar al organizador" para esta
 * actividad: el que confirmó su dueño para ella, o el de su cuenta si no ha
 * puesto ninguno —el comportamiento de siempre—.
 *
 * Espera la fila tal como la devuelve buscarEvento(): con correo_contacto (la
 * columna de eventos) y organizador_email (el alias del JOIN a usuarios).
 */
function correoContactoEvento(array $ev): string
{
    $propio = trim((string) ($ev['correo_contacto'] ?? ''));

    return $propio !== '' ? $propio : (string) ($ev['organizador_email'] ?? '');
}

/**
 * ¿Hay un código pedido y todavía vivo para esta actividad? Si lo hay, a qué
 * correo se mandó —lo necesita el formulario para saber qué pantalla enseñar
 * y a quién le está pidiendo el código a quien lo escribe—.
 */
function correoContactoPendiente(int $eventoId): ?string
{
    $st = db()->prepare(
        'SELECT email FROM codigos_correo_contacto
          WHERE evento_id = ? AND usado_en IS NULL AND expira_en > NOW()
       ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$eventoId]);
    $email = $st->fetchColumn();

    return $email !== false ? (string) $email : null;
}

/** Limpieza de códigos caducados. Mismo criterio que purgarCodigosViejos() en
 *  includes/auth.php: 1 de cada 50 en vez de en cada petición. */
function purgarCodigosCorreoContactoViejos(): void
{
    if (random_int(1, 50) !== 1) return;

    db()->prepare('DELETE FROM codigos_correo_contacto WHERE expira_en < DATE_SUB(NOW(), INTERVAL 1 DAY)')
        ->execute();
}

/**
 * Genera un código, lo guarda y lo manda al correo NUEVO —no al de la cuenta—.
 *
 * El correo puede ser cualquiera que quien edita se invente: por eso los
 * frenos son por actividad Y por IP, igual que solicitarCodigo() en
 * includes/auth.php frena por correo Y por IP. Sin el de IP, este formulario
 * sería una forma barata de mandarle "confirma tu correo" a cualquier buzón
 * ajeno con el nombre de una actividad real puesto delante.
 *
 * @return array{0:bool,1:string} [ok, mensaje para enseñar]
 */
function solicitarCodigoCorreoContacto(int $eventoId, string $email, string $tituloEvento): array
{
    $email = mb_strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        return [false, t('evento.correo_contacto.error_invalido')];
    }

    $pdo = db();
    purgarCodigosCorreoContactoViejos();

    $espera = CORREO_CONTACTO_ESPERA_SEG;
    $st = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(creado_en > DATE_SUB(NOW(), INTERVAL $espera SECOND)), 0) AS recientes
           FROM codigos_correo_contacto
          WHERE evento_id = ? AND creado_en > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    $st->execute([$eventoId]);
    $porActividad = $st->fetch();

    if ((int) $porActividad['recientes'] > 0) {
        return [false, t('evento.correo_contacto.error_espera')];
    }
    if ((int) $porActividad['total'] >= CORREO_CONTACTO_MAX_POR_HORA) {
        return [false, t('evento.correo_contacto.error_demasiados')];
    }

    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM codigos_correo_contacto
          WHERE ip = ? AND creado_en > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
    );
    $st->execute([ipBinaria()]);

    if ((int) $st->fetchColumn() >= CORREO_CONTACTO_MAX_IP_HORA) {
        return [false, t('evento.correo_contacto.error_demasiadas_ip')];
    }

    // Un código vivo por actividad: pedir uno nuevo invalida el anterior,
    // mismo motivo que en solicitarCodigo() —si no, quien pide dos seguidos
    // para dos correos distintos tiene los dos vivos a la vez—.
    $pdo->prepare('UPDATE codigos_correo_contacto SET usado_en = NOW() WHERE evento_id = ? AND usado_en IS NULL')
        ->execute([$eventoId]);

    $codigo   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $vigencia = CORREO_CONTACTO_VIGENCIA_MIN;

    $pdo->prepare(
        "INSERT INTO codigos_correo_contacto (evento_id, email, codigo_hash, expira_en, ip)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL $vigencia MINUTE), ?)"
    )->execute([$eventoId, $email, password_hash($codigo, PASSWORD_DEFAULT), ipBinaria()]);

    $id = (int) $pdo->lastInsertId();

    if (!enviarCodigoCorreoContacto($email, $codigo, $vigencia, $tituloEvento)) {
        $pdo->prepare('DELETE FROM codigos_correo_contacto WHERE id = ?')->execute([$id]);
        return [false, t('auth.error_envio')];
    }

    return [true, sprintf(t('evento.correo_contacto.enviado'), $email)];
}

/**
 * Comprueba el código y, si es el bueno, deja ese correo como el de contacto
 * de la actividad.
 *
 * @return array{0:bool,1:string} [ok, mensaje para enseñar]
 */
function confirmarCodigoCorreoContacto(int $eventoId, string $codigo): array
{
    $codigo = preg_replace('/\D+/', '', $codigo);

    if (strlen((string) $codigo) !== 6) {
        return [false, t('auth.codigo_formato')];
    }

    $pdo = db();

    $st = $pdo->prepare(
        'SELECT id, email, codigo_hash, intentos
           FROM codigos_correo_contacto
          WHERE evento_id = ? AND usado_en IS NULL AND expira_en > NOW()
       ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$eventoId]);
    $fila = $st->fetch();

    if (!$fila) {
        return [false, t('auth.codigo_caducado')];
    }

    if ((int) $fila['intentos'] >= CORREO_CONTACTO_MAX_INTENTOS) {
        $pdo->prepare('UPDATE codigos_correo_contacto SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);
        return [false, t('auth.demasiados_intentos')];
    }

    // El intento se cuenta ANTES de comprobar, mismo motivo que en
    // verificarCodigo() (includes/auth.php): contarlo después deja la puerta
    // abierta a quien corte la conexión al ver que falla.
    $pdo->prepare('UPDATE codigos_correo_contacto SET intentos = intentos + 1 WHERE id = ?')->execute([$fila['id']]);

    if (!password_verify((string) $codigo, $fila['codigo_hash'])) {
        $quedan = CORREO_CONTACTO_MAX_INTENTOS - ((int) $fila['intentos'] + 1);
        return [false, $quedan > 0
            ? sprintf(t('auth.codigo_incorrecto_quedan'), $quedan)
            : t('auth.codigo_incorrecto_final')];
    }

    $pdo->prepare('UPDATE codigos_correo_contacto SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);
    $pdo->prepare('UPDATE eventos SET correo_contacto = ? WHERE id = ?')->execute([$fila['email'], $eventoId]);

    return [true, sprintf(t('evento.correo_contacto.confirmado'), $fila['email'])];
}

/** Cancela el código pendiente, sin esperar a que caduque. Vuelve a la
 *  pantalla de "escribe un correo" en vez de dejar colgada la de "escribe
 *  el código" cuando quien edita se arrepiente o se equivocó al escribirlo. */
function cancelarCodigoCorreoContacto(int $eventoId): void
{
    db()->prepare('UPDATE codigos_correo_contacto SET usado_en = NOW() WHERE evento_id = ? AND usado_en IS NULL')
        ->execute([$eventoId]);
}

/** Vuelve a usar el correo de la cuenta: no hace falta código, porque ese ya
 *  está verificado —es al que llega el código de acceso—. */
function quitarCorreoContactoEvento(int $eventoId): void
{
    db()->prepare('UPDATE eventos SET correo_contacto = NULL WHERE id = ?')->execute([$eventoId]);
}
