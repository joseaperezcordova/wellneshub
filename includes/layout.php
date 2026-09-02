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
/* Qué enlace del menú va marcado. Se puede pasar a mano con $seccion; si no, se
   deduce.

   PRIMERO POR LA RUTA DEL ENRUTADOR, y solo después por el archivo. Desde que
   las direcciones limpias pasan por router.php (REQ-00002), SCRIPT_NAME vale
   "router.php" en TODAS ellas, así que el mapa de archivos devolvía vacío y
   ningún enlace salía marcado al llegar por /actividades o /como-funciona.
   Seguía funcionando solo entrando por el .php, que es justo la dirección que no
   se publica. El mapa de archivos se queda para esas entradas directas. */
$seccionesPorRuta = [
    'inicio'        => 'inicio',
    'actividades'   => 'buscar',
    'como-funciona' => 'como',
    'blog'          => 'blog',
];

$seccion = $seccion
    ?? ($seccionesPorRuta[$GLOBALS['rutaActual'] ?? ''] ?? null)
    ?? [
        'index.php'          => 'inicio',
        'buscar.php'         => 'buscar',
        'blog.php'           => 'blog',
        'como-funciona.php'  => 'como',
    ][basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''))] ?? '';

/** El enlace del menú, con su marca si es la sección en la que estamos. */
function enlaceMenu(string $claveRuta, string $claveTexto, string $clave, string $seccion): string
{
    $activo = $clave === $seccion ? ' class="active"' : '';
    $aqui   = $clave === $seccion ? ' aria-current="page"' : '';

    // La dirección sale de url() y el texto de t(): los dos dependen del
    // idioma, y escribir aquí "/buscar.php" mandaría a un inglés a una
    // dirección en español —justo lo que el requerimiento prohíbe.
    return '<a href="' . e(url($claveRuta)) . '"' . $activo . $aqui . '>' . et($claveTexto) . '</a>';
}
?><!DOCTYPE html>
<?php /* El sitio en español es de México —"es-MX" fija ortografía y formato de
         fecha—; el inglés no se marca región: no hay una versión británica y
         otra estadounidense, y elegir una haría que la otra pareciera un
         error. */ ?>
<html lang="<?= e(idiomaActual() === 'es' ? 'es-MX' : 'en') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo ?? t('marca.nombre')) ?> · <?= et('marca.nombre') ?></title>
<link rel="icon" type="image/svg+xml" href="<?= URL_BASE ?>/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="<?= URL_BASE ?>/favicon.png">
<link rel="apple-touch-icon" href="<?= URL_BASE ?>/apple-touch-icon.png">
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
<?php
/*
 * Canonical y hreflang.
 *
 * El canonical apunta a la dirección limpia de esta página en SU idioma, no a
 * la que el visitante escribió: /buscar.php y /actividades sirven lo mismo, y
 * sin esto Google decide por su cuenta cuál indexar —a veces la .php, que es
 * la que no queremos publicar.
 *
 * hreflang le dice que /actividades y /activities son la misma página en dos
 * idiomas y no contenido duplicado. Tiene que declararse en las DOS
 * direcciones y cada una incluirse a sí misma; si solo una apunta a la otra,
 * Google ignora el par entero. x-default marca cuál servir a quien no encaja
 * en ninguno de los dos idiomas.
 *
 * Se emite en las páginas del enrutador, y también en las que no estando ahí
 * declaran su propia equivalencia en $GLOBALS['urlEquivalente'] —la ficha de
 * actividad, desde REQ-00002 fase 5— para las mismas dos etiquetas. Sin
 * ninguna de las dos cosas, mejor callarse que anunciar una versión que no
 * existe.
 */
$claveRutaSeo = $GLOBALS['rutaActual'] ?? null;
if ($claveRutaSeo !== null):
?>
<link rel="canonical" href="<?= e(url($claveRutaSeo)) ?>">
<?php foreach (idiomasDisponibles() as $idiomaSeo): ?>
  <?php if (isset(rutasSitio()[$claveRutaSeo][$idiomaSeo])): ?>
