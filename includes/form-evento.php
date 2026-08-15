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

/*
 * Información de contacto del organizador (REQ-00012).
 *
 * NO son campos de la actividad: viven en su cuenta y se reutilizan en todas
 * las que publique. Por eso no pasan por $v() —que lee del evento— y llevan el
 * prefijo «org_», para que quede claro de un vistazo qué se guarda dónde.
 */
$orgFicha  = fichaDeUsuario((int) usuarioActual()['id']);
$orgCampos = camposContactoDisponibles();

/** El valor de un campo de contacto: lo recién escrito si falló, o lo guardado. */
$ov = function (string $columna) use ($orgFicha) {
    if (isset($_POST['org_' . $columna])) return (string) $_POST['org_' . $columna];

    return (string) ($orgFicha[$columna] ?? '');
};

$orgNombre = isset($_POST['org_nombre'])
    ? (string) $_POST['org_nombre']
    : (string) ($orgFicha['nombre'] ?? '');

/* ¿Hay algo guardado que ofrecer? El nombre no cuenta: lo tiene todo el mundo
   desde el alta —se deduce del correo— y ofrecer «usar lo guardado» para eso
   solo sería enseñar un resumen de un dato que nadie escribió. */
$orgHayGuardado = false;
foreach (array_keys($orgCampos) as $columna) {
    if (trim((string) ($orgFicha[$columna] ?? '')) !== '') $orgHayGuardado = true;
}

/* Tras un envío fallido la sección se abre: si alguien acaba de escribir aquí y
   el formulario falló por otro campo, cerrarla esconde lo que escribió. */
$orgAbierta = $_SERVER['REQUEST_METHOD'] === 'POST';

/* Y si lo que mandó no es lo que tenía guardado, es que lo estaba editando: el
   resumen no se enseña, porque enseñarlo taparía sus cambios detrás de un
   botón «Editar» y parecería que se perdieron. */
$orgEditado = false;
if ($orgAbierta) {
    if (isset($_POST['org_nombre']) && trim((string) $_POST['org_nombre']) !== trim((string) ($orgFicha['nombre'] ?? ''))) {
        $orgEditado = true;
    }
    foreach (array_keys($orgCampos) as $columna) {
        if (isset($_POST['org_' . $columna])
            && trim((string) $_POST['org_' . $columna]) !== trim((string) ($orgFicha[$columna] ?? ''))) {
            $orgEditado = true;
        }
    }
}
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
    <rect x="3.5" y="5" width="17" height="15" rx="2"/>
    <path d="M3.5 9.5h17"/>
    <path d="M8 3v4M16 3v4"/>
  </svg>
  <h2>2. Fecha y horario</h2>
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
  <h2>3. Ubicación</h2>
</div>

<div class="campo-fila campo-fila-3">
  <div class="campo<?= $mal('entidad') ?>">
    <label for="entidad">Estado</label>
    <select id="entidad" name="entidad" required>
      <option value="">Selecciona un estado</option>
      <?php foreach (estadosMexico() as $estado): ?>
        <option value="<?= e($estado) ?>" <?= $v('entidad') === $estado ? 'selected' : '' ?>><?= e($estado) ?></option>
      <?php endforeach; ?>
    </select>
    <?= $err('entidad') ?>
  </div>
  <div class="campo<?= $mal('ciudad') ?>" id="campoCiudad">
    <label for="ciudad">Ciudad</label>
    <input id="ciudad" name="ciudad" type="text" required autocomplete="off"
           list="listaCiudades" placeholder="Escribe para buscar…"
           value="<?= e($v('ciudad')) ?>">
    <datalist id="listaCiudades">
      <?php foreach (municipiosPorEstado()[$v('entidad')] ?? [] as $municipio): ?>
        <option value="<?= e($municipio) ?>"></option>
      <?php endforeach; ?>
    </datalist>
    <?= $err('ciudad') ?>
  </div>
  <div class="campo<?= $mal('lugar') ?>" id="campoLugar">
    <label for="lugar">Nombre del lugar</label>
    <input id="lugar" name="lugar" type="text" required maxlength="160"
           value="<?= e($v('lugar')) ?>" placeholder="Ej. Centro Holístico Luz">
    <?= $err('lugar') ?>
  </div>
</div>

