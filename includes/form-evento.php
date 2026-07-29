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

/** Formato que exige type="date": 2026-08-16. */
$fechaSoloInput = function (string $campo) use ($e) {
    if (empty($e[$campo])) return '';
    $ts = strtotime((string) $e[$campo]);
    return $ts === false ? '' : date('Y-m-d', $ts);
};

/** La hora (H:i) dentro de un DATETIME guardado, para "Actividad de un día". */
$horaDeFecha = function (string $campo) use ($e) {
    if (empty($e[$campo])) return '';
    $ts = strtotime((string) $e[$campo]);
    return $ts === false ? '' : date('H:i', $ts);
};

/** Formato que exige type="time": 19:30. Hora de inicio de una recurrente. */
$horaInput = function () use ($e) {
    if (empty($e['hora_recurrente'])) return '';
    $ts = strtotime((string) $e['hora_recurrente']);
    return $ts === false ? '' : date('H:i', $ts);
};

/** Hora de fin de una recurrente. */
$horaFinRecurrenteInput = function () use ($e) {
    if (empty($e['hora_fin_recurrente'])) return '';
    $ts = strtotime((string) $e['hora_fin_recurrente']);
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

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <rect x="3.5" y="5" width="17" height="15" rx="2"/>
    <path d="M3.5 9.5h17"/>
    <path d="M8 3v4M16 3v4"/>
  </svg>
  <h2>3. Fecha y horario</h2>
</div>

<div class="tipo-fecha-grupo">
  <div class="tipo-fecha-tarjeta">
    <label class="tipo-fecha-header">
      <input type="radio" name="tipo_actividad" value="unico" id="tipoUnico" <?= $esRecurrente ? '' : 'checked' ?>>
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"
           stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3.5" y="5" width="17" height="15" rx="2"/>
        <path d="M3.5 9.5h17"/>
        <path d="M8 3v4M16 3v4"/>
      </svg>
      <span>Actividad de un día</span>
    </label>
    <div class="tipo-fecha-campos">
      <div class="campo<?= $mal('fecha_unica') ?>">
        <label for="fecha_unica">Fecha</label>
        <input id="fecha_unica" name="fecha_unica" type="date" <?= $esRecurrente ? '' : 'required' ?>
               value="<?= e($esRecurrente ? '' : $fechaSoloInput('fecha_inicio')) ?>">
        <?= $err('fecha_unica') ?>
      </div>
      <div class="campo-fila">
        <div class="campo<?= $mal('hora_inicio_unica') ?>">
          <label for="hora_inicio_unica">Hora de inicio</label>
          <input id="hora_inicio_unica" name="hora_inicio_unica" type="time" <?= $esRecurrente ? '' : 'required' ?>
                 value="<?= e($esRecurrente ? '' : $horaDeFecha('fecha_inicio')) ?>">
          <?= $err('hora_inicio_unica') ?>
        </div>
        <div class="campo<?= $mal('hora_fin_unica') ?>">
          <label for="hora_fin_unica">Hora de fin</label>
          <input id="hora_fin_unica" name="hora_fin_unica" type="time" <?= $esRecurrente ? '' : 'required' ?>
                 value="<?= e($esRecurrente ? '' : $horaDeFecha('fecha_fin')) ?>">
          <?= $err('hora_fin_unica') ?>
        </div>
      </div>
      <div class="campo">
        <label for="fecha_fin_unica">Termina otro día <span class="opcional">opcional</span></label>
        <input id="fecha_fin_unica" name="fecha_fin_unica" type="date"
               value="<?= e((!$esRecurrente && $fechaSoloInput('fecha_fin') !== '' && $fechaSoloInput('fecha_fin') !== $fechaSoloInput('fecha_inicio')) ? $fechaSoloInput('fecha_fin') : '') ?>">
        <div class="pista">Para retiros de varios días. Si no se pone, se asume el mismo día.</div>
      </div>
    </div>
  </div>

  <div class="tipo-fecha-tarjeta">
    <label class="tipo-fecha-header">
      <input type="radio" name="tipo_actividad" value="recurrente" id="tipoRecurrente" <?= $esRecurrente ? 'checked' : '' ?>>
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"
           stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 7h13a3 3 0 0 1 3 3v1"/>
        <path d="M17 4l3 3-3 3"/>
        <path d="M20 17H7a3 3 0 0 1-3-3v-1"/>
        <path d="M7 20l-3-3 3-3"/>
      </svg>
      <span>Actividad recurrente</span>
    </label>
    <div class="tipo-fecha-campos">
      <div class="campo-fila">
        <div class="campo<?= $mal('fecha_inicio_rec') ?>">
          <label for="fecha_inicio_rec">Fecha de inicio</label>
          <input id="fecha_inicio_rec" name="fecha_inicio_rec" type="date" <?= $esRecurrente ? 'required' : '' ?>
                 value="<?= e($esRecurrente ? $fechaSoloInput('fecha_inicio') : '') ?>">
          <?= $err('fecha_inicio_rec') ?>
        </div>
        <div class="campo<?= $mal('fecha_fin_rec') ?>">
          <label for="fecha_fin_rec">Fecha de fin</label>
          <input id="fecha_fin_rec" name="fecha_fin_rec" type="date" <?= $esRecurrente ? 'required' : '' ?>
                 value="<?= e($esRecurrente ? $fechaSoloInput('fecha_fin') : '') ?>">
          <?= $err('fecha_fin_rec') ?>
        </div>
      </div>
      <div class="campo<?= $mal('frecuencia') ?>">
        <label for="frecuencia">Frecuencia</label>
        <select id="frecuencia" name="frecuencia" <?= $esRecurrente ? 'required' : '' ?>>
          <option value="">Selecciona una frecuencia</option>
          <?php foreach (frecuenciasRecurrencia() as $clave => $etiqueta): ?>
            <option value="<?= e($clave) ?>" <?= $v('frecuencia') === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $err('frecuencia') ?>
      </div>
      <div class="campo-fila">
        <div class="campo<?= $mal('hora_recurrente') ?>">
          <label for="hora_recurrente">Hora de inicio</label>
          <input id="hora_recurrente" name="hora_recurrente" type="time" <?= $esRecurrente ? 'required' : '' ?>
                 value="<?= e($horaInput()) ?>">
          <?= $err('hora_recurrente') ?>
        </div>
        <div class="campo<?= $mal('hora_fin_recurrente') ?>">
          <label for="hora_fin_recurrente">Hora de fin</label>
          <input id="hora_fin_recurrente" name="hora_fin_recurrente" type="time" <?= $esRecurrente ? 'required' : '' ?>
                 value="<?= e($horaFinRecurrenteInput()) ?>">
          <?= $err('hora_fin_recurrente') ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z"/>
    <circle cx="12" cy="9" r="2.5"/>
  </svg>
  <h2>4. Ubicación</h2>
</div>

<div class="campo<?= $mal('enlace_acceso') ?>">
  <label for="enlace_acceso">Enlace de acceso <span class="opcional">opcional</span></label>
  <input id="enlace_acceso" name="enlace_acceso" type="url" maxlength="500"
         value="<?= e($v('enlace_acceso')) ?>" placeholder="https://">
  <div class="pista">Enlace privado que solo será visible para las personas registradas o confirmadas.</div>
  <?= $err('enlace_acceso') ?>
</div>

<div id="bloqueUbicacionFisica">
  <div class="campo-fila campo-fila-3">
    <div class="campo<?= $mal('lugar') ?>" id="campoLugar">
      <label for="lugar">Lugar</label>
      <input id="lugar" name="lugar" type="text" maxlength="160"
             value="<?= e($v('lugar')) ?>" placeholder="Ej. Centro Holístico Luz">
      <?= $err('lugar') ?>
    </div>
    <div class="campo<?= $mal('ciudad') ?>" id="campoCiudad">
      <label for="ciudad">Ciudad</label>
      <select id="ciudad" name="ciudad">
        <option value="">Selecciona una ciudad</option>
        <?php foreach (municipiosPorEstado()[$v('entidad')] ?? [] as $municipio): ?>
          <option value="<?= e($municipio) ?>" <?= $v('ciudad') === $municipio ? 'selected' : '' ?>><?= e($municipio) ?></option>
        <?php endforeach; ?>
      </select>
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

  <div class="campo">
    <div id="mapaInteractivo" class="mapa-interactivo" data-lat="<?= e($v('latitud')) ?>" data-lng="<?= e($v('longitud')) ?>"></div>
    <div class="aviso aviso-info" style="margin:10px 0 0;">Arrastra el pin para ajustar la ubicación exacta del lugar.</div>
  </div>

  <div class="campo">
    <label>Coordenadas <span class="opcional">se obtienen automáticamente al mover el pin</span></label>
  </div>
  <div class="campo-fila">
    <div class="campo">
      <label for="latitud">Latitud</label>
      <input id="latitud" name="latitud" type="text" readonly value="<?= e($v('latitud')) ?>">
    </div>
    <div class="campo">
      <label for="longitud">Longitud</label>
      <input id="longitud" name="longitud" type="text" readonly value="<?= e($v('longitud')) ?>">
    </div>
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

<?php /* Va antes del script grande y no después: ese script comprueba
         "typeof MUNICIPIOS_POR_ESTADO" al cargar, y si esta variable llegara
         más tarde la comprobación siempre daría "undefined" y el selector de
         ciudad se quedaría sin repoblarse al cambiar de estado. */ ?>
<script>
var MUNICIPIOS_POR_ESTADO = <?= json_encode(municipiosPorEstado(), JSON_UNESCAPED_UNICODE) ?>;
</script>

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

/* De un día / recurrente son dos tarjetas que se ven las dos completas a la
   vez —así lo pide el diseño—, así que aquí no hay nada que mostrar u
   ocultar. Lo único que cambia es cuál de los dos juegos de campos es
   obligatorio: los de la tarjeta que no se eligió se marcan no-requeridos
   para que no bloqueen el envío con un campo que la persona decidió no usar. */
(function(){
  var radios = document.querySelectorAll('input[name="tipo_actividad"]');
  if (!radios.length) return;

  var camposUnico = document.querySelectorAll(
    '[name="fecha_unica"], [name="hora_inicio_unica"], [name="hora_fin_unica"]'
  );
  var camposRecurrente = document.querySelectorAll(
    '[name="fecha_inicio_rec"], [name="fecha_fin_rec"], [name="frecuencia"], [name="hora_recurrente"], [name="hora_fin_recurrente"]'
  );

  function sync(){
    var recurrente = document.querySelector('input[name="tipo_actividad"]:checked').value === 'recurrente';
    camposUnico.forEach(function(campo){ campo.required = !recurrente; });
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
  var lugar        = document.getElementById('lugar');
  var ciudad       = document.getElementById('ciudad');
  var entidad      = document.getElementById('entidad');
  if (!radios.length || !bloqueFisico) return;

  function sync(){
    var esFisico = document.querySelector('input[name="modalidad"]:checked').value !== 'en_linea';

    bloqueFisico.style.display = esFisico ? '' : 'none';

    if (lugar)   lugar.required   = esFisico;
    if (ciudad)  ciudad.required  = esFisico;
    if (entidad) entidad.required = esFisico;
  }

  radios.forEach(function(r){ r.addEventListener('change', sync); });
  sync();
})();

/* Ciudad depende del Estado elegido: es un catálogo cerrado de municipios
   —a diferencia de antes, ya no acepta cualquier texto—, así que sus
   opciones se arman de nuevo cada vez que cambia el estado. El PHP ya deja
   las correctas puestas si se llega aquí con un estado guardado (al editar,
   o después de un error de validación); este script solo entra en acción
   cuando la persona cambia el estado a mano. */
(function(){
  var entidad = document.getElementById('entidad');
  var ciudad  = document.getElementById('ciudad');
  if (!entidad || !ciudad || typeof MUNICIPIOS_POR_ESTADO === 'undefined') return;

  entidad.addEventListener('change', function(){
    var lista = MUNICIPIOS_POR_ESTADO[entidad.value] || [];
    ciudad.innerHTML = '<option value="">Selecciona una ciudad</option>' + lista.map(function(m){
      return '<option value="' + m.replace(/"/g, '&quot;') + '">' + m + '</option>';
    }).join('');
  });
})();

/* El mapa interactivo: un pin que se arrastra o se coloca con un clic, y las
   coordenadas se escriben solas en los campos de abajo. Leaflet + OpenStreet­Map
   y no la API de Google: no pide clave ni tarjeta, que es el mismo criterio
   que ya usa el mapa de la ficha (includes/mapa.php). */
(function(){
  var contenedor = document.getElementById('mapaInteractivo');
  if (!contenedor || typeof L === 'undefined') return;

  var latInput = document.getElementById('latitud');
  var lngInput = document.getElementById('longitud');
  var ciudadSel = document.getElementById('ciudad');
  var entidadSel = document.getElementById('entidad');

  var CENTRO_MEXICO = [23.6345, -102.5528];
  var lat0 = parseFloat(contenedor.dataset.lat);
  var lng0 = parseFloat(contenedor.dataset.lng);
  var hayPunto = !isNaN(lat0) && !isNaN(lng0);

  var mapa = L.map(contenedor).setView(hayPunto ? [lat0, lng0] : CENTRO_MEXICO, hayPunto ? 15 : 5);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
    maxZoom: 19
  }).addTo(mapa);

  var pin = L.marker(hayPunto ? [lat0, lng0] : CENTRO_MEXICO, {draggable: true}).addTo(mapa);

  function fijarPunto(latlng){
    latInput.value = latlng.lat.toFixed(6);
    lngInput.value = latlng.lng.toFixed(6);
  }

  pin.on('dragend', function(){ fijarPunto(pin.getLatLng()); });
  mapa.on('click', function(ev){ pin.setLatLng(ev.latlng); fijarPunto(ev.latlng); });

  /*
   * Al elegir ciudad se centra el mapa ahí —Nominatim, el buscador gratuito
   * de OpenStreetMap—, para no obligar a nadie a encontrar su colonia a ojo
   * en un mapa de todo México. Si ya había un pin puesto a mano, cambiar de
   * ciudad no lo mueve: no tiene sentido borrar un ajuste fino que la
   * persona ya hizo.
   */
  function centrarEnCiudad(){
    if (!ciudadSel.value || !entidadSel.value) return;
    var consulta = encodeURIComponent(ciudadSel.value + ', ' + entidadSel.value + ', México');

    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + consulta)
      .then(function(resp){ return resp.json(); })
      .then(function(resultados){
        if (!resultados.length) return;
        var punto = {lat: parseFloat(resultados[0].lat), lng: parseFloat(resultados[0].lon)};
        mapa.setView(punto, 13);

        // setLatLng no dispara "dragend" —solo lo hace un arrastre de verdad—,
        // así que sin fijarPunto() aquí el pin se vería en su sitio pero los
        // campos de latitud/longitud se quedarían vacíos si nadie lo mueve.
        if (!latInput.value) {
          pin.setLatLng(punto);
          fijarPunto(punto);
        }
      })
      .catch(function(){});
  }
  ciudadSel.addEventListener('change', centrarEnCiudad);
})();
</script>
