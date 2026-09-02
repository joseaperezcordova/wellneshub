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

/*
 * Las categorías marcadas. Tras un envío fallido ya vienen en $e['categorias']
 * —validarEvento() las deja puestas aunque hayan fallado, igual que hace con
 * cualquier otro campo—; si no, es que se llegó aquí por primera vez: al
 * editar, lo guardado en eventos_categorias; al dar de alta, ninguna.
 */
$categoriasSeleccionadas = isset($e['categorias']) && is_array($e['categorias'])
    ? $e['categorias']
    : (isset($e['id']) ? categoriasDeEvento((int) $e['id']) : []);

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
 * Correo de contacto de la actividad (migración 24, requerimiento del
 * cliente 2026-09-02): quién recibe "Contactar al organizador" para ESTA
 * actividad. Vive dentro de la tarjeta "Solicitar información" de la
 * sección 8, no en un <form> aparte —un <form> no puede ir dentro de otro—,
 * así que sus botones son botones de envío normales, con su propio "name",
 * dentro del mismo formulario grande. evento-nuevo.php y evento-editar.php
 * leen esos "name" ANTES de la validación de todo lo demás.
 *
 * $puedeEnviarCodigoCorreo: pedir un código necesita que la actividad ya
 * tenga id —el código se guarda contra esa fila—, así que en el alta
 * (evento-nuevo.php, $e sin 'id') no hay botón de "Enviar código" suelto: el
 * correo alternativo que se haya escrito se manda solo, como parte de crear
 * la actividad, si se publica con ese botón. Ver evento-nuevo.php.
 */
$correoCuenta            = (string) (usuarioActual()['email'] ?? '');
$correoPendiente         = isset($e['id']) ? correoContactoPendiente((int) $e['id']) : null;
$tieneCorreoPropio       = trim((string) ($e['correo_contacto'] ?? '')) !== '';
$correoContactoEfectivo  = $tieneCorreoPropio ? (string) $e['correo_contacto'] : $correoCuenta;
$puedeEnviarCodigoCorreo = isset($e['id']);

?>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M7 3h7l4 4v14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/>
    <path d="M14 3v4h4"/>
  </svg>
  <h2><?= et('evento.form.seccion1') ?></h2>
</div>

<div class="campo<?= $mal('titulo') ?>">
  <div class="label-fila">
    <label for="titulo"><?= et('evento.form.titulo_label') ?></label>
    <span class="contador" id="contadorTitulo"></span>
  </div>
  <input id="titulo" name="titulo" type="text" required maxlength="160"
         value="<?= e($v('titulo')) ?>" placeholder="<?= et('evento.form.titulo_placeholder') ?>">
  <?= $err('titulo') ?>
</div>

<div class="campo<?= $mal('categorias') ?>">
  <div class="label-fila">
    <label><?= et('evento.form.categorias_label') ?> <span class="pista" style="font-weight:400;"><?= et('evento.form.categorias_pista') ?> <?= EVENTO_CATEGORIAS_MAX ?></span></label>
    <span class="contador" id="contadorCategorias"></span>
  </div>
  <div class="checklist categorias-grid" id="categoriasGrupo">
    <?php foreach (categoriasMenu() as $catNombre => $catDatos): ?>
      <label>
        <input type="checkbox" name="categorias[]" value="<?= e($catNombre) ?>"
               <?= in_array($catNombre, $categoriasSeleccionadas, true) ? 'checked' : '' ?>>
        <?= e($catDatos[0] . '  ' . $catDatos[1]) ?>
      </label>
    <?php endforeach; ?>
  </div>
  <div class="pista"><?= et('evento.form.categorias_ayuda') ?></div>
  <?= $err('categorias') ?>
</div>

<div class="campo<?= $mal('descripcion') ?>">
  <div class="label-fila">
    <label for="descripcion"><?= et('evento.form.descripcion_label') ?></label>
    <span class="contador" id="contadorDescripcion"></span>
  </div>
  <textarea id="descripcion" name="descripcion" rows="7" required minlength="50" maxlength="2000"
            placeholder="<?= et('evento.form.descripcion_placeholder') ?>"><?= e($v('descripcion')) ?></textarea>
  <div class="pista"><?= et('evento.form.descripcion_ayuda') ?></div>
  <?= $err('descripcion') ?>