<div class="campo<?= $mal('direccion') ?>">
  <label for="direccion">Dirección <span class="opcional">opcional</span></label>
  <input id="direccion" name="direccion" type="text" maxlength="255"
         value="<?= e($v('direccion')) ?>" placeholder="Calle, número, colonia">
  <div class="pista">Se completa sola al mover el pin; corrígela si hace falta.</div>
  <?= $err('direccion') ?>
</div>

<div class="campo">
  <div id="mapaInteractivo" class="mapa-interactivo" data-lat="<?= e($v('latitud')) ?>" data-lng="<?= e($v('longitud')) ?>"></div>
  <div class="aviso aviso-info" style="margin:10px 0 0;">Arrastra el pin para ajustar la ubicación exacta del lugar. Ciudad, estado y dirección se completan solos.</div>

  <div class="enlace-maps-grupo">
    <label for="enlaceMapsPegado">¿Tienes el enlace de Google Maps del lugar? Pégalo y movemos el pin por ti</label>
    <div class="enlace-maps-fila">
      <input type="text" id="enlaceMapsPegado" placeholder="https://maps.app.goo.gl/…">
      <button type="button" id="enlaceMapsBoton">Usar enlace</button>
    </div>
    <div id="enlaceMapsMensaje" class="aviso" hidden></div>
  </div>

  <div id="geocodingMensaje" class="aviso aviso-info" style="margin:10px 0 0;" hidden></div>
</div>

<?php
/*
 * Las coordenadas ya no se enseñan: salen solas al mover el pin y no hay nada
 * que hacer con ellas a mano —eran de solo lectura—, así que enseñar dos
 * campos con quince decimales solo daba de qué preocuparse.
 *
 * Pero los campos siguen aquí, ocultos, y no se pueden borrar: son estos —no
 * el mapa— los que viajan en el POST. El JavaScript de abajo les escribe la
 * latitud y la longitud cada vez que el pin se mueve, y validarEvento() lee
 * exactamente estos dos nombres. Sin ellos el pin se podría arrastrar, se
 * vería moverse, y al guardar no quedaría ninguna coordenada: la ficha se
 * publicaría sin mapa y sin que nada avisara de por qué.
 */
?>
<input id="latitud"  name="latitud"  type="hidden" value="<?= e($v('latitud')) ?>">
<input id="longitud" name="longitud" type="hidden" value="<?= e($v('longitud')) ?>">

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M12.5 3H20a1 1 0 0 1 1 1v7.5a1 1 0 0 1-.3.7l-9 9a1 1 0 0 1-1.4 0l-7.5-7.5a1 1 0 0 1 0-1.4l9-9a1 1 0 0 1 .7-.3z"/>
    <circle cx="16.5" cy="7.5" r="1.3" fill="currentColor" stroke="none"/>
  </svg>
  <h2>4. Precio</h2>
</div>

<?php $esDePago = isset($e['gratuito']) ? empty($e['gratuito']) : false; ?>

<div class="campo">
  <div class="precio-grupo">
    <label class="precio-op">
      <input type="radio" name="precio_modo" value="sin_costo" id="precioSinCosto" <?= $esDePago ? '' : 'checked' ?>>
      <svg class="precio-icono" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
           stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9"/>
        <path d="M7 7l10 10"/>
      </svg>
      <span class="precio-titulo">Sin costo</span>
    </label>
    <label class="precio-op">
      <input type="radio" name="precio_modo" value="de_pago" id="precioDePago" <?= $esDePago ? 'checked' : '' ?>>
      <svg class="precio-icono" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
           stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12.5 3H20a1 1 0 0 1 1 1v7.5a1 1 0 0 1-.3.7l-9 9a1 1 0 0 1-1.4 0l-7.5-7.5a1 1 0 0 1 0-1.4l9-9a1 1 0 0 1 .7-.3z"/>
        <circle cx="16.5" cy="7.5" r="1.3" fill="currentColor" stroke="none"/>
      </svg>
      <span class="precio-titulo">De pago</span>
    </label>
  </div>
</div>

