<?php
/**
 * El formulario de la actividad, compartido por el alta y la edición.
 *
 * Está en un solo archivo porque los dos formularios son el mismo. Cuando
 * estaban duplicados, cada campo nuevo había que añadirlo dos veces y la
 * segunda se olvidaba.
 *
 * Espera definidas: $e (valores), $errores (por campo), $textoBoton.
 */

declare(strict_types=1);

/** El valor de un campo, con lo que el usuario acababa de escribir si falló. */
$v = function (string $campo, $porDefecto = '') use ($e) {
    return isset($e[$campo]) && $e[$campo] !== null ? (string) $e[$campo] : (string) $porDefecto;
};

/** Formato que exige datetime-local: 2026-08-16T19:30 */
$fechaInput = function (string $campo) use ($e) {
    if (empty($e[$campo])) return '';
    $ts = strtotime((string) $e[$campo]);
    return $ts === false ? '' : date('Y-m-d\TH:i', $ts);
};

/** Formato que exige type="date": 2026-08-16. Para la fecha de una recurrente. */
$fechaSoloInput = function (string $campo) use ($e) {
    if (empty($e[$campo])) return '';
    $ts = strtotime((string) $e[$campo]);
    return $ts === false ? '' : date('Y-m-d', $ts);
};

/** Formato que exige type="time": 19:30. */
$horaInput = function () use ($e) {
    if (empty($e['hora_recurrente'])) return '';
    $ts = strtotime((string) $e['hora_recurrente']);
    return $ts === false ? '' : date('H:i', $ts);
};

$esRecurrente = ($e['tipo_actividad'] ?? 'unico') === 'recurrente';

/** El mensaje de error de un campo, si lo hay. */
$err = function (string $campo) use ($errores) {
    return isset($errores[$campo])
        ? '<div class="campo-error">' . e($errores[$campo]) . '</div>'
        : '';
};

/**
 * La clase del contenedor cuando el campo falló.
 *
 * Sin esto, el único indicio del error era una línea de texto pequeña bajo el
 * campo, igual de discreta que las pistas grises de al lado. El aviso de arriba
 * decía «revisa los campos marcados» y no había ningún campo marcado: había que
 * repasar el formulario entero a ojo.
 */
$mal = function (string $campo) use ($errores) {
    return isset($errores[$campo]) ? ' con-error' : '';
};
?>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M7 3h7l4 4v14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/>
    <path d="M14 3v4h4"/>
  </svg>
  <h2>1. Información general</h2>
</div>

<div class="campo<?= $mal('titulo') ?>">
  <div class="label-fila">
    <label for="titulo">Título de la actividad</label>
    <span class="contador" id="contadorTitulo"></span>
  </div>
  <input id="titulo" name="titulo" type="text" required maxlength="160"
         value="<?= e($v('titulo')) ?>" placeholder="Amanecer en el Cenote — Yoga y Sonido">
  <?= $err('titulo') ?>
</div>

<div class="campo-fila">
  <div class="campo<?= $mal('categoria') ?>">
    <label for="categoria">Categoría</label>
    <select id="categoria" name="categoria" required>
      <option value="">Elige una…</option>
      <?php foreach (categorias() as $nombre => $icono): ?>
        <option value="<?= e($nombre) ?>" <?= $v('categoria') === $nombre ? 'selected' : '' ?>>
          <?= e($icono . '  ' . $nombre) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?= $err('categoria') ?>
  </div>

  <div class="campo">
    <label>Tipo de actividad</label>
    <div class="tipo-actividad">
      <label class="tipo-op">
        <input type="radio" name="tipo_actividad" value="unico" id="tipoUnico" <?= $esRecurrente ? '' : 'checked' ?>>
        <svg class="tipo-icono" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3.5" y="5" width="17" height="15" rx="2"/>
          <path d="M3.5 9.5h17"/>
          <path d="M8 3v4M16 3v4"/>
        </svg>
        <span>Actividad de un día</span>
      </label>
      <label class="tipo-op">
        <input type="radio" name="tipo_actividad" value="recurrente" id="tipoRecurrente" <?= $esRecurrente ? 'checked' : '' ?>>
        <svg class="tipo-icono" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M4 7h13a3 3 0 0 1 3 3v1"/>
          <path d="M17 4l3 3-3 3"/>
          <path d="M20 17H7a3 3 0 0 1-3-3v-1"/>
          <path d="M7 20l-3-3 3-3"/>
        </svg>
        <span>Actividad recurrente</span>
      </label>
    </div>
  </div>
