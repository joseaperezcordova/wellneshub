<?php
/**
 * Política de Cookies (REQ-00016).
 *
 * EL TEXTO NO SE TOCA
 *
 * Tercero y último de los documentos legales que llega redactado, con el mismo
 * criterio que los otros dos: se publica el contenido aprobado, tal cual,
 * incluidas las comillas rectas de la cláusula 8 y la grafía de «omdara» en
 * minúsculas donde el documento la escribe así.
 *
 * LA TABLA DE COOKIES YA NO ES PARTE DE LA POLÍTICA
 *
 * Hasta hoy esta página llevaba catorce cookies con nombre, proveedor y
 * duración, y decía que ese inventario era definitivo. No lo era: las de
 * OMDARA sí salen del código, pero las de Google, Microsoft y Meta salían de la
 * documentación de cada proveedor y el conjunto real depende de cómo se instale
 * cada herramienta. La cláusula 7 del texto aprobado resuelve esto por su
 * cuenta —remite el detalle al mecanismo de gestión de cookies—, así que la
 * tabla baja a un anexo informativo, separado del documento y marcado como lo
 * que es: pendiente de comprobar contra la instalación real.
 *
 * EL BOTÓN DE PREFERENCIAS SOLO SE PINTA SI HAY ALGO QUE CONFIGURAR
 *
 * El panel lo monta consentimiento.js sobre el marcado de cookies-dialogo.php,
 * que no se pinta cuando no hay ninguna herramienta configurada. Un botón que
 * no abre nada es peor que no tenerlo, sobre todo en la página que promete que
 * las preferencias se pueden cambiar.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

// Punto 7 de docs/pendientes.md: ver el porqué en redirigirSiEsDirecto().
redirigirSiEsDirecto(url('cookies'));

$titulo      = t('pagina.cookies.titulo');
$descripcion = t('pagina.cookies.meta');
$anchoLibre  = true;

$legalTitulo      = 'Política de Cookies';
$legalActualizado = '14 Agosto, 2026';

/** Los dos párrafos de entrada, anteriores a la primera cláusula. */
$entradilla = [
    'En omdara utilizamos cookies y tecnologías similares para garantizar el funcionamiento de '
    . 'nuestra plataforma, mejorar la experiencia de navegación, analizar el uso del sitio y, '
    . 'cuando corresponda, medir la efectividad de nuestras acciones de marketing.',

    'Esta Política de Cookies explica qué son las cookies, qué tecnologías utilizamos, para qué '
    . 'las utilizamos y cómo puedes gestionar tus preferencias.',
];

/**
 * Las cláusulas, tal como llegaron.
 *
 * Mismo formato que las otras dos legales: [título, bloques]. Aquí hay cuatro
 * clases de bloque, porque este documento tiene dos niveles de subtítulo que
 * los otros no:
 *
 *   'texto plano'                  → un párrafo
 *   ['a', 'b', 'c']                → una lista
 *   ['apartado'    => '…']         → un subtítulo numerado (2.1, 2.2, 2.3)
 *   ['herramienta' => '…']         → el nombre de una herramienta, sin numerar
 *   ['accion'      => true]        → el botón de preferencias de cookies
 *
 * La numeración —tanto la de la cláusula como la del apartado— sale del
 * contador de CSS y no escrita en el texto: insertar un apartado no debería
 * obligar a renumerar los de abajo a mano.
 */