<div id="bloquePago">
  <div class="campo<?= $mal('forma_pago') ?>">
    <label for="forma_pago">Forma de pago</label>
    <select id="forma_pago" name="forma_pago" <?= $esDePago ? 'required' : '' ?>>
      <option value="">Selecciona una opción</option>
      <option value="completa" <?= $v('forma_pago') === 'completa' ? 'selected' : '' ?>>Actividad completa</option>
      <option value="sesion" <?= $v('forma_pago') === 'sesion' ? 'selected' : '' ?>>Por sesión</option>
    </select>
    <?= $err('forma_pago') ?>
  </div>

  <div class="campo<?= $mal('precio') ?>" id="campoPrecio">
    <label for="precio">Precio por persona (MXN)</label>
    <input id="precio" name="precio" type="text" inputmode="decimal"
           value="<?= e($v('precio')) ?>" placeholder="2450">
    <?= $err('precio') ?>
  </div>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <circle cx="9" cy="8" r="3.2"/>
    <path d="M2.8 20c.6-3.6 3.3-5.7 6.2-5.7s5.6 2.1 6.2 5.7"/>
    <circle cx="17" cy="9" r="2.6"/>
    <path d="M15.2 14.6c2.2.4 3.9 2.2 4.3 5.4"/>
  </svg>
  <h2>5. Cupo máximo <span class="opcional">opcional</span></h2>
</div>

<div class="campo<?= $mal('cupo_maximo') ?>">
  <label for="cupo_maximo">Número máximo de participantes</label>
  <input id="cupo_maximo" name="cupo_maximo" type="text" inputmode="numeric" maxlength="6"
         value="<?= e($v('cupo_maximo')) ?>" placeholder="Ej. 20">
  <?= $err('cupo_maximo') ?>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <rect x="3" y="4" width="18" height="16" rx="2"/>
    <circle cx="8.5" cy="9.5" r="1.6"/>
    <path d="M21 16l-5.5-5.5a1.5 1.5 0 0 0-2.1 0L4 19"/>
  </svg>
  <h2>6. Imagen de la actividad <span class="opcional">opcional</span></h2>
</div>

<div class="campo<?= $mal('imagen') ?>">
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

  <label class="imagen-dropzone" id="imagenDropzone" for="imagen">
    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.6"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M7 18a4.5 4.5 0 0 1-.5-8.97A5.5 5.5 0 0 1 17.2 8.05 4 4 0 0 1 17 16"/>
      <path d="M12 12v7M9 15l3-3 3 3"/>
    </svg>
    <span class="imagen-dropzone-texto">Subir imagen</span>
    <span class="imagen-dropzone-nombre" id="imagenNombre"></span>
  </label>
  <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="imagen-input-oculto">

  <div class="pista">
    JPG, PNG o WebP. Máx. <?= round(IMAGEN_MAX_BYTES / 1048576) ?> MB. Recomendado: 1200 × 800 px.
    <?= $imagenActual !== null ? 'Elige otra solo si quieres cambiarla.' : 'Si no agregas imagen, se muestra un color de fondo predeterminado en la tarjeta pública.' ?>
  </div>
  <?= $err('imagen') ?>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <circle cx="12" cy="8.2" r="3.8"/>
    <path d="M4.8 20c.6-3.7 3.6-5.9 7.2-5.9s6.6 2.2 7.2 5.9"/>
  </svg>
  <h2>7. Información de contacto <span class="opcional">opcional</span></h2>
</div>

<?php
/*
 * Va antes de «Información adicional» —donde lo pide REQ-00012— y por eso los
 * dos apartados de abajo corren un número.
 *
 * COLAPSABLE CON <details>, no con JavaScript: abre y cierra solo, el teclado
 * ya sabe manejarlo y el buscador del navegador (Ctrl+F) encuentra lo de
 * dentro aunque esté cerrado.
 */
