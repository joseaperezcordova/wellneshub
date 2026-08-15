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
<title><?= e($titulo ?? 'OMDARA') ?> · OMDARA</title>
<?php
/*
 * Meta description + Open Graph/Twitter Card. Cada página puede fijar
 * $descripcion (y opcionalmente $imagenOg, una URL absoluta) antes de pedir
 * este archivo —evento.php lo hace con el resumen de la actividad—; las que
 * no lo hacen, paneles privados y formularios sobre todo, se quedan con la
 * descripción general del sitio, que sigue siendo mejor que no tener nada.
 */
$descripcionMeta = trim((string) ($descripcion
    ?? 'OMDARA — directorio de retiros, festivales y círculos de bienestar en México. Encuentra actividades de yoga, breathwork, sound healing y más, o publica la tuya.'));
$urlActualMeta = URL_BASE . (string) ($_SERVER['REQUEST_URI'] ?? '/');
?>
<meta name="description" content="<?= e($descripcionMeta) ?>">
<meta property="og:site_name" content="OMDARA">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($titulo ?? 'OMDARA') ?>">
<meta property="og:description" content="<?= e($descripcionMeta) ?>">
<meta property="og:url" content="<?= e($urlActualMeta) ?>">
<?php if (!empty($imagenOg)): ?>
<meta property="og:image" content="<?= e($imagenOg) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php
/*
 * La hoja de Google Fonts es la única <link rel="stylesheet"> que de verdad
 * bloquea el primer pintado —app.css y portada.css son propias y pesan menos
 * que un viaje de ida y vuelta a otro dominio—. El truco de media="print" hace
 * que el navegador la baje sin esperarla para pintar, y onload la reactiva
 * para todos los medios en cuanto llega. display=swap (ya en la URL) cubre la
 * otra mitad: mientras tanto se ve el texto con la tipografía de respaldo, no
 * invisible. <noscript> es el respaldo de quien tiene JavaScript apagado,
 * donde el truco de media no se revertiría solo.
 */
$fuentesUrl = 'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap';
?>
<link rel="stylesheet" href="<?= e($fuentesUrl) ?>" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="<?= e($fuentesUrl) ?>"></noscript>
<link rel="stylesheet" href="<?= e(assetUrl('assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(assetUrl('assets/css/portada.css')) ?>">
<?php
/*
 * Analítica: GA4, Microsoft Clarity y Meta Pixel, cada una con su propia
 * llave en config.local.php y su propio candado — solo se imprime si tiene
 * ID puesto Y fuera de localhost/127.0.0.1. En local nunca hay analítica,
 * ni con los IDs puestos, para que probar el sitio en la máquina de quien
 * programa no ensucie los datos reales. Ver includes/config.local.example.php
 * para dónde se ponen los IDs y por qué es seguro tenerlos ya, antes del
 * dominio final.
 *
 * whTrack() es el único punto de entrada para los eventos propios del resto
 * del sitio (evento.php, buscar.js, etc.) y reparte a las tres a la vez. Cada
 * comprobación typeof es independiente: si solo GA4 está activo, fbq y
 * clarity() simplemente no existen y esas dos líneas no hacen nada, sin que
 * whTrack() tenga que saber cuáles de las tres están encendidas.
 */
$ga4Id       = trim((string) ($CONFIG['analytics']['ga4_id'] ?? ''));
$clarityId   = trim((string) ($CONFIG['analytics']['clarity_id'] ?? ''));
$metaPixelId = trim((string) ($CONFIG['analytics']['meta_pixel_id'] ?? ''));
$scVerifica  = trim((string) ($CONFIG['analytics']['search_console_verificacion'] ?? ''));
$analiticaActiva = empty($CONFIG['es_local']);
?>
<?php if ($scVerifica !== ''): ?>
<meta name="google-site-verification" content="<?= e($scVerifica) ?>">
<?php endif; ?>
<?php if ($ga4Id !== '' && $analiticaActiva): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4Id) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments); }
  gtag('js', new Date());
  gtag('config', <?= json_encode($ga4Id) ?>);
</script>
<?php endif; ?>
<?php if ($clarityId !== '' && $analiticaActiva): ?>
<script>
  (function(c,l,a,r,i,t,y){
      c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
      t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
      y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
  })(window, document, "clarity", "script", <?= json_encode($clarityId) ?>);
</script>
<?php endif; ?>
<?php if ($metaPixelId !== '' && $analiticaActiva): ?>
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', <?= json_encode($metaPixelId) ?>);
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=<?= e($metaPixelId) ?>&amp;ev=PageView&amp;noscript=1" alt=""></noscript>
<?php endif; ?>
<script>
  window.whTrack = function (nombre, params) {
    var p = params || {};
    if (typeof gtag === 'function')    gtag('event', nombre, p);
    if (typeof fbq === 'function')     fbq('trackCustom', nombre, p);
    if (typeof clarity === 'function') clarity('event', nombre);
  };
