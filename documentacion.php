<?php
/**
 * Documentación técnica, para administradores.
 *
 * Los archivos que enseña —README.md y docs/*— nunca se sirven por HTTP
 * directo (ver docs/.htaccess y el bloque para README.md en el .htaccess de
 * la raíz): esta página es la única puerta, y por eso empieza igual que
 * admin.php — exigirSesion() + esAdmin(), sin excepción.
 *
 * ?ver=clave      → muestra el documento (el .md se convierte a HTML; el
 *                    .html ya es una página completa y se sirve tal cual).
 * ?descargar=clave → el archivo de origen, para bajarlo.
 * Sin parámetros   → el índice.
 *
 * La "clave" nunca es una ruta: documentoValido() solo acepta una de las
 * que ya están en catalogoDocumentos(). No hay forma de pedir un archivo
 * fuera de esa lista cambiando el parámetro.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php'; // esAdmin()

$u = exigirSesion();

if (!esAdmin($u)) {
    http_response_code(403);
    $titulo = 'Sin permiso';
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>Esta página no es para ti</h1>'
       . '<p class="sub">Solo los administradores ven la documentación.</p></div>';
    pie();
    exit;
}

$clave = (string) ($_GET['ver'] ?? $_GET['descargar'] ?? '');
$doc   = $clave !== '' ? documentoValido($clave) : null;

// --- descarga: el archivo de origen, sin pasar por el layout del sitio ---
if (isset($_GET['descargar'])) {
    if (!$doc) { http_response_code(404); exit('Documento no encontrado.'); }

    $nombre = basename($doc['archivo']);
    header('Content-Type: ' . ($doc['tipo'] === 'md' ? 'text/markdown' : 'text/html') . '; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Content-Length: ' . (string) filesize($doc['archivo']));
    readfile($doc['archivo']);
    exit;
}

// --- ver un .html ya armado: se sirve tal cual, es una página completa por sí sola ---
if (isset($_GET['ver']) && $doc && $doc['tipo'] === 'html') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($doc['archivo']);
    exit;
}

$titulo     = $doc ? $doc['titulo'] : 'Documentación';
$anchoLibre = true;
require __DIR__ . '/includes/layout.php';
?>

<div class="admin-shell">
  <div class="wrap doc-wrap">

    <?php if ($doc): ?>

      <a class="volver-admin" href="<?= URL_BASE ?>/documentacion.php">← Toda la documentación</a>

      <div class="doc-contenido">
        <?php /* El propio markdown ya trae su título como primer # — repetirlo
                 aquí encima se veía como un encabezado duplicado. Solo el botón
                 de descarga necesita su propia fila. */ ?>
        <div class="doc-cab">
          <a class="btn-ghost" href="<?= URL_BASE ?>/documentacion.php?descargar=<?= e($clave) ?>">Descargar</a>
        </div>
        <?= markdownAHtml(file_get_contents($doc['archivo'])) ?>
      </div>

    <?php elseif ($clave !== ''): ?>

      <div class="admin-header">
        <div class="eyebrow">Documentación</div>
        <h1>No se encontró ese documento</h1>
      </div>
      <a class="volver-admin" href="<?= URL_BASE ?>/documentacion.php">← Toda la documentación</a>

    <?php else: ?>

      <div class="admin-header">
        <div class="eyebrow">Panel administrador</div>
        <h1>Documentación</h1>
      </div>

      <div class="doc-lista">
        <?php foreach (catalogoDocumentos() as $clave => $d): ?>
          <div class="doc-item">
            <div class="doc-item-texto">
              <h3><?= e($d['titulo']) ?></h3>
              <p><?= e($d['descripcion']) ?></p>
            </div>
            <div class="doc-item-acciones">
              <a class="btn-ghost" href="<?= URL_BASE ?>/documentacion.php?ver=<?= e($clave) ?>"<?= $d['tipo'] === 'html' ? ' target="_blank" rel="noopener"' : '' ?>>Ver</a>
              <a class="btn-ghost" href="<?= URL_BASE ?>/documentacion.php?descargar=<?= e($clave) ?>">Descargar</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </div>
</div>

<?php pie(); ?>