?>
<details class="contacto-org"<?= $orgAbierta ? ' open' : '' ?>>
  <summary>Información de contacto <span class="opcional">opcional</span></summary>

  <div class="contacto-org-cuerpo">
    <?php if ($orgHayGuardado && !$orgEditado): ?>
      <?php /* Lo guardado ya viene cargado en los campos de abajo: esto es su
               resumen, para no tener que leer cuatro cajas de formulario para
               comprobar que sigue siendo lo mismo de siempre. «Editar» destapa
               los campos. Sin JavaScript se ven las dos cosas, que es feo pero
               funciona; con JavaScript, solo el resumen hasta que se pide
               editar. */ ?>
      <div class="contacto-org-guardado" id="orgGuardado">
        <div class="contacto-org-tit">Usar la información guardada</div>
        <div class="contacto-org-lista">
          <div><span>Nombre</span><strong><?= e($orgNombre) ?></strong></div>
          <?php foreach ($orgCampos as $columna => $etiqueta): ?>
            <?php $valorOrg = trim((string) ($orgFicha[$columna] ?? '')); ?>
            <?php if ($valorOrg !== ''): ?>
              <div><span><?= e($etiqueta) ?></span><strong><?= e($valorOrg) ?></strong></div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <button type="button" class="actionbtn" id="orgEditar">Editar</button>
      </div>
    <?php endif; ?>

    <div class="contacto-org-campos" id="orgCampos">
      <div class="campo">
        <label for="org_nombre">Nombre del organizador</label>
        <input id="org_nombre" name="org_nombre" type="text" maxlength="120"
               value="<?= e($orgNombre) ?>" placeholder="Yoga Baja">
        <div class="pista">Es el nombre que aparece como organizador en tus actividades.</div>
      </div>

      <?php foreach ($orgCampos as $columna => $etiqueta): ?>
        <?php
        $marcador = [
            'telefono'  => '+52 612 123 4567',
            'instagram' => '@tucuenta',
            'sitio_web' => 'https://tusitio.com',
        ][$columna] ?? '';
        ?>
        <div class="campo">
          <label for="org_<?= e($columna) ?>"><?= e($etiqueta) ?></label>
          <input id="org_<?= e($columna) ?>" name="org_<?= e($columna) ?>"
                 type="<?= $columna === 'telefono' ? 'tel' : 'text' ?>"
                 maxlength="<?= $columna === 'sitio_web' ? 500 : 120 ?>"
                 value="<?= e($ov($columna)) ?>" placeholder="<?= e($marcador) ?>">
          <?php if ($columna === 'sitio_web'): ?>
            <div class="pista">El tuyo, no el de esta actividad — ese va en el apartado siguiente.</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="pista contacto-org-nota">
      Esta información se guardará para facilitar tus próximas publicaciones.
      Puedes cambiarla cuando quieras desde <strong>Mi cuenta</strong>.
    </div>
  </div>
</details>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M9.5 14.5l5-5"/>
    <path d="M11 7.5l1-1a3.5 3.5 0 0 1 5 5l-1 1"/>
    <path d="M13 16.5l-1 1a3.5 3.5 0 0 1-5-5l1-1"/>
  </svg>
  <h2>8. Información adicional <span class="opcional">opcional</span></h2>
</div>

<div class="campo<?= $mal('sitio_web') ?>">
  <label for="sitio_web">Sitio web o enlace</label>
  <input id="sitio_web" name="sitio_web" type="url" maxlength="500"
         value="<?= e($v('sitio_web')) ?>" placeholder="https://tusitio.com">
  <div class="pista">Comparte un sitio web o perfil de redes sociales para que los interesados conozcan más sobre tu actividad.</div>
  <?= $err('sitio_web') ?>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6l-5 4H4a1 1 0 0 0-1 1z"/>
    <path d="M16 9a3.5 3.5 0 0 1 0 6"/>
    <path d="M18.5 6.5a7 7 0 0 1 0 11"/>
  </svg>
  <h2>9. Acción principal *</h2>
</div>

