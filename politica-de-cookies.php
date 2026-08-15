<?php
/**
 * Política de Cookies.
 *
 * Existe porque el pie la enlaza (REQ-00001). El texto legal lo redacta quien
 * asesore legalmente; lo que aporta el código —y ninguna plantilla genérica
 * puede saber— es qué cookies pone este sitio de verdad.
 *
 * Desde REQ-00003 esa lista es obligatoria y no cortesía: el requerimiento pide
 * expresamente documentar nombre, proveedor, finalidad, duración y categoría de
 * cada cookie, y mantener esta página al día con ellas.
 *
 * OJO CON LA COLUMNA DE DURACIÓN. Sale de la documentación de cada proveedor,
 * no de haberlas visto en el navegador. Es lo mejor que se puede afirmar antes
 * de que las tres herramientas estén encendidas en producción con tráfico real;
 * en cuanto lo estén hay que comprobarla y corregir lo que no coincida. Está
 * anotado en docs/pendientes.md.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$titulo      = t('pagina.cookies.titulo');
$descripcion = t('pagina.cookies.meta');
$anchoLibre  = true;

require __DIR__ . '/includes/layout.php';

/**
 * Una fila de la tabla. Se pinta con una función y no a mano porque son
 * catorce, y catorce <tr> escritos uno a uno es donde se cuela la que le falta
 * una celda.
 */
function filaCookie(string $nombre, string $proveedor, string $finalidad, string $duracion): void
{
    echo '<tr>'
       . '<td><code>' . e($nombre) . '</code></td>'
       . '<td>' . e($proveedor) . '</td>'
       . '<td>' . e($finalidad) . '</td>'
       . '<td>' . e($duracion) . '</td>'
       . '</tr>';
}
?>