</div>

<div class="campo<?= $mal('descripcion') ?>">
  <div class="label-fila">
    <label for="descripcion">Descripción</label>
    <span class="contador" id="contadorDescripcion"></span>
  </div>
  <textarea id="descripcion" name="descripcion" rows="7" required minlength="50" maxlength="2000"
            placeholder="Describe tu actividad. Incluye qué aprenderán los asistentes, a quién está dirigida, qué incluye y cualquier información importante."><?= e($v('descripcion')) ?></textarea>
  <div class="pista">Se muestra tal cual en la ficha. Los saltos de línea se respetan.</div>
  <?= $err('descripcion') ?>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z"/>
    <circle cx="12" cy="9" r="2.5"/>
  </svg>
  <h2>2. Modalidad</h2>
</div>

<div class="campo">
  <div class="modalidad-grupo">
    <label class="modalidad-op">
      <input type="radio" name="modalidad" value="presencial" id="modPresencial" <?= $v('modalidad', 'presencial') === 'presencial' ? 'checked' : '' ?>>
      <svg class="modalidad-icono" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor"
           stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z"/>
        <circle cx="12" cy="9" r="2.5"/>
      </svg>
      <span class="modalidad-titulo">Presencial</span>
      <span class="modalidad-desc">La actividad se realiza en un lugar físico.</span>
    </label>
    <label class="modalidad-op">
      <input type="radio" name="modalidad" value="en_linea" id="modEnLinea" <?= $v('modalidad', 'presencial') === 'en_linea' ? 'checked' : '' ?>>
      <svg class="modalidad-icono" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor"
           stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M5 12.5a11 11 0 0 1 14 0"/>
        <path d="M8 16a6.5 6.5 0 0 1 8 0"/>
        <circle cx="12" cy="19.3" r="1" fill="currentColor" stroke="none"/>
      </svg>
      <span class="modalidad-titulo">En línea</span>
      <span class="modalidad-desc">La actividad se realiza a través de internet.</span>
    </label>
    <label class="modalidad-op">
      <input type="radio" name="modalidad" value="hibrida" id="modHibrida" <?= $v('modalidad', 'presencial') === 'hibrida' ? 'checked' : '' ?>>
      <svg class="modalidad-icono" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor"
           stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3" y="4.5" width="12" height="9" rx="1.3"/>
        <path d="M3 16.5h12"/>
        <rect x="17.3" y="9" width="4.2" height="8.5" rx="1"/>
      </svg>
      <span class="modalidad-titulo">Híbrida</span>
      <span class="modalidad-desc">La actividad es presencial y en línea.</span>
    </label>
  </div>
</div>

<div class="campo-fila">
  <div class="campo<?= $mal('lugar') ?>" id="campoLugar">
    <label for="lugar">Lugar</label>
    <input id="lugar" name="lugar" type="text" maxlength="160"
           value="<?= e($v('lugar')) ?>" placeholder="Ej. Centro Holístico Luz">
    <?= $err('lugar') ?>
  </div>
  <div class="campo<?= $mal('enlace_acceso') ?>">
    <label for="enlace_acceso">Enlace de acceso <span class="opcional">opcional</span></label>
    <input id="enlace_acceso" name="enlace_acceso" type="url" maxlength="500"
           value="<?= e($v('enlace_acceso')) ?>" placeholder="https://">
    <div class="pista">Enlace privado que solo será visible para las personas registradas o confirmadas.</div>
    <?= $err('enlace_acceso') ?>
  </div>
