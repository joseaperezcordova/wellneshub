<?php
/**
 * Términos y Condiciones (REQ-00014).
 *
 * EL TEXTO NO SE TOCA
 *
 * Llega redactado, y el criterio de aceptación lo dice con todas las letras:
 * «el contenido se muestra completo y sin modificaciones». Aquí no se corrige
 * la redacción como se hizo en las preguntas frecuentes o en «¿Cómo
 * funciona?» —allí el texto describía cómo funciona el software y no
 * coincidía; esto obliga a la empresa y lo redacta quien responde de ello—.
 *
 * ACTUALIZADO CON EL TEXTO DEL CLIENTE, REQ. 17092026 PUNTO 7
 *
 * Reemplaza entero el texto anterior (14 Agosto, 2026, once cláusulas) y
 * cierra el pendiente 2ñ de docs/pendientes.md: las dos cosas que el sitio ya
 * hacía y los TyC no decían —el plazo de 24 horas para retirar una actividad
 * y que la moderación es posterior, no previa— ya están aquí, en la cláusula
 * nueva «Revisión y moderación de publicaciones». Se comprobó de nuevo que
 * ninguna cláusula contradice al código.
 *
 * Público, sin sesión: no hay exigirSesion() ni lo puede haber. Se aceptan al
 * darse de alta (REQ-00008), así que hay que poder leerlos ANTES de tener
 * cuenta.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

// Punto 7 de docs/pendientes.md: ver el porqué en redirigirSiEsDirecto().
redirigirSiEsDirecto(url('terminos'));

$titulo      = t('pagina.terminos.titulo');
$descripcion = t('pagina.terminos.meta');
$anchoLibre  = true;

$legalTitulo      = 'Términos y Condiciones';
$legalActualizado = '17 Septiembre, 2026';

/**
 * Las cláusulas, tal como llegaron.
 *
 * En un array y no en el marcado por tres razones: se leen seguidas al
 * revisarlas, la numeración sale de la posición —insertar una cláusula no
 * obliga a renumerar las de abajo, que es como acaban dos con el mismo
 * número—, y quien tenga que cotejarlas con el documento firmado no tiene que
 * saltarse HTML por medio.
 *
 * Cada una es [título, uno o más párrafos].
 */
