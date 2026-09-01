<?php
/**
 * Buscar actividades.
 *
 * Era una de las vistas que vivían dentro de index.php y se conmutaban con
 * JavaScript. Ahora es una página con su propia dirección, y los filtros van
 * dentro de ella: buscar.php?ciudad=Tulum&cat=Yoga.
 *
 * Eso es lo que permite entrar a la ficha de una actividad y volver a la misma
 * búsqueda —el enlace de vuelta se lo lleva puesto—, y de paso que una
 * búsqueda se pueda compartir o guardar en marcadores.
 *
 * El filtrado ya NO lo hace el navegador: buscar-datos.php resuelve cada
 * búsqueda en SQL y trae solo una página de resultados. Antes los eventos
 * publicados venían todos incrustados aquí (hasta 60) y se filtraban en el
 * navegador; con más de 60 actividades esa lista ya no cabía entera. Esta
 * página solo arma el panel con las opciones que existen de verdad y deja los
 * controles puestos según la dirección —el JS pide la primera página nada más
 * cargar, así que hay un único camino para "cargar" y para "cargar más".
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';
require_once __DIR__ . '/includes/busqueda.php';

$filtros = filtrosDesdePeticion($_GET);

/*
 * Los desplegables de estado y ciudad salen de los eventos que hay, no de una
 * lista escrita a mano. Escribirlos a mano tenía dos costes: un estado sin
 * eventos solo llevaba a un resultado vacío, y una ciudad nueva —que cualquiera
 * puede teclear al publicar— no aparecía nunca.
 */
$ubicaciones      = ubicacionesConActividad();
$entidadesFiltro  = $ubicaciones['entidades'];
$ciudadesFiltro   = $ubicaciones['ciudades'];

$titulo        = t('pagina.buscar.titulo');
$descripcion   = t('pagina.buscar.meta');
$anchoLibre    = true;

/*
 * La dirección pública del directorio es /actividades (REQ-00006). Este archivo
 * responde también a /buscar.php, que es la interna, y sin canónica Google
 * podría indexar esa —lo mismo que se acaba de arreglar en la ficha—.
 *
 * Cuando se llega por /actividades el enrutador ya deja puesta la ruta y
 * layout.php emite la canónica y los hreflang por su cuenta; este $canonical
 * solo cubre la entrada directa por el .php.
 */
