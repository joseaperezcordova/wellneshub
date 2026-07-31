<?php
/**
 * El módulo de métricas: lo que hay detrás de metricas.php y de las cifras
 * reales del dashboard de admin.php.
 *
 * Todo lo de aquí sale de las tablas que ya existen —eventos, usuarios,
 * contactos, clics—. No hay nada conectado a un servicio externo (Google
 * Analytics, etc.): eso es aparte y no vive en este archivo.
 */

declare(strict_types=1);

/**
 * Registra un clic en "Comprar boletos" / "Reservar lugar".
 *
 * Un enlace externo no se puede contar solo: por eso pasa primero por
 * salida.php, que llama esto y de inmediato redirige a la URL real.
 */
function registrarClic(int $eventoId, string $tipo): void
{
    if (!in_array($tipo, ['boletos', 'reservar'], true)) return;

    db()->prepare(
        'INSERT INTO clics (evento_id, tipo, ip) VALUES (?, ?, ?)'
    )->execute([$eventoId, $tipo, ipBinaria()]);
}

function contarActividadesPublicadas(): int
{
    return (int) db()->query(
        "SELECT COUNT(*) FROM eventos WHERE situacion = 'publicado'"
    )->fetchColumn();
}

/** Publicadas con fecha de inicio dentro de los próximos $dias días. */
function contarActividadesProximas(int $dias = 7): int
{
    $st = db()->prepare(
        "SELECT COUNT(*) FROM eventos
          WHERE situacion = 'publicado'
            AND fecha_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)"
    );
    $st->execute([$dias]);

    return (int) $st->fetchColumn();
}

/** Publicadas cuyo último día ya pasó —siguen en el sitio, pero no en el listado. */
function contarActividadesExpiradas(): int
{
    return (int) db()->query(
        "SELECT COUNT(*) FROM eventos
          WHERE situacion = 'publicado'
            AND COALESCE(fecha_fin, fecha_inicio) < NOW()"
    )->fetchColumn();
}

/** Organizadores con al menos una actividad publicada. */
function contarOrganizadoresActivos(): int
{
    return (int) db()->query(
        "SELECT COUNT(DISTINCT usuario_id) FROM eventos WHERE situacion = 'publicado'"
    )->fetchColumn();
}

function contarMensajesContacto(int $dias): int
{
    $st = db()->prepare(
        'SELECT COUNT(*) FROM contactos WHERE creado_en > DATE_SUB(NOW(), INTERVAL ? DAY)'
    );
    $st->execute([$dias]);

    return (int) $st->fetchColumn();
}

function contarClics(string $tipo, int $dias): int
{
    $st = db()->prepare(
        'SELECT COUNT(*) FROM clics WHERE tipo = ? AND creado_en > DATE_SUB(NOW(), INTERVAL ? DAY)'
    );
    $st->execute([$tipo, $dias]);

    return (int) $st->fetchColumn();
}

/**
 * Actividades publicadas acumuladas, mes a mes, para la gráfica de
 * crecimiento. Cuenta por publicado_en —cuándo se hizo pública—, no por
 * fecha_inicio —cuándo ocurre—: son cosas distintas y la que mide
 * crecimiento del directorio es la primera.
 *
 * @return array<int, array{mes:string, total:int}> el más antiguo primero
 */
function actividadesPublicadasPorMes(int $meses = 8): array
{
    $st = db()->prepare(
        "SELECT DATE_FORMAT(publicado_en, '%Y-%m') AS mes, COUNT(*) AS total
           FROM eventos
          WHERE situacion IN ('publicado', 'oculto')
            AND publicado_en IS NOT NULL
            AND publicado_en >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL ? MONTH)
       GROUP BY mes
       ORDER BY mes ASC"
    );
    $st->execute([$meses - 1]);
    $porMes = $st->fetchAll();

    // Total de antes de la ventana, para que el acumulado arranque en el
    // número real y no en cero —si el directorio ya tenía 80 actividades
    // antes del primer mes mostrado, la curva tiene que empezar en 80.
    $st2 = db()->prepare(
        "SELECT COUNT(*) FROM eventos
          WHERE situacion IN ('publicado', 'oculto')
            AND publicado_en IS NOT NULL
            AND publicado_en < DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL ? MONTH)"
    );
    $st2->execute([$meses - 1]);
    $acumulado = (int) $st2->fetchColumn();

    $porMesIndexado = [];
    foreach ($porMes as $fila) {
        $porMesIndexado[$fila['mes']] = (int) $fila['total'];
    }

    $resultado = [];
    $ts = strtotime(date('Y-m-01', strtotime('-' . ($meses - 1) . ' months')));
    for ($i = 0; $i < $meses; $i++) {
        $clave = date('Y-m', $ts);
        $acumulado += $porMesIndexado[$clave] ?? 0;
        $resultado[] = ['mes' => $clave, 'total' => $acumulado];
        $ts = strtotime('+1 month', $ts);
    }

    return $resultado;
}