</div>

<div class="campo<?= $mal('titulo_en') ?>">
  <div class="label-fila">
    <label for="titulo_en"><?= et('evento.form.titulo_en_label') ?> <span class="opcional"><?= et('campo.opcional') ?></span></label>
    <span class="contador" id="contadorTituloEn"></span>
  </div>
  <input id="titulo_en" name="titulo_en" type="text" maxlength="160"
         value="<?= e($v('titulo_en')) ?>" placeholder="<?= et('evento.form.titulo_en_placeholder') ?>">
  <div class="pista"><?= et('evento.form.titulo_en_ayuda') ?></div>
  <?= $err('titulo_en') ?>
</div>

<div class="campo<?= $mal('descripcion_en') ?>">
  <div class="label-fila">
    <label for="descripcion_en"><?= et('evento.form.descripcion_en_label') ?> <span class="opcional"><?= et('campo.opcional') ?></span></label>
    <span class="contador" id="contadorDescripcionEn"></span>
  </div>
  <textarea id="descripcion_en" name="descripcion_en" rows="7" maxlength="2000"
            placeholder="<?= et('evento.form.descripcion_en_placeholder') ?>"><?= e($v('descripcion_en')) ?></textarea>
  <div class="pista"><?= et('evento.form.descripcion_en_ayuda') ?></div>
  <?= $err('descripcion_en') ?>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <rect x="3.5" y="5" width="17" height="15" rx="2"/>
    <path d="M3.5 9.5h17"/>
    <path d="M8 3v4M16 3v4"/>
  </svg>
  <h2><?= et('evento.form.seccion2') ?></h2>
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
      <span><?= et('evento.form.dia_unico') ?></span>
    </label>
    <div class="tipo-fecha-campos">
      <div class="campo<?= $mal('fecha_unica') ?>">
        <label for="fecha_unica"><?= et('evento.form.fecha_label') ?></label>
        <input id="fecha_unica" name="fecha_unica" type="date" <?= $esRecurrente ? '' : 'required' ?>
               value="<?= e($esRecurrente ? '' : $fechaSoloInput('fecha_inicio')) ?>">
        <?= $err('fecha_unica') ?>
      </div>
      <div class="campo-fila">
        <div class="campo<?= $mal('hora_inicio_unica') ?>">
          <label for="hora_inicio_unica"><?= et('evento.form.hora_inicio_label') ?></label>
          <input id="hora_inicio_unica" name="hora_inicio_unica" type="time" <?= $esRecurrente ? '' : 'required' ?>
                 value="<?= e($esRecurrente ? '' : $horaDeFecha('fecha_inicio')) ?>">
          <?= $err('hora_inicio_unica') ?>
        </div>
        <div class="campo<?= $mal('hora_fin_unica') ?>">
          <label for="hora_fin_unica"><?= et('evento.form.hora_fin_label') ?></label>
          <input id="hora_fin_unica" name="hora_fin_unica" type="time" <?= $esRecurrente ? '' : 'required' ?>
                 value="<?= e($esRecurrente ? '' : $horaDeFecha('fecha_fin')) ?>">
          <?= $err('hora_fin_unica') ?>
        </div>
      </div>
      <div class="campo">
        <label for="fecha_fin_unica"><?= et('evento.form.termina_otro_dia') ?> <span class="opcional"><?= et('campo.opcional') ?></span></label>
        <input id="fecha_fin_unica" name="fecha_fin_unica" type="date"
               value="<?= e((!$esRecurrente && $fechaSoloInput('fecha_fin') !== '' && $fechaSoloInput('fecha_fin') !== $fechaSoloInput('fecha_inicio')) ? $fechaSoloInput('fecha_fin') : '') ?>">
        <div class="pista"><?= et('evento.form.termina_otro_dia_ayuda') ?></div>
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
      <span><?= et('evento.form.recurrente') ?></span>
    </label>
    <div class="tipo-fecha-campos">
      <div class="campo-fila">
        <div class="campo<?= $mal('fecha_inicio_rec') ?>">
          <label for="fecha_inicio_rec"><?= et('evento.form.fecha_inicio_label') ?></label>
          <input id="fecha_inicio_rec" name="fecha_inicio_rec" type="date" <?= $esRecurrente ? 'required' : '' ?>
                 value="<?= e($esRecurrente ? $fechaSoloInput('fecha_inicio') : '') ?>">
          <?= $err('fecha_inicio_rec') ?>
        </div>
        <div class="campo<?= $mal('fecha_fin_rec') ?>">
          <label for="fecha_fin_rec"><?= et('evento.form.fecha_fin_label') ?></label>
          <input id="fecha_fin_rec" name="fecha_fin_rec" type="date" <?= $esRecurrente ? 'required' : '' ?>
                 value="<?= e($esRecurrente ? $fechaSoloInput('fecha_fin') : '') ?>">
          <?= $err('fecha_fin_rec') ?>
        </div>
      </div>
      <div class="campo<?= $mal('frecuencia') ?>">
        <label for="frecuencia"><?= et('evento.form.frecuencia_label') ?></label>
        <select id="frecuencia" name="frecuencia" <?= $esRecurrente ? 'required' : '' ?>>
          <option value=""><?= et('evento.form.frecuencia_placeholder') ?></option>
          <?php foreach (frecuenciasRecurrencia() as $clave => $etiqueta): ?>
            <option value="<?= e($clave) ?>" <?= $v('frecuencia') === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $err('frecuencia') ?>
      </div>
      <div class="campo-fila">
        <div class="campo<?= $mal('hora_recurrente') ?>">
          <label for="hora_recurrente"><?= et('evento.form.hora_inicio_label') ?></label>
          <input id="hora_recurrente" name="hora_recurrente" type="time" <?= $esRecurrente ? 'required' : '' ?>
                 value="<?= e($horaInput()) ?>">
          <?= $err('hora_recurrente') ?>
        </div>
        <div class="campo<?= $mal('hora_fin_recurrente') ?>">
          <label for="hora_fin_recurrente"><?= et('evento.form.hora_fin_label') ?></label>
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
  <h2><?= et('evento.form.seccion3') ?></h2>