$clausulas = [
    [
        'Aceptación de los términos',
        ['Al usar la plataforma OMDARA, el usuario acepta estos Términos y Condiciones.'],
    ],
    [
        'Objeto de la plataforma',
        [
            'OMDARA es un directorio que conecta a usuarios con organizadores de actividades y '
            . 'experiencias relacionadas con el bienestar. OMDARA no organiza, presta ni supervisa '
            . 'directamente los servicios ofrecidos por los organizadores.',

            'Las actividades publicadas deben tener una relación clara con el propósito de OMDARA y '
            . 'presentar información clara, veraz y suficiente para que las personas puedan decidir si '
            . 'desean participar.',
        ],
    ],
    [
        'Registro de organizadores',
        [
            'El organizador declara que la información publicada es verídica, suficiente y no '
            . 'engañosa, y que cuenta con los permisos, autorizaciones, licencias o requisitos que, en '
            . 'su caso, sean necesarios para ofrecer la actividad.',

            'El organizador es responsable de que su actividad cumpla con la legislación aplicable y '
            . 'con estos Términos y Condiciones.',
        ],
    ],
    [
        'Actividades y contenido permitido',
        [
            'Pueden publicarse actividades relacionadas, entre otras, con:',
            '• Movimiento y actividad física.',
            '• Yoga, meditación y relajación.',
            '• Bienestar emocional y desarrollo personal.',
            '• Talleres y experiencias de autocuidado.',
            '• Terapias y prácticas de bienestar, cuando estén debidamente descritas.',
            '• Actividades de naturaleza, descanso y conexión.',
            '• Experiencias de comunidad y bienestar.',
            '• Talleres, encuentros y experiencias que tengan una relación clara con el propósito de OMDARA.',

            'La inclusión de una actividad en OMDARA no implica que la plataforma avale, certifique o '
            . 'garantice al organizador, la actividad, sus resultados o las afirmaciones realizadas por '
            . 'este.',
        ],
    ],
    [
        'Contenido no permitido',
        [
            'No se permite publicar actividades o contenido que:',
            '• No tengan relación con el propósito de OMDARA.',
            '• Contengan información falsa, engañosa o deliberadamente incompleta.',
            '• Promuevan discriminación, acoso o violencia.',
            '• Presenten afirmaciones de salud o resultados garantizados de manera engañosa.',
            '• Puedan representar un riesgo indebido para los participantes.',
            '• Infrinjan la legislación aplicable.',
            '• Incluyan, como parte de la actividad, el consumo, administración o uso de sustancias '
            . 'psicoactivas, psicodélicas, alucinógenas o de efectos farmacológicos significativos, '
            . 'incluyendo, entre otras, ayahuasca, 5-MeO-DMT ("sapo"), hongos psilocibios y otras '
            . 'sustancias de naturaleza similar.',
            '• Promuevan o incluyan prácticas que impliquen la administración de sustancias o '
            . 'tratamientos médicos que requieran supervisión profesional especializada y que no '
            . 'correspondan al propósito de OMDARA.',
            '• Contengan contenido sexual explícito o contenido que no corresponda al propósito de la '
            . 'plataforma.',
            '• Utilicen OMDARA para promocionar productos o servicios que no correspondan a su línea de '
            . 'contenido.',
            '• Infrinjan derechos de propiedad intelectual, imagen, privacidad u otros derechos de '
            . 'terceros.',
        ],
    ],
    [
        'Revisión y moderación de publicaciones',
        [
            'Las actividades pueden publicarse automáticamente y quedar sujetas a revisión posterior.',

            'OMDARA podrá revisar las actividades publicadas para verificar que cumplan con estos '
            . 'Términos y Condiciones, sus políticas y los criterios aplicables a la plataforma.',

            'Si OMDARA detecta que una actividad incumple estos términos o criterios, podrá, según '
            . 'corresponda:',
            '• Solicitar al organizador que corrija o complete información.',
            '• Modificar temporalmente la visibilidad de la actividad.',
            '• Retirar la actividad de la plataforma.',
            '• Suspender o limitar la cuenta del organizador.',
            '• Tomar otras medidas razonables conforme a sus políticas y a la legislación aplicable.',

            'La publicación automática de una actividad no constituye una aprobación, certificación ni '
            . 'validación por parte de OMDARA.',
        ],
    ],
    [
        'Responsabilidad de los organizadores',
        [
            'Cada organizador es responsable del contenido de sus publicaciones, precios, horarios, '
            . 'condiciones de participación, reservas, pagos, cancelaciones, cambios, permisos '
            . 'necesarios y calidad de sus actividades.',

            'También es responsable de informar de manera adecuada cualquier condición, requisito, '
            . 'contraindicación, riesgo o limitación relevante para la participación en la actividad.',
        ],
    ],
    [
        'Responsabilidad de OMDARA',
        [
            'OMDARA funciona como plataforma de conexión y no garantiza la realización de las '
            . 'actividades ni responde por accidentes, incumplimientos, cancelaciones, daños, pérdidas '
            . 'o resultados derivados de la relación entre los usuarios y los organizadores, salvo '
            . 'cuando la legislación aplicable establezca lo contrario.',

            'La publicación de una actividad no significa que OMDARA la recomiende, certifique o '
            . 'garantice.',
        ],
    ],
    [
        'Contacto con organizadores',
        [
            'Al enviar un formulario para contactar a un organizador, el usuario autoriza que los '
            . 'datos proporcionados sean compartidos con dicho organizador para que pueda responder a '
            . 'su solicitud.',

            'El organizador será responsable del tratamiento de los datos que reciba directamente a '
            . 'través de la plataforma, de conformidad con la legislación aplicable.',
        ],
    ],
    [
        'Propiedad intelectual',
        [
            'El contenido de la plataforma pertenece a OMDARA o a sus respectivos titulares y no puede '
            . 'copiarse, reproducirse, distribuirse o utilizarse sin la autorización correspondiente.',

            'El organizador debe contar con los derechos o autorizaciones necesarios sobre las '
            . 'imágenes, textos, marcas y demás materiales que publique en OMDARA.',
        ],
    ],
    [
        'Suspensión de cuentas y retiro de publicaciones',
        [
            'OMDARA podrá retirar publicaciones, modificar su visibilidad, limitar funcionalidades o '
            . 'suspender cuentas cuando exista incumplimiento de estos Términos y Condiciones, de las '
            . 'políticas de la plataforma o de la legislación aplicable.',

            'Estas medidas podrán adoptarse como resultado de una revisión posterior, un reporte '
            . 'recibido por OMDARA o cualquier otra circunstancia que permita identificar un posible '
            . 'incumplimiento.',
        ],
    ],
    [
        'Modificaciones',
        ['OMDARA podrá actualizar estos Términos y Condiciones cuando resulte necesario. La versión '
         . 'vigente será la publicada en la plataforma.'],
    ],
    [
        'Legislación aplicable y jurisdicción competente',
        [
            'Los presentes Términos y Condiciones se rigen e interpretan de conformidad con las '
            . 'leyes vigentes de los Estados Unidos Mexicanos.',

            'Para cualquier controversia relacionada con la interpretación, cumplimiento, '
            . 'ejecución o validez de estos Términos y Condiciones, el usuario y OMDARA se '
            . 'someten expresamente a la jurisdicción de los tribunales competentes de La Paz, '
            . 'Baja California Sur, México, renunciando a cualquier otro fuero que pudiera '
            . 'corresponderles en razón de su domicilio presente o futuro.',

            'Lo anterior será aplicable sin perjuicio de los derechos irrenunciables que las '
            . 'leyes mexicanas otorguen a los consumidores cuando resulten aplicables.',
        ],
    ],
];

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block legal">
  <?php require __DIR__ . '/includes/legal-cabecera.php'; ?>

  <ol class="legal-clausulas">
    <?php foreach ($clausulas as [$encabezado, $parrafos]): ?>
      <li>
        <h2><?= e($encabezado) ?></h2>
        <?php foreach ($parrafos as $parrafo): ?>
          <p><?= e($parrafo) ?></p>
        <?php endforeach; ?>
      </li>
    <?php endforeach; ?>
  </ol>

  <?php /* Los otros dos documentos, desde aquí: quien lee uno suele querer
           mirar el siguiente, y volver al pie a buscarlo es un viaje de más. */ ?>
  <div class="legal-otros">
    <a href="<?= e(url('privacidad')) ?>">Aviso de Privacidad</a>
    <a href="<?= e(url('cookies')) ?>">Política de Cookies</a>
  </div>
</section>

<?php pie(); ?>