</div>

<div id="bloqueUbicacionFisica">
  <div class="campo-fila">
    <div class="campo<?= $mal('ciudad') ?>">
      <label for="ciudad">Ciudad</label>
      <input id="ciudad" name="ciudad" type="text" maxlength="90" list="listaCiudades"
             value="<?= e($v('ciudad')) ?>" placeholder="Tulum" autocomplete="off">
      <datalist id="listaCiudades"></datalist>
      <?= $err('ciudad') ?>
    </div>
    <div class="campo<?= $mal('entidad') ?>">
      <label for="entidad">Estado</label>
      <select id="entidad" name="entidad">
        <option value="">Selecciona un estado</option>
        <?php foreach (estadosMexico() as $estado): ?>
          <option value="<?= e($estado) ?>" <?= $v('entidad') === $estado ? 'selected' : '' ?>><?= e($estado) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $err('entidad') ?>
    </div>
  </div>

  <div class="campo<?= $mal('mapa_url') ?>">
    <label for="mapa_url">Ubicación en el mapa <span class="opcional">opcional</span></label>
    <input id="mapa_url" name="mapa_url" type="text" maxlength="500"
           value="<?= e($v('mapa_url')) ?>" placeholder="https://maps.app.goo.gl/…">

    <?php /* Las instrucciones van antes del error y no después: quien las
             necesita es justo quien acaba de equivocarse, y si están debajo del
             mensaje rojo se leen tarde. */ ?>
    <div class="pista">
      Busca el sitio en Google Maps, pulsa <strong>Compartir</strong> y pega aquí el enlace.
      En la ficha sale un mapa con el punto y un botón para llegar.
      Si tienes las coordenadas a mano, también valen: <span class="mono">20.2114, -87.4654</span>.
    </div>

    <?php
    /*
     * Si el enlace ya se leyó, se enseña el mapa aquí mismo. Es la única forma de
     * que el organizador compruebe que el punto cayó donde tenía que caer antes
     * de publicar: un enlace copiado de una búsqueda a medias apunta al centro de
     * la ciudad, y desde el texto del enlace eso no se ve.
     */
    if (!empty($e['latitud']) && !empty($e['longitud'])):
    ?>
      <div class="mapa-previo">
        <div class="pista pista-ok">Ubicación reconocida. Comprueba que el punto está donde debe.</div>
        <iframe src="<?= e(urlMapaEmbebido((float) $e['latitud'], (float) $e['longitud'])) ?>"
                title="Vista previa de la ubicación" loading="lazy" referrerpolicy="no-referrer"></iframe>
      </div>
    <?php endif; ?>

    <?= $err('mapa_url') ?>
  </div>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <rect x="3.5" y="5" width="17" height="15" rx="2"/>
    <path d="M3.5 9.5h17"/>
    <path d="M8 3v4M16 3v4"/>
  </svg>
  <h2>3. Fecha y horario</h2>
</div>

