<?php
/**
 * Edición de una actividad.
 *
 * El permiso lo decide puedeEditarEvento(): el administrador siempre, y el
 * dueño siempre —sin plazo, desde REQ-000-XX—. Si $puede sale en false aquí
 * es porque quien mira no es ni una cosa ni la otra —está viendo la ficha
 * pública de la actividad de alguien más y llegó a esta URL a mano—, no
 * porque se le haya acabado un margen de tiempo.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';

$u  = exigirSesion();
$ev = buscarEvento((int) ($_GET['id'] ?? 0));

// Se comprueba VER antes que editar. Sin esto, quien abriera el editor de un
// borrador ajeno recibía la pantalla de "ya no se puede editar" con el título
// dentro, que es filtrar el contenido de una actividad que nadie ha publicado.
if ($ev && !puedeVerEvento($ev, $u)) {
    $ev = null;
}

if (!$ev) {
    http_response_code(404);
    $titulo = t('evento.editar.no_encontrada_titulo');
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>' . et('evento.editar.no_encontrada_h1') . '</h1>'
       . '<p class="sub">' . et('evento.editar.no_encontrada_texto') . '</p>'
       . '<a class="btn-principal" style="text-decoration:none; display:block; text-align:center;" href="' . URL_BASE . '/">' . et('evento.editar.volver_inicio') . '</a></div>';
    echo '<script>whTrack("404", ' . json_encode(['ruta' => (string) ($_SERVER['REQUEST_URI'] ?? '')]) . ');</script>';
    pie();
    exit;
}

// Para que el selector de idioma de la cabecera se quede en este mismo
// formulario en vez de mandar al inicio (urlEquivalente(), includes/idioma.php).
$GLOBALS['urlEquivalente'] = [
    'es' => urlEditarEvento($ev, 'es'),
    'en' => urlEditarEvento($ev, 'en'),
];

$puede = puedeEditarEvento($ev, $u);

$e       = $ev;
$errores = [];

// Correo de contacto de esta actividad (migración 24): fuera del formulario
// grande a propósito —ver includes/correo-contacto-evento.php—, así que sus
// tres acciones se comprueban antes de entrar al bloque del formulario
// grande, y ese bloque se queda como «ninguna de las tres».
$avisoCorreoContacto = '';
$errorCorreoContacto = '';

if ($puede && postDesbordado()) {
    $errores['general'] = t('evento.error.imagen_pesada');

} elseif ($puede && $_SERVER['REQUEST_METHOD'] === 'POST' && !csrfValido($_POST['csrf'] ?? null)) {
    $errores['general'] = t('evento.error.sesion_caducada');
    $e = $_POST;
    $e['imagen_url'] = imagenArrastrada($_POST['imagen_previa'] ?? null, $ev['imagen_url']);

} elseif ($puede && isset($_POST['enviar_codigo_correo'])) {
    [$ok, $msg] = solicitarCodigoCorreoContacto((int) $ev['id'], (string) ($_POST['correo_contacto_nuevo'] ?? ''), $ev['titulo']);
    if ($ok) { $avisoCorreoContacto = $msg; } else { $errorCorreoContacto = $msg; }

} elseif ($puede && isset($_POST['confirmar_codigo_correo'])) {
    [$ok, $msg] = confirmarCodigoCorreoContacto((int) $ev['id'], (string) ($_POST['codigo_correo_contacto'] ?? ''));
    if ($ok) {
        $avisoCorreoContacto = $msg;
        $ev = buscarEvento((int) $ev['id']) ?? $ev;   // para que $e traiga el correo_contacto ya puesto
        $e  = $ev;
    } else {
        $errorCorreoContacto = $msg;
    }

} elseif ($puede && isset($_POST['cancelar_codigo_correo'])) {
    cancelarCodigoCorreoContacto((int) $ev['id']);

} elseif ($puede && isset($_POST['quitar_codigo_correo'])) {
    quitarCorreoContactoEvento((int) $ev['id']);
    $ev = buscarEvento((int) $ev['id']) ?? $ev;
    $e  = $ev;

} elseif ($puede && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // La que ya tiene guardada. No mandar archivo significa «déjala como está»,
    // no «bórrala».
    $imagenPrevia = $ev['imagen_url'];

    [$e, $errores] = validarEvento($_POST);

    [$e['imagen_url'], $errorImagen] = imagenDelFormulario($_POST, $_FILES, $imagenPrevia);
    if ($errorImagen !== null) {
        $errores['imagen'] = $errorImagen;
    }

    if (!nombreOrganizadorValido($_POST)) {
        $errores['org_nombre'] = t('evento.error.falta_organizador');
    }

    if (!$errores && eventoDuplicado((int) $ev['usuario_id'], $e['entidad'], $e['ciudad'], $e['categoria'], $e['fecha_inicio'], (int) $ev['id'])) {
        $errores['general'] = sprintf(t('evento.error.duplicado'), $e['categoria'], $e['ciudad'], $e['entidad']);
    }

    if (!$errores) {
        actualizarEvento($e, (int) $ev['id']);
        olvidarImagenEnVuelo($e['imagen_url']);

        // La anterior se borra solo cuando el cambio ya está guardado. Al
        // revés, un fallo al actualizar dejaría la ficha apuntando a un
        // archivo que ya no existe.
        if ($imagenPrevia !== null && $e['imagen_url'] !== $imagenPrevia) {
            borrarImagenGuardada($imagenPrevia);
        }

        // El mismo formulario trae la sección de contacto del organizador
        // (REQ-00012), así que también aquí se guarda: corregir un teléfono
        // mal escrito es justo lo que se viene a hacer a «editar».
        guardarContactoOrganizador((int) $u['id'], $_POST);

        $_SESSION['evento_aviso'] = t('evento.editar.cambios_guardados');
        $_SESSION['eventos_ga'] = [
            ['nombre' => 'editar_actividad', 'params' => ['id' => (int) $ev['id'], 'categoria' => $e['categoria']]],
        ];

        // Si se llegó aquí desde el panel admin, esa procedencia se lleva
        // a la ficha para que su enlace de vuelta apunte al mismo sitio.
        $volver = (string) ($_GET['volver'] ?? '');
        // «?volver» y no «&volver»: la dirección limpia no lleva ya el
        // «?id=» al que aquel se enganchaba.
        redirigir(urlEvento($ev)
            . ($volver !== '' ? '?volver=' . urlencode($volver) : ''));
    }

    /*
     * Si algo falló, la foto recién subida SE QUEDA y el formulario vuelve a
     * salir con ella puesta. Antes se borraba y se volvía a enseñar la
     * antigua, con lo que el cambio de imagen se perdía sin avisar: parecía
     * que no se había llegado a elegir ninguna.
     *
     * La guardada de la actividad no se toca hasta que el cambio esté escrito.
     */
}

