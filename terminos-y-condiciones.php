<?php
/**
 * Términos y Condiciones (REQ-00014).
 *
 * EL TEXTO NO SE TOCA
 *
 * Es la primera de las tres páginas legales que llega redactada, y el criterio
 * de aceptación lo dice con todas las letras: «el contenido se muestra completo
 * y sin modificaciones». Aquí no se corrige la redacción como se hizo en las
 * preguntas frecuentes o en «¿Cómo funciona?» —allí el texto describía cómo
 * funciona el software y no coincidía; esto obliga a la empresa y lo redacta
 * quien responde de ello—.
 *
 * Se comprobó que ninguna cláusula contradice al código, que era el riesgo
 * real: no promete revisión previa, no promete gestionar pagos, y lo que dice
 * de compartir datos con el organizador es exactamente lo que hace el
 * formulario de contacto. Lo que sí falta —el plazo de 24 horas para corregir y
 * el modelo de moderación posterior— queda anotado en docs/pendientes.md, para
 * que lo decida quien asesora y no quien programa.
 *
 * Público, sin sesión: no hay exigirSesion() ni lo puede haber. Se aceptan al
 * darse de alta (REQ-00008), así que hay que poder leerlos ANTES de tener
 * cuenta.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$titulo      = t('pagina.terminos.titulo');
$descripcion = t('pagina.terminos.meta');
$anchoLibre  = true;

$legalTitulo      = 'Términos y Condiciones';
$legalActualizado = '14 Agosto, 2026';

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
        ['Al usar la plataforma, el usuario acepta estos TyC.'],
    ],
    [
        'Objeto de la plataforma',
        ['OMDARA es un directorio que conecta usuarios con organizadores de experiencias de '
         . 'bienestar; no organiza ni presta directamente los servicios.'],
    ],
    [
        'Registro de organizadores',
        ['El organizador declara que la información publicada es verídica y que cuenta con los '
         . 'permisos necesarios para ofrecer sus actividades.'],
    ],
    [
        'Responsabilidad de los organizadores',
        ['Cada organizador es responsable del contenido, precios, horarios, cancelaciones y '
         . 'calidad de sus eventos.'],
    ],
    [
        'Responsabilidad de OMDARA',
        ['La plataforma no garantiza la realización de los eventos ni responde por accidentes, '
         . 'incumplimientos o daños derivados de ellos.'],
    ],
    [
        'Contacto con organizadores',
        ['Al enviar un formulario, el usuario autoriza que sus datos se compartan con el '
         . 'organizador para responder su solicitud.'],
    ],
    [
        'Contenido prohibido',
        ['No se permite publicar información falsa, ilegal, ofensiva o que infrinja derechos de '
         . 'terceros.'],
    ],
    [
        'Propiedad intelectual',
        ['El contenido de la plataforma pertenece a OMDARA o a sus respectivos titulares; no '
         . 'puede copiarse sin autorización.'],
    ],
    [
        'Suspensión de cuentas',
        ['OMDARA puede eliminar publicaciones o suspender cuentas que incumplan los TyC.'],
    ],
    [
        'Modificaciones',
        ['OMDARA podrá actualizar los TyC y publicará la versión vigente.'],
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

  <?php require __DIR__ . '/includes/legal-pie.php'; ?>
</section>

<?php pie(); ?>
