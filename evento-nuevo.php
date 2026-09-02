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
    $errores['general'] = t('evento.error.imagen_pesada');

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $errores['general'] = t('evento.error.sesion_caducada');
        $e = $_POST;
        $e['imagen_url'] = imagenArrastrada($_POST['imagen_previa'] ?? null, null);
    } else {
        [$e, $errores] = validarEvento($_POST);

        [$e['imagen_url'], $errorImagen] = imagenDelFormulario($_POST, $_FILES, null);
        if ($errorImagen !== null) {
            $errores['imagen'] = $errorImagen;
        }

        if (!$errores && eventoDuplicado((int) $u['id'], $e['entidad'], $e['ciudad'], $e['categoria'], $e['fecha_inicio'])) {
            $errores['general'] = sprintf(t('evento.error.duplicado'), $e['categoria'], $e['ciudad'], $e['entidad']);
        }

        if (!$errores) {
            $id = crearEvento($e, (int) $u['id']);
            olvidarImagenEnVuelo($e['imagen_url']);   // ya tiene dueño

            /*
             * Correo de contacto de la actividad (migración 24). La tarjeta
             * "Solicitar información" no trae aquí un botón "Enviar código"
             * aparte —ver includes/form-evento.php, $puedeEnviarCodigoCorreo—
             * porque pedir un código necesita el id de la actividad, y hasta
             * este punto no existía. Publicar es lo que lo crea, así que
             * publicar es también lo que manda el código, en la misma
             * petición: si se desmarcó "usar el correo de mi cuenta" y se
             * escribió uno, se manda ahora.
             */
            if (($e['accion_principal'] ?? '') === 'informacion'
                && empty($_POST['usar_correo_cuenta'])
                && trim((string) ($_POST['correo_contacto_nuevo'] ?? '')) !== ''
            ) {
                solicitarCodigoCorreoContacto($id, (string) $_POST['correo_contacto_nuevo'], $e['titulo']);
            }

            // Se relee para tener el slug que acaba de generarse y el nombre
            // del organizador (el JOIN de buscarEvento()), que es lo que
            // necesita el aviso a los administradores además de la ficha.
            $nuevoEvento = buscarEvento($id);
            if ($nuevoEvento !== null) {
                avisarAdminsNuevaActividad($nuevoEvento);
            }

            // Si se acaba de pedir un código, la ficha no tiene dónde
            // escribirlo —esa pantalla la trae "Editar"—. Si no, es la
            // ficha de siempre. Si por lo que sea no viniera $nuevoEvento,
            // las dos funciones se apañan con el id y la dirección sigue
            // llevando al sitio correcto.
            $destino = correoContactoPendiente($id) !== null
                ? urlEditarEvento($nuevoEvento ?? ['id' => $id])
                : urlEvento($nuevoEvento ?? ['id' => $id]);
            redirigir($destino);
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

$titulo = t('evento.nuevo.titulo');
$mapaInteractivo = true;
require __DIR__ . '/includes/layout.php';
?>

<div class="form-con-guia">
  <div class="auth-caja caja-ancha">
    <h1><?= et('evento.nuevo.titulo') ?></h1>
    <p class="sub"><?= et('evento.nuevo.sub') ?></p>

    <?php require __DIR__ . '/includes/aviso-errores.php'; ?>
    <?php if ($errores): ?>
      <script>whTrack('error_formulario', <?= json_encode(['form' => 'evento_nuevo', 'campos' => array_keys($errores)]) ?>);</script>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
      <?php $textoBoton = t('evento.nuevo.boton'); require __DIR__ . '/includes/form-evento.php'; ?>
    </form>
  </div>

  <?php require __DIR__ . '/includes/guia-accion.php'; ?>
</div>

<?php pie(); ?>
