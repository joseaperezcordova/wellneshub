<?php
/**
 * Datos del buscador, en JSON.
 *
 * buscar.php pide esto mismo en cada carga —primera página incluida— y en
 * cada «Cargar más»: un solo camino para las dos cosas, en vez de que la
 * carga inicial y la paginación tuvieran cada una su propia lógica y se
 * fueran desincronizando con el tiempo.
 *
 * GET, público, de solo lectura: los mismos filtros que ya validaba
 * filtrosDesdePeticion() para buscar.php, más "offset" para la página.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';
require_once __DIR__ . '/includes/busqueda.php';

const BUSCAR_PAGINA = 24;

header('Content-Type: application/json; charset=utf-8');

$filtros = filtrosDesdePeticion($_GET);
$offset  = max(0, (int) ($_GET['offset'] ?? 0));

$resultado = eventosBuscar($filtros, BUSCAR_PAGINA, $offset);

echo json_encode([
    'total'   => $resultado['total'],
    'eventos' => array_map('eventoParaTarjeta', $resultado['eventos']),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
