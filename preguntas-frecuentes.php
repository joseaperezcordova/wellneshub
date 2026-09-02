<?php
/**
 * Preguntas frecuentes.
 *
 * LAS RESPUESTAS DESCRIBEN LO QUE EL SITIO HACE HOY
 *
 * Siete de las que traía el requerimiento no coincidían con el código, y todas
 * en la misma dirección: daban por hecho una revisión previa que no existe.
 * Aquí no hay cola de aprobación —publicarEvento() pone la actividad en línea
 * en el momento en que su dueño le da a publicar— y la moderación es posterior:
 * alguien reporta, un administrador mira y, si toca, la oculta.
 *
 * Publicar «revisamos cada publicación, tarda entre 24 y 72 horas hábiles»
 * habría dejado a los organizadores esperando un correo que no llega, y a los
 * visitantes creyendo que alguien comprobó lo que están leyendo. Las respuestas
 * afectadas se reescribieron para decir lo que pasa de verdad; la redacción
 * original está en docs/pendientes.md, para restituirla el día que exista esa
 * revisión.
 *
 * ACORDEONES CON <details>, NO CON JAVASCRIPT
 *
 * Abren y cierran solos, el teclado ya sabe manejarlos, los lectores de
 * pantalla los anuncian como lo que son y el buscador del navegador encuentra
 * el texto de dentro aunque estén cerrados. Una pregunta frecuente que no se
 * puede encontrar con Ctrl+F no es de mucha ayuda.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';

$titulo      = t('pagina.faq.titulo');
$descripcion = t('pagina.faq.meta');
$anchoLibre  = true;

/*
 * La lista de categorías sale del catálogo y no escrita a mano. Escribirla aquí
 * significaría que cada categoría nueva deja esta página mintiendo un poco más,
 * y nadie va a acordarse de venir a corregirla.
 */
$categoriasTexto = implode(', ', array_keys(categoriasMenu()));

/**
 * Las preguntas, por bloques. En un array y no en el marcado para que añadir
 * una sea escribir dos líneas, y para que quien las revise pueda leerlas
 * seguidas sin HTML en medio.
 *
 * La respuesta admite HTML: varias necesitan un enlace, y mandar a alguien a
 * buscar «Publicar actividad» por su cuenta desde una respuesta que habla de
 * ella es trabajo de más.
 */