<div class="campo-fila" id="bloqueFechaRecurrente" <?= $esRecurrente ? '' : 'style="display:none;"' ?>>
  <div class="campo<?= $mal('fecha_inicio') ?>">
    <?php // El id se lleva a "fecha_inicio" solo cuando este es el bloque activo:
          // es al que tiene que apuntar el enlace de aviso-errores.php cuando el
          // error viene de aquí y no del otro modo. ?>
    <label for="<?= $esRecurrente ? 'fecha_inicio' : 'fecha_inicio_rec' ?>">Fecha inicio</label>
    <input id="<?= $esRecurrente ? 'fecha_inicio' : 'fecha_inicio_rec' ?>" name="fecha_inicio_rec" type="date" <?= $esRecurrente ? 'required' : '' ?>
           value="<?= e($esRecurrente ? $fechaSoloInput('fecha_inicio') : '') ?>">
    <?= $err('fecha_inicio') ?>
  </div>
  <div class="campo<?= $mal('fecha_fin') ?>">
    <label for="<?= $esRecurrente ? 'fecha_fin' : 'fecha_fin_rec' ?>">Fecha fin</label>
    <input id="<?= $esRecurrente ? 'fecha_fin' : 'fecha_fin_rec' ?>" name="fecha_fin_rec" type="date" <?= $esRecurrente ? 'required' : '' ?>
           value="<?= e($esRecurrente ? $fechaSoloInput('fecha_fin') : '') ?>">
    <?= $err('fecha_fin') ?>
  </div>
  <div class="campo<?= $mal('frecuencia') ?>">
    <label for="frecuencia">Frecuencia</label>
    <select id="frecuencia" name="frecuencia" <?= $esRecurrente ? 'required' : '' ?>>
      <option value="">Elige una…</option>
      <?php foreach (frecuenciasRecurrencia() as $clave => $etiqueta): ?>
        <option value="<?= e($clave) ?>" <?= $v('frecuencia') === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
      <?php endforeach; ?>
    </select>
    <?= $err('frecuencia') ?>
  </div>
  <div class="campo<?= $mal('hora_recurrente') ?>">
    <label for="hora_recurrente">Hora</label>
    <input id="hora_recurrente" name="hora_recurrente" type="time" <?= $esRecurrente ? 'required' : '' ?>
           value="<?= e($horaInput()) ?>">
    <?= $err('hora_recurrente') ?>
  </div>
</div>

<div class="campo-fila" id="bloqueFechaUnica" <?= $esRecurrente ? 'style="display:none;"' : '' ?>>
  <div class="campo<?= $esRecurrente ? '' : $mal('fecha_inicio') ?>">
    <label <?= $esRecurrente ? '' : 'for="fecha_inicio"' ?>>Empieza</label>
    <input <?= $esRecurrente ? '' : 'id="fecha_inicio"' ?> name="fecha_inicio" type="datetime-local" <?= $esRecurrente ? '' : 'required' ?>
           value="<?= e($fechaInput('fecha_inicio')) ?>">
    <?= $esRecurrente ? '' : $err('fecha_inicio') ?>
  </div>
  <div class="campo<?= $esRecurrente ? '' : $mal('fecha_fin') ?>">
    <label <?= $esRecurrente ? '' : 'for="fecha_fin"' ?>>Termina <span class="opcional">opcional</span></label>
    <input <?= $esRecurrente ? '' : 'id="fecha_fin"' ?> name="fecha_fin" type="datetime-local"
           value="<?= e($fechaInput('fecha_fin')) ?>">
    <div class="pista">Para retiros de varios días.</div>
    <?= $esRecurrente ? '' : $err('fecha_fin') ?>
  </div>
</div>

<div class="campo">
  <label class="check">
    <input type="checkbox" name="gratuito" value="1" id="gratuito"
           <?= !empty($e['gratuito']) ? 'checked' : '' ?>>
    <span>Es gratuito</span>
  </label>
</div>

<div class="campo<?= $mal('precio') ?>" id="campoPrecio">
  <label for="precio">Precio por persona (MXN)</label>
  <input id="precio" name="precio" type="text" inputmode="decimal"
         value="<?= e($v('precio')) ?>" placeholder="2450">
  <?= $err('precio') ?>
</div>

<div class="campo<?= $mal('url_boletos') ?>">
  <label for="url_boletos">Enlace para comprar o reservar <span class="opcional">opcional</span></label>
  <input id="url_boletos" name="url_boletos" type="url" maxlength="500"
         value="<?= e($v('url_boletos')) ?>" placeholder="https://…">
  <?= $err('url_boletos') ?>
