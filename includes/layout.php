<?php
/**
 * La cabecera y el pie de todo el sitio.
 *
 * Hasta que el sitio se partió en páginas, esto convivía con una copia distinta
 * dentro de index.php: la portada tenía su propia barra —con el menú de idioma,
 * el botón de hamburguesa y el pie largo— y el resto de páginas usaba esta, más
 * pobre y con los tres enlaces del menú apuntando todos a «/». Pasar de una a
 * otra cambiaba la cabecera a media navegación.
 *
 * Ahora hay una sola, la de la portada, y los enlaces van a páginas reales.
 *
 * Uso:
 *   $titulo = 'Buscar actividades';
 *   $seccion = 'buscar';          // opcional: marca el enlace del menú
 *   $anchoLibre = true;           // opcional: sin el contenedor estrecho
 *   require __DIR__ . '/includes/layout.php';
 *   ... contenido ...
 *   pie();
 *
 * Sobre $anchoLibre: las páginas de formulario —login, alta, moderación— van
 * dentro de un contenedor de 1180px con sus márgenes. Las públicas no pueden:
 * el banner de la portada y las franjas de color van a sangre, de borde a
 * borde, y llevan su propio .wrap dentro. Por eso el contenedor es opcional en
 * vez de estar siempre puesto.
 */

declare(strict_types=1);

$u = usuarioActual();

/* Qué enlace del menú va marcado. Se puede pasar a mano con $seccion; si no, se
   deduce del archivo que se esté sirviendo, que acierta en todos los casos que
   hay hoy y no obliga a acordarse de ponerlo. */
$seccion = $seccion ?? [
    'index.php'  => 'inicio',
    'buscar.php' => 'buscar',
    'blog.php'   => 'blog',
][basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''))] ?? '';