</div>

<div class="campo-fila campo-fila-3">
  <div class="campo<?= $mal('entidad') ?>">
    <label for="entidad"><?= et('evento.form.estado_label') ?></label>
    <select id="entidad" name="entidad" required>
      <option value=""><?= et('evento.form.estado_placeholder') ?></option>
      <?php foreach (estadosMexico() as $estado): ?>
        <option value="<?= e($estado) ?>" <?= $v('entidad') === $estado ? 'selected' : '' ?>><?= e($estado) ?></option>
      <?php endforeach; ?>
    </select>
    <?= $err('entidad') ?>
  </div>
  <div class="campo<?= $mal('ciudad') ?>" id="campoCiudad">
    <label for="ciudad"><?= et('evento.form.ciudad_label') ?></label>
    <input id="ciudad" name="ciudad" type="text" required autocomplete="off"
           list="listaCiudades" placeholder="<?= et('evento.form.ciudad_placeholder') ?>"
           value="<?= e($v('ciudad')) ?>">
    <datalist id="listaCiudades">
      <?php foreach (municipiosPorEstado()[$v('entidad')] ?? [] as $municipio): ?>
        <option value="<?= e($municipio) ?>"></option>
      <?php endforeach; ?>
    </datalist>
    <?= $err('ciudad') ?>
  </div>
  <div class="campo<?= $mal('lugar') ?>" id="campoLugar">
    <label for="lugar"><?= et('evento.form.lugar_label') ?></label>
    <input id="lugar" name="lugar" type="text" required maxlength="160"
           value="<?= e($v('lugar')) ?>" placeholder="<?= et('evento.form.lugar_placeholder') ?>">
    <?= $err('lugar') ?>
  </div>
</div>

<div class="campo<?= $mal('direccion') ?>">
  <label for="direccion"><?= et('evento.form.direccion_label') ?> <span class="opcional"><?= et('campo.opcional') ?></span></label>
  <input id="direccion" name="direccion" type="text" maxlength="255"
         value="<?= e($v('direccion')) ?>" placeholder="<?= et('evento.form.direccion_placeholder') ?>">
  <div class="pista"><?= et('evento.form.direccion_ayuda') ?></div>
  <?= $err('direccion') ?>