<link rel="alternate" hreflang="<?= e($idiomaSeo) ?>" href="<?= e(url($claveRutaSeo, $idiomaSeo)) ?>">
  <?php endif; ?>
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?= e(url($claveRutaSeo, IDIOMA_POR_DEFECTO)) ?>">
<?php elseif (!empty($canonical)): ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<?php foreach ($GLOBALS['urlEquivalente'] ?? [] as $idiomaSeo => $urlSeo): ?>
<link rel="alternate" hreflang="<?= e($idiomaSeo) ?>" href="<?= e($urlSeo) ?>">
<?php endforeach; ?>
<?php if (isset($GLOBALS['urlEquivalente'][IDIOMA_POR_DEFECTO])): ?>
<link rel="alternate" hreflang="x-default" href="<?= e($GLOBALS['urlEquivalente'][IDIOMA_POR_DEFECTO]) ?>">
<?php endif; ?>
<?php endif; ?>
<meta property="og:site_name" content="<?= et('marca.nombre') ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="<?= e(idiomaActual() === 'es' ? 'es_MX' : 'en_US') ?>">
<meta property="og:title" content="<?= e($titulo ?? t('marca.nombre')) ?>">
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
 * Analítica: GA4, Microsoft Clarity y Meta Pixel.
 *
 * AQUÍ YA NO SE CARGA NINGUNA (REQ-00003). Lo único que sale al HTML son los
 * IDs y a qué categoría pertenece cada herramienta; quien las enciende es
 * assets/js/consentimiento.js, y solo después de que alguien haya aceptado esa
 * categoría. includes/consentimiento.php explica por qué la puerta está en el
 * navegador y no aquí.
 *
 * herramientasAnalitica() se queda con el candado de siempre: una herramienta
 * sin ID no existe, y en local no hay analítica ni con los IDs puestos, para
 * que probar el sitio en la máquina de quien programa no ensucie los datos
 * reales.
 *
 * La verificación de Search Console no es una cookie ni rastrea a nadie: es una
 * etiqueta que Google lee para comprobar que el dominio es tuyo. Va siempre.
 *
 * whTrack() es el único punto de entrada para los eventos propios del resto del
 * sitio (evento.php, buscar.js, etc.) y reparte a las tres a la vez. Cada
 * comprobación typeof es independiente, así que ahora cumple una segunda
 * función: sin consentimiento no existe ninguna de las tres y whTrack() no hace
 * nada, sin que quien lo llama tenga que preguntar por el permiso.
 */