$bloques = [
    'Para usuarios' => [
        [
            /*
             * Texto del cliente, 2026-09-02: reemplaza la pregunta y respuesta
             * anteriores tal cual las mandó.
             */
            '¿Qué es Omdara?',
            'Omdara es un directorio de actividades y experiencias de bienestar que conecta a personas con '
            . 'organizadores.',
        ],
        [
            '¿Tiene algún costo usar la plataforma?',
            'No. Buscar y explorar actividades es gratuito.',
        ],
        [
            '¿Cómo me inscribo a una actividad?',
            'Cada organizador elige cómo recibir inscripciones. En la página de la actividad encontrarás un botón para '
            . 'solicitar información, reservar o comprar boletos, según corresponda.',
        ],
        [
            /* Texto del cliente, 2026-09-02: mismo criterio que la pregunta 1. */
            '¿Omdara organiza las actividades?',
            'No. Las actividades son organizadas y ofrecidas por terceros. Omdara funciona como una plataforma de '
            . 'conexión entre usuarios y organizadores.',
        ],
        [
            /* Nueva, texto del cliente, 2026-09-02. */
            '¿Puedo reservar o comprar una actividad en Omdara?',
            'Depende del organizador. Algunas actividades permiten solicitar información, reservar o comprar entradas '
            . 'mediante los canales indicados en cada publicación.',
        ],
        [
            /* Nueva, texto del cliente, 2026-09-02. */
            '¿Omdara garantiza las actividades publicadas?',
            'Omdara busca publicar información clara y verificable, pero no organiza ni presta los servicios '
            . 'ofrecidos por los organizadores.',
        ],
        [
            '¿Cómo sé si un evento sigue disponible?',
            'La información la proporciona el organizador. Te recomendamos confirmar directamente con él antes de asistir.',
        ],
        [
            '¿Puedo cancelar una reserva?',
            'Las cancelaciones y reembolsos dependen de las políticas de cada organizador. OMDARA no gestiona pagos ni '
            . 'reservas, así que no puede cancelarlas ni devolver un importe.',
        ],
        [
            /*
             * El requerimiento decía «encontrarás sus datos de contacto o el
             * botón». Los datos de contacto del organizador NO se publican
             * —REQ-00009 dice expresamente que su ficha de cuenta no es un
             * perfil público—, así que lo que hay es el botón.
             */
            '¿Cómo contacto al organizador?',
            'En la página de la actividad hay un botón para comunicarte. Si el organizador eligió «Contactar al '
            . 'organizador», se abre un formulario y tu mensaje le llega por correo, con tu dirección puesta para que '
            . 'pueda responderte directamente. No hace falta tener cuenta.',
        ],
        [
            '¿Puedo sugerir un evento o actividad?',
            'Sí. <a href="' . e(url('contacto')) . '">Escríbenos</a> para recomendar un evento o un organizador.',
        ],
        [
            /*
             * Requerimiento del cliente, «Configuración y uso de correos de
             * Omdara» (2026-09-02): FAQ → Problemas o contacto, con soporte@
             * para justo esta lista de casos (entrar, la cuenta, publicar o
             * editar, errores del sitio). Si el buzón todavía no está
             * configurado, se cae al formulario de /contacto en vez de
             * enseñar una pregunta sin respuesta.
             */
            '¿Tengo un problema técnico, qué hago?',
            '¿No puedes iniciar sesión, algo falla con tu cuenta, no logras publicar o editar una actividad, o '
            . 'encontraste un error dentro de la plataforma? '
            . (correoSoporte() !== ''
                ? 'Escríbenos a <a href="mailto:' . e(correoSoporte()) . '">' . e(correoSoporte()) . '</a>.'
                : '<a href="' . e(url('contacto')) . '">Escríbenos</a> y te ayudamos.'),
        ],
    ],

    'Para organizadores' => [
        [
            '¿Quién puede publicar actividades?',
            'Cualquier organizador, empresa o profesional que ofrezca experiencias relacionadas con el bienestar.',
        ],
        [
            '¿Publicar tiene costo?',
            'Durante la etapa beta, publicar actividades es gratuito.',
        ],
        [
            /*
             * Cambiado: no hay «enviar para revisión». El formulario guarda un
             * borrador, se ve la vista previa y publicar es una decisión del
             * propio organizador, que surte efecto en el momento.
             */
            '¿Cómo publico un evento?',
            'Crea una cuenta, completa el formulario de <a href="' . e(url('publicar')) . '">publicación</a> y verás una '
            . 'vista previa de tu actividad tal como la verá la gente. Desde ahí decides si publicarla. '
            . 'Se hace pública en ese momento.',
        ],
        [
            /*
             * Cambiado: la pregunta original —«¿Por qué mi evento debe ser
             * revisado?»— parte de algo que no ocurre. Se reformula para
             * responder la duda de fondo, que es si alguien vigila lo que se
             * publica.
             */
            '¿Revisan mi actividad antes de publicarla?',
            'No. Tu actividad se publica en cuanto la envías, sin esperar una aprobación. '
            . 'La revisión es posterior. Cualquier persona puede reportarla y, si no cumple con nuestros criterios '
            . 'o políticas, podremos retirarla.',
        ],
        [
            /*
             * Cambiado: no hay aprobación que esperar. La pregunta se queda
             * porque es la que la gente va a buscar, con la respuesta correcta.
             */
            '¿Cuánto tarda en aparecer mi actividad?',
            'Aparece de inmediato. En cuanto la publicas ya se puede encontrar en el buscador y compartir su enlace.',
        ],
        [
            /*
             * Cambiado: «antes o después de su publicación» es cierto solo a
             * medias, y la mitad que falta es la que genera el problema.
             */
            '¿Puedo editar mi evento?',
            'Sí, en cualquier momento: borrador o ya publicada, no hay plazo para corregirla. '
            . 'Lo que sí conserva un plazo es <em>eliminarla</em> —ver la siguiente pregunta—, '
            . 'que es distinto.',
        ],
        [
            '¿Qué tipo de actividades aceptan?',
            'Experiencias de bienestar en cualquiera de estas categorías: ' . e($categoriasTexto) . '. '
            . 'Si lo tuyo encaja en el bienestar y no ves su categoría, <a href="' . e(url('contacto')) . '">dínoslo</a>.',
        ],
        [
            '¿Puedo incluir un enlace para reservas o boletos?',
            'Sí. Al publicar eliges la acción principal de tu actividad: contactarte, comprar boletos o reservar lugar. '
            . 'En las dos últimas agregas el enlace que ya utilices —Eventbrite, Boletia, tu propio sitio, un formulario— '
            . 'y el botón de la ficha lleva ahí.',
        ],
        [
            /*
             * Cambiado: no existe «no aprobado». Lo que sí puede pasar es que
             * se oculte después, y eso es lo que se explica.
             */
            '¿Qué pasa si mi actividad se retira?',
            'Si una actividad publicada incumple las reglas, se oculta y te avisamos del motivo para que puedas '
            . 'corregirla. Dejar de estar visible no la borra: sigue en tu panel.',
        ],
        [
            /*
             * Cambiado: el panel del organizador no tiene «ocultar» —esa es una
             * acción de administración—. Lo que sí puede hacer su dueño es
             * eliminarla mientras esté dentro del plazo de eliminación
             * (REQ-000-XX separó este plazo del de editar, que ya no tiene).
             */
            '¿Cómo elimino un evento?',
            'Desde la página de tu actividad, mientras sigan sin pasar ' . EVENTO_MARGEN_ELIMINACION_H . ' horas '
            . 'desde que la publicaste. Después de ese plazo, '
            . '<a href="' . e(url('contacto')) . '">escríbenos</a> y la retiramos: es para que una ficha no '
            . 'desaparezca de golpe cuando ya hay gente que contaba con ella.',
        ],
    ],
];

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="block-head">
    <div>
      <div class="eyebrow">Ayuda</div>
      <h1 style="margin-top:6px;">Preguntas frecuentes</h1>
    </div>
  </div>

  <div class="faq">
    <?php foreach ($bloques as $bloque => $preguntas): ?>
      <h3 class="faq-bloque"><?= e($bloque) ?></h3>

      <?php foreach ($preguntas as [$pregunta, $respuesta]): ?>
        <details class="faq-item">
          <?php /* El «+» y el «−» los pone el CSS, no el marcado: un signo
                   escrito aquí lo leería en voz alta un lector de pantalla
                   —«más, ¿cómo publico un evento?»— cuando ya anuncia por su
                   cuenta si está abierto o cerrado. */ ?>
          <summary><?= e($pregunta) ?></summary>
          <div class="faq-respuesta"><?= $respuesta ?></div>
        </details>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>

  <div class="faq-pie">
    ¿No está tu pregunta? <a href="<?= e(url('contacto')) ?>">Escríbenos</a> y te contestamos.
  </div>
</section>

<?php pie(); ?>