</div>

<div class="campo">
  <div id="mapaInteractivo" class="mapa-interactivo" data-lat="<?= e($v('latitud')) ?>" data-lng="<?= e($v('longitud')) ?>"></div>
  <div class="aviso aviso-info" style="margin:10px 0 0;"><?= et('evento.form.mapa_ayuda') ?></div>

  <div class="enlace-maps-grupo">
    <label for="enlaceMapsPegado"><?= et('evento.form.maps_enlace_label') ?></label>
    <div class="enlace-maps-fila">
      <input type="text" id="enlaceMapsPegado" placeholder="<?= et('evento.form.maps_enlace_placeholder') ?>">
      <button type="button" id="enlaceMapsBoton"><?= et('evento.form.maps_usar_enlace') ?></button>
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
  <h2><?= et('evento.form.seccion4') ?></h2>
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
      <span class="precio-titulo"><?= et('evento.form.sin_costo') ?></span>
    </label>
    <label class="precio-op">
      <input type="radio" name="precio_modo" value="de_pago" id="precioDePago" <?= $esDePago ? 'checked' : '' ?>>
      <svg class="precio-icono" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
           stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12.5 3H20a1 1 0 0 1 1 1v7.5a1 1 0 0 1-.3.7l-9 9a1 1 0 0 1-1.4 0l-7.5-7.5a1 1 0 0 1 0-1.4l9-9a1 1 0 0 1 .7-.3z"/>
        <circle cx="16.5" cy="7.5" r="1.3" fill="currentColor" stroke="none"/>
      </svg>
      <span class="precio-titulo"><?= et('evento.form.de_pago') ?></span>
    </label>
  </div>
</div>

<div id="bloquePago">
  <div class="campo<?= $mal('forma_pago') ?>">
    <label for="forma_pago"><?= et('evento.form.forma_pago_label') ?></label>
    <select id="forma_pago" name="forma_pago" <?= $esDePago ? 'required' : '' ?>>
      <option value=""><?= et('evento.form.forma_pago_placeholder') ?></option>
      <option value="completa" <?= $v('forma_pago') === 'completa' ? 'selected' : '' ?>><?= et('evento.form.forma_pago_completa') ?></option>
      <option value="sesion" <?= $v('forma_pago') === 'sesion' ? 'selected' : '' ?>><?= et('evento.form.forma_pago_sesion') ?></option>
    </select>
    <?= $err('forma_pago') ?>
  </div>

  <div class="campo<?= $mal('precio') ?>" id="campoPrecio">
    <label for="precio"><?= et('evento.form.precio_label') ?></label>
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
  <h2><?= et('evento.form.seccion5') ?> <span class="opcional"><?= et('campo.opcional') ?></span></h2>
</div>

<div class="campo<?= $mal('cupo_maximo') ?>">
  <label for="cupo_maximo"><?= et('evento.form.cupo_label') ?></label>
  <input id="cupo_maximo" name="cupo_maximo" type="text" inputmode="numeric" maxlength="6"
         value="<?= e($v('cupo_maximo')) ?>" placeholder="<?= et('evento.form.cupo_placeholder') ?>">
  <?= $err('cupo_maximo') ?>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <rect x="3" y="4" width="18" height="16" rx="2"/>
    <circle cx="8.5" cy="9.5" r="1.6"/>
    <path d="M21 16l-5.5-5.5a1.5 1.5 0 0 0-2.1 0L4 19"/>
  </svg>
  <h2><?= et('evento.form.seccion6') ?> <span class="opcional"><?= et('campo.opcional') ?></span></h2>
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
      <img src="<?= e($imagenActual) ?>" alt="<?= et('evento.form.imagen_alt') ?>">
      <?php if (esImagenEnVuelo($imagenGuardada)): ?>
        <div class="pista"><?= et('evento.form.imagen_elegida') ?></div>
      <?php endif; ?>
      <label class="check">
        <input type="checkbox" name="quitar_imagen" value="1">
        <span><?= et('evento.form.quitar_imagen') ?></span>
      </label>
    </div>
  <?php endif; ?>

  <label class="imagen-dropzone" id="imagenDropzone" for="imagen">
    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.6"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M7 18a4.5 4.5 0 0 1-.5-8.97A5.5 5.5 0 0 1 17.2 8.05 4 4 0 0 1 17 16"/>
      <path d="M12 12v7M9 15l3-3 3 3"/>
    </svg>
    <span class="imagen-dropzone-texto"><?= et('evento.form.subir_imagen') ?></span>
    <span class="imagen-dropzone-nombre" id="imagenNombre"></span>
  </label>
  <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="imagen-input-oculto">

  <div class="pista">
    <?= et('evento.form.imagen_pista_1') ?> <?= round(IMAGEN_MAX_BYTES / 1048576) ?> <?= et('evento.form.imagen_pista_2') ?>
    <?= $imagenActual !== null ? et('evento.form.imagen_pista_cambiar') : et('evento.form.imagen_pista_sin') ?>
  </div>
  <?= $err('imagen') ?>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <circle cx="12" cy="8.2" r="3.8"/>
    <path d="M4.8 20c.6-3.7 3.6-5.9 7.2-5.9s6.6 2.2 7.2 5.9"/>
  </svg>
  <h2><?= et('evento.form.seccion7') ?></h2>
