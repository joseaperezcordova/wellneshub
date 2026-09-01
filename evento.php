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
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';
require_once __DIR__ . '/includes/busqueda.php';

$u  = usuarioActual();
$ev = buscarEvento((int) ($_GET['id'] ?? 0));

if (!$ev || !puedeVerEvento($ev, $u)) {
    http_response_code(404);
    $titulo = 'Actividad no encontrada';
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>Esa actividad no existe</h1>'
       . '<p class="sub">Puede que se haya borrado o que todavía no esté publicada.</p>'
       . '<a class="btn-principal" style="text-decoration:none; display:block; text-align:center;" href="' . URL_BASE . '/">Ver las que sí</a></div>';
    echo '<script>whTrack("404", ' . json_encode(['ruta' => (string) ($_SERVER['REQUEST_URI'] ?? '')]) . ');</script>';
    pie();
    exit;
}

/*
 * Dirección canónica: /actividad/{slug} (REQ-00006).
 *
 * Un mismo contenido servido en dos direcciones es contenido duplicado para
 * Google, que elige una por su cuenta —a veces la .php, justo la que no
 * queremos publicar—, y deja los enlaces repartidos entre las dos. Así solo hay
 * una, y las otras llevan a ella.
 *
 * Esto cubre DOS casos con el mismo código:
 *   · los enlaces viejos, /evento.php?id=7, que ya están compartidos y en el
 *     índice de Google;
 *   · las direcciones de una actividad a la que luego le cambiaron el título,
 *     porque al cambiar el título cambia el slug.
 *
 * VA DESPUÉS DE COMPROBAR QUIÉN PUEDE VERLA, y no antes. Redirigir primero
 * contaría el título de un borrador a cualquiera que probase números: la
 * redirección lleva el slug, y el slug es el título. Puesto aquí, quien no
 * puede verla recibe el 404 de arriba y no se entera de nada.
 *
 * Solo en GET: un POST redirigido se convierte en GET y pierde lo enviado, así
 * que los formularios de la propia ficha —publicar, ocultar, eliminar— dejarían
 * de funcionar sin decir por qué.
 */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $rutaPedida = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $urlCanonica = urlEvento($ev);

    if ($rutaPedida !== (string) parse_url($urlCanonica, PHP_URL_PATH)) {
        // El resto de parámetros se conserva: admin.php enlaza con
        // ?volver=admin y perderlo devolvería a quien modera al sitio
        // equivocado.
        $resto = $_GET;
        unset($resto['id']);
        $cola = $resto ? '?' . http_build_query($resto) : '';

        header('Location: ' . $urlCanonica . $cola, true, 301);
        exit;
    }
}

/* Para el <link rel="canonical"> del <head>. La ficha no pasa por rutasSitio()
   —no es una página fija—, así que layout.php no puede deducirla sola. */