</div>

<div class="campo<?= $mal('imagen') ?>">
  <label for="imagen">Imagen <span class="opcional">opcional</span></label>

  <?php
  /*
   * El campo oculto es lo que hace que una foto ya elegida sobreviva a un
   * formulario devuelto por otro error. Un <input type="file"> no se puede
   * rellenar desde el servidor —el navegador no lo permite, y con razón: si se
   * pudiera, una página podría mandarse archivos del ordenador de quien la
   * visita—. Así que la foto se guarda ya y aquí solo viaja su nombre.
   *
   * Quién puede usar ese nombre lo decide imagenArrastrada(), en subidas.php.
   */
  $imagenGuardada = $e['imagen_url'] ?? null;
  $imagenActual   = urlImagen($imagenGuardada);
  ?>
  <?php if ($imagenActual !== null): ?>
    <input type="hidden" name="imagen_previa" value="<?= e((string) $imagenGuardada) ?>">
    <div class="imagen-actual">
      <img src="<?= e($imagenActual) ?>" alt="Imagen de la actividad">
      <?php if (esImagenEnVuelo($imagenGuardada)): ?>
        <div class="pista">Esta es la que acabas de elegir. Sigue puesta: no hace falta que la busques otra vez.</div>
      <?php endif; ?>
      <label class="check">
        <input type="checkbox" name="quitar_imagen" value="1">
        <span>Quitar esta imagen</span>
      </label>
    </div>
  <?php endif; ?>

  <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp">
  <div class="pista">
    JPG, PNG o WebP, hasta <?= round(IMAGEN_MAX_BYTES / 1048576) ?> MB. Si es muy grande se reduce sola.
    <?= $imagenActual !== null ? 'Elige otra solo si quieres cambiarla.' : 'Sin imagen se usa el color de abajo, como en el diseño de la portada.' ?>
  </div>
  <?= $err('imagen') ?>
</div>

<div class="campo">
  <label>Color de la tarjeta</label>
  <div class="colores">
    <?php $colorElegido = $v('color', coloresEvento()[0]); ?>
    <?php foreach (coloresEvento() as $color): ?>
      <label class="color-op">
        <input type="radio" name="color" value="<?= e($color) ?>"
               <?= $colorElegido === $color ? 'checked' : '' ?>>
        <span style="background:<?= e($color) ?>;"></span>
      </label>
    <?php endforeach; ?>
  </div>
  <div class="pista">Se usa cuando no hay imagen, igual que en el diseño de la portada.</div>
</div>

<button class="btn-principal" type="submit"><?= e($textoBoton) ?></button>

<script>
/* El precio no pinta nada si el evento es gratuito. Ocultarlo evita la duda de
   si hay que poner 0 o dejarlo vacío. */
(function(){
  var check  = document.getElementById('gratuito');
  var precio = document.getElementById('campoPrecio');
  function sync(){ precio.style.display = check.checked ? 'none' : ''; }
  check.addEventListener('change', sync);
  sync();
})();

/* Contador de la descripción. El mínimo y máximo ya los exige el servidor
   —esto es solo para que se vean venir antes de enviar—. */
(function(){
  var campo    = document.getElementById('descripcion');
  var contador = document.getElementById('contadorDescripcion');
  if (!campo || !contador) return;

  var MIN = 50, MAX = 2000;

  function sync(){
    var n = campo.value.length;
    contador.textContent = n + ' / ' + MAX;
    contador.classList.toggle('corto', n < MIN);
  }
  campo.addEventListener('input', sync);
  sync();
})();

/* Contador del título. Solo tiene máximo, así que no hace falta el aviso de
   "corto" que sí lleva la descripción. */
(function(){
  var campo    = document.getElementById('titulo');
  var contador = document.getElementById('contadorTitulo');
  if (!campo || !contador) return;

  function sync(){ contador.textContent = campo.value.length + ' / 160'; }
  campo.addEventListener('input', sync);
  sync();
})();