<section class="wrap block">
  <div class="block-head">
    <div>
      <div class="eyebrow">Legal</div>
      <h2 style="margin-top:6px;">Política de Cookies</h2>
    </div>
  </div>

  <?php $avisoPendiente = 'La redacción legal debe revisarla quien asesore legalmente a OMDARA. El inventario de cookies de abajo sí es definitivo: sale del código.'; ?>
  <?php require __DIR__ . '/includes/aviso-pendiente.php'; ?>

  <div style="max-width:860px; font-size:15px; line-height:1.75;">
    <p>Una cookie es un archivo pequeño que un sitio guarda en tu navegador. OMDARA
       usa las mínimas para funcionar y, si tú lo autorizas, algunas más para
       entender cómo se usa la plataforma y medir sus campañas.</p>

    <p><strong>Nada que no sea estrictamente necesario se activa antes de que
       aceptes.</strong> Al entrar por primera vez verás un aviso con tres opciones:
       aceptar todas, rechazar las no necesarias o elegir por categoría. Mientras no
       respondas, solo funciona la cookie de sesión.</p>

    <p>Puedes cambiar de opinión cuando quieras:</p>

    <p style="margin:18px 0 30px;">
      <button type="button" class="btn-principal" data-cookies="configurar"
              style="display:inline-block; width:auto;"><?= et('cookies.abrir_preferencias') ?></button>
    </p>

    <h3 style="font-size:19px; margin-top:34px;">Necesarias</h3>
    <p style="color:var(--gris); font-size:14px;">Siempre activas. Sin ellas el sitio no
       funciona: no podrías iniciar sesión ni enviar un formulario. No requieren
       consentimiento y no se pueden desactivar desde aquí.</p>

    <div class="tabla-cookies-wrap">
      <table class="tabla-cookies">
        <thead>
          <tr><th>Nombre</th><th>Proveedor</th><th>Finalidad</th><th>Duración</th></tr>
        </thead>
        <tbody>
          <?php
          filaCookie('wh_sesion', 'OMDARA',
              'Mantiene tu sesión iniciada y protege los formularios frente a envíos falsificados.',
              'Hasta cerrar el navegador');
          filaCookie('omdara_cookies', 'OMDARA',
              'Guarda tu respuesta a este aviso, para no volver a preguntártela en cada página.',
              '6 meses');
          ?>
        </tbody>
      </table>
    </div>

    <h3 style="font-size:19px; margin-top:34px;">Analíticas</h3>
    <p style="color:var(--gris); font-size:14px;">Solo si las aceptas. Nos dicen qué
       páginas se visitan y dónde se atasca la gente, sin identificarte por nombre.
       Incluye <strong>Google Analytics 4</strong> (Google Ireland Ltd.) y
       <strong>Microsoft Clarity</strong> (Microsoft Corporation).</p>

    <div class="tabla-cookies-wrap">
      <table class="tabla-cookies">
        <thead>
          <tr><th>Nombre</th><th>Proveedor</th><th>Finalidad</th><th>Duración</th></tr>
        </thead>
        <tbody>
          <?php
          filaCookie('_ga', 'Google',
              'Distingue un navegador de otro para contar visitantes.', '2 años');
          filaCookie('_ga_<ID>', 'Google',
              'Mantiene el estado de la sesión de Google Analytics 4. El sufijo es el identificador del flujo de datos.',
              '2 años');
          filaCookie('_gcl_au', 'Google',
              'Mide qué clics vienen de un anuncio. Solo aparece si la cuenta se enlaza con Google Ads.',
              '3 meses');
          filaCookie('_clck', 'Microsoft',
              'Conserva el identificador de Clarity del navegador entre visitas.', '1 año');
          filaCookie('_clsk', 'Microsoft',
              'Une en una sola grabación las páginas que ves en una misma visita.', '1 día');
          filaCookie('CLID', 'Microsoft · clarity.ms',
              'Identifica el navegador la primera vez que carga Clarity. Se pone en el dominio de Microsoft, no en el nuestro.',
              '1 año');
          filaCookie('MUID, ANONCHK, SM', 'Microsoft · clarity.ms, bing.com',
              'Identificación entre servicios de Microsoft y sincronización de esos identificadores. Se ponen en dominios de Microsoft.',
              'MUID 1 año · ANONCHK 10 minutos · SM sesión');
          ?>
        </tbody>
      </table>
    </div>

    <h3 style="font-size:19px; margin-top:34px;">Marketing</h3>
    <p style="color:var(--gris); font-size:14px;">Solo si las aceptas. Sirven para medir
       si nuestros anuncios funcionan. Incluye <strong>Meta Pixel</strong>
       (Meta Platforms Ireland Ltd.).</p>

    <div class="tabla-cookies-wrap">
      <table class="tabla-cookies">
        <thead>
          <tr><th>Nombre</th><th>Proveedor</th><th>Finalidad</th><th>Duración</th></tr>
        </thead>
        <tbody>
          <?php
          filaCookie('_fbp', 'Meta',
              'Identifica el navegador para atribuir una visita a un anuncio. La pone el píxel en nuestro propio dominio.',
              '3 meses');
          filaCookie('_fbc', 'Meta',
              'Guarda el identificador del anuncio del que vienes. Solo aparece si llegas desde un enlace publicitario de Meta.',
              '3 meses');
          filaCookie('fr', 'Meta · facebook.com',
              'Entrega de publicidad y medición. Se pone en el dominio de Facebook, no en el nuestro.',
              '3 meses');
          ?>
        </tbody>
      </table>
    </div>

    <h3 style="font-size:19px; margin-top:34px;">Cómo desactivarlas</h3>
    <p>Desde el botón de más arriba puedes retirar el permiso a las categorías que
       hayas aceptado. Al hacerlo se borran las cookies que estén en el dominio de
       OMDARA y se recarga la página.</p>
    <p><strong>Las que un proveedor pone en su propio dominio</strong> —las de
       <code>clarity.ms</code>, <code>bing.com</code> y <code>facebook.com</code>
       marcadas arriba— no se pueden borrar desde este sitio. Para esas hay que usar
       la configuración de cookies de tu navegador.</p>
    <p>También puedes bloquear o borrar cookies desde tu navegador, incluidas las
       necesarias. Si bloqueas la cookie de sesión, <strong>no podrás iniciar sesión ni
       publicar una actividad</strong>: el sitio no tiene forma de recordar quién eres
       entre una página y la siguiente.</p>
  </div>
</section>

<?php pie(); ?>
