<?php
/**
 * Aviso de Privacidad (REQ-00015).
 *
 * EL TEXTO NO SE TOCA
 *
 * Segundo de los tres documentos legales que llega redactado, y con el mismo
 * criterio de aceptación que los Términos: «el contenido se muestra completo y
 * sin modificaciones». Obliga a la empresa frente a los titulares de los datos
 * y lo redacta quien responde de ello. Va tal cual, incluidas las comillas y la
 * grafía de «omdara» donde el documento la escribe en minúsculas.
 *
 * LO QUE ESTE AVISO CUBRE Y LO QUE EL SITIO HACE NO COINCIDEN DEL TODO
 *
 * El documento describe el tratamiento del formulario «Contactar Organizador».
 * El sitio, además de eso, guarda correo e identificador de Google de quien se
 * registra, códigos de acceso, direcciones IP de quien reporta o escribe, y los
 * datos del formulario de /contacto; y desde REQ-00008 este aviso hay que
 * aceptarlo al crear cuenta, un momento que la cláusula 8 no menciona.
 *
 * No he añadido nada —no me toca, y un aviso de privacidad ampliado por quien
 * programa no es un aviso de privacidad—. El inventario completo de lo que el
 * sitio guarda hoy, que estaba en esta misma página mientras no había texto,
 * está en docs/pendientes.md para quien tenga que cerrarlo.
 *
 * Público, sin sesión, y por la misma razón que los Términos: se acepta al
 * darse de alta, así que hay que poder leerlo ANTES de tener cuenta.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

// Punto 7 de docs/pendientes.md: ver el porqué en redirigirSiEsDirecto().
redirigirSiEsDirecto(url('privacidad'));

$titulo      = t('pagina.privacidad.titulo');
$descripcion = t('pagina.privacidad.meta');
$anchoLibre  = true;

$legalTitulo      = 'Aviso de Privacidad';
$legalActualizado = '13 de agosto de 2026';

/** El párrafo de entrada, anterior a la primera cláusula. */
$entradilla = 'Omdara (en adelante, "la Plataforma") reconoce la importancia de proteger los '
    . 'datos personales de sus usuarios y se compromete a tratarlos de conformidad con la '
    . 'legislación aplicable en materia de protección de datos personales.';

/**
 * Las cláusulas, tal como llegaron.
 *
 * Mismo formato que terminos-y-condiciones.php: [título, bloques]. Un bloque es
 * un texto —un párrafo— o un array de textos —una lista—; este documento sí
 * trae listas y aquel no.
 *
 * En un array y no en el marcado por lo de siempre: se leen seguidas al
 * revisarlas, la numeración sale de la posición —insertar una cláusula no
 * obliga a renumerar las de abajo, que es como acaban dos con el mismo número—,
 * y quien tenga que cotejarlas con el documento firmado no tiene que saltarse
 * HTML por medio.
 */
$clausulas = [
    [
        'Datos personales que recabamos',
        [
            'Cuando utilices el formulario "Contactar Organizador", podremos solicitar los '
            . 'siguientes datos:',
            [
                'Nombre.',
                'Correo electrónico.',
                'Número telefónico o WhatsApp (si aplica).',
                'Mensaje o consulta.',
                'Cualquier otra información que decidas proporcionar voluntariamente.',
            ],
        ],
    ],
    [
        'Finalidad del tratamiento',
        [
            'Los datos personales serán utilizados para:',
            [
                'Enviar tu solicitud o consulta al organizador del evento, actividad o '
                . 'experiencia de tu interés.',
                'Permitir que el organizador se comunique contigo para responder tus dudas o '
                . 'brindar información.',
                'Dar seguimiento a la solicitud realizada.',
                'Mejorar el funcionamiento y la calidad de la Plataforma.',
            ],
        ],
    ],
    [
        'Transferencia de datos',
        [
            'Al enviar el formulario, aceptas que los datos proporcionados sean compartidos con '
            . 'el organizador del evento o actividad correspondiente, exclusivamente para '
            . 'atender tu solicitud de contacto.',

            'Omdara no vende ni comercializa tus datos personales con terceros.',
        ],
    ],
    [
        'Conservación de la información',
        ['Los datos personales serán conservados únicamente durante el tiempo necesario para '
         . 'cumplir con las finalidades descritas en este aviso o para atender obligaciones '
         . 'legales aplicables.'],
    ],
    [
        'Derechos del titular',
        ['Podrás solicitar el acceso, rectificación, cancelación u oposición al tratamiento de '
         . 'tus datos personales, así como revocar el consentimiento otorgado, enviando una '
         . 'solicitud al correo electrónico de contacto de omdara.'],
    ],
    [
        'Seguridad',
        ['Omdara implementa medidas razonables de seguridad para proteger la información contra '
         . 'pérdida, uso indebido, acceso no autorizado o alteración.'],
    ],
    [
        'Cambios al aviso de privacidad',
        ['Este Aviso de Privacidad podrá modificarse en cualquier momento. Las actualizaciones '
         . 'serán publicadas dentro de la Plataforma y surtirán efectos a partir de su '
         . 'publicación.'],
    ],
    [
        'Consentimiento',
        ['Al enviar el formulario "Contactar Organizador", manifiestas haber leído y aceptado el '
         . 'presente Aviso de Privacidad y autorizas el tratamiento y la transferencia de tus '
         . 'datos al organizador correspondiente para atender tu solicitud.'],
    ],
];

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block legal">
  <?php require __DIR__ . '/includes/legal-cabecera.php'; ?>

  <p class="legal-entrada"><?= e($entradilla) ?></p>

  <ol class="legal-clausulas">
    <?php foreach ($clausulas as [$encabezado, $bloques]): ?>
      <li>
        <h2><?= e($encabezado) ?></h2>
        <?php foreach ($bloques as $bloque): ?>
          <?php if (is_array($bloque)): ?>
            <ul>
              <?php foreach ($bloque as $punto): ?>
                <li><?= e($punto) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p><?= e($bloque) ?></p>
          <?php endif; ?>
        <?php endforeach; ?>
      </li>
    <?php endforeach; ?>
  </ol>

  <?php /* Los otros dos documentos, desde aquí: quien lee uno suele querer
           mirar el siguiente, y volver al pie a buscarlo es un viaje de más. */ ?>
  <div class="legal-otros">
    <a href="<?= e(url('terminos')) ?>">Términos y Condiciones</a>
    <a href="<?= e(url('cookies')) ?>">Política de Cookies</a>
  </div>
</section>

<?php pie(); ?>
