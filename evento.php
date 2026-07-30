<?php
/**
 * La ficha de la actividad. Es a la vez la página pública y la vista previa.
 *
 * Que sean la misma página no es por ahorrar archivos: es la única forma de que
 * la vista previa sea de fiar. Una pantalla de previsualización aparte se
 * desincroniza de la real en cuanto alguien cambia una de las dos, y entonces
 * enseña algo que no es lo que se va a publicar.
 *
 * Un borrador solo lo ve su dueño y el administrador. Para los demás no existe:
 * responde 404 y no 403, porque un 403 confirmaría que ahí hay algo.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/eventos.php';
require __DIR__ . '/includes/busqueda.php';

$u  = usuarioActual();
$ev = buscarEvento((int) ($_GET['id'] ?? 0));

if (!$ev || !puedeVerEvento($ev, $u)) {
    http_response_code(404);
    $titulo = 'Actividad no encontrada';
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>Esa actividad no existe</h1>'
       . '<p class="sub">Puede que se haya borrado o que todavía no esté publicada.</p>'
       . '<a class="btn-principal" style="text-decoration:none; display:block; text-align:center;" href="' . URL_BASE . '/">Ver las que sí</a></div>';
    pie();
    exit;
}

$esDueno = $u !== null && (int) $ev['usuario_id'] === (int) $u['id'];
$mando   = $esDueno || esAdmin($u);
$error   = '';

// ---- acciones sobre la actividad -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'La sesión caducó. Vuelve a intentarlo.';

    } elseif (!$mando) {
        http_response_code(403);
        exit('No puedes hacer eso.');

    } elseif (isset($_POST['publicar']) && ($ev['situacion'] !== 'oculto' || esAdmin($u))) {
        // Volver a publicar una actividad oculta deshace una decisión de moderación,
        // así que es cosa de un administrador. Sin esto, el dueño podría mandar
        // el POST a mano y saltarse por qué se ocultó en primer lugar.
        publicarEvento((int) $ev['id'], (int) $ev['usuario_id']);

        // El filtro de palabras se pasa DESPUÉS de publicar y no antes: no
        // bloquea nada, solo levanta la mano para que un administrador lo mire.
        // Ver palabrasVigiladas() en moderacion.php para el porqué.
        revisarAlPublicar($ev);

        $_SESSION['evento_aviso'] = '¡Publicado! Ya aparece en la portada.';
        redirigir('/evento.php?id=' . (int) $ev['id']);

    } elseif (isset($_POST['ocultar']) && esAdmin($u)) {
        cambiarSituacionEvento((int) $ev['id'], 'oculto');
        $_SESSION['evento_aviso'] = 'Actividad oculta. Ya no aparece en el listado.';
        redirigir('/evento.php?id=' . (int) $ev['id']);

    } elseif (isset($_POST['eliminar'])) {
        // El dueño solo puede borrar mientras podría editar. Pasado ese plazo
        // borrar sería la puerta de atrás para saltarse la regla de las 24
        // horas: quitar la ficha y volver a subirla cambiada.
        if (!puedeEditarEvento($ev, $u)) {
            $error = 'Ya pasó el plazo para borrar esta actividad. Pídeselo al administrador.';
        } else {
            eliminarEvento((int) $ev['id']);
            $_SESSION['evento_aviso'] = 'Actividad eliminada.';
            redirigir('/');
        }
    }
}

$aviso = '';
if (!empty($_SESSION['evento_aviso'])) {
    $aviso = (string) $_SESSION['evento_aviso'];
    unset($_SESSION['evento_aviso']);
}

$esBorrador = $ev['situacion'] === 'borrador';
$partes     = fechaPartes($ev['fecha_inicio']);

/*
 * A dónde vuelve el enlace de arriba.
 *
 * Si se llegó desde una búsqueda, la tarjeta trajo esa búsqueda pegada en el
 * parámetro «volver», y ahí se vuelve: con los mismos filtros puestos. Es lo
 * que evita el camino de siempre —filtrar, entrar a una actividad, volver y
 * tener que filtrar otra vez.
 *
 * No se usa lo que llega tal cual: urlVolverABuscar() lo deshace, lo valida
 * contra las listas de verdad y arma la dirección de nuevo. Así lo que acaba en
 * el enlace lo hemos escrito nosotros, y por ahí no se cuela un destino ajeno.
 *
 * El botón «atrás» del navegador hace lo mismo por su cuenta. Este enlace es
 * para cuando no lo hay: una pestaña nueva, un enlace compartido.
 *
 * Si se llegó desde el panel admin, «volver» no trae filtros de búsqueda sino
 * la palabra «admin»: no tendría sentido devolver ahí a alguien a la lista
 * pública de actividades cuando venía de gestionarlas.
 */