/** @return array<int, array{n:string, v:int}> */
function categoriasTop(int $limite = 6): array
{
    $st = db()->prepare(
        "SELECT categoria AS n, COUNT(*) AS v
           FROM eventos
          WHERE situacion = 'publicado'
       GROUP BY categoria
       ORDER BY v DESC, categoria ASC
          LIMIT " . (int) $limite
    );
    $st->execute();

    return array_map(
        static fn(array $f): array => ['n' => (string) $f['n'], 'v' => (int) $f['v']],
        $st->fetchAll()
    );
}

/** @return array<int, array{n:string, v:int}> */
function ciudadesTop(int $limite = 6): array
{
    $st = db()->prepare(
        "SELECT ciudad AS n, COUNT(*) AS v
           FROM eventos
          WHERE situacion = 'publicado' AND ciudad != ''
       GROUP BY ciudad
       ORDER BY v DESC, ciudad ASC
          LIMIT " . (int) $limite
    );
    $st->execute();

    return array_map(
        static fn(array $f): array => ['n' => (string) $f['n'], 'v' => (int) $f['v']],
        $st->fetchAll()
    );
}

/**
 * Las 20 categorías del catálogo, cada una con cuántas actividades
 * publicadas tiene —incluidas las que tienen cero—. A diferencia de
 * categoriasTop(), esto no es un ranking para una gráfica: es el catálogo
 * completo para el panel de administración, así que nada se recorta.
 *
 * @return array<int, array{nombre:string, icono:string, total:int}>
 */
function categoriasConConteo(): array
{
    $st = db()->query(
        "SELECT categoria, COUNT(*) AS total
           FROM eventos
          WHERE situacion = 'publicado'
       GROUP BY categoria"
    );
    $conteos = array_column($st->fetchAll(), 'total', 'categoria');

    $resultado = [];
    foreach (categoriasMenu() as $nombre => $datos) {
        $resultado[] = ['nombre' => $nombre, 'icono' => $datos[0], 'total' => (int) ($conteos[$nombre] ?? 0)];
    }

    usort($resultado, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);

    return $resultado;
}

/**
 * Estados con actividad publicada, con cuántas ciudades distintas tienen
 * cada uno. Solo los que ya tienen algo —a diferencia de categorías, aquí
 * enumerar los 32 estados completos con la mayoría en cero no ayuda a
 * nadie, y municipiosPorEstado() son casi 2,500 filas: no hay catálogo
 * chico que recorrer para rellenar los que faltan.
 *
 * @return array<int, array{nombre:string, ciudades:int, actividades:int}>
 */
function estadosConConteo(): array
{
    $st = db()->query(
        "SELECT entidad AS nombre, COUNT(DISTINCT ciudad) AS ciudades, COUNT(*) AS actividades
           FROM eventos
          WHERE situacion = 'publicado' AND entidad != ''
       GROUP BY entidad
       ORDER BY actividades DESC, nombre ASC"
    );

    return array_map(
        static fn(array $f): array => [
            'nombre'      => (string) $f['nombre'],
            'ciudades'    => (int) $f['ciudades'],
            'actividades' => (int) $f['actividades'],
        ],
        $st->fetchAll()
    );
}

/**
 * Ciudades con actividad publicada, sin el límite de ciudadesTop() —esa es
 * para la gráfica de la portada de métricas; esta es el listado completo
 * del panel de administración.
 *
 * @return array<int, array{nombre:string, entidad:string, actividades:int}>
 */
function ciudadesConConteo(): array
{
    $st = db()->query(
        "SELECT ciudad AS nombre, entidad, COUNT(*) AS actividades
           FROM eventos
          WHERE situacion = 'publicado' AND ciudad != ''
       GROUP BY ciudad, entidad
       ORDER BY actividades DESC, ciudad ASC"
    );

    return array_map(
        static fn(array $f): array => [
            'nombre'      => (string) $f['nombre'],
            'entidad'     => (string) $f['entidad'],
            'actividades' => (int) $f['actividades'],
        ],
        $st->fetchAll()
    );
}