$clausulas = [
    [
        '¿Qué son las cookies?',
        [
            'Las cookies son pequeños archivos de texto que se almacenan en tu dispositivo cuando '
            . 'visitas un sitio web. Permiten que el sitio recuerde determinada información y '
            . 'pueden utilizarse para facilitar su funcionamiento, analizar su uso o personalizar '
            . 'determinadas funcionalidades.',

            'También podemos utilizar tecnologías similares, como píxeles, etiquetas o '
            . 'identificadores, que cumplen funciones similares a las cookies.',
        ],
    ],
    [
        '¿Qué tipos de cookies utilizamos?',
        [
            'En omdara podemos utilizar las siguientes categorías:',

            ['apartado' => 'Cookies necesarias'],
            'Son aquellas necesarias para que el sitio web funcione correctamente y para '
            . 'proporcionar determinadas funciones que hayas solicitado.',
            'Pueden utilizarse, entre otras cosas, para:',
            [
                'Mantener sesiones de usuario.',
                'Permitir el acceso y funcionamiento de las cuentas de organizadores.',
                'Mantener la seguridad de la plataforma.',
                'Recordar determinadas preferencias técnicas.',
                'Permitir el funcionamiento de formularios y otras funciones esenciales.',
            ],
            'Estas cookies no se utilizan para crear perfiles publicitarios.',

            ['apartado' => 'Cookies analíticas'],
            'Utilizamos tecnologías de análisis para conocer cómo interactúan los usuarios con '
            . 'omdara y mejorar la plataforma.',

            ['herramienta' => 'Google Analytics 4 (Google)'],
            'Nos permite recopilar información estadística sobre el uso de omdara, como:',
            [
                'número de visitantes;',
                'páginas visitadas;',
                'fuente de tráfico;',
                'dispositivo utilizado;',
                'eventos e interacciones;',
                'navegación dentro del sitio;',
                'conversiones o acciones relevantes.',
            ],
            'Utilizamos esta información principalmente de forma agregada para comprender el '
            . 'comportamiento de los usuarios y mejorar nuestros contenidos, funcionalidades y '
            . 'experiencia de usuario.',

            ['herramienta' => 'Microsoft Clarity (Microsoft)'],
            'Nos permite analizar cómo interactúan los usuarios con determinadas páginas mediante '
            . 'herramientas como mapas de calor y análisis de comportamiento.',
            'Puede ayudarnos a identificar, por ejemplo:',
            [
                'elementos con los que interactúan los usuarios;',
                'áreas de una página que reciben mayor atención;',
                'problemas de navegación;',
                'dificultades de uso;',
                'patrones generales de interacción.',
            ],
            'No utilizamos esta información para identificar personalmente a los usuarios.',

            ['apartado' => 'Cookies de marketing'],
            'Podemos utilizar tecnologías de marketing para medir el rendimiento de nuestras '
            . 'campañas y comprender las interacciones de los usuarios con nuestros contenidos y '
            . 'anuncios.',

            ['herramienta' => 'Meta Pixel (Meta Platforms, Inc.)'],
            'Podemos utilizar Meta Pixel para:',
            [
                'medir conversiones;',
                'conocer el rendimiento de campañas publicitarias;',
                'crear audiencias para campañas;',
                'mejorar la relevancia de nuestros anuncios;',
                'comprender cómo interactúan los usuarios con omdara después de visualizar o '
                . 'interactuar con publicidad.',
            ],
            'Estas tecnologías pueden permitir que terceros, como Meta, reciban determinada '
            . 'información relacionada con la navegación y las interacciones realizadas en el '
            . 'sitio, de acuerdo con sus propias políticas.',
        ],
    ],
    [
        'Cookies y tecnologías de terceros',
        [
            'Algunas de las tecnologías utilizadas en omdara pueden ser proporcionadas por '
            . 'terceros, incluyendo:',
            [
                'Google;',
                'Microsoft;',
                'Meta.',
            ],
            'Estos terceros pueden procesar determinada información de acuerdo con sus propias '
            . 'políticas de privacidad y cookies.',

            'Omdara no controla las prácticas de privacidad de terceros y recomendamos consultar '
            . 'sus respectivas políticas para obtener información adicional sobre el tratamiento '
            . 'de datos.',
        ],
    ],
    [
        '¿Qué información pueden recopilar estas tecnologías?',
        [
            'Dependiendo de la tecnología utilizada y de su configuración, pueden recopilarse '
            . 'datos como:',
            [
                'dirección IP o información derivada de ella;',
                'tipo de dispositivo;',
                'sistema operativo;',
                'navegador;',
                'páginas visitadas;',
                'fecha y hora de acceso;',
                'fuente desde la que llegaste al sitio;',
                'interacciones realizadas dentro de omdara;',
                'información relacionada con eventos y navegación.',
            ],
            'La información recopilada mediante herramientas analíticas y de marketing se utiliza '
            . 'para las finalidades descritas en esta Política de Cookies y en nuestro Aviso de '
            . 'Privacidad.',
        ],
    ],
    [
        'Consentimiento',
        [
            'Cuando corresponda, las cookies y tecnologías que no sean estrictamente necesarias '
            . 'se activarán únicamente después de obtener tu consentimiento mediante el mecanismo '
            . 'de gestión de cookies implementado en omdara.',

            'Al ingresar por primera vez al sitio, podrás:',
            [
                'aceptar las cookies y tecnologías no esenciales;',
                'rechazarlas; o',
                'configurar tus preferencias por categoría.',
            ],
            'Las cookies estrictamente necesarias podrán utilizarse cuando sean indispensables '
            . 'para el funcionamiento de la plataforma.',

            'Puedes modificar o retirar tu consentimiento posteriormente mediante las opciones '
            . 'disponibles en el sitio o mediante la configuración de tu navegador, según '
            . 'corresponda.',
        ],
    ],
    [
        '¿Cómo puedo gestionar las cookies?',
        [
            'Puedes aceptar, rechazar o configurar las cookies no esenciales mediante el '
            . 'mecanismo de consentimiento disponible en omdara.',

            'También puedes configurar tu navegador para bloquear, eliminar o limitar el uso de '
            . 'cookies.',

            'Ten en cuenta que desactivar determinadas cookies puede afectar algunas '
            . 'funcionalidades o la experiencia de navegación del sitio.',

            /* Aquí y no al final: el requerimiento pide que se pueda volver a
               las preferencias, y este es el párrafo que lo promete. Tenerlo a
               la vista mientras se lee ahorra buscarlo. */
            ['accion' => true],
        ],
    ],
    [
        'Duración de las cookies',
        [
            'Las cookies pueden ser:',
            [
                'Cookies de sesión: se eliminan cuando cierras el navegador o finaliza la sesión '
                . 'correspondiente.',
                'Cookies persistentes: permanecen almacenadas durante un periodo determinado o '
                . 'hasta que las elimines.',
            ],
            'La duración específica de cada cookie puede variar dependiendo de la herramienta, '
            . 'proveedor y configuración utilizada.',

            'La información detallada sobre las cookies efectivamente instaladas en omdara podrá '
            . 'consultarse en el mecanismo de gestión de cookies implementado en el sitio.',
        ],
    ],
    [
        'Actualizaciones de esta Política',
        [
            'Podemos actualizar esta Política de Cookies cuando sea necesario, por ejemplo, '
            . 'cuando incorporemos nuevas herramientas, tecnologías o funcionalidades, o cuando '
            . 'cambien los requisitos legales aplicables.',

            'Cuando realicemos modificaciones relevantes, actualizaremos la fecha de "Última '
            . 'actualización" indicada al inicio de esta política.',
        ],
    ],
    [
        'Contacto',
        [
            'Si tienes preguntas sobre el uso de cookies o tecnologías similares en omdara, '
            . 'puedes contactarnos mediante:',
            /* Los dos huecos del documento aprobado —«[correo de omdara]» y
               «[URL de omdara]»—. El sitio se sabe solo; el correo público no
               existe todavía y se rellena desde config.local.php. Mientras no
               esté, se ofrece el formulario, que sí funciona. Anotado en
               docs/pendientes.md. */
            array_values(array_filter([
                correoContacto() !== '' ? 'Correo electrónico: ' . correoContacto() : null,
                'Sitio web: ' . URL_BASE,
                correoContacto() === '' ? 'Formulario de contacto: ' . url('contacto') : null,
            ])),
        ],
    ],
];