</div>

<?php
/*
 * REQ-000-XX fusionó lo que antes eran dos secciones numeradas —«7.
 * Información de contacto» (el organizador) y «8. Información adicional»
 * (el sitio de la actividad)— en una sola.
 *
 * "Datos del organizador" —el acordeón con nombre, WhatsApp, Instagram y
 * sitio web de la CUENTA— se quitó de aquí a pedido del cliente
 * (2026-09-02): esos mismos datos ya se piden y se editan en «Mi cuenta»
 * (mi-cuenta.php), y tenerlos duplicados en cada publicación era pedir dos
 * veces lo mismo. El nombre del organizador que se ve en cada ficha
 * («Organiza: X») sigue saliendo de la cuenta —usuarios.nombre—, solo que ya
 * no se puede tocar desde este formulario.
 *
 * Lo único que queda es el sitio de ESTA actividad, que nunca fue parte de
 * la cuenta y no tiene sentido duplicar en «Mi cuenta».
 */
?>
<div class="campo<?= $mal('sitio_web') ?>">
  <label for="sitio_web"><?= et('evento.form.sitio_web_actividad_label') ?> <span class="opcional"><?= et('campo.opcional') ?></span></label>
  <input id="sitio_web" name="sitio_web" type="url" maxlength="500"
         value="<?= e($v('sitio_web')) ?>" placeholder="<?= et('evento.form.sitio_web_placeholder') ?>">
  <div class="pista"><?= et('evento.form.sitio_web_actividad_ayuda') ?></div>
  <?= $err('sitio_web') ?>
</div>

<div class="form-seccion-titulo">
  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6l-5 4H4a1 1 0 0 0-1 1z"/>
    <path d="M16 9a3.5 3.5 0 0 1 0 6"/>
    <path d="M18.5 6.5a7 7 0 0 1 0 11"/>
  </svg>
  <h2><?= et('evento.form.seccion8') ?> *</h2>
</div>

