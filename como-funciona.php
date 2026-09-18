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
 * Un requerimiento anterior describía un segundo paso de revisión previa
 * —«envía tu actividad a revisión», «nuestro equipo verificará… antes de
 * hacerla visible», «una vez aprobada»— y eso nunca fue lo que hace el sitio:
 * publicarEvento() la pone en línea en el momento en que su dueño le da a
 * publicar. La moderación es posterior, cuando alguien reporta.
 *
 * El documento del cliente "Req. 17092026" (puntos 1, 5 y 6) LO CONFIRMA por
 * escrito: publicación automática, sin cola de aprobación, con revisión
 * posterior conforme a políticas y criterios. Cierra el pendiente 2l de
 * docs/pendientes.md —ya no hace falta restituir ninguna redacción anterior—.
 * Los cuatro pasos y las preguntas frecuentes de aquí abajo son el texto tal
 * como lo mandó el cliente en ese documento.
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
        'Revísala en la vista previa',
        'Antes de publicar, podrás ver tu actividad tal como la verá la gente, con sus fotografías, '
        . 'mapa e información de contacto. Desde ahí puedes corregir la información antes de hacerla '
        . 'pública.',
    ],
    [
        'Llega a nuevas personas',
        'En cuanto la publiques, tu actividad aparecerá en Omdara y podrá encontrarse mediante el '
        . 'buscador, los filtros por ciudad o categoría y su propio enlace. No necesitas esperar una '
        . 'aprobación previa.',
    ],
    [
        'Recibe contactos directamente',
        'Las personas interesadas podrán contactarte a través del formulario o de los canales de '
        . 'contacto que hayas proporcionado. Tú gestionas directamente con cada participante la '
        . 'disponibilidad, reservación, pago y demás detalles de la actividad.',
    ],
];

/*
 * Preguntas frecuentes de esta misma página (Req. 17092026 punto 5) —
 * distintas de las de preguntas-frecuentes.php: aquí son las que le importan
 * a alguien que está a punto de publicar, sobre todo la de si hace falta
 * aprobación previa (no) y qué se puede y no se puede publicar.
 *
 * La respuesta admite HTML: dos mencionan los Términos y Condiciones y llevan
 * su enlace, mismo criterio que preguntas-frecuentes.php.
 */