$scVerifica = trim((string) ($CONFIG['analytics']['search_console_verificacion'] ?? ''));
?>
<?php if ($scVerifica !== ''): ?>
<meta name="google-site-verification" content="<?= e($scVerifica) ?>">
<?php endif; ?>
<script>
  window.whTrack = function (nombre, params) {
    var p = params || {};
    if (typeof gtag === 'function')    gtag('event', nombre, p);
    if (typeof fbq === 'function')     fbq('trackCustom', nombre, p);
    if (typeof clarity === 'function') clarity('event', nombre);
  };
  <?php /* assets/js/tarjetas.js es un solo archivo compartido por la
           portada, el buscador y los relacionados de la ficha: necesita su
           traducción aquí, global, y no una por página (REQ-00002). */ ?>
  window.TARJETA_T = <?= json_encode([
      'gratis'        => t('ficha.precio.gratis'),
      'porConfirmar'  => t('ficha.precio.por_confirmar'),
      'desde'         => t('tarjeta.desde'),
      'verActividad'  => t('tarjeta.ver_actividad'),
  ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  window.OMDARA_COOKIES = <?= json_encode([
      'cookie'       => CONSENTIMIENTO_COOKIE,
      'version'      => CONSENTIMIENTO_VERSION,
      'dias'         => CONSENTIMIENTO_DIAS,
      'seguro'       => strpos(URL_BASE, 'https://') === 0,
      'herramientas' => herramientasAnalitica(),
  ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<?php /* Sin defer y en la cabecera a propósito: si ya hay respuesta guardada,
         las herramientas permitidas tienen que arrancar ANTES de que el
         principio del <body> dispare los whTrack() que vienen de una
         redirección. Cargado al final, esos eventos se perderían en silencio. */ ?>
<script src="<?= e(assetUrl('assets/js/consentimiento.js')) ?>"></script>
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
    <a class="logo" href="<?= e(url('inicio')) ?>">
      <div class="logo-mark"></div>
      <div class="logo-text"><?= et('marca.nombre') ?><small><?= et('marca.subtitulo') ?></small></div>
    </a>

    <?php /* Enlaces de verdad y no botones con JavaScript: así funcionan el clic
             central, «abrir en pestaña nueva» y el buscador de Google. Mientras
             las vistas se conmutaban en el navegador no había otra opción. */ ?>
    <nav class="mainnav" id="mainnav">
      <?= enlaceMenu('inicio',      'nav.inicio',      'inicio', $seccion) ?>
      <?= enlaceMenu('actividades', 'nav.actividades', 'buscar', $seccion) ?>
      <?php /* «¿Cómo funciona?» arriba y en el pie, las dos a /como-funciona
               (REQ-00013). Es la página que convierte a quien mira en quien
               publica, y desde el pie solo la encuentra quien ya bajó hasta
               abajo. */ ?>
      <?= enlaceMenu('como-funciona', 'nav.como_funciona', 'como', $seccion) ?>
      <?php /* El blog no entra en el MVP (REQ-00004). El enlace no se borra: se
               pregunta por él, y vuelve solo el día que se quite de
               SECCIONES_OCULTAS. */ ?>
      <?php if (seccionVisible('blog')): ?>
        <?= enlaceMenu('blog',      'nav.blog',        'blog',   $seccion) ?>
      <?php endif; ?>
    </nav>

    <div class="topbar-right">
      <?php
      /*
       * Selector de idioma: enlaces, no botones.
       *
       * Antes eran dos <button> que cambiaban el titular del hero con
       * JavaScript y nada más — la dirección seguía siendo la misma y el resto
       * de la página seguía en español. Eso es exactamente lo que el
       * requerimiento llama traducción parcial.
       *
       * Siendo enlaces, cada idioma tiene su dirección propia: se puede
       * compartir, Google los indexa por separado y funcionan sin JavaScript.
       * urlEquivalente() es lo que mantiene al visitante en la misma página al
       * cambiar; solo cae al inicio cuando esa página no existe en el otro
       * idioma, que es el único caso que el requerimiento permite.
       */
      ?>
      <nav class="langtoggle" aria-label="<?= et('idioma.cambiar') ?>">
        <?php foreach (idiomasDisponibles() as $idiomaOpcion): ?>
          <?php $esActual = $idiomaOpcion === idiomaActual(); ?>
          <a href="<?= e(urlEquivalente($idiomaOpcion)) ?>"
             hreflang="<?= e($idiomaOpcion) ?>"
             lang="<?= e($idiomaOpcion) ?>"
             <?= $esActual ? 'class="active" aria-current="true"' : '' ?>><?= e(mb_strtoupper($idiomaOpcion)) ?></a>
        <?php endforeach; ?>
      </nav>

      <!-- «Publicar actividad» lo ve todo el mundo, con sesión o sin ella. Quien no
           la tenga pasa por el login y vuelve aquí solo: esconder el botón a los
           visitantes es esconder justo lo que queremos que hagan, y un directorio
           sin organizadores nuevos no crece. La puerta la guarda el servidor
           —exigirSesion() en evento-nuevo.php—, no la ausencia del enlace. -->
      <a class="btn-publicar" href="<?= e(url('publicar')) ?>">
        <?= et('nav.publicar_corto') ?><span class="btn-publicar-extra"> <?= et('nav.publicar_sufijo') ?></span>
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
            <!-- «Mi cuenta», con sus dos entradas (REQ-00009). El rótulo no es
                 un enlace a propósito: no hay ninguna página «Mi cuenta» que
                 enseñar por encima de estas dos, y un enlace que lleva a un
                 índice de dos elementos es un clic de más.

                 «Mis actividades» y no «Publicar actividad»: publicar ya está en
                 el botón de al lado, y lo que hay detrás de este enlace es la
                 lista de las actividades propias, que es otra cosa.
                 Antes iban a /#panel-organizador y /#admin porque los dos
                 paneles vivían dentro de la portada. Ahora son páginas. -->
            <div class="cuenta-grupo">Mi cuenta</div>
            <a href="<?= URL_BASE ?>/mi-cuenta.php">Información de contacto</a>
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
        <a class="btn-cuenta" href="<?= e(url('login')) ?>" aria-label="<?= et('nav.entrar_aria') ?>">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"
               fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
            <circle cx="12" cy="8.2" r="3.8"/>
            <path d="M4.8 20c.6-3.7 3.6-5.9 7.2-5.9s6.6 2.2 7.2 5.9"/>
          </svg>
          <span><?= et('nav.entrar') ?></span>
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
      <div class="logo-text" style="color:var(--blanco);"><?= et('marca.nombre') ?></div>
      <p class="foot-lema"><?= et('pie.lema') ?></p>

      <?php
      /*
       * Las direcciones salen de config.local.php —'redes', el mismo patrón
       * que 'correo.contacto'—: distinto en cada entorno y fuera de git.
       * Sin URL no se pinta el icono: un enlace muerto a "#" es peor para
       * accesibilidad y SEO que simplemente no mostrarlo.
       */
      $redInstagram = filter_var(trim((string) ($GLOBALS['CONFIG']['redes']['instagram'] ?? '')), FILTER_VALIDATE_URL) ?: '';
      $redFacebook  = filter_var(trim((string) ($GLOBALS['CONFIG']['redes']['facebook']  ?? '')), FILTER_VALIDATE_URL) ?: '';
      $redWhatsapp  = filter_var(trim((string) ($GLOBALS['CONFIG']['redes']['whatsapp']  ?? '')), FILTER_VALIDATE_URL) ?: '';
      ?>
      <?php if ($redInstagram || $redFacebook || $redWhatsapp): ?>
      <div class="foot-redes">
        <?php if ($redInstagram): ?>
        <a href="<?= e($redInstagram) ?>" aria-label="<?= et('pie.instagram') ?>" target="_blank" rel="noopener nofollow">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
               stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="5"/>
            <circle cx="12" cy="12" r="4"/>
            <circle cx="17.5" cy="6.5" r="1.1" fill="currentColor" stroke="none"/>
          </svg>
        </a>
        <?php endif; ?>
        <?php if ($redFacebook): ?>
        <a href="<?= e($redFacebook) ?>" aria-label="<?= et('pie.facebook') ?>" target="_blank" rel="noopener nofollow">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
               stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14.5 8.5h2.2V5.4h-2.6c-2.3 0-3.7 1.4-3.7 3.8v1.6H8.2v3.1h2.2V21h3.3v-7.1h2.4l.4-3.1h-2.8V9.6c0-.8.3-1.1.8-1.1z"/>
          </svg>
        </a>
        <?php endif; ?>
        <?php if ($redWhatsapp): ?>
        <a href="<?= e($redWhatsapp) ?>" aria-label="<?= et('pie.whatsapp') ?>" target="_blank" rel="noopener nofollow">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
               stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3.5 20.5l1.3-4.2A8.2 8.2 0 1 1 8 19.3z"/>
            <path d="M9.2 9c.2 1.6 1.6 4.2 4.1 5.2.7.3 1.3-.2 1.5-.7"/>
          </svg>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <div>
      <h5><?= et('pie.explora') ?></h5>
      <a href="<?= e(url('actividades')) ?>"><?= et('pie.actividades') ?></a>
    </div>
    <div>
      <?php /* Columna propia y no un enlace más dentro de EXPLORA: así lo pidió
               el cliente (2026-09-02), y solo va aquí —nunca en el menú
               principal—. */ ?>
      <h5><?= et('pie.sobre_omdara') ?></h5>
      <a href="<?= e(url('sobre-omdara')) ?>"><?= et('pie.sobre_omdara') ?></a>
    </div>
    <div>
      <h5><?= et('pie.organizadores') ?></h5>
      <a href="<?= e(url('publicar')) ?>"><?= et('pie.publicar') ?></a>
      <a href="<?= e(url('como-funciona')) ?>"><?= et('pie.como_funciona') ?></a>
    </div>
    <div>
      <h5><?= et('pie.ayuda') ?></h5>
      <a href="<?= e(url('faq')) ?>"><?= et('pie.faq') ?></a>
      <a href="<?= e(url('contacto')) ?>"><?= et('pie.contacto') ?></a>
      <?php /*
       * Los dos correos públicos que pide el cliente, cada uno con su etiqueta
       * corta: con uno solo bastaba la dirección a secas, pero con dos
       * apiladas sin distinguir nadie sabe cuál usar para qué. Se imprime la
       * dirección tal cual y no un «Escríbenos»: quien la quiere copiar la ve
       * sin abrir su cliente de correo, y quien solo quiere escribir tiene el
       * mailto igual. correoContacto()/correoSoporte() devuelven cadena vacía
       * mientras no estén configuradas en config.local.php, y entonces esa
       * línea no se pinta —igual que los iconos de redes de aquí al lado—:
       * una dirección a medias en el pie de todas las páginas es peor que
       * ninguna.
       */ ?>
      <?php if (correoContacto() !== ''): ?>
      <a href="mailto:<?= e(correoContacto()) ?>"><?= et('pie.correo_general_label') ?> <?= e(correoContacto()) ?></a>
      <?php endif; ?>
      <?php if (correoSoporte() !== ''): ?>
      <a href="mailto:<?= e(correoSoporte()) ?>"><?= et('pie.correo_soporte_label') ?> <?= e(correoSoporte()) ?></a>
      <?php endif; ?>
    </div>
    <div>
      <h5><?= et('pie.legal') ?></h5>
      <a href="<?= e(url('terminos')) ?>"><?= et('pie.terminos') ?></a>
      <a href="<?= e(url('privacidad')) ?>"><?= et('pie.privacidad') ?></a>
      <a href="<?= e(url('cookies')) ?>"><?= et('pie.cookies') ?></a>
    </div>
  </div>

  <?php /* Sustituye al copyright. Decir «estamos en beta» donde antes había un
           «todos los derechos reservados» es una decisión de producto: invita a
           avisar de los fallos en vez de asumir que el sitio está terminado. */ ?>
  <div class="foot-bottom">
    <?= et('pie.beta') ?>
    <a href="<?= e(url('contacto')) ?>"><?= et('pie.beta_enlace') ?></a>.
  </div>
</footer>

<?php /* El banner y el panel de cookies (REQ-00003). Salen ocultos; los enseña
         assets/js/consentimiento.js. Van fuera del <footer> porque no son parte
         del pie: se pintan flotando sobre la página. */ ?>
<?php require __DIR__ . '/cookies-dialogo.php'; ?>

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