<div class="campo<?= $mal('accion_principal') ?>">
  <div class="pista" style="margin:0 0 10px;"><?= et('evento.form.accion_ayuda') ?></div>

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
        <span class="accion-titulo"><?= et('evento.form.accion_contactar') ?></span>
      </label>

      <div class="accion-campos">
        <div class="campo">
          <label><?= et('evento.correo_contacto.campo_label') ?> <span class="obligatorio-si">*</span></label>

          <?php if (!empty($avisoCorreoContacto ?? '')): ?>
            <div class="aviso aviso-ok"><?= e($avisoCorreoContacto) ?></div>
          <?php endif; ?>
          <?php if (!empty($errorCorreoContacto ?? '')): ?>
            <div class="aviso aviso-error"><?= e($errorCorreoContacto) ?></div>
          <?php endif; ?>

          <?php if ($correoPendiente !== null): ?>
            <?php /* Ya se pidió un código para un correo nuevo: aquí no hay nada
                     que elegir, solo confirmarlo o cancelarlo. */ ?>
            <p class="pista"><?= e(sprintf(t('evento.correo_contacto.pendiente_texto'), $correoPendiente)) ?></p>
            <div class="campo">
              <label for="codigo_correo_contacto"><?= et('evento.correo_contacto.codigo_label') ?></label>
              <input id="codigo_correo_contacto" name="codigo_correo_contacto" type="text" inputmode="numeric"
                     autocomplete="one-time-code" maxlength="6"
                     placeholder="<?= et('evento.correo_contacto.codigo_placeholder') ?>">
            </div>
            <div class="barra-acciones">
              <button class="btn-barra destacado" type="submit" name="confirmar_codigo_correo" value="1"
                      formnovalidate><?= et('evento.correo_contacto.confirmar_btn') ?></button>
              <button class="btn-barra" type="submit" name="cancelar_codigo_correo" value="1"
                      formnovalidate><?= et('evento.correo_contacto.cancelar_btn') ?></button>
            </div>
          <?php else: ?>
            <label class="check">
              <input type="checkbox" id="usarCorreoCuenta" name="usar_correo_cuenta" value="1"
                     <?= $tieneCorreoPropio ? '' : 'checked' ?>>
              <span><?= et('evento.correo_contacto.usar_cuenta') ?></span>
            </label>
            <div class="pista" id="correoCuentaTexto"<?= $tieneCorreoPropio ? ' hidden' : '' ?>>
              <?= e($correoCuenta) ?>
            </div>

            <div id="correoOtroBloque"<?= $tieneCorreoPropio ? '' : ' hidden' ?>>
              <div class="campo" style="margin-top:10px;">
                <label for="correo_contacto_nuevo"><?= et('evento.correo_contacto.campo_label') ?> <span class="obligatorio-si">*</span></label>
                <input id="correo_contacto_nuevo" name="correo_contacto_nuevo" type="email" maxlength="190"
                       value="<?= e($tieneCorreoPropio ? $correoContactoEfectivo : '') ?>"
                       placeholder="<?= et('evento.correo_contacto.nuevo_placeholder') ?>">
              </div>
              <?php if ($puedeEnviarCodigoCorreo): ?>
                <div class="barra-acciones">
                  <button class="btn-barra" type="submit" name="enviar_codigo_correo" value="1"
                          formnovalidate><?= et('evento.correo_contacto.enviar_btn') ?></button>
                </div>
              <?php endif; ?>
            </div>

            <div class="pista" style="margin-top:10px;"><?= et('evento.correo_contacto.info_texto') ?></div>
          <?php endif; ?>
        </div>
      </div>
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
        <span class="accion-titulo"><?= et('evento.form.accion_comprar') ?></span>
      </label>
      <div class="accion-campos">
        <div class="campo<?= $mal('url_boletos') ?>">
          <label for="url_boletos"><?= et('evento.form.url_compra_label') ?> <span class="obligatorio-si">*</span></label>
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
        <span class="accion-titulo"><?= et('evento.form.accion_reservar') ?></span>
      </label>
      <div class="accion-campos">
        <div class="campo<?= $mal('url_reserva') ?>">
          <label for="url_reserva"><?= et('evento.form.url_reserva_label') ?> <span class="obligatorio-si">*</span></label>
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
  <label><?= et('evento.form.color_label') ?></label>
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
  <div class="pista"><?= et('evento.form.color_ayuda') ?></div>
</div>

<button class="btn-principal" type="submit"><?= e($textoBoton) ?></button>

<?php /* Va antes del script grande y no después: ese script comprueba
         "typeof MUNICIPIOS_POR_ESTADO" al cargar, y si esta variable llegara
         más tarde la comprobación siempre daría "undefined" y el selector de
         ciudad se quedaría sin repoblarse al cambiar de estado. */ ?>
<script>
var MUNICIPIOS_POR_ESTADO = <?= json_encode(municipiosPorEstado(), JSON_UNESCAPED_UNICODE) ?>;

