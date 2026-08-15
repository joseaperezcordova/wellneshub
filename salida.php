<?php
/**
 * Salida hacia el enlace externo de "Comprar boletos" / "Reservar lugar".
 *
 * Un enlace directo al sitio del organizador no se puede contar. Este paso
 * de en medio es la única forma: registra el clic y de inmediato redirige a
 * la URL real. No hay pantalla ni espera — quien lo pulsa no debería notar
 * que pasó por aquí.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';

$ev   = buscarEvento((int) ($_GET['id'] ?? 0));
$tipo = (string) ($_GET['tipo'] ?? '');

if (!$ev || $ev['situacion'] !== 'publicado' || !in_array($tipo, ['boletos', 'reservar'], true)) {
    http_response_code(404);
    exit;
}

$destino = $tipo === 'boletos' ? $ev['url_boletos'] : $ev['url_reserva'];

if (empty($destino)) {
    http_response_code(404);
    exit;
}

registrarClic((int) $ev['id'], $tipo);

header('Location: ' . $destino, true, 302);
exit;