$canonical = urlEvento($ev);

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
        // Antes de publicar: si quien manda es todavía 'visitante', esto lo va a
        // convertir en organizador (publicarEvento() hace ese ascenso). Se mira
        // aquí, no después, porque después ya no hay forma de saber cuál era su
        // rol un segundo antes.
        $eraVisitante = $u['rol'] === 'visitante';

        // Volver a publicar una actividad oculta deshace una decisión de moderación,
        // así que es cosa de un administrador. Sin esto, el dueño podría mandar
        // el POST a mano y saltarse por qué se ocultó en primer lugar.
        publicarEvento((int) $ev['id'], (int) $ev['usuario_id']);

        // El filtro de palabras se pasa DESPUÉS de publicar y no antes: no
        // bloquea nada, solo levanta la mano para que un administrador lo mire.
        // Ver palabrasVigiladas() en moderacion.php para el porqué.
        revisarAlPublicar($ev);

        $_SESSION['evento_aviso'] = '¡Publicado! Ya aparece en la portada.';
        $_SESSION['eventos_ga'] = [
            ['nombre' => 'publicar_actividad', 'params' => ['id' => (int) $ev['id'], 'categoria' => $ev['categoria']]],
        ];
        if ($eraVisitante && (int) $ev['usuario_id'] === (int) $u['id']) {
            $_SESSION['eventos_ga'][] = ['nombre' => 'alta_organizador', 'params' => []];
        }
        redirigir(urlEvento($ev));

    } elseif (isset($_POST['ocultar']) && esAdmin($u)) {
        cambiarSituacionEvento((int) $ev['id'], 'oculto');
        $_SESSION['evento_aviso'] = 'Actividad oculta. Ya no aparece en el listado.';
        redirigir(urlEvento($ev));

    } elseif (isset($_POST['eliminar'])) {
        // El dueño solo puede borrar mientras podría editar. Pasado ese plazo
        // borrar sería la puerta de atrás para saltarse la regla de las 24
        // horas: quitar la ficha y volver a subirla cambiada.
        if (!puedeEliminarEvento($ev, $u)) {
            $error = 'Ya pasó el plazo para borrar esta actividad. Pídeselo al administrador.';
        } else {
            eliminarEvento((int) $ev['id']);
            $_SESSION['evento_aviso'] = 'Actividad eliminada.';
            $_SESSION['eventos_ga'] = [
                ['nombre' => 'eliminar_actividad', 'params' => ['id' => (int) $ev['id'], 'categoria' => $ev['categoria']]],
            ];
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
$descripcion   = resumenParaMeta($ev['descripcion']);
$imagenOg      = urlImagen($ev['imagen_url']);
$scriptsPagina = ['assets/js/evento.js'];
require __DIR__ . '/includes/layout.php';
?>

<?php if ($ev['situacion'] === 'publicado'): ?>
  <!-- Solo la ficha ya publicada cuenta como "visualización de actividad": la
       vista previa de un borrador la recarga una y otra vez quien la escribe
       mientras la ajusta, y eso no es tráfico real. Mismo motivo por el que
       el marcado Schema.org va aquí adentro y no siempre: un borrador no
       tiene por qué anunciarse a Google como si ya existiera. -->
  <script>whTrack('ver_actividad', <?= json_encode(['id' => (int) $ev['id'], 'categoria' => $ev['categoria'], 'ciudad' => $ev['ciudad']]) ?>);</script>
  <?php /*
   * JSON_UNESCAPED_SLASHES NO va aquí: sin escapar "/", un título o
   * descripción que trajera literalmente "</script>" —el organizador
   * escribe ese texto libre, sin depurar— cerraría esta etiqueta y
   * ejecutaría lo que viniera después. json_encode() por defecto escribe
   * "\/" en su lugar, que un navegador nunca lee como cierre de etiqueta;
   * es la misma razón por la que las URL de aquí abajo se ven con barras
   * escapadas y no es un error.
   */ ?>
  <script type="application/ld+json"><?= json_encode(datosEstructuradosEvento($ev), JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>

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
          <?php
          /*
           * Editar ya no tiene plazo (REQ-000-XX): el organizador puede
           * corregir una actividad publicada en cualquier momento, así que
           * aquí no hay nada que contar sobre eso. Lo que SÍ sigue teniendo
           * plazo es ELIMINAR, y por eso es lo único que se avisa —para que
           * el botón "Eliminar" no desaparezca sin explicación cuando pasen
           * las horas—.
           */
          $quedanEliminar = minutosRestantesEliminacion($ev);
          ?>
          <?php if ($quedanEliminar > 0): ?>
            <strong>Publicada.</strong> Puedes eliminarla durante
            <?= $quedanEliminar >= 60 ? intdiv($quedanEliminar, 60) . ' h ' . ($quedanEliminar % 60) . ' min' : $quedanEliminar . ' min' ?> más.
          <?php elseif (esAdmin($u)): ?>
            <strong>Publicada.</strong> Eres administrador: puedes eliminarla aunque pasara el plazo.
          <?php else: ?>
            <strong>Publicada.</strong> Pasó el plazo para eliminarla; pídeselo al administrador si hace falta.
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

        <?php if (puedeEliminarEvento($ev, $u)): ?>
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
      <img src="<?= e($imagen) ?>" alt="<?= e($ev['titulo']) ?>" referrerpolicy="no-referrer" width="800" height="340">
    <?php endif; ?>
    <div class="ficha-fecha">
      <span class="d"><?= e($partes['d']) ?></span>
      <span class="m"><?= e($partes['m']) ?></span>
    </div>
  </div>

  <div class="ficha-cuerpo">
    <?php
    // Todas las que marcó el organizador, no solo la principal —esa es cosa
    // de la tarjeta de la portada, donde no cabe más que una—.
    $catalogoIconos  = categorias();
    $categoriasFicha = categoriasDeEvento((int) $ev['id']) ?: [$ev['categoria']];
    ?>
    <div class="ficha-cat"><?= e(implode(' · ', array_map(
        static fn(string $c): string => trim(($catalogoIconos[$c] ?? '') . ' ' . $c),
        $categoriasFicha
    ))) ?></div>
    <h1><?= e($ev['titulo']) ?></h1>

    <div class="ficha-compartir">
      <button type="button" class="btn-barra" id="btnCompartir"
              <?php /* La que se comparte es la limpia (REQ-00006): dice de qué
                       va antes de abrirla y no cuenta con qué está hecho el
                       sitio. */ ?>
              data-url="<?= e(urlEvento($ev)) ?>"
              data-titulo="<?= e($ev['titulo']) ?>">↗ Compartir</button>
      <span class="aviso-copiado" id="avisoCopiado">Enlace copiado.</span>
    </div>

    <div class="ficha-datos">
      <div class="dato">
        <span class="k">Cuándo</span>
        <span class="val">
          <?php if ($ev['tipo_actividad'] === 'recurrente'): ?>
            <?= e(frecuenciasRecurrencia()[$ev['frecuencia']] ?? '') ?>
            · <?= e(substr((string) $ev['hora_recurrente'], 0, 5)) ?>–<?= e(substr((string) $ev['hora_fin_recurrente'], 0, 5)) ?>
            <br><span class="tenue">
              Del <?= e(fechaCorta($ev['fecha_inicio'])) ?> al <?= e(fechaCorta($ev['fecha_fin'])) ?>
            </span>
          <?php elseif (terminaOtroDia($ev)): ?>
            <?php /* Un retiro: aquí las dos fechas completas sí hacen falta. */ ?>
            <?= e(fechaLarga($ev['fecha_inicio'])) ?>
            <br><span class="tenue">hasta el <?= e(fechaLarga($ev['fecha_fin'])) ?></span>
          <?php else: ?>
            <?php /* De un día: la fecha una vez y el horario debajo. Ver
                     terminaOtroDia() para por qué no se pregunta por fecha_fin. */ ?>
            <?= e(fechaCorta($ev['fecha_inicio'])) ?>
            <br><span class="tenue"><?= e(horarioDelDia($ev)) ?></span>
          <?php endif; ?>
        </span>
      </div>

      <div class="dato">
        <span class="k">Dónde</span>
        <span class="val">
          <?php if (!empty($ev['lugar'])): ?><?= e($ev['lugar']) ?><br><?php endif; ?>
          <?php if (!empty($ev['direccion'])): ?><span class="tenue"><?= e($ev['direccion']) ?></span><br><?php endif; ?>
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

      <?php if (!empty($ev['cupo_maximo'])): ?>
        <div class="dato">
          <span class="k">Cupo</span>
          <span class="val"><?= (int) $ev['cupo_maximo'] ?> personas</span>
        </div>
      <?php endif; ?>

      <?php if (!empty($ev['sitio_web'])): ?>
        <div class="dato">
          <span class="k">Más información</span>
          <span class="val">
            <a href="<?= e($ev['sitio_web']) ?>" target="_blank" rel="noopener nofollow">Ver sitio o perfil →</a>
          </span>
        </div>
      <?php endif; ?>
    </div>

    <?php /* El sitio del organizador es un enlace a otro dominio: lo cuenta
             solo el "seguimiento mejorado" de GA4 (clics salientes), que se
             activa en la propia consola de GA4, no aquí en el código.
             Boletos/reservar/contactar sí necesitan el whTrack() de abajo:
             pasan primero por salida.php o contactar.php, un salto dentro del
             propio sitio, y eso el seguimiento mejorado no lo ve como "salida". */ ?>
    <?php if ($ev['accion_principal'] === 'boletos' && !empty($ev['url_boletos'])): ?>
      <a class="btn-principal btn-boletos" href="<?= URL_BASE ?>/salida.php?id=<?= (int) $ev['id'] ?>&tipo=boletos"
         target="_blank" rel="noopener nofollow"
         onclick="whTrack('clic_boletos', <?= e(json_encode(['id' => (int) $ev['id']])) ?>)">Comprar boletos</a>

    <?php elseif ($ev['accion_principal'] === 'reservar' && !empty($ev['url_reserva'])): ?>
      <a class="btn-principal btn-boletos" href="<?= URL_BASE ?>/salida.php?id=<?= (int) $ev['id'] ?>&tipo=reservar"
         target="_blank" rel="noopener nofollow"
         onclick="whTrack('clic_reservar', <?= e(json_encode(['id' => (int) $ev['id']])) ?>)">Reservar mi lugar</a>

    <?php elseif ($ev['accion_principal'] === 'informacion'): ?>
      <a class="btn-principal btn-boletos" href="<?= URL_BASE ?>/contactar.php?id=<?= (int) $ev['id'] ?>"
         onclick="whTrack('clic_contactar', <?= e(json_encode(['id' => (int) $ev['id']])) ?>)">Contactar al organizador</a>
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