$canonical = url('actividades');
$scriptsPagina = ['assets/js/buscar.js'];

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="results-heading">
    <div class="eyebrow" id="resultsEyebrow"><?= et('buscar.eyebrow') ?></div>
    <div class="block-head"><h1 id="resultsTitle"><?= et('buscar.js.todas') ?></h1></div>
  </div>

  <div class="results-layout">
    <?php /* «Modalidad: presencial / online» no está en el panel: no hay ningún
             campo en la base que diga si una actividad es en línea, así que la
             casilla no podía hacer otra cosa que mentir. Vuelve el día que el
             formulario de alta lo pregunte. */ ?>
    <aside class="filters">
      <h4><label for="fTexto"><?= et('buscar.filtros.nombre') ?></label></h4>
      <input id="fTexto" type="search" placeholder="<?= et('buscar.filtros.nombre_placeholder') ?>"
             autocomplete="off" value="<?= e($filtros['texto']) ?>">

      <h4><label for="fEstado"><?= et('buscar.filtros.estado') ?></label></h4>
      <select id="fEstado">
        <option value=""><?= et('buscar.filtros.todos_estados') ?></option>
        <?php foreach ($entidadesFiltro as $en): ?>
          <option value="<?= e($en) ?>"<?= $en === $filtros['entidad'] ? ' selected' : '' ?>><?= e($en) ?></option>
        <?php endforeach; ?>
      </select>

      <h4><label for="fCiudad"><?= et('buscar.filtros.ciudad') ?></label></h4>
      <select id="fCiudad">
        <option value=""><?= et('buscar.filtros.todas_ciudades') ?></option>
        <?php foreach ($ciudadesFiltro as $c): ?>
          <option value="<?= e($c) ?>"<?= $c === $filtros['ciudad'] ? ' selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>

      <h4><label for="fFecha"><?= et('buscar.filtros.fecha') ?></label></h4>
      <select id="fFecha">
        <?php foreach (['' => t('inicio.buscador.cuando_cualquiera'), 'finde' => t('inicio.buscador.cuando_finde'),
                        '7dias' => t('inicio.buscador.cuando_7dias'), 'mes' => t('inicio.buscador.cuando_mes')] as $clave => $texto): ?>
          <option value="<?= e($clave) ?>"<?= $clave === $filtros['fecha'] ? ' selected' : '' ?>><?= e($texto) ?></option>
        <?php endforeach; ?>
      </select>

      <?php /* Las categorías salen de categoriasMenu(), la misma lista que
               valida el formulario de alta. Escritas a mano en los dos sitios,
               una categoría nueva aparecía aquí y no se podía elegir al
               publicar —o al revés. */ ?>
      <h4><?= et('buscar.filtros.categoria') ?></h4>
      <div class="checklist" id="fCats">
        <?php foreach (categoriasMenu() as $catNombre => $catDatos): ?>
          <label>
            <input type="checkbox" value="<?= e($catNombre) ?>"
                   <?= in_array($catNombre, $filtros['cats'], true) ? 'checked' : '' ?>>
            <?= e($catDatos[1]) ?>
          </label>
        <?php endforeach; ?>
      </div>

      <h4><?= et('buscar.filtros.precio') ?></h4>
      <div class="checklist">
        <label><input type="checkbox" id="fGratis"<?= $filtros['gratis'] ? ' checked' : '' ?>> <?= et('buscar.filtros.solo_gratuitas') ?></label>
      </div>

      <button type="button" class="filtros-limpiar" id="fLimpiar" hidden><?= et('buscar.filtros.quitar') ?></button>
    </aside>

    <div>
      <div class="results-head">
        <div class="count" id="resultsCount"></div>
        <?php /* Las tres opciones y su orden salen de ordenesBusqueda(), que es
                 también de donde sale el whitelist de la dirección. Escritas
                 aquí a mano, reordenar el menú podía cambiar sin querer cuál
                 era el orden por defecto —lo era la primera de la otra lista. */ ?>
        <select class="sortsel" id="fOrden" aria-label="<?= et('buscar.orden_aria') ?>">
          <?php foreach (ordenesBusqueda() as $clave => $texto): ?>
            <option value="<?= e($clave) ?>"<?= $clave === $filtros['orden'] ? ' selected' : '' ?>><?= e($texto) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="results-grid" id="resultsGrid"></div>
      <button type="button" class="btn-cargar-mas" id="btnCargarMas" hidden><?= et('buscar.cargar_mas') ?></button>
    </div>
  </div>
</section>

<?php /* Cuál es el orden por defecto lo decide PHP. buscar.js lo necesita para
         no escribirlo en la dirección, y tenerlo escrito a mano en los dos
         sitios es la forma de que un día dejen de coincidir. */ ?>
<script>var ORDEN_DEFECTO = <?= json_encode(ordenPorDefecto()) ?>;</script>

<?php /* buscar.js arma el título de resultados, el contador y los avisos de
         "sin resultados" en el navegador, según lo que se vaya filtrando —no
         hay recarga de página de por medio para pedírselo a PHP cada vez—.
         Sin este objeto esos textos se quedarían fijos en español pase lo que
         pase con la dirección /activities. */ ?>
<script>
var BUSCAR_T = <?= json_encode([
    'todas'             => t('buscar.js.todas'),
    'actividades'       => t('buscar.js.actividades'),
    'gratuitas'         => t('buscar.js.gratuitas'),
    'en'                => t('buscar.js.en'),
    'findeSufijo'       => t('buscar.js.finde'),
    'sieteDiasSufijo'   => t('buscar.js.7dias'),
    'mesSufijo'         => t('buscar.js.mes'),
    'sinCoincidencias'  => t('buscar.js.sin_coincidencias'),
    'unaEncontrada'     => t('buscar.js.una_encontrada'),
    'deTotal'           => t('buscar.js.de_total'),
    'actividadesSufijo' => t('buscar.js.actividades_sufijo'),
    'encontradasSufijo' => t('buscar.js.encontradas_sufijo'),
    'vacioDirectorio'   => t('buscar.js.vacio_directorio'),
    'sinResultados'     => t('buscar.js.sin_resultados'),
    'buscando'          => t('buscar.js.buscando'),
    'cargando'          => t('buscar.js.cargando'),
    'cargarMas'         => t('buscar.cargar_mas'),
    'quitarFiltros'     => t('buscar.filtros.quitar'),
    'publicarPrimera'   => t('tarjetas.publicar_primera'),
    'error'             => t('buscar.js.error'),
], JSON_UNESCAPED_UNICODE) ?>;
</script>

<?php pie(); ?>