/* De un día / recurrente cambian qué fechas se piden. Los campos del bloque
   que queda oculto se marcan no-requeridos: si no, un navegador que sí valida
   los "required" ocultos —la mayoría los ignora, pero no hay que confiar en
   eso— bloquearía el envío por un campo que la persona ni ve. */
(function(){
  var radios = document.querySelectorAll('input[name="tipo_actividad"]');
  var bloqueUnico      = document.getElementById('bloqueFechaUnica');
  var bloqueRecurrente = document.getElementById('bloqueFechaRecurrente');
  if (!radios.length || !bloqueUnico || !bloqueRecurrente) return;

  var campoInicioUnico = bloqueUnico.querySelector('[name="fecha_inicio"]');
  var camposRecurrente = bloqueRecurrente.querySelectorAll('[name="fecha_inicio_rec"], [name="fecha_fin_rec"], [name="frecuencia"], [name="hora_recurrente"]');

  function sync(){
    var recurrente = document.querySelector('input[name="tipo_actividad"]:checked').value === 'recurrente';

    bloqueUnico.style.display      = recurrente ? 'none' : '';
    bloqueRecurrente.style.display = recurrente ? '' : 'none';

    if (campoInicioUnico) campoInicioUnico.required = !recurrente;
    camposRecurrente.forEach(function(campo){ campo.required = recurrente; });
  }

  radios.forEach(function(r){ r.addEventListener('change', sync); });
  sync();
})();

/* Presencial / en línea / híbrida deciden si hace falta un lugar físico.
   "En línea" oculta lugar, ciudad, estado y el mapa; los otros dos modos los
   piden igual que siempre. El enlace de acceso no depende de esto: sirve
   también para una actividad presencial que además transmite o manda un
   grupo de WhatsApp. */
(function(){
  var radios = document.querySelectorAll('input[name="modalidad"]');
  var bloqueFisico = document.getElementById('bloqueUbicacionFisica');
  var campoLugar   = document.getElementById('campoLugar');
  var lugar        = document.getElementById('lugar');
  var ciudad       = document.getElementById('ciudad');
  var entidad      = document.getElementById('entidad');
  if (!radios.length || !bloqueFisico) return;

  function sync(){
    var esFisico = document.querySelector('input[name="modalidad"]:checked').value !== 'en_linea';

    bloqueFisico.style.display = esFisico ? '' : 'none';
    if (campoLugar) campoLugar.style.display = esFisico ? '' : 'none';

    if (lugar)   lugar.required   = esFisico;
    if (ciudad)  ciudad.required  = esFisico;
    if (entidad) entidad.required = esFisico;
  }

  radios.forEach(function(r){ r.addEventListener('change', sync); });
  sync();
})();

/* Sugerencias de ciudad según el estado elegido. Es un <datalist>, no un
   select: sigue aceptando cualquier texto, así que un pueblo que no está en
   la lista —la lista no pretende ser exhaustiva— se puede escribir igual. */
(function(){
  var entidad   = document.getElementById('entidad');
  var ciudad    = document.getElementById('ciudad');
  var datalist  = document.getElementById('listaCiudades');
  if (!entidad || !ciudad || !datalist || typeof CIUDADES_POR_ESTADO === 'undefined') return;

  function sync(){
    var lista = CIUDADES_POR_ESTADO[entidad.value] || [];
    datalist.innerHTML = lista.map(function(c){
      return '<option value="' + c.replace(/"/g, '&quot;') + '">';
    }).join('');
  }
  entidad.addEventListener('change', sync);
  sync();
})();
</script>

<script>
var CIUDADES_POR_ESTADO = <?= json_encode(ciudadesSugeridasPorEstado(), JSON_UNESCAPED_UNICODE) ?>;
</script>
