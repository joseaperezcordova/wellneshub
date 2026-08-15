<?php
/**
 * Alta de una actividad.
 *
 * Guarda como BORRADOR y manda a la vista previa. Nada se publica desde este
 * formulario: quien escribe una ficha larga quiere verla antes de enseñarla, y
 * un botón "publicar" al final del formulario no deja hacerlo.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';

$u = exigirSesion();

$e       = ['color' => coloresEvento()[0]];
$errores = [];

if (postDesbordado()) {
    // Se comprueba antes que el CSRF: con el cuerpo descartado tampoco llega el
    // token, y el mensaje "la sesión caducó" mandaría a buscar el problema al
    // sitio equivocado.
    $errores['general'] = 'La imagen pesa más de lo que admite el servidor. Prueba con una más ligera.';

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $errores['general'] = 'La sesión caducó. Vuelve a enviarlo.';
        $e = $_POST;
        $e['imagen_url'] = imagenArrastrada($_POST['imagen_previa'] ?? null, null);
    } else {
        [$e, $errores] = validarEvento($_POST);

        [$e['imagen_url'], $errorImagen] = imagenDelFormulario($_POST, $_FILES, null);
        if ($errorImagen !== null) {
            $errores['imagen'] = $errorImagen;
        }

        if (!$errores && eventoDuplicado((int) $u['id'], $e['entidad'], $e['ciudad'], $e['categoria'], $e['fecha_inicio'])) {
            $errores['general'] = 'Ya tienes otra actividad de "' . $e['categoria'] . '" en ' . $e['ciudad'] . ', ' . $e['entidad'] . ' para ese mismo día. Si es una repetición sin querer, revisa tus actividades; si es otra cosa, cambia la fecha, la ciudad o la categoría.';
        }

        if (!$errores) {
            $id = crearEvento($e, (int) $u['id']);
            olvidarImagenEnVuelo($e['imagen_url']);   // ya tiene dueño

            /*
             * Las dos cosas de golpe (REQ-00012): la actividad y los datos de
             * contacto en su cuenta, para que la próxima vez ya estén puestos.
             *
             * Aquí y no antes de validar: si la actividad no se llegó a crear,
             * guardar a medias dejaría su cuenta cambiada por un formulario que
             * nunca se envió. Y no impide publicar —todos los campos son
             * opcionales—, así que no hay nada que comprobar antes.
             */
            guardarContactoOrganizador((int) $u['id'], $_POST);
            // Se relee para tener el slug que acaba de generarse. Si por lo que
            // sea no viniera, urlEvento() se apaña con el id y la dirección
            // sigue llevando a la ficha.
            redirigir(urlEvento(buscarEvento($id) ?? ['id' => $id]));
        }

        /*
         * Si algo falló, la imagen SE QUEDA.
         *
         * Antes se borraba aquí para no dejar archivos sueltos en el disco, y el
         * precio lo pagaba quien rellenaba la ficha: bastaba una descripción
         * corta para tener que volver a buscar la foto en el ordenador. Un
         * <input type="file"> no se puede rellenar desde el servidor —el
         * navegador no lo permite—, así que conservarla es la única forma.
         *
         * Los archivos que queden de una ficha abandonada se los lleva
         * limpiarImagenesHuerfanas(), abajo.
         */
    }
}

/*
 * Barrido de imágenes abandonadas, de vez en cuando y solo al abrir el
 * formulario. Es el mismo patrón que usa PHP para caducar sesiones: hacerlo
 * siempre sería una consulta de más en cada visita, y hacerlo nunca llena el
 * disco de un hosting compartido.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && random_int(1, 20) === 1) {
    limpiarImagenesHuerfanas();
}

$titulo = 'Publicar una actividad';
$mapaInteractivo = true;
require __DIR__ . '/includes/layout.php';
?>

<div class="form-con-guia">
  <div class="auth-caja caja-ancha">
    <h1>Publicar una actividad</h1>
    <p class="sub">Rellena la ficha. Antes de publicarla la vas a ver tal como la verá la gente.</p>

    <?php require __DIR__ . '/includes/aviso-errores.php'; ?>
    <?php if ($errores): ?>
      <script>whTrack('error_formulario', <?= json_encode(['form' => 'evento_nuevo', 'campos' => array_keys($errores)]) ?>);</script>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
      <?php $textoBoton = 'Ver la vista previa'; require __DIR__ . '/includes/form-evento.php'; ?>
    </form>
  </div>

  <?php require __DIR__ . '/includes/guia-accion.php'; ?>
</div>

<?php pie(); ?>
