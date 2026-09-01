<?php
/**
 * La portada.
 *
 * Hasta ahora este archivo era el sitio entero: siete vistas dentro de un mismo
 * documento que se conmutaban con JavaScript. Servía mientras fue un prototipo,
 * pero tenía un coste que se notaba: nada tenía dirección propia. No se podía
 * enlazar a un resultado de búsqueda, el botón «atrás» del navegador no hacía
 * lo que se espera, y Google veía una sola página.
 *
 * Ahora cada cosa es una página: inicio aquí, /buscar.php, /blog.php, la ficha
 * en /evento.php, y los dos paneles en /mis-eventos.php y /admin.php.
 *
 * Se fueron dos vistas por el camino, y no por descuido: la ficha de ejemplo
 * («Amanecer en el Cenote», con su cupo y su botón de comprar) y el perfil de
 * organizador («Raíz Colectivo»). Las dos eran maqueta escrita a mano, sin
 * nada detrás, y la ficha de verdad —evento.php— lleva tiempo funcionando. Sus
 * maquetas siguen intactas en prototipos/v3-final, que es para lo que están.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';

$u = usuarioActual();

/*
 * Los eventos de verdad, en la forma que espera el JavaScript de las tarjetas.
 * El prototipo traía un array de ejemplo escrito a mano; ahora sale de la base
 * y el render se queda igual.
 */
$eventosJs = array_map('eventoParaTarjeta', eventosPublicados());

// Para el «Dónde» del buscador: solo se proponen sitios donde hay algo.
$lugares = array_values(array_unique(array_merge(
    array_column($eventosJs, 'ciudad'),
    array_column($eventosJs, 'entidad')
)));
sort($lugares, SORT_NATURAL | SORT_FLAG_CASE);

$titulo        = t('pagina.inicio.titulo');
$descripcion   = t('pagina.inicio.meta');
$seccion       = 'inicio';
$anchoLibre    = true;
$scriptsPagina = ['assets/js/inicio.js', 'assets/js/buscador.js'];

/*
 * Para «/» exacto, Apache sirve este archivo directo por DirectoryIndex —la
 * condición !-d de .htaccess excluye los directorios, y la raíz lo es—, sin
 * pasar por router.php. Es la única página a la que le pasa esto, y
 * layout.php necesita $GLOBALS['rutaActual'] para imprimir el canonical y el
 * hreflang: sin esto la portada se queda sin ninguno de los dos. Con isset()
 * porque cuando sí se llega por router.php (/en, por ejemplo) ya viene
 * puesto, y no hay que pisarlo.
 */
if (!isset($GLOBALS['rutaActual'])) {
    $GLOBALS['rutaActual'] = 'inicio';
}

require __DIR__ . '/includes/layout.php';
?>