$titulo = t('evento.editar.titulo');
$mapaInteractivo = true;
require __DIR__ . '/includes/layout.php';
?>

<?php if (!$puede): ?>

<div class="auth-caja caja-ancha">

  <h1><?= et('evento.editar.no_puede_titulo') ?></h1>
  <p class="sub">«<?= e($ev['titulo']) ?>»</p>

  <div class="aviso aviso-error">
    <?= et('evento.editar.no_puede_aviso') ?>
  </div>

  <p style="font-size:14px; line-height:1.6;">
    <?= et('evento.editar.no_puede_texto') ?>
  </p>

  <a class="btn-principal" style="text-decoration:none; display:block; text-align:center;"
     href="<?= e(urlEvento($ev)) ?>"><?= et('evento.editar.volver_ficha') ?></a>

</div>

<?php else: ?>

<div class="form-con-guia">
  <div class="auth-caja caja-ancha">

    <?php $volverAdmin = ($_GET['volver'] ?? '') === 'admin' && esAdmin($u); ?>
    <a class="volver" href="<?= e($volverAdmin ? URL_BASE . '/admin.php' : urlEvento($ev)) ?>">← <?= $volverAdmin ? et('evento.editar.volver_admin') : et('evento.editar.volver_ficha') ?></a>

    <h1><?= et('evento.editar.titulo') ?></h1>
    <?php if ($ev['situacion'] === 'borrador'): ?>
      <p class="sub"><?= et('evento.editar.borrador_sub') ?></p>
    <?php elseif ($ev['situacion'] === 'oculto'): ?>
      <p class="sub"><?= et('evento.editar.oculto_sub') ?></p>
    <?php else: ?>
      <p class="sub">
        <?= et('evento.editar.publicado_sub') ?> <?= e(fechaLarga($ev['actualizado_en'])) ?>.
      </p>
    <?php endif; ?>

    <?php require __DIR__ . '/includes/aviso-errores.php'; ?>
    <?php if ($errores): ?>
      <script>whTrack('error_formulario', <?= json_encode(['form' => 'evento_editar', 'campos' => array_keys($errores)]) ?>);</script>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
      <?php $textoBoton = t('evento.editar.boton'); require __DIR__ . '/includes/form-evento.php'; ?>
    </form>

    <?php
    $correoPendiente = correoContactoPendiente((int) $ev['id']);
    $correoEfectivo  = correoContactoEvento($ev);
    $tieneOverride   = trim((string) ($ev['correo_contacto'] ?? '')) !== '';
    require __DIR__ . '/includes/correo-contacto-evento.php';
    ?>

    <?php /* Mismas comprobaciones que ya hace evento.php al recibir el POST
             —permiso de admin para republicar, plazo para borrar—: estos botones
             mandan ahí en vez de duplicar esa lógica aquí. */ ?>
    <div class="barra-acciones" style="margin-top:18px;">
      <?php if ($ev['situacion'] === 'oculto' && esAdmin($u)): ?>
        <form method="post" action="<?= e(urlEvento($ev)) ?>">
          <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
          <button class="btn-barra destacado" type="submit" name="publicar" value="1"><?= et('evento.editar.volver_publicar') ?></button>
        </form>
      <?php endif; ?>

      <?php /* Guardado aparte de $puede (que ya solo mide EDITAR): desde que
               los dos permisos se separaron, alguien puede poder editar y ya
               no poder eliminar —publicada hace más de EVENTO_MARGEN_ELIMINACION_H
               horas—, y mostrar este botón igual lo mandaría a un error en
               vez de simplemente no ofrecérselo. */ ?>
      <?php if (puedeEliminarEvento($ev, $u)): ?>
        <form method="post" action="<?= e(urlEvento($ev)) ?>"
              onsubmit="return confirm(<?= json_encode(sprintf(t('evento.editar.confirmar_eliminar'), $ev['titulo'])) ?>);">
          <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
          <button class="btn-barra peligro" type="submit" name="eliminar" value="1"><?= et('evento.editar.eliminar') ?></button>
        </form>
      <?php endif; ?>
    </div>

  </div>

  <?php require __DIR__ . '/includes/guia-accion.php'; ?>
</div>

<?php endif; ?>

<?php pie(); ?>
