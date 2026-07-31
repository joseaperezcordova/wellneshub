<?php
/**
 * Mapa del sitio para buscadores.
 *
 * Es .php y no un .xml estático porque la lista de actividades cambia todo
 * el tiempo: cada publicación, edición o baja tiene que reflejarse aquí sin
 * que nadie tenga que regenerar nada a mano. robots.txt apunta a esta
 * dirección tal cual —no hace falta reescritura de URL para que un buscador
 * la acepte como sitemap.
 *
 * Solo entran las páginas públicas indexables y las actividades publicadas
 * y todavía vigentes: lo que no se ve en el sitio tampoco tiene por qué
 * aparecer aquí.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/eventos.php';

header('Content-Type: application/xml; charset=utf-8');

$paginas = [
    ['loc' => URL_BASE . '/',            'prioridad' => '1.0', 'frecuencia' => 'daily'],
    ['loc' => URL_BASE . '/buscar.php',  'prioridad' => '0.8', 'frecuencia' => 'daily'],
    ['loc' => URL_BASE . '/blog.php',    'prioridad' => '0.4', 'frecuencia' => 'weekly'],
];

$eventos = eventosPublicadosParaSitemap();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($paginas as $p): ?>
  <url>
    <loc><?= e($p['loc']) ?></loc>
    <changefreq><?= e($p['frecuencia']) ?></changefreq>
    <priority><?= e($p['prioridad']) ?></priority>
  </url>
<?php endforeach; ?>
<?php foreach ($eventos as $ev): ?>
  <url>
    <loc><?= e(URL_BASE . '/evento.php?id=' . $ev['id']) ?></loc>
    <lastmod><?= e(date('Y-m-d', strtotime($ev['actualizado_en']))) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach; ?>
</urlset>
