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
 * Las categorías, con el icono que usa el menú de la portada.
 *
 * Esta lista es la única fuente: el menú lineal de la portada se dibuja desde
 * aquí y el desplegable del formulario también. Cuando estaban escritas en dos
 * sitios, una categoría nueva aparecía en el menú y no se podía elegir al
 * publicar, o al revés.
 */
function categoriasMenu(): array
{
    // La clave es el nombre que se guarda en la base; la etiqueta es lo que se
    // lee en el menú. Hoy coinciden todas, pero siguen separadas porque son dos
    // cosas distintas: cambiar cómo se lee una categoría en pantalla no debería
    // obligar a tocar lo que hay guardado en miles de filas.
    //
    // El orden no es alfabético a propósito: el carril de la portada se lee de
    // izquierda a derecha y casi nadie llega al final, así que va agrupado por
    // parentesco —práctica, ceremonia, aire libre, cuidado del cuerpo— y lo más
    // buscado primero.
    return [
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
        'diaria'    => 'Diaria',
        'semanal'   => 'Semanal',
        'quincenal' => 'Quincenal',
        'mensual'   => 'Mensual',
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
        'informacion' => 'Contactar al organizador',
        'boletos'     => 'Comprar boletos',
        'reservar'    => 'Reservar lugar',
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
        'titulo'          => 'Título de la actividad',
        'categorias'      => 'Categorías',
        'descripcion'     => 'Descripción',
        'ciudad'          => 'Ciudad',
        'entidad'         => 'Estado',
        'lugar'           => 'Nombre del lugar',
        'direccion'       => 'Dirección',
        'mapa_url'        => 'Enlace de Google Maps',

        // No es un campo de la actividad —vive en la cuenta del organizador,
        // ver guardarContactoOrganizador() en includes/auth.php— pero
        // REQ-000-XX lo volvió obligatorio en el formulario, así que necesita
        // su propia etiqueta aquí igual que cualquier otro campo requerido.
        'org_nombre'      => 'Nombre del organizador',

        // Actividad de un día.
        'fecha_unica'        => 'Fecha',
        'hora_inicio_unica'  => 'Hora de inicio',
        'hora_fin_unica'     => 'Hora de fin',
        'fecha_fin_unica'    => 'Fecha de fin',

        // Actividad recurrente.
        'fecha_inicio_rec'   => 'Fecha de inicio',
        'fecha_fin_rec'      => 'Fecha de fin',
        'frecuencia'         => 'Frecuencia',
        'hora_recurrente'    => 'Hora de inicio',
        'hora_fin_recurrente' => 'Hora de fin',

        'precio'          => 'Precio',
        'forma_pago'      => 'Forma de pago',
        'cupo_maximo'     => 'Cupo máximo',
        'url_boletos'     => 'URL de compra',
        'url_reserva'     => 'URL de reserva',
        'sitio_web'       => 'Sitio web o enlace',
        'accion_principal' => 'Acción principal',
        'imagen'          => 'Imagen',
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
    if (mb_strlen($e['descripcion']) < 50) {
        $errores['descripcion'] = 'Agrega una descripción más completa (mínimo 50 caracteres).';
    } elseif (mb_strlen($e['descripcion']) > 2000) {
        $errores['descripcion'] = 'La descripción no puede pasar de 2,000 caracteres.';
    }

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
        $errores['categorias'] = 'Elige al menos una categoría de la lista.';
    } elseif (count($categoriasValidas) > EVENTO_CATEGORIAS_MAX) {
        $errores['categorias'] = 'Elige como máximo ' . EVENTO_CATEGORIAS_MAX . ' categorías.';
    }

    $e['categorias'] = array_slice($categoriasValidas, 0, EVENTO_CATEGORIAS_MAX);
    $e['categoria']  = $e['categorias'][0] ?? '';

    $e['entidad'] = trim((string) ($in['entidad'] ?? ''));
    if (!in_array($e['entidad'], estadosMexico(), true)) {
        $errores['entidad'] = 'Elige un estado de la lista.';
    }

    $e['ciudad'] = trim((string) ($in['ciudad'] ?? ''));
    $municipiosDelEstado = municipiosPorEstado()[$e['entidad']] ?? [];
    if (!in_array($e['ciudad'], $municipiosDelEstado, true)) {
        $errores['ciudad'] = $e['entidad'] === ''
            ? 'Elige primero el estado.'
            : 'Elige una ciudad de la lista.';
    }

    $e['lugar'] = trim((string) ($in['lugar'] ?? ''));
    if ($e['lugar'] === '') $errores['lugar'] = 'Falta el lugar donde se realiza.';

    // Aparte del nombre del lugar: la calle y número, para quien quiera
    // corregir o completar lo que puso el geocoding automático.
    $e['direccion'] = trim((string) ($in['direccion'] ?? ''));
    if (mb_strlen($e['direccion']) > 255) {
        $errores['direccion'] = 'La dirección no puede pasar de 255 caracteres.';
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
            $errores['frecuencia'] = 'Elige cada cuánto se repite.';
            $e['frecuencia'] = null;
        }

        $e['hora_recurrente'] = $horaValida(trim((string) ($in['hora_recurrente'] ?? '')));
        if ($e['hora_recurrente'] === null) {
            $errores['hora_recurrente'] = 'Pon la hora a la que empieza cada sesión.';
        }

        $e['hora_fin_recurrente'] = $horaValida(trim((string) ($in['hora_fin_recurrente'] ?? '')));
        if ($e['hora_fin_recurrente'] === null) {
            $errores['hora_fin_recurrente'] = 'Pon la hora a la que termina cada sesión.';
        } elseif ($e['hora_recurrente'] !== null && $e['hora_fin_recurrente'] <= $e['hora_recurrente']) {
            $errores['hora_fin_recurrente'] = 'El final no puede ser antes que el inicio.';
        }

        $inicioRec = trim((string) ($in['fecha_inicio_rec'] ?? ''));
        $inicioRecValido = ($inicioRec !== '' && strtotime($inicioRec) !== false) ? $inicioRec : null;
        if ($inicioRecValido === null) {
            $errores['fecha_inicio_rec'] = 'Pon la fecha en la que empieza a repetirse.';
        }

        $finRec = trim((string) ($in['fecha_fin_rec'] ?? ''));
        $finRecValido = ($finRec !== '' && strtotime($finRec) !== false) ? $finRec : null;
        if ($finRecValido === null) {
            $errores['fecha_fin_rec'] = 'Pon la fecha en la que termina de repetirse.';
        }

        $e['fecha_inicio'] = ($inicioRecValido !== null && $e['hora_recurrente'] !== null)
            ? normalizarFecha($inicioRecValido . 'T' . $e['hora_recurrente']) : null;

        $e['fecha_fin'] = ($finRecValido !== null && $e['hora_fin_recurrente'] !== null)
            ? normalizarFecha($finRecValido . 'T' . $e['hora_fin_recurrente']) : null;

        if ($e['fecha_fin'] !== null && $e['fecha_inicio'] !== null
            && strtotime($e['fecha_fin']) < strtotime($e['fecha_inicio'])) {
            $errores['fecha_fin_rec'] = 'El final no puede ser anterior al principio.';
        }
    } else {
        $e['frecuencia']          = null;
        $e['hora_recurrente']     = null;
        $e['hora_fin_recurrente'] = null;

        $fechaUnica = trim((string) ($in['fecha_unica'] ?? ''));
        $fechaUnicaValida = ($fechaUnica !== '' && strtotime($fechaUnica) !== false) ? $fechaUnica : null;
        if ($fechaUnicaValida === null) {
            $errores['fecha_unica'] = 'Pon la fecha de la actividad.';
        }

        $horaIniValida = $horaValida(trim((string) ($in['hora_inicio_unica'] ?? '')));
        if ($horaIniValida === null) {
            $errores['hora_inicio_unica'] = 'Pon la hora de inicio.';
        }

        $horaFinValida = $horaValida(trim((string) ($in['hora_fin_unica'] ?? '')));
        if ($horaFinValida === null) {
            $errores['hora_fin_unica'] = 'Pon la hora de fin.';
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
            $errores['hora_fin_unica'] = 'El final no puede ser anterior al principio.';
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
            $errores[$campo] = 'Esa fecha ya pasó, así que la actividad no aparecería en el listado.';
        }
    }

    $e['gratuito'] = ($in['precio_modo'] ?? '') === 'de_pago' ? 0 : 1;

    if ($e['gratuito']) {
        $e['precio']     = null;
        $e['forma_pago'] = null;
    } else {
        $precio = str_replace([',', ' '], '', (string) ($in['precio'] ?? ''));

        if ($precio === '') {
            $errores['precio'] = 'Pon el precio, o marca que es sin costo.';
            $e['precio'] = null;
        } elseif (!is_numeric($precio) || (float) $precio < 0) {
            $errores['precio'] = 'El precio tiene que ser un número.';
            $e['precio'] = null;
        } else {
            $e['precio'] = round((float) $precio, 2);
        }

        $e['forma_pago'] = (string) ($in['forma_pago'] ?? '');
        if (!in_array($e['forma_pago'], ['completa', 'sesion'], true)) {
            $errores['forma_pago'] = 'Elige si el precio es por toda la actividad o por sesión.';
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
            $errores['cupo_maximo'] = 'El cupo tiene que ser un número entero mayor que cero.';
        } else {
            $e['cupo_maximo'] = (int) $cupo;
        }
    }

    $e['accion_principal'] = (string) ($in['accion_principal'] ?? '');
    if (!isset(accionesPrincipales()[$e['accion_principal']])) {
        $errores['accion_principal'] = 'Elige qué esperas que haga quien vea la ficha.';
    }

    // Cada acción principal pide su propio enlace. Solo es obligatorio el de
    // la acción elegida: el otro puede quedar vacío sin que bloquee el envío.
    $e['url_boletos'] = urlValida((string) ($in['url_boletos'] ?? ''));
    if ($e['url_boletos'] === false) {
        $errores['url_boletos'] = 'Esa dirección no parece válida. Empieza por https://';
        $e['url_boletos'] = null;
    } elseif ($e['url_boletos'] === null && $e['accion_principal'] === 'boletos') {
        $errores['url_boletos'] = 'Agrega el enlace donde se compran los boletos.';
    }

    $e['url_reserva'] = urlValida((string) ($in['url_reserva'] ?? ''));
    if ($e['url_reserva'] === false) {
        $errores['url_reserva'] = 'Esa dirección no parece válida. Empieza por https://';
        $e['url_reserva'] = null;
    } elseif ($e['url_reserva'] === null && $e['accion_principal'] === 'reservar') {
        $errores['url_reserva'] = 'Agrega el enlace donde se reserva el lugar.';
    }

    // Igual que url_boletos, pero sin obligación ninguna de rellenarlo: es
    // informativo y puede llevarse aunque los otros enlaces también estén llenos.
    $e['sitio_web'] = urlValida((string) ($in['sitio_web'] ?? ''));
    if ($e['sitio_web'] === false) {
        $errores['sitio_web'] = 'Esa dirección no parece válida. Empieza por https://';
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
           (usuario_id, titulo, slug, descripcion, categoria, tipo_actividad,
            frecuencia, hora_recurrente, hora_fin_recurrente,
            ciudad, entidad, lugar, direccion, mapa_url, latitud, longitud, fecha_inicio, fecha_fin,
            gratuito, precio, forma_pago, cupo_maximo,
            url_boletos, url_reserva, sitio_web, accion_principal,
            imagen_url, color, situacion)
         VALUES (?, ?, "", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "borrador")'
    )->execute([
        $usuarioId, $e['titulo'], $e['descripcion'], $e['categoria'],
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
            . urlEvento($ev) . "\n";

    foreach ($admins as $a) {
        enviarCorreo($a['email'], 'Nueva actividad creada: ' . $ev['titulo'], $cuerpo);
    }
}

function actualizarEvento(array $e, int $id): void
{
    db()->prepare(
        'UPDATE eventos SET
            titulo = ?, slug = ?, descripcion = ?, categoria = ?,
            tipo_actividad = ?, frecuencia = ?, hora_recurrente = ?, hora_fin_recurrente = ?,
            ciudad = ?,
            entidad = ?, lugar = ?, direccion = ?, mapa_url = ?, latitud = ?, longitud = ?,
            fecha_inicio = ?, fecha_fin = ?,
            gratuito = ?, precio = ?, forma_pago = ?, cupo_maximo = ?,
            url_boletos = ?, url_reserva = ?, sitio_web = ?, accion_principal = ?,
            imagen_url = ?, color = ?
          WHERE id = ?'
    )->execute([
        $e['titulo'], generarSlug($e['titulo'], $id), $e['descripcion'],
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
        return (frecuenciasRecurrencia()[$ev['frecuencia']] ?? 'Varias fechas')
             . ', ' . substr((string) $ev['hora_recurrente'], 0, 5);
    }

    $ts = (int) strtotime((string) $ev['fecha_inicio']);

    if (terminaOtroDia($ev)) {
        return 'Del ' . fechaCorta((string) $ev['fecha_inicio'])
             . ' al ' . fechaCorta((string) $ev['fecha_fin']);
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

    return 'de ' . $desde . ' a ' . date('H:i', (int) strtotime((string) $ev['fecha_fin']));
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
    if (!empty($ev['gratuito']))    return 'Gratis';
    if ($ev['precio'] === null)     return 'Por confirmar';

    $texto = '$' . number_format((float) $ev['precio'], 0, '.', ',') . ' MXN';

    // Importa sobre todo en una recurrente: "$400" solo, sin aclarar, no dice
    // si es por toda la serie de sesiones o por cada una.
    if ($ev['forma_pago'] === 'sesion') {
        $texto .= ' / sesión';
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
