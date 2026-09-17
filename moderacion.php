<?php
/**
 * Bandeja de moderación — ahora vive dentro del panel admin, pestaña
 * "Reportes" (Req. 17092026 punto 4: el cliente pidió las actividades
 * reportadas integradas al mismo dashboard, no en su propia puerta).
 *
 * Este archivo se queda como redirección y no se borra: es el enlace que
 * cualquiera pudo haber guardado o compartido, y moverlo sin dejar rastro
 * lo habría convertido en un 404 mudo.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

redirigir('/admin.php?panel=reportes');