<div class="campo<?= $mal('accion_principal') ?>">
  <div class="pista" style="margin:0 0 10px;">Elige la acción principal que verán las personas en tu actividad.</div>

  <div class="accion-grupo">
    <div class="accion-tarjeta">
      <label class="accion-header">
        <input type="radio" name="accion_principal" value="informacion" id="accion_informacion"
               <?= $v('accion_principal', 'informacion') === 'informacion' ? 'checked' : '' ?>>
        <svg class="accion-icono" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M3 6.5A1.5 1.5 0 0 1 4.5 5h15A1.5 1.5 0 0 1 21 6.5v11A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5z"/>
          <path d="M3.5 6.2l8.5 6.8 8.5-6.8"/>
        </svg>
        <?php /* Sin descripción bajo el título (REQ-00011). Las tres decían, en
                 corto, lo mismo que la guía de la derecha explica entero: leer
                 dos veces lo mismo con distintas palabras hace dudar de si son
                 dos cosas distintas. La guía se queda; esto se va. */ ?>
        <span class="accion-titulo">Contactar al organizador</span>
      </label>
    </div>

    <div class="accion-tarjeta">
      <label class="accion-header">
        <input type="radio" name="accion_principal" value="boletos" id="accion_boletos"
               <?= $v('accion_principal', 'informacion') === 'boletos' ? 'checked' : '' ?>>
        <svg class="accion-icono" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M20.5 9.5a2 2 0 0 1 0-3.9V4.5a1 1 0 0 0-1-1h-15a1 1 0 0 0-1 1v1.1a2 2 0 0 1 0 3.9v1a2 2 0 0 1 0 3.9v1.1a1 1 0 0 0 1 1h15a1 1 0 0 0 1-1v-1.1a2 2 0 0 1 0-3.9z"/>
          <path d="M9.5 4v16" stroke-dasharray="2 2"/>
        </svg>
        <span class="accion-titulo">Comprar boletos</span>
      </label>
      <div class="accion-campos">
        <div class="campo<?= $mal('url_boletos') ?>">
          <label for="url_boletos">URL de compra <span class="obligatorio-si">*</span></label>
          <input id="url_boletos" name="url_boletos" type="url" maxlength="500"
                 value="<?= e($v('url_boletos')) ?>" placeholder="https://…">
          <?= $err('url_boletos') ?>
        </div>
      </div>
    </div>

    <div class="accion-tarjeta">
      <label class="accion-header">
        <input type="radio" name="accion_principal" value="reservar" id="accion_reservar"
               <?= $v('accion_principal', 'informacion') === 'reservar' ? 'checked' : '' ?>>
        <svg class="accion-icono" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3.5" y="5" width="17" height="15" rx="2"/>
          <path d="M3.5 9.5h17"/>
          <path d="M8 3v4M16 3v4"/>
        </svg>
        <span class="accion-titulo">Reservar lugar</span>
      </label>
      <div class="accion-campos">
        <div class="campo<?= $mal('url_reserva') ?>">
          <label for="url_reserva">URL de reserva <span class="obligatorio-si">*</span></label>
          <input id="url_reserva" name="url_reserva" type="url" maxlength="500"
                 value="<?= e($v('url_reserva')) ?>" placeholder="https://…">
          <?= $err('url_reserva') ?>
        </div>
      </div>
    </div>
  </div>

  <?= $err('accion_principal') ?>
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
/* "Forma de pago" y "Precio" no pintan nada si la actividad es sin costo.
   Ocultarlos evita la duda de si hay que poner 0 o dejarlos vacíos. */
(function(){
  var radios      = document.querySelectorAll('input[name="precio_modo"]');
  var bloquePago  = document.getElementById('bloquePago');
  var formaPago   = document.getElementById('forma_pago');
  var precio      = document.getElementById('precio');
  if (!radios.length || !bloquePago) return;

  function sync(){
    var dePago = document.querySelector('input[name="precio_modo"]:checked').value === 'de_pago';
    bloquePago.style.display = dePago ? '' : 'none';
    if (formaPago) formaPago.required = dePago;
  }

  radios.forEach(function(r){ r.addEventListener('change', sync); });
  sync();
})();

/* Las tres tarjetas de "Acción principal" se ven completas a la vez, así que
   aquí no hay nada que mostrar u ocultar. Lo único que cambia es cuál de los
   dos enlaces es obligatorio: el de la tarjeta que no se eligió se marca
   no-requerido para que no bloquee el envío con un campo que la persona
   decidió no usar. "Contactar al organizador" no pide nada: el formulario de
   contacto ya vive en contactar.php. */
(function(){
  var radios = document.querySelectorAll('input[name="accion_principal"]');
  var urlBoletos = document.getElementById('url_boletos');
  var urlReserva = document.getElementById('url_reserva');
  if (!radios.length) return;

  function sync(){
    var elegida = document.querySelector('input[name="accion_principal"]:checked').value;
    if (urlBoletos) urlBoletos.required = elegida === 'boletos';
    if (urlReserva) urlReserva.required = elegida === 'reservar';
  }

  radios.forEach(function(r){ r.addEventListener('change', sync); });
  sync();
})();

