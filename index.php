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
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/eventos.php';

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

$titulo        = 'Directorio de eventos wellness en México';
$seccion       = 'inicio';
$anchoLibre    = true;
$scriptsPagina = ['assets/js/inicio.js'];

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
         izquierda, que nombra el evento que se esta viendo. Asi hay un
         unico h1 y el mensaje del sitio no depende de donde pare el giro. -->
    <div class="carrusel" id="carrusel">
      <div class="slide activa" aria-hidden="false">
        <div class="hero-media m1"></div>
        <div class="hero-scrim"></div>
        <div class="slide-chip"><span class="cat">Sound Healing</span> Amanecer en el Cenote · Tulum</div>
      </div>
      <div class="slide" aria-hidden="true">
        <div class="hero-media m2"></div>
        <div class="hero-scrim"></div>
        <div class="slide-chip"><span class="cat">Festival</span> Festival Holístico Raíz · CDMX</div>
      </div>
      <div class="slide" aria-hidden="true">
        <div class="hero-media m3"></div>
        <div class="hero-scrim"></div>
        <div class="slide-chip"><span class="cat">Breathwork</span> Bajo las estrellas · San Miguel</div>
      </div>
      <div class="slide" aria-hidden="true">
        <div class="hero-media m4"></div>
        <div class="hero-scrim"></div>
        <div class="slide-chip"><span class="cat">Retiro</span> Silencio Vipassana · Oaxaca</div>
      </div>
    </div>

    <div class="cctrl">
      <div class="cdots" id="cdots">
        <button type="button" aria-current="true"  aria-label="Ver imagen 1"></button>
        <button type="button" aria-current="false" aria-label="Ver imagen 2"></button>
        <button type="button" aria-current="false" aria-label="Ver imagen 3"></button>
        <button type="button" aria-current="false" aria-label="Ver imagen 4"></button>
      </div>
      <button type="button" class="cnav" id="cprev" aria-label="Imagen anterior">‹</button>
      <button type="button" class="cnav" id="cnext" aria-label="Imagen siguiente">›</button>
    </div>

    <div class="hero-content">
      <div class="eyebrow">Directorio de eventos · México</div>
      <h1>Encuentra tu próximo <em>retiro, festival o círculo</em> de bienestar</h1>
      <p class="sub">Retiros de yoga, breathwork, sound healing y festivales holísticos, reunidos en un solo lugar — sin buscar por veinte cuentas de Instagram distintas.</p>
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
    <form class="buscador" method="get" action="<?= URL_BASE ?>/buscar.php">
      <div class="bcampo">
        <label for="bDonde">Dónde</label>
        <input id="bDonde" name="q" type="text" placeholder="Tulum, CDMX, Oaxaca…"
               autocomplete="off" list="lugaresConEventos">
        <?php /* El datalist propone solo sitios donde hay algo. Es la forma
                 barata de que nadie teclee una ciudad vacía. */ ?>
        <datalist id="lugaresConEventos">
          <?php foreach ($lugares as $l): ?><option value="<?= e($l) ?>"></option><?php endforeach; ?>
        </datalist>
      </div>
      <div class="bsep" aria-hidden="true"></div>
      <div class="bcampo">
        <label for="bCuando">Cuándo</label>
        <select id="bCuando" name="fecha">
          <option value="">Cualquier fecha</option>
          <option value="finde">Este fin de semana</option>
          <option value="7dias">Próximos 7 días</option>
          <option value="mes">Este mes</option>
        </select>
      </div>
      <div class="bsep" aria-hidden="true"></div>
      <div class="bcampo">
        <label for="bQue">Qué</label>
        <?php /* Las prácticas salen de categoriasMenu(), la misma lista que
                 valida el formulario de alta. Escritas a mano aquí, este
                 desplegable ofrecía Retiro y Festival y se dejaba fuera
                 Temazcal, Cacao, Ceremonia, Ecstatic Dance y Pilates: buscar
                 por ellas era imposible desde la portada. */ ?>
        <select id="bQue" name="cat">
          <option value="">Cualquier práctica</option>
          <?php foreach (categoriasMenu() as $catNombre => $catDatos): ?>
            <option value="<?= e($catNombre) ?>"><?= e($catDatos[1]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" aria-label="Buscar eventos">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20.2 20.2l-4-4"/></svg>
      </button>
    </form>

    <div class="hero-actions">
      <?php /* Sin sesión no tiene sentido llevarlo al panel: primero hay que
               saber quién es. Va al login, que ya trae la vía de entrada. */ ?>
      <a class="ghostlink" href="<?= URL_BASE ?><?= $u ? '/mis-eventos.php' : '/login.php' ?>">
        ¿Organizas eventos? Publica el tuyo →
      </a>
    </div>
  </div>
</section>

<!-- Los emoji son marcadores de posicion, igual que las imagenes: cuando haya
     un juego de iconos de linea se sustituye el contenido de .ic y nada mas
     cambia. -->
<section class="catbar">
  <div class="catbar-inner">
    <span class="eyebrow">Explora por categoría</span>
    <div class="catrail-wrap">
      <div class="catrail" id="catrail">
        <?php /* Enlaces y no botones: cada categoría tiene ahora su propia
                 dirección —/buscar.php?cat=Yoga—, que se puede compartir,
                 abrir en otra pestaña e indexar. */ ?>
        <?php foreach (categoriasMenu() as $catNombre => $catDatos): ?>
          <a class="catitem" href="<?= URL_BASE ?>/buscar.php?cat=<?= urlencode($catNombre) ?>">
            <span class="ic"><?= e($catDatos[0]) ?></span>
            <span class="lbl"><?= e($catDatos[1]) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <button type="button" class="catnext" id="catnext" aria-label="Ver más categorías">›</button>
    </div>
  </div>
</section>

<!-- Proximos eventos en carril horizontal. La lista de filas anterior daba
     mas densidad, pero la tarjeta con imagen es lo que deja ver de que va
     cada evento antes de entrar — que es lo que se le pide a un directorio. -->
<section class="block wrap">
  <div class="block-head">
    <h2>Próximos eventos</h2>
    <a class="more" href="<?= URL_BASE ?>/buscar.php">Ver todos los eventos →</a>
  </div>
  <div class="evrail-wrap">
    <div class="evrail" id="proximosRail"></div>
    <button type="button" class="evnext" id="evnext" aria-label="Ver más eventos">›</button>
  </div>
</section>

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
      <div class="b-img" style="background-color:#496B52;"></div>
      <div class="b-body">
        <div class="eyebrow">Guía</div>
        <h3>Cómo elegir tu primer retiro de silencio</h3>
      </div>
    </a>
    <a class="card-blog" href="<?= URL_BASE ?>/blog.php">
      <div class="b-img" style="background-color:#C76E43;"></div>
      <div class="b-body">
        <div class="eyebrow">Ciudades</div>
        <h3>Tulum más allá de la playa: dónde se practica de verdad</h3>
      </div>
    </a>
    <a class="card-blog" href="<?= URL_BASE ?>/blog.php">
      <div class="b-img" style="background-color:#3E6375;"></div>
      <div class="b-body">
        <div class="eyebrow">Prácticas</div>
        <h3>Qué esperar de tu primera ceremonia de cacao</h3>
      </div>
    </a>
  </div>
</section>

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

<script>
  var EVENTOS = <?= json_encode($eventosJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                          | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<?php pie(); ?>
