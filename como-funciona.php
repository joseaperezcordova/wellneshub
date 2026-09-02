<?php
/**
 * ¿Cómo funciona? — la guía para organizadores (REQ-00013).
 *
 * Se llega desde el menú de arriba y desde el pie, y es la página que convierte
 * a alguien que mira en alguien que publica. Por eso termina —dos veces— en el
 * botón de publicar.
 *
 * EL PASO 2 NO ES «ENVIAR A REVISIÓN»
 *
 * El requerimiento describía un segundo paso de revisión previa —«envía tu
 * actividad a revisión», «nuestro equipo verificará… antes de hacerla
 * visible», «una vez aprobada»— y eso no es lo que hace el sitio:
 * publicarEvento() la pone en línea en el momento en que su dueño le da a
 * publicar. La moderación es posterior, cuando alguien reporta.
 *
 * Prometerlo aquí es peor que en las preguntas frecuentes: esta página se lee
 * ANTES de publicar, así que quien la crea espera un correo de aprobación,
 * no revisa que su actividad ya está pública, y descubre el error cuando le
 * escribe la primera persona. El paso 2 describe el que sí existe —la vista
 * previa—, que además es el punto donde de verdad se decide publicar.
 *
 * Está anotado en docs/pendientes.md junto con lo mismo de las FAQ, para
 * restituir la redacción original el día que exista esa revisión.
 *
 * EL BOTÓN DE PUBLICAR NO COMPRUEBA LA SESIÓN, Y NO HACE FALTA
 *
 * Va a /publicar-una-actividad y ya está. evento-nuevo.php llama a
 * exigirSesion(), que guarda a dónde iba y manda al login; al terminar
 * —entrando o dándose de alta— destinoTrasLogin() devuelve aquí. Comprobarlo
 * también en este enlace sería una segunda copia de la regla, y la copia es la
 * que un día deja de coincidir. La puerta la guarda el servidor, no la forma
 * del enlace.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

// Punto 7 de docs/pendientes.md: ver el porqué en redirigirSiEsDirecto().
redirigirSiEsDirecto(url('como-funciona'));

$titulo      = t('pagina.como_funciona.titulo');
$descripcion = t('pagina.como_funciona.meta');
$anchoLibre  = true;

/**
 * Los cuatro pasos. En un array para que se lean seguidos al revisarlos y para
 * que la numeración salga de su posición: insertar uno en medio no obliga a
 * renumerar los de abajo a mano, que es como acaban dos pasos con el mismo
 * número.
 */
$pasos = [
    [
        'Publica tu actividad',
        'Crea una cuenta y completa el formulario con la información de tu actividad: fotografías, '
        . 'descripción, fecha, horario, ubicación, precio y datos de contacto. Publicar una actividad '
        . 'en Omdara es gratis para los organizadores.',
    ],
    [
        /*
         * Cambiado. El requerimiento decía «Envía tu actividad a revisión» y
         * aquí no hay revisión previa. Este es el segundo paso de verdad, y es
         * el que importa: nada se publica sin pasar por él.
         */
        'Revísala en la vista previa',
        'Antes de publicar nada verás tu actividad tal como la verá la gente, con sus fotos, su mapa '
        . 'y su botón. Desde ahí decides si publicarla o volver a corregirla. Nada se hace público '
        . 'hasta que tú lo dices.',
    ],
    [
        /*
         * Cambiado: «una vez aprobada» daba por hecha una aprobación que no
         * existe, y sobre todo hacía esperar.
         */
        'Llega a nuevas personas',
        'En cuanto la publicas aparece en Omdara y ya se puede encontrar en el buscador, filtrar por '
        . 'ciudad o categoría y compartir con su propio enlace. Sin esperas.',
    ],
    [
        'Recibe contactos directamente',
        'Las personas interesadas podrán contactarte a través del formulario o de los canales de '
        . 'contacto que hayas proporcionado. Tú gestionas directamente con cada participante la '
        . 'disponibilidad, reservación, pago y demás detalles de la actividad.',
    ],
];

/** Los motivos, tal como los enumera el requerimiento. */
$motivos = [
    'Publicación gratuita',
    'Mayor visibilidad para tu actividad',
    'Contacto directo con personas interesadas',
    'Tú gestionas las reservas y los pagos',
    'Sin intermediarios',
];

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="block-head">
    <div>
      <div class="eyebrow">Para organizadores</div>
      <h1 style="margin-top:6px;">Publica tu actividad gratis</h1>
    </div>
  </div>

  <p class="guia-intro">En Omdara puedes publicar tus actividades sin costo y llegar a nuevas
     personas que buscan experiencias de bienestar.</p>

  <h3 class="guia-titulo">¿Por qué publicar en Omdara?</h3>
  <ul class="guia-motivos">
    <?php foreach ($motivos as $motivo): ?>
      <li><?= e($motivo) ?></li>
    <?php endforeach; ?>
  </ul>

  <?php /* La numeración sale de la posición en el array y no escrita a mano:
           insertar un paso en medio no debería obligar a renumerar los de
           abajo, que es como se acaba con dos pasos «3». */ ?>
  <ol class="guia-pasos">
    <?php foreach ($pasos as $i => [$titulo_paso, $texto]): ?>
      <li>
        <span class="guia-num" aria-hidden="true"><?= $i + 1 ?></span>
        <div>
          <h4><?= e($titulo_paso) ?></h4>
          <p><?= e($texto) ?></p>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>

  <div class="guia-acciones">
    <a class="btn-principal btn-cta" href="<?= e(url('publicar')) ?>">Publicar una actividad</a>
  </div>
</section>

<?php /* Cierre a dos voces: hasta aquí la página habla a quien organiza, y este
         bloque recoge también a quien solo venía a mirar. Va en franja de color
         para que se lea como un final y no como un apartado más. */ ?>
<section class="guia-cierre">
  <div class="wrap">
    <h2>Da visibilidad a tu experiencia de bienestar.</h2>
    <p>Publica tu actividad y conecta con personas que buscan nuevas experiencias.</p>

    <div class="guia-acciones">
      <a class="btn-cta btn-cta-claro" href="<?= e(url('actividades')) ?>">Explorar actividades</a>
      <a class="btn-cta btn-cta-hueco" href="<?= e(url('publicar')) ?>">Publicar una actividad</a>
    </div>
  </div>
</section>

<?php pie(); ?>