/** El enlace del menú, con su marca si es la sección en la que estamos. */
function enlaceMenu(string $ruta, string $texto, string $clave, string $seccion): string
{
    $activo = $clave === $seccion ? ' class="active"' : '';
    $aqui   = $clave === $seccion ? ' aria-current="page"' : '';

    return '<a href="' . URL_BASE . $ruta . '"' . $activo . $aqui . '>' . e($texto) . '</a>';
}
?><!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo ?? 'Rueda') ?> · Rueda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(assetUrl('assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(assetUrl('assets/css/portada.css')) ?>">
<?php if (!empty($mapaInteractivo)): ?>
<!-- El mapa arrastrable de alta/edición: Leaflet + OpenStreetMap, no la API
     de Google (includes/mapa.php explica por qué). Solo entra en las dos
     páginas que lo usan; el resto del sitio no paga por esta librería. -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php endif; ?>
</head>
<body>

<div class="topbar">
  <div class="topbar-inner">
    <a class="logo" href="<?= URL_BASE ?>/">
      <div class="logo-mark"></div>
      <div class="logo-text">Rueda<small>Directorio wellness MX</small></div>
    </a>

    <?php /* Enlaces de verdad y no botones con JavaScript: así funcionan el clic
             central, «abrir en pestaña nueva» y el buscador de Google. Mientras
             las vistas se conmutaban en el navegador no había otra opción. */ ?>
    <nav class="mainnav" id="mainnav">
      <?= enlaceMenu('/',           'Inicio',         'inicio', $seccion) ?>
      <?= enlaceMenu('/buscar.php', 'Buscar actividades', 'buscar', $seccion) ?>
      <?= enlaceMenu('/blog.php',   'Blog',           'blog',   $seccion) ?>
    </nav>

    <div class="topbar-right">
      <div class="langtoggle" id="langToggle">
        <button data-lang="es" class="active">ES</button>
        <button data-lang="en">EN</button>
      </div>

      <!-- «Publicar actividad» lo ve todo el mundo, con sesión o sin ella. Quien no
           la tenga pasa por el login y vuelve aquí solo: esconder el botón a los
           visitantes es esconder justo lo que queremos que hagan, y un directorio
           sin organizadores nuevos no crece. La puerta la guarda el servidor
           —exigirSesion() en evento-nuevo.php—, no la ausencia del enlace. -->
      <a class="btn-publicar" href="<?= URL_BASE ?>/evento-nuevo.php">
        Publicar<span class="btn-publicar-extra"> actividad</span>
      </a>

      <?php if ($u): ?>
        <!-- Con sesión: avatar y menú. <details> abre y cierra sin JavaScript y
             el teclado ya sabe manejarlo. -->
        <details class="cuenta">
          <summary aria-label="Mi cuenta">
            <?php if (!empty($u['avatar_url'])): ?>
              <img class="avatar-cuenta" src="<?= e($u['avatar_url']) ?>" alt="" referrerpolicy="no-referrer">
            <?php else: ?>
              <span class="avatar-cuenta avatar-letra"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
            <?php endif; ?>
          </summary>
          <div class="cuenta-menu">
            <div class="cuenta-quien">
              <strong><?= e($u['nombre']) ?></strong>
              <span><?= e($u['email']) ?></span>
            </div>
            <!-- «Mis actividades» y no «Publicar actividad»: publicar ya está en
                 el botón de al lado, y lo que hay detrás de este enlace es la
                 lista de las actividades propias, que es otra cosa.
                 Antes iban a /#panel-organizador y /#admin porque los dos
                 paneles vivían dentro de la portada. Ahora son páginas. -->
            <a href="<?= URL_BASE ?>/mis-eventos.php">Mis actividades</a>
            <?php if ($u['rol'] === 'admin'): ?>
              <a href="<?= URL_BASE ?>/admin.php">Panel admin</a>
              <?php $pend = contarReportesPendientes(); ?>
              <a href="<?= URL_BASE ?>/moderacion.php">
                Moderación<?php if ($pend > 0): ?> <span class="pendientes"><?= $pend ?></span><?php endif; ?>
              </a>
            <?php endif; ?>
            <a href="<?= URL_BASE ?>/logout.php">Cerrar sesión</a>
          </div>
        </details>
      <?php else: ?>
        <!-- Sin sesión: enlace, no botón con JavaScript, para que funcione el
             clic central y el «abrir en pestaña nueva». -->
        <a class="btn-cuenta" href="<?= URL_BASE ?>/login.php" aria-label="Entrar a mi cuenta">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"
               fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
            <circle cx="12" cy="8.2" r="3.8"/>
            <path d="M4.8 20c.6-3.7 3.6-5.9 7.2-5.9s6.6 2.2 7.2 5.9"/>
          </svg>
          <span>Entrar</span>
        </a>
      <?php endif; ?>

      <button class="navburger" id="burger" aria-label="Abrir menú"><span></span><span></span><span></span></button>
    </div>
  </div>
</div>

<?php
/* Aviso de vuelta desde publicar, editar o eliminar un evento. Vive en la
   sesión porque quien lo provoca es otra página: se enseña una vez y se borra.
   Antes solo lo pintaba la portada, y desde que hay más de un destino posible
   —volver a «mis eventos», por ejemplo— tenía que subir aquí. */
if (!empty($_SESSION['evento_aviso'])):
    $avisoLayout = (string) $_SESSION['evento_aviso'];
    unset($_SESSION['evento_aviso']);
?>
  <div class="wrap" style="padding-top:18px;">
    <div class="aviso-portada"><?= e($avisoLayout) ?></div>
  </div>
<?php endif; ?>

<?php
$anchoLibre = $anchoLibre ?? false;
if (!$anchoLibre): ?>
<main class="contenido">
<?php endif;

/**
 * Cierra el documento.
 *
 * Lee $anchoLibre de arriba en vez de pedirlo como argumento para que las ocho
 * páginas que ya llamaban a pie() a secas sigan funcionando sin tocarlas.
 */
function pie(): void
{
    if (empty($GLOBALS['anchoLibre'])) echo "</main>\n";
    ?>
<footer>
  <div class="foot-inner">
    <div>
      <div class="logo-text" style="color:var(--blanco);">Rueda</div>
      <p style="font-size:13px; opacity:.7; margin-top:10px; max-width:260px;">El directorio de actividades wellness más completo de México — retiros, festivales y círculos, curados a mano.</p>
    </div>
    <div>
      <h5>Acerca de</h5>
      <a href="#">Nuestra misión</a>
      <a href="#">Cómo curamos las actividades</a>
      <a href="<?= URL_BASE ?>/evento-nuevo.php">Publica tu actividad</a>
    </div>
    <div>
      <h5>Contacto</h5>
      <a href="#">hola@ruedawellness.mx</a>
      <a href="#">WhatsApp</a>
      <a href="#">Instagram</a>
    </div>
    <div>
      <h5>Explorar</h5>
      <a href="<?= URL_BASE ?>/buscar.php">Buscar actividades</a>
      <a href="<?= URL_BASE ?>/blog.php">Blog</a>
    </div>
  </div>
  <div class="foot-bottom">© <?= date('Y') ?> Rueda — Directorio de actividades wellness MX.</div>
</footer>

<?php /* La raíz del sitio, para el JavaScript. Los archivos .js son estáticos y
         no pasan por PHP, así que no pueden escribir URL_BASE por su cuenta, y
         la ruta cambia entre local —/wellneshub— y el servidor. */ ?>
<script>var RUEDA = {base: <?= json_encode(URL_BASE) ?>};</script>
<script src="<?= e(assetUrl('assets/js/tarjetas.js')) ?>"></script>
<script src="<?= e(assetUrl('assets/js/chrome.js')) ?>"></script>
<?php
    /* El JavaScript propio de cada página va detrás, porque usa lo de arriba.
       La página lo declara antes de abrir el layout:
           $scriptsPagina = ['assets/js/buscar.js']; */
    foreach (($GLOBALS['scriptsPagina'] ?? []) as $js) {
        echo '<script src="' . e(assetUrl($js)) . '"></script>' . "\n";
    }
?>
</body>
</html>
    <?php
}