// Los mensajes del mapa interactivo (geocodificación y "enlace de Google
// Maps") se arman en el navegador, no en PHP, así que necesitan su propio
// idioma aquí igual que BUSCAR_T en buscar.php.
var EVENTO_T = <?= json_encode([
    'mapaPegarEnlace'   => t('evento.mapa.pegar_enlace'),
    'mapaBuscando'      => t('evento.mapa.buscando'),
    'mapaCompleto'      => t('evento.mapa.completo'),
    'mapaIncompleto'    => t('evento.mapa.incompleto'),
    'mapaNoUbicada'     => t('evento.mapa.no_ubicada'),
    'mapaEncontrada'    => t('evento.mapa.encontrada'),
    'mapaErrorDireccion'=> t('evento.mapa.error_direccion'),
    'mapaEnlaceListo'   => t('evento.mapa.enlace_listo'),
    'mapaEnlaceErrorGenerico'  => t('evento.mapa.enlace_error_generico'),
    'mapaEnlaceErrorComprobar'=> t('evento.mapa.enlace_error_comprobar'),
    'mapaUsarEnlace'    => t('evento.mapa.usar_enlace'),
], JSON_UNESCAPED_UNICODE) ?>;
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

/* Solo se ven los campos de la tarjeta elegida (requerimiento del cliente,
   2026-09-02: antes las tres tarjetas se veían completas a la vez, y solo
   cambiaba cuál de los dos enlaces era obligatorio). Se oculta con
   JavaScript porque de todas formas hace falta este mismo sync() para
   decidir el required de cada campo; hacerlo también aquí no cuesta nada
   más y evita depender de un selector CSS aparte. */
(function(){
  var radios = document.querySelectorAll('input[name="accion_principal"]');
  var urlBoletos = document.getElementById('url_boletos');
  var urlReserva = document.getElementById('url_reserva');
  var tarjetas = document.querySelectorAll('.accion-tarjeta');
  if (!radios.length) return;

  function sync(){
    var elegida = document.querySelector('input[name="accion_principal"]:checked').value;

    tarjetas.forEach(function(tarjeta){
      var campos = tarjeta.querySelector('.accion-campos');
      if (!campos) return;
      var esLaElegida = tarjeta.querySelector('input[name="accion_principal"]').value === elegida;
      campos.hidden = !esLaElegida;
    });

    if (urlBoletos) urlBoletos.required = elegida === 'boletos';
    if (urlReserva) urlReserva.required = elegida === 'reservar';
  }

  radios.forEach(function(r){ r.addEventListener('change', sync); });
  sync();
})();

/* El correo de contacto de "Solicitar información": la casilla decide si se
   ve el campo para escribir uno distinto. Desmarcarla es la señal de "quiero
   usar otro", tal como lo pidió el cliente. */
