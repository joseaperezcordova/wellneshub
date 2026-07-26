<?php
/**
 * El formulario del evento, compartido por el alta y la edición.
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

/** El mensaje de error de un campo, si lo hay. */
$err = function (string $campo) use ($errores) {
    return isset($errores[$campo])
        ? '<div class="campo-error">' . e($errores[$campo]) . '</div>'
        : '';
};
?>

<div class="campo">
  <label for="titulo">Título del evento</label>
  <input id="titulo" name="titulo" type="text" required maxlength="160"
         value="<?= e($v('titulo')) ?>" placeholder="Amanecer en el Cenote — Yoga y Sonido">
  <?= $err('titulo') ?>
</div>

<div class="campo">
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
  <label for="descripcion">Descripción</label>
  <textarea id="descripcion" name="descripcion" rows="7" required
            placeholder="Qué van a vivir, qué incluye, qué llevar, para quién es."><?= e($v('descripcion')) ?></textarea>
  <div class="pista">Se muestra tal cual en la ficha. Los saltos de línea se respetan.</div>
  <?= $err('descripcion') ?>
</div>

<div class="campo-fila">
  <div class="campo">
    <label for="ciudad">Ciudad</label>
    <input id="ciudad" name="ciudad" type="text" required maxlength="90"
           value="<?= e($v('ciudad')) ?>" placeholder="Tulum">
    <?= $err('ciudad') ?>
  </div>
  <div class="campo">
    <label for="entidad">Estado</label>
    <input id="entidad" name="entidad" type="text" required maxlength="90"
           value="<?= e($v('entidad')) ?>" placeholder="Quintana Roo">
    <?= $err('entidad') ?>
  </div>
</div>

<div class="campo">
  <label for="lugar">Lugar <span class="opcional">opcional</span></label>
  <input id="lugar" name="lugar" type="text" maxlength="160"
         value="<?= e($v('lugar')) ?>" placeholder="Cenote Zacil-Ha">
</div>

<div class="campo-fila">
  <div class="campo">
    <label for="fecha_inicio">Empieza</label>
    <input id="fecha_inicio" name="fecha_inicio" type="datetime-local" required
           value="<?= e($fechaInput('fecha_inicio')) ?>">
    <?= $err('fecha_inicio') ?>
  </div>
  <div class="campo">
    <label for="fecha_fin">Termina <span class="opcional">opcional</span></label>
    <input id="fecha_fin" name="fecha_fin" type="datetime-local"
           value="<?= e($fechaInput('fecha_fin')) ?>">
    <div class="pista">Para retiros de varios días.</div>
    <?= $err('fecha_fin') ?>
  </div>
</div>

<div class="campo">
  <label class="check">
    <input type="checkbox" name="gratuito" value="1" id="gratuito"
           <?= !empty($e['gratuito']) ? 'checked' : '' ?>>
    <span>Es gratuito</span>
  </label>
</div>

<div class="campo" id="campoPrecio">
  <label for="precio">Precio por persona (MXN)</label>
  <input id="precio" name="precio" type="text" inputmode="decimal"
         value="<?= e($v('precio')) ?>" placeholder="2450">
  <?= $err('precio') ?>
</div>

<div class="campo">
  <label for="url_boletos">Enlace para comprar o reservar <span class="opcional">opcional</span></label>
  <input id="url_boletos" name="url_boletos" type="url" maxlength="500"
         value="<?= e($v('url_boletos')) ?>" placeholder="https://…">
  <?= $err('url_boletos') ?>
</div>

<div class="campo">
  <label for="imagen_url">Imagen por URL <span class="opcional">opcional</span></label>
  <input id="imagen_url" name="imagen_url" type="url" maxlength="500"
         value="<?= e($v('imagen_url')) ?>" placeholder="https://…/foto.jpg">
  <div class="pista">Todavía no se pueden subir archivos: pega la dirección de una imagen ya publicada. Sin imagen se usa el color de abajo.</div>
  <?= $err('imagen_url') ?>
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
</script>