require __DIR__ . '/includes/layout.php';

/**
 * Una fila del anexo. Se pinta con una función y no a mano porque son catorce,
 * y catorce <tr> escritos uno a uno es donde se cuela la que le falta una celda.
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

<section class="wrap block legal">
  <?php require __DIR__ . '/includes/legal-cabecera.php'; ?>

  <?php foreach ($entradilla as $parrafo): ?>
    <p class="legal-entrada"><?= e($parrafo) ?></p>
  <?php endforeach; ?>

  <ol class="legal-clausulas">
    <?php foreach ($clausulas as [$encabezado, $bloques]): ?>
      <li>
        <h2><?= e($encabezado) ?></h2>
        <?php foreach ($bloques as $bloque): ?>

          <?php if (!is_array($bloque)): ?>
            <p><?= e($bloque) ?></p>

          <?php elseif (isset($bloque['apartado'])): ?>
            <h3 class="legal-apartado"><?= e($bloque['apartado']) ?></h3>

          <?php elseif (isset($bloque['herramienta'])): ?>
            <h4 class="legal-herramienta"><?= e($bloque['herramienta']) ?></h4>

          <?php elseif (isset($bloque['accion'])): ?>
            <?php if (hayQueConsentir()): ?>
              <p class="legal-accion">
                <button type="button" class="btn-principal" data-cookies="configurar"
                        style="display:inline-block; width:auto;"><?= et('cookies.abrir_preferencias') ?></button>
              </p>
            <?php else: ?>
              <?php /* Sin herramientas configuradas no se pinta el diálogo, así
                       que el botón no abriría nada. Decirlo es más honesto que
                       enseñar un botón muerto en la página que promete que las
                       preferencias se pueden cambiar. */ ?>
              <p class="legal-accion legal-accion-inactiva">
                En este momento no hay ninguna tecnología no esencial activa en el sitio, así que
                no hay preferencias que configurar. El panel aparecerá en cuanto la haya.
              </p>
            <?php endif; ?>

          <?php else: ?>
            <ul>
              <?php foreach ($bloque as $punto): ?>
                <li><?= e($punto) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

        <?php endforeach; ?>
      </li>
    <?php endforeach; ?>
  </ol>

  <?php /* ---- Anexo, fuera del documento aprobado ----
           La cláusula 7 remite el detalle al mecanismo de gestión de cookies.
           Esto es lo que ese detalle sabe hoy, y se publica separado y con su
           salvedad delante: media tabla es segura y la otra media no. */ ?>
  <div class="legal-anexo">
    <h2>Anexo informativo: qué cookies hemos identificado</h2>
    <p>No forma parte del texto de la Política. Se publica porque ayuda a entender qué
       hace el sitio, y se irá corrigiendo conforme se compruebe contra la instalación
       real de cada herramienta.</p>

    <h3>Las que pone OMDARA</h3>
    <p>Estas dos son seguras: las escribe el propio código de la plataforma, con estos
       nombres y estas duraciones.</p>

    <div class="tabla-cookies-wrap">
      <table class="tabla-cookies">
        <thead>
          <tr><th>Nombre</th><th>Categoría</th><th>Finalidad</th><th>Duración</th></tr>
        </thead>
        <tbody>
          <?php
          filaCookie('wh_sesion', 'Necesaria',
              'Mantiene tu sesión iniciada y protege los formularios frente a envíos falsificados.',
              'Hasta cerrar el navegador');
          filaCookie('omdara_cookies', 'Necesaria',
              'Guarda tu respuesta a este aviso, para no volver a preguntártela en cada página.',
              '6 meses');
          ?>
        </tbody>
      </table>
    </div>

    <h3>Las que pueden poner Google, Microsoft y Meta</h3>
    <div class="aviso aviso-info" style="max-width:760px; margin:14px 0 18px;">
      <strong>Pendiente de comprobar.</strong>
      Esta lista sale de la documentación de cada proveedor, no de haberlas visto en el
      navegador. El conjunto real y sus duraciones dependen de cómo quede configurada
      cada herramienta, así que hay que verificarla con las tres encendidas y tráfico
      real, y corregir aquí lo que no coincida.
    </div>

    <div class="tabla-cookies-wrap">
      <table class="tabla-cookies">
        <thead>
          <tr><th>Nombre</th><th>Proveedor</th><th>Finalidad</th><th>Duración declarada</th></tr>
        </thead>
        <tbody>
          <?php
          filaCookie('_ga', 'Google · analíticas',
              'Distingue un navegador de otro para contar visitantes.', '2 años');
          filaCookie('_ga_<ID>', 'Google · analíticas',
              'Mantiene el estado de la sesión de Google Analytics 4. El sufijo es el identificador del flujo de datos.',
              '2 años');
          filaCookie('_gcl_au', 'Google · analíticas',
              'Mide qué clics vienen de un anuncio. Solo aparece si la cuenta se enlaza con Google Ads.',
              '3 meses');
          filaCookie('_clck', 'Microsoft · analíticas',
              'Conserva el identificador de Clarity del navegador entre visitas.', '1 año');
          filaCookie('_clsk', 'Microsoft · analíticas',
              'Une en una sola grabación las páginas que ves en una misma visita.', '1 día');
          filaCookie('CLID', 'Microsoft · clarity.ms',
              'Identifica el navegador la primera vez que carga Clarity. Se pone en el dominio de Microsoft, no en el nuestro.',
              '1 año');
          filaCookie('MUID, ANONCHK, SM', 'Microsoft · clarity.ms, bing.com',
              'Identificación entre servicios de Microsoft y sincronización de esos identificadores. Se ponen en dominios de Microsoft.',
              'MUID 1 año · ANONCHK 10 minutos · SM sesión');
          filaCookie('_fbp', 'Meta · marketing',
              'Identifica el navegador para atribuir una visita a un anuncio. La pone el píxel en nuestro propio dominio.',
              '3 meses');
          filaCookie('_fbc', 'Meta · marketing',
              'Guarda el identificador del anuncio del que vienes. Solo aparece si llegas desde un enlace publicitario de Meta.',
              '3 meses');
          filaCookie('fr', 'Meta · facebook.com',
              'Entrega de publicidad y medición. Se pone en el dominio de Facebook, no en el nuestro.',
              '3 meses');
          ?>
        </tbody>
      </table>
    </div>

    <h3>Lo que este sitio no puede borrar por ti</h3>
    <p>Al retirar el permiso a una categoría se borran las cookies que estén en el
       dominio de OMDARA. <strong>Las que un proveedor pone en su propio dominio</strong>
       —las de <code>clarity.ms</code>, <code>bing.com</code> y <code>facebook.com</code>
       marcadas arriba— no se pueden borrar desde aquí: para esas hay que usar la
       configuración de cookies del navegador.</p>
    <p>También puedes bloquear o borrar cookies desde tu navegador, incluidas las
       necesarias. Si bloqueas la cookie de sesión, <strong>no podrás iniciar sesión ni
       publicar una actividad</strong>: el sitio no tiene forma de recordar quién eres
       entre una página y la siguiente.</p>
  </div>

  <?php /* Los otros dos documentos, desde aquí: quien lee uno suele querer
           mirar el siguiente, y volver al pie a buscarlo es un viaje de más. */ ?>
  <div class="legal-otros">
    <a href="<?= e(url('terminos')) ?>">Términos y Condiciones</a>
    <a href="<?= e(url('privacidad')) ?>">Aviso de Privacidad</a>
  </div>
</section>

<?php pie(); ?>
