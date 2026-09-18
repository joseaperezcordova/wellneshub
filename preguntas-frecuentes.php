<?php
/**
 * Preguntas frecuentes.
 *
 * TEXTO DEL CLIENTE, REQ. 17092026 PUNTO 6
 *
 * Reemplaza entero el bloque anterior (que ya venía corregido a mano porque un
 * requerimiento todavía más viejo daba por hecha una revisión previa que
 * nunca existió —ver docs/pendientes.md, pendiente 2l, cerrado por este mismo
 * documento—). Este texto ya viene confirmado por el cliente: publicación
 * automática, sin cola de aprobación, con revisión posterior conforme a
 * políticas y criterios.
 *
 * Los enlaces, el correo de soporte dinámico y la lista de categorías del
 * catálogo NO son del documento —ahí van en texto corrido—, pero SÍ son el
 * patrón ya usado en esta misma página: escribir la ciudad o el correo a mano
 * es una cosa más que se desactualiza sola.
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

// Punto 7 de docs/pendientes.md: ver el porqué en redirigirSiEsDirecto().
redirigirSiEsDirecto(url('faq'));

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
            '¿Omdara organiza las actividades?',
            'No. Las actividades son organizadas y ofrecidas por terceros. Omdara funciona como una plataforma de '
            . 'conexión entre usuarios y organizadores.',
        ],
        [
            '¿Puedo reservar o comprar una actividad en Omdara?',
            'Depende del organizador. Algunas actividades permiten solicitar información, reservar o comprar entradas '
            . 'mediante los canales indicados en cada publicación.',
        ],
        [
            '¿Omdara garantiza las actividades publicadas?',
            'Omdara busca publicar información clara y verificable, pero no organiza ni presta los servicios '
            . 'ofrecidos por los organizadores.',
        ],
        [
            '¿Cómo sé si una actividad sigue disponible?',
            'La información la proporciona el organizador. Te recomendamos confirmar directamente con él antes de '
            . 'asistir.',
        ],
        [
            '¿Puedo cancelar una reserva?',
            'Las cancelaciones y reembolsos dependen de las políticas de cada organizador. Omdara no gestiona pagos '
            . 'ni reservas, así que no puede cancelarlas ni devolver un importe.',
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
            '¿Cómo publico una actividad?',
            'Crea una cuenta, completa el formulario de <a href="' . e(url('publicar')) . '">publicación</a> y verás una '
            . 'vista previa de tu actividad tal como la verá la gente. Desde ahí decides si publicarla. '
            . 'Se hace pública en ese momento.',
        ],
        [
            '¿Revisan mi actividad antes de publicarla?',
            'No. Tu actividad se publica en cuanto la envías, sin esperar una aprobación. '
            . 'La revisión es posterior. Cualquier persona puede reportarla y, si no cumple con nuestros criterios '
            . 'o políticas, podremos tomar medidas sobre ella.',
        ],
        [
            '¿Cuánto tarda en aparecer mi actividad?',
            'Aparece de inmediato. En cuanto la publicas ya se puede encontrar en el buscador y compartir su enlace.',
        ],
        [
            '¿Qué tipo de actividades aceptan?',
            'Omdara acepta actividades y experiencias relacionadas con el bienestar, en cualquiera de estas '
            . 'categorías: ' . e($categoriasTexto) . '. Si tu actividad está relacionada con el bienestar y no '
            . 'encuentras una categoría adecuada, <a href="' . e(url('contacto')) . '">dínoslo</a>.',
        ],
        [
            /*
             * Texto del cliente en lista, tal cual —también aparece, con la
             * misma redacción, en como-funciona.php y en terminos-y-condiciones.php.
             * No se comparte un solo array entre las tres porque cada
             * documento lo dictó por separado y no hay garantía de que sigan
             * coincidiendo palabra por palabra en la próxima actualización.
             */
            '¿Qué actividades no se pueden publicar?',
            'No se pueden publicar actividades que:'
            . '<ul>'
            . '<li>No tengan relación con el propósito de Omdara.</li>'
            . '<li>Contengan información falsa, engañosa o deliberadamente incompleta.</li>'
            . '<li>Promuevan discriminación, acoso o violencia.</li>'
            . '<li>Presenten afirmaciones de salud o resultados garantizados de manera engañosa.</li>'
            . '<li>Puedan representar un riesgo indebido para los participantes.</li>'
            . '<li>Infrinjan la legislación aplicable.</li>'
            . '<li>Incluyan el consumo, administración o uso de sustancias psicoactivas, psicodélicas, '
            . 'alucinógenas o de efectos farmacológicos significativos, incluyendo ayahuasca, 5-MeO-DMT '
            . '(«sapo»), hongos psilocibios u otras sustancias de naturaleza similar.</li>'
            . '<li>Incluyan la administración de sustancias o tratamientos médicos que requieran supervisión '
            . 'profesional especializada y que no correspondan al propósito de Omdara.</li>'
            . '<li>Contengan contenido sexual explícito o contenido que no corresponda al propósito de la '
            . 'plataforma.</li>'
            . '<li>Utilicen Omdara para promocionar productos o servicios que no correspondan a su línea de '
            . 'contenido.</li>'
            . '</ul>',
        ],
        [
            '¿Qué información debo proporcionar al publicar?',
            'La información de la actividad debe ser clara, veraz y suficiente para que las personas puedan '
            . 'entender en qué consiste y decidir si desean participar.',
        ],
        [
            '¿Puedo editar mi actividad?',
            'Sí, en cualquier momento, tanto si está en borrador como si ya está publicada. No hay un plazo para '
            . 'corregir o actualizar la información de una actividad.',
        ],
        [
            '¿Puedo incluir un enlace para reservas o boletos?',
            'Sí. Al publicar eliges la acción principal de tu actividad: contactarte, comprar boletos o reservar lugar. '
            . 'En las dos últimas agregas el enlace que ya utilices —Eventbrite, Boletia, tu propio sitio, un formulario, '
            . 'etc.— y el botón de la ficha lleva ahí.',
        ],
        [
            '¿Qué pasa si alguien reporta mi actividad?',
            'Un reporte no significa automáticamente que la actividad será retirada. Omdara revisará el caso para '
            . 'determinar si existe un incumplimiento de los criterios de publicación, las políticas o los '
            . '<a href="' . e(url('terminos')) . '">Términos y Condiciones</a>.',
        ],
        [
            '¿Qué pasa si mi actividad incumple los criterios?',
            'Si una actividad publicada incumple los criterios de publicación, las políticas o los '
            . '<a href="' . e(url('terminos')) . '">Términos y Condiciones</a>, Omdara podrá ocultarla o retirarla '
            . 'de la plataforma y avisarte del motivo cuando corresponda. Dejar de estar visible no significa '
            . 'necesariamente que la actividad se haya eliminado de tu cuenta: puede permanecer en tu panel para '
            . 'consulta o corrección.',
        ],
        [
            /*
             * Migración 26 (Req. 17092026 punto 8): retirar ya no borra nada,
             * solo oculta. ELIMINAR ya no es una acción del organizador —ni
             * siquiera cuando administración interviene—, así que la
             * respuesta no puede prometer una eliminación real; explica que
             * "retirar" es lo que hay, aunque la pregunta (tal como la
             * escribe la gente, y tal como la trae el requerimiento) siga
             * diciendo "elimino".
             */
            '¿Cómo retiro o elimino una actividad?',
            'Puedes modificar la información de tu actividad en cualquier momento desde su página de gestión. '
            . 'Si necesitas retirarla de Omdara, puedes hacerlo directamente desde la página de gestión durante '
            . 'las primeras ' . EVENTO_MARGEN_RETIRO_H . ' horas después de publicarla. Después de ese plazo, '
            . '<a href="' . e(url('contacto')) . '">escríbenos</a> para solicitar su retiro. Este plazo busca '
            . 'evitar que una actividad desaparezca repentinamente cuando las personas ya pueden haberla '
            . 'consultado o planeado asistir.',
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
