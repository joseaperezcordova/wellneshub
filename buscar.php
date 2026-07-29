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
 * El filtrado en sí lo hace el navegador, no esta página: los eventos ya vienen
 * todos aquí y volver al servidor por cada casilla que se marca se nota más que
 * cualquier ahorro. Lo que hace PHP es armar el panel con las opciones que
 * existen de verdad y dejar los controles puestos según la dirección, para que
 * la página ya llegue con la búsqueda hecha.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/eventos.php';
require __DIR__ . '/includes/busqueda.php';

$eventosJs = array_map('eventoParaTarjeta', eventosPublicados());
$filtros   = filtrosDesdePeticion($_GET);

/*
 * Los desplegables de estado y ciudad salen de los eventos que hay, no de una
 * lista escrita a mano. Escribirlos a mano tenía dos costes: un estado sin
 * eventos solo llevaba a un resultado vacío, y una ciudad nueva —que cualquiera
 * puede teclear al publicar— no aparecía nunca.
 */
$entidadesFiltro = array_values(array_unique(array_column($eventosJs, 'entidad')));
$ciudadesFiltro  = array_values(array_unique(array_column($eventosJs, 'ciudad')));
sort($entidadesFiltro, SORT_NATURAL | SORT_FLAG_CASE);
sort($ciudadesFiltro,  SORT_NATURAL | SORT_FLAG_CASE);

$titulo        = 'Buscar actividades';
$anchoLibre    = true;
$scriptsPagina = ['assets/js/buscar.js'];

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="results-heading">
    <div class="eyebrow" id="resultsEyebrow">Buscar actividades</div>
    <div class="block-head"><h2 id="resultsTitle">Todas las actividades</h2></div>
  </div>

  <div class="results-layout">
    <?php /* «Modalidad: presencial / online» no está en el panel: no hay ningún
             campo en la base que diga si una actividad es en línea, así que la
             casilla no podía hacer otra cosa que mentir. Vuelve el día que el
             formulario de alta lo pregunte. */ ?>
    <aside class="filters">
      <h4><label for="fTexto">Nombre</label></h4>
      <input id="fTexto" type="search" placeholder="Retiro, cenote, luna…"
             autocomplete="off" value="<?= e($filtros['texto']) ?>">

      <h4><label for="fEstado">Estado</label></h4>
      <select id="fEstado">
        <option value="">Todos los estados</option>
        <?php foreach ($entidadesFiltro as $en): ?>
          <option value="<?= e($en) ?>"<?= $en === $filtros['entidad'] ? ' selected' : '' ?>><?= e($en) ?></option>
        <?php endforeach; ?>
      </select>

      <h4><label for="fCiudad">Ciudad</label></h4>
      <select id="fCiudad">
        <option value="">Todas las ciudades</option>
        <?php foreach ($ciudadesFiltro as $c): ?>
          <option value="<?= e($c) ?>"<?= $c === $filtros['ciudad'] ? ' selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>

      <h4><label for="fFecha">Fecha</label></h4>
      <select id="fFecha">
        <?php foreach (['' => 'Cualquier fecha', 'finde' => 'Este fin de semana',
                        '7dias' => 'Próximos 7 días', 'mes' => 'Este mes'] as $clave => $texto): ?>
          <option value="<?= e($clave) ?>"<?= $clave === $filtros['fecha'] ? ' selected' : '' ?>><?= e($texto) ?></option>
        <?php endforeach; ?>
      </select>

      <?php /* Las categorías salen de categoriasMenu(), la misma lista que
               valida el formulario de alta. Escritas a mano en los dos sitios,
               una categoría nueva aparecía aquí y no se podía elegir al
               publicar —o al revés. */ ?>
      <h4>Categoría</h4>
      <div class="checklist" id="fCats">
        <?php foreach (categoriasMenu() as $catNombre => $catDatos): ?>
          <label>
            <input type="checkbox" value="<?= e($catNombre) ?>"
                   <?= in_array($catNombre, $filtros['cats'], true) ? 'checked' : '' ?>>
            <?= e($catDatos[1]) ?>
          </label>
        <?php endforeach; ?>
      </div>

      <h4>Precio</h4>
      <div class="checklist">
        <label><input type="checkbox" id="fGratis"<?= $filtros['gratis'] ? ' checked' : '' ?>> Solo gratuitas</label>
      </div>

      <button type="button" class="filtros-limpiar" id="fLimpiar" hidden>Quitar filtros</button>
    </aside>

    <div>
      <div class="results-head">
        <div class="count" id="resultsCount"></div>
        <select class="sortsel" id="fOrden">
          <?php foreach (['fecha' => 'Ordenar: más próximas', 'precio' => 'Precio: menor a mayor',
                          'nuevos' => 'Recién publicadas'] as $clave => $texto): ?>
            <option value="<?= e($clave) ?>"<?= $clave === $filtros['orden'] ? ' selected' : '' ?>><?= e($texto) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="results-grid" id="resultsGrid"></div>
    </div>
  </div>
</section>

<script>
  var EVENTOS = <?= json_encode($eventosJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                          | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<?php pie(); ?>