$volverAdmin = ($_GET['volver'] ?? '') === 'admin' && esAdmin($u);

if ($volverAdmin) {
    $volverA = URL_BASE . '/admin.php';
} else {
    $volverA = urlVolverABuscar(isset($_GET['volver']) ? (string) $_GET['volver'] : null);
}
$vieneDeBusqueda = !$volverAdmin && isset($_GET['volver']) && $_GET['volver'] !== '';

$titulo        = $ev['titulo'];
$scriptsPagina = ['assets/js/evento.js'];
require __DIR__ . '/includes/layout.php';
?>

<div class="ficha-envoltorio">
  <a class="volver" href="<?= e($volverA) ?>">← <?= $volverAdmin ? 'Volver al panel admin' : ($vieneDeBusqueda ? 'Volver a los resultados' : 'Ver todas las actividades') ?></a>
</div>

<?php if ($aviso): ?>
  <div class="ficha-envoltorio"><div class="aviso aviso-ok"><?= e($aviso) ?></div></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="ficha-envoltorio"><div class="aviso aviso-error"><?= e($error) ?></div></div>
<?php endif; ?>

<?php if ($mando): ?>
  <div class="barra-gestion <?= $esBorrador ? 'es-borrador' : '' ?>">
    <div class="barra-inner">
      <div class="barra-texto">
        <?php if ($esBorrador): ?>
          <strong>Vista previa.</strong> Así queda tu ficha. Todavía no la ve nadie más.
        <?php elseif ($ev['situacion'] === 'oculto'): ?>
          <strong>Oculta.</strong> No aparece en el listado.
        <?php else: ?>
          <?php $quedan = minutosRestantesEdicion($ev); ?>
          <?php if ($quedan > 0): ?>
            <strong>Publicada.</strong> Puedes corregirla durante
            <?= $quedan >= 60 ? intdiv($quedan, 60) . ' h ' . ($quedan % 60) . ' min' : $quedan . ' min' ?> más.
          <?php elseif (esAdmin($u)): ?>
            <strong>Publicada.</strong> Eres administrador: puedes editarla aunque pasara el plazo.
          <?php else: ?>
            <strong>Publicada.</strong> Pasó el plazo de edición; pídele los cambios al administrador.
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="barra-acciones">
        <?php if ($esBorrador): ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
            <button class="btn-barra destacado" type="submit" name="publicar" value="1">Publicar</button>
          </form>
        <?php elseif ($ev['situacion'] === 'oculto' && esAdmin($u)): ?>
          <!-- Ocultar es una decisión de moderación, así que deshacerla también
               lo es: el organizador no puede volver a publicar lo que un
               administrador retiró, aunque siga dentro de su plazo de edición. -->
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
            <button class="btn-barra destacado" type="submit" name="publicar" value="1">Volver a publicar</button>
          </form>
        <?php endif; ?>

        <?php if (puedeEditarEvento($ev, $u)): ?>
          <a class="btn-barra" href="<?= URL_BASE ?>/evento-editar.php?id=<?= (int) $ev['id'] ?>">Editar</a>
        <?php endif; ?>

        <?php if (esAdmin($u) && $ev['situacion'] === 'publicado'): ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
            <button class="btn-barra" type="submit" name="ocultar" value="1">Ocultar</button>
          </form>
        <?php endif; ?>

        <?php if (puedeEditarEvento($ev, $u)): ?>
          <form method="post" onsubmit="return confirm('¿Eliminar «<?= e(addslashes($ev['titulo'])) ?>»? No se puede deshacer.');">
            <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
            <button class="btn-barra peligro" type="submit" name="eliminar" value="1">Eliminar</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<article class="ficha">

  <div class="ficha-media" style="background-color:<?= e($ev['color']) ?>;">
    <?php $imagen = urlImagen($ev['imagen_url']); ?>
    <?php if ($imagen !== null): ?>
      <img src="<?= e($imagen) ?>" alt="" referrerpolicy="no-referrer">
    <?php endif; ?>
    <div class="ficha-fecha">
      <span class="d"><?= e($partes['d']) ?></span>
      <span class="m"><?= e($partes['m']) ?></span>
    </div>
  </div>

  <div class="ficha-cuerpo">
    <div class="ficha-cat"><?= e((categorias()[$ev['categoria']] ?? '') . ' ' . $ev['categoria']) ?></div>
    <h1><?= e($ev['titulo']) ?></h1>

    <div class="ficha-compartir">
      <button type="button" class="btn-barra" id="btnCompartir"
              data-url="<?= e(URL_BASE . '/evento.php?id=' . (int) $ev['id']) ?>"
              data-titulo="<?= e($ev['titulo']) ?>">↗ Compartir</button>
      <span class="aviso-copiado" id="avisoCopiado">Enlace copiado.</span>
    </div>

    <div class="ficha-datos">
      <div class="dato">
        <span class="k">Cuándo</span>
        <span class="val">
          <?= e(fechaLarga($ev['fecha_inicio'])) ?>
          <?php if (!empty($ev['fecha_fin'])): ?>
            <br><span class="tenue">hasta el <?= e(fechaLarga($ev['fecha_fin'])) ?></span>
          <?php endif; ?>
        </span>
      </div>

      <div class="dato">
        <span class="k">Dónde</span>
        <span class="val">
          <?php if (!empty($ev['lugar'])): ?><?= e($ev['lugar']) ?><br><?php endif; ?>
          <?= e($ev['ciudad'] . ', ' . $ev['entidad']) ?>
        </span>
      </div>

      <div class="dato">
        <span class="k">Precio</span>
        <span class="val precio-grande <?= !empty($ev['gratuito']) ? 'gratis' : '' ?>">
          <?= e(precioTexto($ev)) ?>
        </span>
      </div>

      <div class="dato">
        <span class="k">Organiza</span>
        <span class="val"><?= e($ev['organizador']) ?></span>
      </div>
    </div>

    <?php if ($ev['accion_principal'] === 'boletos' && !empty($ev['url_boletos'])): ?>
      <a class="btn-principal btn-boletos" href="<?= e($ev['url_boletos']) ?>"
         target="_blank" rel="noopener nofollow">Comprar boletos</a>

    <?php elseif ($ev['accion_principal'] === 'reservar' && !empty($ev['url_reserva'])): ?>
      <a class="btn-principal btn-boletos" href="<?= e($ev['url_reserva']) ?>"
         target="_blank" rel="noopener nofollow">Reservar mi lugar</a>

    <?php elseif ($ev['accion_principal'] === 'informacion'): ?>
      <div class="ficha-contacto">
        <a class="btn-principal btn-boletos" href="<?= URL_BASE ?>/contactar.php?id=<?= (int) $ev['id'] ?>">Contactar al organizador</a>
        <?php if (!empty($ev['whatsapp_contacto'])): ?>
          <?php $textoWa = rawurlencode('Hola, vi tu actividad "' . $ev['titulo'] . '" en Rueda y quería preguntarte algo.'); ?>
          <a class="btn-whatsapp" href="https://wa.me/<?= e($ev['whatsapp_contacto']) ?>?text=<?= e($textoWa) ?>"
             target="_blank" rel="noopener nofollow">Escribir por WhatsApp</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="ficha-desc"><?= nl2br(e($ev['descripcion'])) ?></div>

    <?php
    /*
     * El mapa va DEBAJO de la descripción, no arriba con los demás datos.
     *
     * Quien abre una ficha decide primero si le interesa la actividad y solo
     * después mira dónde cae. Un mapa entre el título y el texto se come el
     * sitio de lo que hay que leer para tomar esa decisión.
     *
     * Es OpenStreetMap: no pide clave ni tarjeta. El botón sí lleva a Google
     * Maps, que es lo que la gente tiene en el teléfono para conducir.
     */
    if (eventoTienePunto($ev)):
        $lat = (float) $ev['latitud'];
        $lng = (float) $ev['longitud'];
    ?>
      <div class="ficha-mapa">
        <iframe src="<?= e(urlMapaEmbebido($lat, $lng)) ?>"
                title="Mapa con la ubicación de <?= e($ev['titulo']) ?>"
                loading="lazy" referrerpolicy="no-referrer"></iframe>
        <a class="btn-comollegar" href="<?= e(urlComoLlegar($lat, $lng)) ?>"
           target="_blank" rel="noopener">Cómo llegar →</a>
      </div>
    <?php endif; ?>

    <?php if ($ev['situacion'] === 'publicado'): ?>
      <!-- Sin cuenta: quien se topa con una estafa no se registra para avisarnos.
           Pedir cuenta aquí no filtra bots —esos sí se registran—, filtra
           personas. Discreto al pie, que es donde se busca cuando hace falta. -->
      <div class="ficha-reportar">
        <a href="<?= URL_BASE ?>/reportar.php?id=<?= (int) $ev['id'] ?>">Reportar esta actividad</a>
      </div>
    <?php endif; ?>
  </div>
</article>

<?php pie(); ?>
