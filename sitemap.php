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
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';

header('Content-Type: application/xml; charset=utf-8');

/*
 * Las direcciones salen de rutasSitio(), no escritas a mano. Añadir una página
 * o un idioma la mete aquí sola, que es justo lo que se pierde cuando el mapa
 * de direcciones vive en dos sitios.
 *
 * Cada página se lista UNA VEZ POR IDIOMA, y cada entrada declara sus
 * alternativas con xhtml:link. Es el equivalente en el sitemap de las
 * etiquetas hreflang del <head>, y Google pide las dos cosas: el sitemap le
 * dice qué existe, el head confirma la relación al visitar.
 *
 * Las prioridades van por clave y no por posición: si mañana se reordena
 * rutasSitio() esto no se descoloca.
 */
$prioridades = [
    'inicio'        => ['1.0', 'daily'],
    'actividades'   => ['0.8', 'daily'],
    'como-funciona' => ['0.6', 'monthly'],
    'sobre-omdara'  => ['0.4', 'monthly'],
    'faq'           => ['0.5', 'monthly'],
    'contacto'      => ['0.5', 'monthly'],
    'blog'          => ['0.4', 'weekly'],
    'terminos'      => ['0.2', 'yearly'],
    'privacidad'    => ['0.2', 'yearly'],
    'cookies'       => ['0.2', 'yearly'],
];

$paginas = [];

foreach (rutasSitio() as $clave => $destino) {
    // 'publicar' no entra: exige sesión, así que para un buscador es una
    // redirección al login y no una página que indexar.
    if (!isset($prioridades[$clave])) continue;

    // Lo que no se enseña tampoco se le ofrece a Google (REQ-00004): un
    // sitemap que anuncia una dirección que devuelve 404 es un error de
    // rastreo en Search Console, no un descuido silencioso.
    if (!seccionVisible($clave)) continue;

    [$prioridad, $frecuencia] = $prioridades[$clave];

    foreach (idiomasDisponibles() as $idioma) {
        if (!isset($destino[$idioma])) continue;

        $alternativas = [];
        foreach (idiomasDisponibles() as $otro) {
            if (isset($destino[$otro])) $alternativas[$otro] = url($clave, $otro);
        }

        $paginas[] = [
            'loc'          => url($clave, $idioma),
            'prioridad'    => $prioridad,
            'frecuencia'   => $frecuencia,
            'alternativas' => $alternativas,
        ];
    }
}

$eventos = eventosPublicadosParaSitemap();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($paginas as $p): ?>
  <url>
    <loc><?= e($p['loc']) ?></loc>
<?php foreach ($p['alternativas'] as $idiomaAlt => $urlAlt): ?>
    <xhtml:link rel="alternate" hreflang="<?= e($idiomaAlt) ?>" href="<?= e($urlAlt) ?>"/>
<?php endforeach; ?>
    <changefreq><?= e($p['frecuencia']) ?></changefreq>
    <priority><?= e($p['prioridad']) ?></priority>
  </url>
<?php endforeach; ?>
<?php foreach ($eventos as $ev): ?>
  <?php
  /*
   * Una entrada por idioma, cada una con las direcciones de la otra como
   * alternativa —igual que las páginas fijas de arriba—, desde que
   * /activity/{slug} existe (REQ-00002 fase 5). El slug es el mismo en los
   * dos: lo único que cambia es el prefijo.
   */
  $urlEs = urlEvento($ev, 'es');
  $urlEn = urlEvento($ev, 'en');
  ?>
  <url>
    <?php /* La dirección limpia, no /evento.php?id= (REQ-00006). Es la que
             evento.php declara como canónica, y ofrecerle a Google una
             distinta de la canónica es pedirle que decida él. */ ?>
    <loc><?= e($urlEs) ?></loc>
    <xhtml:link rel="alternate" hreflang="es" href="<?= e($urlEs) ?>"/>
    <xhtml:link rel="alternate" hreflang="en" href="<?= e($urlEn) ?>"/>
    <lastmod><?= e(date('Y-m-d', strtotime($ev['actualizado_en']))) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= e($urlEn) ?></loc>
    <xhtml:link rel="alternate" hreflang="es" href="<?= e($urlEs) ?>"/>
    <xhtml:link rel="alternate" hreflang="en" href="<?= e($urlEn) ?>"/>
    <lastmod><?= e(date('Y-m-d', strtotime($ev['actualizado_en']))) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach; ?>
</urlset>
