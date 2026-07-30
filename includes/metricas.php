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