</script>
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
      <div class="logo-text">OMDARA<small>Directorio wellness MX</small></div>
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
              <a href="<?= URL_BASE ?>/documentacion.php">Documentación</a>
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
/*
 * Eventos propios que se disparan DESPUÉS de una redirección —publicar,
 * editar, eliminar—: la página que hace el cambio no es la que se enseña al
 * final, así que no puede llamar a whTrack() directamente. Se dejan en una
 * lista en sesión (mismo patrón que evento_aviso, arriba) y aquí, en la
 * página de destino, se disparan y se borran. Es una lista y no un solo
 * evento porque una misma redirección puede encolar dos —publicar una
 * actividad y, si es la primera del organizador, también su alta—. Como
 * whTrack() no hace nada sin GA4 activo, esto es gratis sin analítica.
 */
if (!empty($_SESSION['eventos_ga'])):
    $eventosGaLayout = $_SESSION['eventos_ga'];
    unset($_SESSION['eventos_ga']);
    foreach ($eventosGaLayout as $gaLayout):
?>
  <script>whTrack(<?= json_encode($gaLayout['nombre']) ?>, <?= json_encode((object) ($gaLayout['params'] ?? [])) ?>);</script>
<?php
    endforeach;
endif;
?>

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
<?php /* Las direcciones van limpias —/actividades, no /buscar.php— porque son
         las que se publican y se comparten: sobreviven a que el archivo se
         renombre y no le cuentan al visitante con qué está hecho el sitio. Las
         reglas que las resuelven están en el .htaccess de la raíz. */ ?>
<footer>
  <div class="foot-inner">
    <div class="foot-marca">
      <div class="logo-text" style="color:var(--blanco);">OMDARA</div>
      <p class="foot-lema">Tu guía de experiencias de bienestar en México.
         Conecta con actividades que nutren cuerpo, mente y alma.</p>

      <?php /* Las direcciones de las redes todavía no existen: el requerimiento
               las deja como «[Agregar URLs definitivas]». Van con rel="nofollow"
               y aria-label porque un icono suelto no le dice nada a un lector de
               pantalla. En cuanto haya perfiles, se sustituye el "#". */ ?>
      <div class="foot-redes">
        <a href="#" aria-label="OMDARA en Instagram" target="_blank" rel="noopener nofollow">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
               stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="5"/>
            <circle cx="12" cy="12" r="4"/>
            <circle cx="17.5" cy="6.5" r="1.1" fill="currentColor" stroke="none"/>
          </svg>
        </a>
        <a href="#" aria-label="OMDARA en Facebook" target="_blank" rel="noopener nofollow">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
               stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14.5 8.5h2.2V5.4h-2.6c-2.3 0-3.7 1.4-3.7 3.8v1.6H8.2v3.1h2.2V21h3.3v-7.1h2.4l.4-3.1h-2.8V9.6c0-.8.3-1.1.8-1.1z"/>
          </svg>
        </a>
        <a href="#" aria-label="OMDARA en WhatsApp" target="_blank" rel="noopener nofollow">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
               stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3.5 20.5l1.3-4.2A8.2 8.2 0 1 1 8 19.3z"/>
            <path d="M9.2 9c.2 1.6 1.6 4.2 4.1 5.2.7.3 1.3-.2 1.5-.7"/>
          </svg>
        </a>
      </div>
    </div>

    <div>
      <h5>Explora</h5>
      <a href="<?= URL_BASE ?>/actividades">Actividades</a>
    </div>
    <div>
      <h5>Para organizadores</h5>
      <a href="<?= URL_BASE ?>/publicar-actividad">Publicar tu actividad</a>
      <a href="<?= URL_BASE ?>/como-funciona">¿Cómo funciona?</a>
    </div>
    <div>
      <h5>Ayuda</h5>
      <a href="<?= URL_BASE ?>/preguntas-frecuentes">Preguntas frecuentes</a>
      <a href="<?= URL_BASE ?>/contacto">Contacto</a>
    </div>
    <div>
      <h5>Legal</h5>
      <a href="<?= URL_BASE ?>/terminos-y-condiciones">Términos y Condiciones</a>
      <a href="<?= URL_BASE ?>/aviso-de-privacidad">Aviso de Privacidad</a>
      <a href="<?= URL_BASE ?>/politica-de-cookies">Política de Cookies</a>
    </div>
  </div>

  <?php /* Sustituye al copyright. Decir «estamos en beta» donde antes había un
           «todos los derechos reservados» es una decisión de producto: invita a
           avisar de los fallos en vez de asumir que el sitio está terminado. */ ?>
  <div class="foot-bottom">
    Versión beta: estamos mejorando continuamente la plataforma.
    Si encuentras algún problema, <a href="<?= URL_BASE ?>/contacto">contáctanos</a>.
  </div>
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
