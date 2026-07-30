<?php
/**
 * Endpoint AJAX: convierte un enlace de Google Maps pegado a mano en un
 * lat/lng para mover el pin del formulario.
 *
 * Ver includes/mapa.php → resolverEnlaceMaps(). Solo pide sesión, no rol de
 * admin: cualquiera que pueda llegar al formulario de publicar puede usarlo.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$u = usuarioActual();
if (!$u) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Tu sesión caducó. Recarga la página.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValido($_POST['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'La sesión caducó. Recarga la página e inténtalo otra vez.']);
    exit;
}

$enlace = trim((string) ($_POST['enlace'] ?? ''));

if ($enlace === '' || !filter_var($enlace, FILTER_VALIDATE_URL)) {
    echo json_encode(['ok' => false, 'error' => 'Eso no parece un enlace.']);
    exit;
}

$punto = resolverEnlaceMaps($enlace);

if (!$punto) {
    echo json_encode(['ok' => false, 'error' => 'No pudimos leer la ubicación de ese enlace. Mueve el pin a mano.']);
    exit;
}

echo json_encode(['ok' => true, 'lat' => $punto[0], 'lng' => $punto[1]]);