$faqOrganizador = [
    [
        '¿Qué tipo de actividades puedo publicar?',
        'Puedes publicar actividades y experiencias relacionadas con el bienestar, como movimiento y '
        . 'actividad física, yoga, meditación, relajación, bienestar emocional, desarrollo personal, '
        . 'autocuidado, terapias y prácticas de bienestar, actividades de naturaleza, descanso, '
        . 'conexión, comunidad, talleres y encuentros. La actividad debe tener una relación clara con '
        . 'el propósito de Omdara y contar con información suficiente para que las personas puedan '
        . 'entender en qué consiste y decidir si desean participar.',
    ],
    [
        '¿Qué actividades no se pueden publicar?',
        'No corresponden a Omdara las actividades que no tengan relación con el propósito de la '
        . 'plataforma, que contengan información falsa o engañosa, promuevan discriminación, acoso o '
        . 'violencia, presenten afirmaciones de salud o resultados garantizados de manera engañosa, '
        . 'puedan representar un riesgo indebido para los participantes o infrinjan la legislación '
        . 'aplicable. Tampoco se permiten actividades que incluyan el consumo, administración o uso de '
        . 'sustancias psicoactivas, psicodélicas, alucinógenas o de efectos farmacológicos '
        . 'significativos, como ayahuasca, 5-MeO-DMT («sapo»), hongos psilocibios u otras sustancias de '
        . 'naturaleza similar. También quedan fuera las actividades que impliquen tratamientos o '
        . 'administración de sustancias que requieran supervisión profesional especializada y que no '
        . 'correspondan al propósito de Omdara, así como el contenido sexual explícito y la promoción '
        . 'de productos o servicios que no correspondan a la línea de la plataforma.',
    ],
    [
        '¿Mi actividad necesita ser aprobada antes de publicarse?',
        'No. Las actividades pueden publicarse automáticamente una vez que completes el proceso de '
        . 'publicación. Omdara puede revisarlas posteriormente para verificar que cumplan con los '
        . 'criterios de publicación, las políticas de la plataforma y los '
        . '<a href="' . e(url('terminos')) . '">Términos y Condiciones</a>.',
    ],
    [
        '¿Qué pasa si mi actividad no cumple con los criterios de publicación?',
        'Si después de su publicación se detecta que una actividad incumple los criterios de Omdara, '
        . 'la plataforma podrá modificar su visibilidad, retirarla o tomar otras medidas conforme a sus '
        . 'políticas y ' . '<a href="' . e(url('terminos')) . '">Términos y Condiciones</a>.',
    ],
    [
        '¿Omdara revisa todas las actividades antes de publicarlas?',
        'No. Omdara no realiza una aprobación manual previa de cada actividad. La publicación es '
        . 'automática y puede existir una revisión posterior. Por eso, como organizador, eres '
        . 'responsable de proporcionar información clara, veraz y suficiente sobre tu actividad y de '
        . 'asegurarte de que cumple con los criterios de publicación.',
    ],
    [
        '¿Quién gestiona las reservas y los pagos?',
        'Tú. Omdara funciona como plataforma de descubrimiento y conexión. El organizador gestiona '
        . 'directamente con las personas interesadas la disponibilidad, reservación, pago, cambios, '
        . 'cancelaciones y demás condiciones de la actividad.',
    ],
    [
        '¿Omdara cobra comisión por las actividades?',
        'No. Publicar una actividad en Omdara es gratuito y actualmente Omdara no cobra una comisión '
        . 'por las reservas o pagos que gestiones directamente con los participantes.',
    ],
    [
        '¿Puedo modificar mi actividad después de publicarla?',
        'Sí. Puedes actualizar la información de tu actividad cuando sea necesario. Es importante '
        . 'mantener actualizados datos como fecha, horario, precio, ubicación, disponibilidad y '
        . 'condiciones de participación.',
    ],
    [
        '¿Qué pasa si mi actividad cambia o se cancela?',
        'Si la actividad cambia, debes actualizar la información publicada para que las personas '
        . 'encuentren datos correctos. Si la actividad se cancela, debes actualizarla o retirarla según '
        . 'corresponda y comunicar la cancelación a las personas que ya hayan contactado contigo o '
        . 'reservado directamente contigo.',
    ],
    [
        '¿Qué responsabilidad tengo como organizador?',
        'Como organizador eres responsable de que la información publicada sea verdadera, suficiente y '
        . 'esté actualizada, así como de la organización y prestación de la actividad, las condiciones '
        . 'de participación, las reservas, los pagos y la atención a las personas participantes. Omdara '
        . 'facilita el descubrimiento y contacto, pero no organiza ni presta directamente las '
        . 'actividades publicadas por terceros.',
    ],
    [
        '¿Qué pasa si alguien reporta mi actividad?',
        'Los reportes pueden ser revisados posteriormente por Omdara. Si se determina que una '
        . 'actividad incumple los criterios de publicación, las políticas de la plataforma o los '
        . 'Términos y Condiciones, Omdara podrá modificar su visibilidad, retirarla o tomar las medidas '
        . 'correspondientes. Un reporte por sí solo no significa que la actividad sea retirada '
        . 'automáticamente.',
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

  <?php /* Mismo marcado <details> que preguntas-frecuentes.php: acordeón sin
           JavaScript, encontrable con Ctrl+F aunque esté cerrado. */ ?>
  <h3 class="guia-titulo" style="margin-top:48px;">Preguntas frecuentes</h3>
  <div class="faq">
    <?php foreach ($faqOrganizador as [$pregunta, $respuesta]): ?>
      <details class="faq-item">
        <summary><?= e($pregunta) ?></summary>
        <div class="faq-respuesta"><?= $respuesta ?></div>
      </details>
    <?php endforeach; ?>
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