(function(){
  var casilla = document.getElementById('usarCorreoCuenta');
  var textoCuenta = document.getElementById('correoCuentaTexto');
  var bloqueOtro  = document.getElementById('correoOtroBloque');
  var campoOtro   = document.getElementById('correo_contacto_nuevo');
  if (!casilla || !bloqueOtro) return;

  function sync(){
    var usaCuenta = casilla.checked;
    if (textoCuenta) textoCuenta.hidden = !usaCuenta;
    bloqueOtro.hidden = usaCuenta;
    if (campoOtro) campoOtro.required = !usaCuenta;
  }

  casilla.addEventListener('change', sync);
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

/* Mismos dos contadores, para la versión en inglés. Sin mínimo: los dos
   campos son opcionales, así que un campo corto —o vacío— no es un error. */
(function(){
  var campo    = document.getElementById('titulo_en');
  var contador = document.getElementById('contadorTituloEn');
  if (!campo || !contador) return;

  function sync(){ contador.textContent = campo.value.length + ' / 160'; }
  campo.addEventListener('input', sync);
  sync();
})();

(function(){
  var campo    = document.getElementById('descripcion_en');
  var contador = document.getElementById('contadorDescripcionEn');
  if (!campo || !contador) return;

  function sync(){ contador.textContent = campo.value.length + ' / 2000'; }
  campo.addEventListener('input', sync);
  sync();
})();

/* Tope de categorías en el navegador. El servidor ya lo exige —validarEvento()
   descarta el sobrante—, pero avisar antes de enviar evita el viaje en balde
   de mandar el formulario para enterarse ahí de que sobraba una. Las que no
   están marcadas se deshabilitan al llegar al tope, así que no hace falta
   explicar "ya no puedes" con otro mensaje: el propio checkbox lo dice. */
(function(){
  var MAX = <?= (int) EVENTO_CATEGORIAS_MAX ?>;
  var grupo = document.getElementById('categoriasGrupo');
  var contador = document.getElementById('contadorCategorias');
  if (!grupo) return;

  var cajas = grupo.querySelectorAll('input[type="checkbox"]');

  function sync(){
    var marcadas = grupo.querySelectorAll('input[type="checkbox"]:checked').length;
    if (contador) contador.textContent = marcadas + ' / ' + MAX;
    cajas.forEach(function(caja){
      caja.disabled = !caja.checked && marcadas >= MAX;
    });
  }

  cajas.forEach(function(caja){ caja.addEventListener('change', sync); });
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
          geocodingMsg.textContent = completo ? EVENTO_T.mapaCompleto : EVENTO_T.mapaIncompleto;
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
   * Al escribir o corregir la dirección, mover el pin ahí en vez de esperar
   * a que alguien lo arrastre (REQ-000-XX). "change" —al salir del campo— y
   * no "input" en cada tecla: Nominatim pide como mucho una petición por
   * segundo, y una por letra se sale de eso con la primera palabra.
   *
   * A diferencia de centrarEnCiudad(), esto SÍ mueve el pin aunque ya
   * hubiera uno puesto: el requerimiento pide justo eso —que corregir la
   * dirección lo actualice de nuevo—, y la dirección es una señal más
   * precisa que la ciudad.
   */
  function geocodificarDireccion(){
    var direccion = direccionInput.value.trim();
    if (!direccion || !geocodingMsg) return;

    var partes = [direccion];
    if (ciudadSel.value)  partes.push(ciudadSel.value);
    if (entidadSel.value) partes.push(entidadSel.value);
    partes.push('México');

    var consulta = encodeURIComponent(partes.join(', '));

    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + consulta)
      .then(function(resp){ return resp.json(); })
      .then(function(resultados){
        if (!resultados.length) {
          geocodingMsg.textContent = EVENTO_T.mapaNoUbicada;
          geocodingMsg.className = 'aviso aviso-error';
          geocodingMsg.hidden = false;
          return;
        }

        var punto = {lat: parseFloat(resultados[0].lat), lng: parseFloat(resultados[0].lon)};
        mapa.setView(punto, 16);
        pin.setLatLng(punto);
        fijarPunto(punto);

        geocodingMsg.textContent = EVENTO_T.mapaEncontrada;
        geocodingMsg.className = 'aviso aviso-info';
        geocodingMsg.hidden = false;
      })
      .catch(function(){
        geocodingMsg.textContent = EVENTO_T.mapaErrorDireccion;
        geocodingMsg.className = 'aviso aviso-error';
        geocodingMsg.hidden = false;
      });
  }
  direccionInput.addEventListener('change', geocodificarDireccion);

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
        mostrarMensajeEnlace(EVENTO_T.mapaPegarEnlace, 'error');
        return;
      }

      enlaceBoton.disabled = true;
      enlaceBoton.textContent = EVENTO_T.mapaBuscando;

      var datos = new URLSearchParams();
      datos.set('enlace', url);
      datos.set('csrf', csrfInput ? csrfInput.value : '');

      fetch('<?= URL_BASE ?>/resolver-mapa.php', { method: 'POST', body: datos })
        .then(function(resp){ return resp.json(); })
        .then(function(json){
          if (!json.ok) {
            mostrarMensajeEnlace(json.error || EVENTO_T.mapaEnlaceErrorGenerico, 'error');
            return;
          }

          var punto = { lat: json.lat, lng: json.lng };
          mapa.setView(punto, 16);
          pin.setLatLng(punto);
          fijarPunto(punto);
          geocodificarInverso(punto.lat, punto.lng);
          mostrarMensajeEnlace(EVENTO_T.mapaEnlaceListo, 'ok');
        })
        .catch(function(){
          mostrarMensajeEnlace(EVENTO_T.mapaEnlaceErrorComprobar, 'error');
        })
        .finally(function(){
          enlaceBoton.disabled = false;
          enlaceBoton.textContent = EVENTO_T.mapaUsarEnlace;
        });
    });
  }

})();
</script>