/* La zona de "Subir imagen" es un <label> disfrazado: el <input type="file">
   real sigue ahí pero invisible, así que todo lo que ya sabe hacer un input
   de archivo —arrastrar y soltar incluido, es soporte nativo del navegador—
   sigue funcionando igual. Esto solo enseña el nombre del archivo elegido. */
(function(){
  var campo  = document.getElementById('imagen');
  var nombre = document.getElementById('imagenNombre');
  if (!campo || !nombre) return;

  campo.addEventListener('change', function(){
    nombre.textContent = campo.files.length ? campo.files[0].name : '';
  });
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

/* Ciudad depende del Estado elegido: sigue siendo un catálogo cerrado de
   municipios —el servidor solo acepta uno de la lista—, pero ahora se
   escribe en vez de elegirse de un desplegable largo, con las sugerencias
   de ese catálogo como datalist. Sus opciones se arman de nuevo cada vez
   que cambia el estado. El PHP ya deja las correctas puestas si se llega
   aquí con un estado guardado (al editar, o después de un error de
   validación); este script solo entra en acción cuando la persona cambia
   el estado a mano —y entonces sí vacía la ciudad, porque una de otro
   estado ya no es válida—. */
(function(){
  var entidad  = document.getElementById('entidad');
  var ciudad   = document.getElementById('ciudad');
  var datalist = document.getElementById('listaCiudades');
  if (!entidad || !ciudad || !datalist || typeof MUNICIPIOS_POR_ESTADO === 'undefined') return;

  entidad.addEventListener('change', function(){
    var lista = MUNICIPIOS_POR_ESTADO[entidad.value] || [];
    datalist.innerHTML = lista.map(function(m){
      return '<option value="' + m.replace(/"/g, '&quot;') + '"></option>';
    }).join('');
    ciudad.value = '';
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
  var direccionInput = document.getElementById('direccion');
  var geocodingMsg = document.getElementById('geocodingMensaje');

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

  // Índice normalizado del catálogo cerrado —sin acentos ni mayúsculas—,
  // para encajar lo que devuelve Nominatim aunque no venga escrito exacto
  // como en el catálogo.
  function normaliza(s){
    return (s || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
  }
  var ESTADOS_NORM = {};
  if (typeof MUNICIPIOS_POR_ESTADO !== 'undefined') {
    Object.keys(MUNICIPIOS_POR_ESTADO).forEach(function(est){ ESTADOS_NORM[normaliza(est)] = est; });
  }

  /*
   * Geocoding inverso: a partir del punto del pin, intenta adivinar ciudad,
   * estado y dirección con Nominatim —el mismo buscador gratuito que ya usa
   * centrarEnCiudad(), pero al revés—. Nunca es exacto, así que solo rellena
   * lo que la persona no haya escrito ya a mano, y siempre deja un aviso
   * para que se revise antes de publicar.
   */
  function geocodificarInverso(lat, lng){
    var url = 'https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1'
            + '&accept-language=es&lat=' + lat + '&lon=' + lng;

    fetch(url)
      .then(function(resp){ return resp.json(); })
      .then(function(datos){
        var dir = datos && datos.address;
        if (!dir) return;

        var estado  = ESTADOS_NORM[normaliza(dir.state)];
        var completo = false;

        if (estado && !entidadSel.value) {
          entidadSel.value = estado;
          var lista = MUNICIPIOS_POR_ESTADO[estado] || [];
          var datalist = document.getElementById('listaCiudades');
          if (datalist) {
            datalist.innerHTML = lista.map(function(m){
              return '<option value="' + m.replace(/"/g, '&quot;') + '"></option>';
            }).join('');
          }
          completo = true;
        }

        var estadoActivo = estado || entidadSel.value;
        if (estadoActivo && !ciudadSel.value) {
          var municipios = MUNICIPIOS_POR_ESTADO[estadoActivo] || [];
          var candidatos = [dir.city, dir.town, dir.village, dir.municipality, dir.county]
            .filter(Boolean).map(normaliza);
          var match = municipios.find(function(m){ return candidatos.indexOf(normaliza(m)) !== -1; });
          if (match) { ciudadSel.value = match; completo = true; }
        }

        if (direccionInput && !direccionInput.value) {
          var calle = [dir.road, dir.house_number].filter(Boolean).join(' ');
          if (calle) {
            direccionInput.value = calle + (dir.suburb ? ', ' + dir.suburb : '');
            completo = true;
          }
        }

        if (geocodingMsg) {
          geocodingMsg.textContent = completo
            ? 'Completamos ciudad, estado y/o dirección a partir del mapa. Revisa que estén bien.'
            : 'No pudimos adivinar la ubicación exacta desde el mapa. Complétala a mano.';
          geocodingMsg.hidden = false;
        }
      })
      .catch(function(){});
  }

  pin.on('dragend', function(){
    var p = pin.getLatLng();
    fijarPunto(p);
    geocodificarInverso(p.lat, p.lng);
  });
  mapa.on('click', function(ev){
    pin.setLatLng(ev.latlng);
    fijarPunto(ev.latlng);
    geocodificarInverso(ev.latlng.lat, ev.latlng.lng);
  });

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

  /*
   * Pegar un enlace de Google Maps en vez de buscar el punto a mano. El
   * enlace corto que comparte el celular (maps.app.goo.gl) no trae las
   * coordenadas escritas: resolverEnlaceMaps(), en el servidor, sigue la
   * redirección para encontrarlas —por eso esto pasa por un fetch propio y
   * no se resuelve aquí mismo, en el navegador.
   */
  var enlaceInput   = document.getElementById('enlaceMapsPegado');
  var enlaceBoton   = document.getElementById('enlaceMapsBoton');
  var enlaceMensaje = document.getElementById('enlaceMapsMensaje');
  var csrfInput     = document.querySelector('input[name="csrf"]');

  function mostrarMensajeEnlace(texto, tipo){
    enlaceMensaje.textContent = texto;
    enlaceMensaje.className = 'aviso aviso-' + tipo;
    enlaceMensaje.hidden = false;
  }

  if (enlaceBoton && enlaceInput) {
    enlaceBoton.addEventListener('click', function(){
      var url = enlaceInput.value.trim();
      if (!url) {
        mostrarMensajeEnlace('Pega primero un enlace de Google Maps.', 'error');
        return;
      }

      enlaceBoton.disabled = true;
      enlaceBoton.textContent = 'Buscando…';

      var datos = new URLSearchParams();
      datos.set('enlace', url);
      datos.set('csrf', csrfInput ? csrfInput.value : '');

      fetch('<?= URL_BASE ?>/resolver-mapa.php', { method: 'POST', body: datos })
        .then(function(resp){ return resp.json(); })
        .then(function(json){
          if (!json.ok) {
            mostrarMensajeEnlace(json.error || 'No se pudo leer ese enlace.', 'error');
            return;
          }

          var punto = { lat: json.lat, lng: json.lng };
          mapa.setView(punto, 16);
          pin.setLatLng(punto);
          fijarPunto(punto);
          geocodificarInverso(punto.lat, punto.lng);
          mostrarMensajeEnlace('Listo, movimos el pin a esa ubicación. Ajústalo si hace falta.', 'ok');
        })
        .catch(function(){
          mostrarMensajeEnlace('No se pudo comprobar el enlace. Inténtalo de nuevo.', 'error');
        })
        .finally(function(){
          enlaceBoton.disabled = false;
          enlaceBoton.textContent = 'Usar enlace';
        });
    });
  }

  /* ---------- informacion de contacto del organizador (REQ-00012) ----------
     Los campos ya vienen rellenos con lo que hay guardado. Esto solo decide
     cual de las dos caras se ve: el resumen o los campos.

     El resumen se esconde DESDE AQUI y no con un atributo hidden en el HTML:
     sin JavaScript hay que poder editar igual, y un campo escondido en el
     marcado no habria forma de destaparlo. Asi, sin JavaScript se ven las dos
     cosas —feo, pero utilizable— y con JavaScript solo el resumen hasta que se
     pide editar. */
  var orgGuardado = document.getElementById('orgGuardado');
  var orgCampos   = document.getElementById('orgCampos');
  var orgEditar   = document.getElementById('orgEditar');

  if (orgGuardado && orgCampos && orgEditar) {
    orgCampos.hidden = true;

    orgEditar.addEventListener('click', function () {
      orgGuardado.hidden = true;
      orgCampos.hidden   = false;

      var primero = orgCampos.querySelector('input');
      if (primero) primero.focus();
    });
  }
})();
</script>