<section class="hero">
  <div class="hero-banner">
    <!-- Carrusel de escenas. Cada .hero-media es un marcador de posicion
         compuesto con gradientes; el dia que haya fotografia se cambia cada
         uno por un background-image y ni los controles ni el titulo se
         enteran.

         El titulo de la pagina NO rota: vive fuera de las diapositivas, al
         pie del banner. Solo cambian la imagen y la etiqueta de arriba a la
         izquierda, que nombra la actividad que se esta viendo. Asi hay un
         unico h1 y el mensaje del sitio no depende de donde pare el giro. -->
    <div class="carrusel" id="carrusel">
      <div class="slide activa" aria-hidden="false">
        <div class="hero-media m1"></div>
        <div class="hero-scrim"></div>
        <div class="slide-chip"><span class="cat"><?= et('inicio.hero.slide1_cat') ?></span> <?= et('inicio.hero.slide1_texto') ?></div>
      </div>
      <div class="slide" aria-hidden="true">
        <div class="hero-media m2"></div>
        <div class="hero-scrim"></div>
        <div class="slide-chip"><span class="cat"><?= et('inicio.hero.slide2_cat') ?></span> <?= et('inicio.hero.slide2_texto') ?></div>
      </div>
      <div class="slide" aria-hidden="true">
        <div class="hero-media m3"></div>
        <div class="hero-scrim"></div>
        <div class="slide-chip"><span class="cat"><?= et('inicio.hero.slide3_cat') ?></span> <?= et('inicio.hero.slide3_texto') ?></div>
      </div>
      <div class="slide" aria-hidden="true">
        <div class="hero-media m4"></div>
        <div class="hero-scrim"></div>
        <div class="slide-chip"><span class="cat"><?= et('inicio.hero.slide4_cat') ?></span> <?= et('inicio.hero.slide4_texto') ?></div>
      </div>
    </div>

    <div class="cctrl">
      <div class="cdots" id="cdots">
        <button type="button" aria-current="true"  aria-label="<?= et('inicio.hero.ver_imagen') ?> 1"></button>
        <button type="button" aria-current="false" aria-label="<?= et('inicio.hero.ver_imagen') ?> 2"></button>
        <button type="button" aria-current="false" aria-label="<?= et('inicio.hero.ver_imagen') ?> 3"></button>
        <button type="button" aria-current="false" aria-label="<?= et('inicio.hero.ver_imagen') ?> 4"></button>
      </div>
      <button type="button" class="cnav" id="cprev" aria-label="<?= et('inicio.hero.imagen_anterior') ?>">‹</button>
      <button type="button" class="cnav" id="cnext" aria-label="<?= et('inicio.hero.imagen_siguiente') ?>">›</button>
    </div>

    <div class="hero-content">
      <div class="eyebrow"><?= et('inicio.hero.eyebrow') ?></div>
      <h1><?= t('inicio.hero.titulo') ?></h1>
      <p class="sub"><?= et('inicio.hero.sub') ?></p>
    </div>
  </div>

  <div class="hero-inner">
    <?php /* Un formulario GET de toda la vida, sin JavaScript de por medio:
             lleva a /buscar.php con lo que se haya puesto y allí el panel
             aparece con esos filtros. Funciona con el teclado, con el botón
             «atrás», abriendo en pestaña nueva y aunque el JavaScript falle.

             «Dónde» viaja como «q», que es la búsqueda por texto. Busca en el
             lugar, y también en el título, la categoría y quien organiza, así
             que escribir «Tulum» encuentra Tulum sin necesitar un campo de
             ciudad aparte. */ ?>
    <form class="buscador" method="get" action="<?= e(url('actividades')) ?>">
      <div class="bcampo">
        <label for="bDonde"><?= et('inicio.buscador.donde_label') ?></label>
        <input id="bDonde" name="q" type="text" placeholder="<?= et('inicio.buscador.donde_placeholder') ?>"
               autocomplete="off" list="lugaresConEventos">
        <?php /* El datalist propone solo sitios donde hay algo. Es la forma
                 barata de que nadie teclee una ciudad vacía.

                 Desde REQ-00005 la lista que se ve NO es esta: el navegador la
                 dibuja a su manera —estrecha y pegada al campo, la «nube de
                 diálogo» del requerimiento— y no hay CSS que la cambie.
                 assets/js/buscador.js lee estas opciones, monta con ellas un
                 panel igual que el de los otros dos campos y le quita el
                 atributo «list» al input para que no salgan las dos listas.

                 El datalist se queda por lo mismo que se quedan los <select>:
                 si ese archivo no llega a ejecutarse, el campo sigue
                 proponiendo sitios. */ ?>
        <datalist id="lugaresConEventos">
          <?php foreach ($lugares as $l): ?><option value="<?= e($l) ?>"></option><?php endforeach; ?>
        </datalist>
      </div>
      <div class="bsep" aria-hidden="true"></div>
      <div class="bcampo">
        <label for="bCuando"><?= et('inicio.buscador.cuando_label') ?></label>
        <select id="bCuando" name="fecha">
          <option value=""><?= et('inicio.buscador.cuando_cualquiera') ?></option>
          <option value="finde"><?= et('inicio.buscador.cuando_finde') ?></option>
          <option value="7dias"><?= et('inicio.buscador.cuando_7dias') ?></option>
          <option value="mes"><?= et('inicio.buscador.cuando_mes') ?></option>
        </select>
      </div>
      <div class="bsep" aria-hidden="true"></div>
      <div class="bcampo">
        <label for="bQue"><?= et('inicio.buscador.que_label') ?></label>
        <?php /* Las prácticas salen de categoriasMenu(), la misma lista que
                 valida el formulario de alta. Escritas a mano aquí, este
                 desplegable ofrecía Retiro y Festival y se dejaba fuera
                 Temazcal, Cacao, Ceremonia, Ecstatic Dance y Pilates: buscar
                 por ellas era imposible desde la portada. */ ?>
        <select id="bQue" name="cat">
          <option value=""><?= et('inicio.buscador.que_cualquiera') ?></option>
          <?php foreach (categoriasMenu() as $catNombre => $catDatos): ?>
            <option value="<?= e($catNombre) ?>"><?= e($catDatos[1]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" aria-label="<?= et('inicio.buscador.boton_aria') ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20.2 20.2l-4-4"/></svg>
      </button>
    </form>

    <div class="hero-actions">
      <?php /* Sin sesión no tiene sentido llevarlo al panel: primero hay que
               saber quién es. Va al login, que ya trae la vía de entrada. */ ?>
      <a class="ghostlink" href="<?= $u ? e(URL_BASE . '/mis-eventos.php') : e(url('login')) ?>">
        <?= et('inicio.publica.enlace') ?>
      </a>
    </div>
  </div>
</section>

<?php /* El emoji fue marcador de posición hasta que volvió el set de iconos
         de línea original —ver iconosLineaCategoria() en includes/eventos.php—.
         Si alguna categoría nueva no tuviera todavía su icono dibujado, cae al
         emoji de categoriasMenu() en vez de dejar el círculo vacío. */ ?>
<?php $iconosLinea = iconosLineaCategoria(); ?>
<section class="catbar">
  <div class="catbar-inner">
    <span class="eyebrow"><?= et('inicio.categorias.eyebrow') ?></span>
    <div class="catrail-wrap">
      <div class="catrail" id="catrail">
        <?php /* Enlaces y no botones: cada categoría tiene ahora su propia
                 dirección —/buscar.php?cat=Yoga—, que se puede compartir,
                 abrir en otra pestaña e indexar. */ ?>
        <?php foreach (categoriasMenu() as $catNombre => $catDatos): ?>
          <a class="catitem" href="<?= e(url('actividades')) ?>?cat=<?= urlencode($catNombre) ?>">
            <span class="ic"><?= $iconosLinea[$catNombre] ?? e($catDatos[0]) ?></span>
            <span class="lbl"><?= e($catDatos[1]) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <button type="button" class="catnext" id="catnext" aria-label="<?= et('inicio.categorias.mas_aria') ?>">›</button>
    </div>
  </div>
</section>

<!-- Proximas actividades en carril horizontal. La lista de filas anterior daba
     mas densidad, pero la tarjeta con imagen es lo que deja ver de que va
     cada una antes de entrar — que es lo que se le pide a un directorio. -->
<section class="block wrap">
  <div class="block-head">
    <h2><?= et('inicio.proximas.titulo') ?></h2>
    <a class="more" href="<?= e(url('actividades')) ?>"><?= et('inicio.proximas.ver_todas') ?></a>
  </div>
  <div class="evrail-wrap">
    <div class="evrail" id="proximosRail"></div>
    <button type="button" class="evnext" id="evnext" aria-label="<?= et('inicio.proximas.mas_aria') ?>">›</button>
  </div>
</section>

<?php
/*
 * Blog y Newsletter no entran en el MVP (REQ-00004). Los dos bloques se quedan
 * escritos tal cual —maquetados, con sus estilos y su JavaScript— y solo se
 * pregunta si toca enseñarlos. Ver includes/secciones.php.
 */
if (seccionVisible('blog')): ?>
<section class="block wrap">
  <div class="block-head">
    <h2>Artículos</h2>
    <a class="more" href="<?= URL_BASE ?>/blog.php">Ir al blog →</a>
  </div>
  <div class="grid-blog">
    <?php /* El blog es maqueta todavía: no hay tabla de artículos ni forma de
             escribirlos. Los tres enlaces llevan al índice del blog, que dice
             lo mismo. Cuando haya artículos de verdad, cada uno irá al suyo. */ ?>
    <a class="card-blog" href="<?= URL_BASE ?>/blog.php">
      <div class="b-img" style="background-color:var(--verde-claro);"></div>
      <div class="b-body">
        <div class="eyebrow">Guía</div>
        <h3>Cómo elegir tu primer retiro de silencio</h3>
      </div>
    </a>
    <a class="card-blog" href="<?= URL_BASE ?>/blog.php">
      <div class="b-img" style="background-color:var(--terracota);"></div>
      <div class="b-body">
        <div class="eyebrow">Ciudades</div>
        <h3>Tulum más allá de la playa: dónde se practica de verdad</h3>
      </div>
    </a>
    <a class="card-blog" href="<?= URL_BASE ?>/blog.php">
      <div class="b-img" style="background-color:var(--petroleo-suave);"></div>
      <div class="b-body">
        <div class="eyebrow">Prácticas</div>
        <h3>Qué esperar de tu primera ceremonia de cacao</h3>
      </div>
    </a>
  </div>
</section>
<?php endif; ?>

<?php if (seccionVisible('newsletter')): ?>
<section class="block wrap">
  <div class="newsletter">
    <div>
      <h3>Un correo al mes con lo mejor del bienestar en México</h3>
      <p>Sin spam. Solo retiros, festivales y círculos que valen la pena.</p>
    </div>
    <div>
      <?php /* Todavía no guarda nada: no hay lista de correo detrás. Enseña el
               acuse y limpia el campo, que es lo que hacía en el prototipo. */ ?>
      <form class="nform" id="boletin">
        <input type="email" required placeholder="tucorreo@ejemplo.com">
        <button type="submit">Suscribirme</button>
      </form>
      <div class="toast">✓ Gracias — revisa tu correo para confirmar.</div>
    </div>
  </div>
</section>
<?php endif; ?>

<script>
  var EVENTOS = <?= json_encode($eventosJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                          | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  // Lo que necesita inicio.js para el aviso de "todavía no hay actividades"
  // del carril —ver vacioHTML() en tarjetas.js—, en el idioma de la página.
  var INICIO_T = <?= json_encode([
      'vacio'           => t('buscar.js.vacio_directorio'),
      'publicarPrimera' => t('tarjetas.publicar_primera'),
  ], JSON_UNESCAPED_UNICODE) ?>;
</script>

<?php pie(); ?>
