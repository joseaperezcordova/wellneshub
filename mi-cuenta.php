<?php
/**
 * Mi cuenta → Información de contacto (REQ-00009).
 *
 * La otra mitad de «Mi cuenta» es «Mis actividades», que ya existía en
 * mis-eventos.php. Aquí está lo que faltaba: poder ver y corregir los datos
 * propios sin pedírselo a nadie.
 *
 * ES INFORMACIÓN PRIVADA, Y SE DICE EN PANTALLA
 *
 * El requerimiento insiste en que esto no crea un perfil público, así que la
 * página lo dice donde se lee y no solo en el código: nada de lo de aquí sale
 * en una ficha de actividad.
 *
 * VER Y EDITAR SON DOS ESTADOS, Y SE DISTINGUEN POR LA DIRECCIÓN
 *
 * ?editar=1 enseña el formulario; sin eso, los datos se leen. Es lo que pide el
 * requerimiento —«los campos deben contar con una opción de Editar»— y sale
 * gratis: sin JavaScript, con el botón «atrás» funcionando y con «Cancelar»
 * siendo un enlace de verdad.
 *
 * CAMBIAR EL CORREO ES UN FLUJO APARTE, NO UN CAMPO MÁS (punto 18 de
 * docs/pendientes.md, migración 25)
 *
 * Es la credencial con la que se entra: aquí no hay contraseñas, y el código de
 * acceso va justo a ese buzón. Cambiarlo sin verificar antes el nuevo dejaría a
 * alguien fuera de su cuenta para siempre, sin ninguna forma de recuperarla —un
 * dedazo basta—. Por eso el campo de correo sigue disabled —no viaja en el
 * "Guardar cambios" de siempre— y el cambio de verdad vive en sus propios
 * botones dentro del mismo <form>: solicitarCambioCorreo() manda un código al
 * correo NUEVO, confirmarCambioCorreo() lo verifica y recién ahí cambia
 * usuarios.email, y avisa al correo VIEJO por si no fue su dueño quien lo
 * pidió —el único de los cuatro pasos que detecta un secuestro—. Las tres
 * funciones viven en includes/auth.php.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

$u     = exigirSesion();
$ficha = fichaDeUsuario((int) $u['id']);

if (!$ficha) redirigir('/logout.php');

/*
 * Los mismos campos que el formulario de publicar (REQ-00012), sacados de la
 * misma lista: WhatsApp, Instagram y sitio web. Editarlos aquí o al publicar
 * cambia lo mismo, y por eso ninguna de las dos pantallas tiene su propia idea
 * de cuáles son.
 */
$campos = camposContactoOrganizador();

$editando = !empty($_GET['editar']);
$error    = '';
$aviso    = '';
$nombre   = (string) $ficha['nombre'];

// Cambio de correo de la cuenta (punto 18 de docs/pendientes.md, migración
// 25): sus tres acciones no tienen nada que ver con guardar nombre/WhatsApp/
// Instagram/sitio web, así que se comprueban antes que ese guardado y no
// como parte de él.
$errorCorreo = '';
$avisoCorreo = '';

// Aviso que dejó el guardado antes de redirigir (POST-redirect-GET: sin él,
// recargar volvería a mandar el formulario).
if (!empty($_SESSION['cuenta_aviso'])) {
    $aviso = (string) $_SESSION['cuenta_aviso'];
    unset($_SESSION['cuenta_aviso']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editando = true;

    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'La sesión caducó. Vuelve a intentarlo.';

    } elseif (isset($_POST['enviar_codigo_cambio_correo'])) {
        [$ok, $msg] = solicitarCambioCorreo((int) $u['id'], (string) ($_POST['correo_nuevo'] ?? ''));
        if ($ok) { $avisoCorreo = $msg; } else { $errorCorreo = $msg; }

    } elseif (isset($_POST['confirmar_codigo_cambio_correo'])) {
        [$ok, $msg] = confirmarCambioCorreo((int) $u['id'], (string) ($_POST['codigo_cambio_correo'] ?? ''));
        if ($ok) {
            $avisoCorreo = $msg;
            // Para que el correo que se enseña en esta misma respuesta ya
            // sea el nuevo, sin esperar a la siguiente visita.
            $ficha = fichaDeUsuario((int) $u['id']) ?? $ficha;
        } else {
            $errorCorreo = $msg;
        }

    } elseif (isset($_POST['cancelar_codigo_cambio_correo'])) {
        cancelarCambioCorreo((int) $u['id']);

    } elseif (trim((string) ($_POST['org_nombre'] ?? '')) === '') {
        // Sin nombre, la cabecera de su propia cuenta se lee como un error de
        // la página, y en el panel admin aparece una fila en blanco.
        $nombre = (string) ($_POST['org_nombre'] ?? '');
        $error  = 'Escribe tu nombre: es como te ven en el sitio.';

    } else {
        // El mismo guardado que usa el formulario de publicar, con los mismos
        // nombres de campo. Dos caminos que escriben las mismas columnas acaban
        // escribiéndolas distinto.
        guardarContactoOrganizador((int) $u['id'], $_POST);

        $_SESSION['cuenta_aviso'] = 'Datos guardados.';
        redirigir('/mi-cuenta.php');
    }
}

$correoPendiente = correoCambioPendiente((int) $u['id']);

/** El valor de un campo: lo recién escrito si falló, o lo guardado. */
$valorCampo = function (string $columna) use ($ficha) {
    if (isset($_POST['org_' . $columna])) return (string) $_POST['org_' . $columna];

    return (string) ($ficha[$columna] ?? '');
};

$titulo     = 'Mi cuenta';
$anchoLibre = true;

require __DIR__ . '/includes/layout.php';

/**
 * "14 de agosto de 2026".
 *
 * Propia y no fechaCorta() de eventos.php: esta página no necesita nada más de
 * ese archivo, y cargarlo entero —con sus consultas y su catálogo de
 * categorías— para dar formato a tres fechas sería pagar de más en cada visita.
 */
function fechaDeCuenta(string $fecha): string
{
    static $meses = ['enero','febrero','marzo','abril','mayo','junio','julio',
                     'agosto','septiembre','octubre','noviembre','diciembre'];

    $ts = (int) strtotime($fecha);

    return (int) date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

/** Una fila de «dato: valor», con guion cuando está vacío. */
function filaCuenta(string $etiqueta, string $valor, string $pista = ''): void
{
    echo '<div class="cuenta-dato">'
       . '<span class="k">' . e($etiqueta) . '</span>'
       . '<span class="v">' . ($valor !== '' ? e($valor) : '<span class="vacio">—</span>') . '</span>'
       . ($pista !== '' ? '<span class="pista">' . e($pista) . '</span>' : '')
       . '</div>';
}
?>

<div class="wrap">
  <div class="op-shell">
    <div class="op-header">
      <div class="who">
        <?php if (!empty($u['avatar_url'])): ?>
          <img class="avatar" style="border-radius:50%; object-fit:cover;"
               src="<?= e($u['avatar_url']) ?>" alt="" referrerpolicy="no-referrer">
        <?php else: ?>
          <div class="avatar" style="border-radius:50%;"></div>
        <?php endif; ?>
        <div>
          <div class="eyebrow">Mi cuenta</div>
          <h1 style="font-size:22px;">Información de contacto</h1>
        </div>
      </div>
      <a class="btn-add" style="background:var(--terracota); color:var(--tinta-boton);"
         href="<?= URL_BASE ?>/mis-eventos.php">Mis actividades →</a>
    </div>

    <?php if ($aviso): ?>
      <div class="aviso aviso-ok"><?= e($aviso) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="aviso aviso-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="cuenta-caja">

      <?php if (!$editando): ?>

        <div class="cuenta-caja-cab">
          <h2>Tus datos</h2>
          <a class="actionbtn" href="<?= URL_BASE ?>/mi-cuenta.php?editar=1">Editar</a>
        </div>

        <?php
        filaCuenta('Nombre', (string) $ficha['nombre'],
            'Es el nombre que aparece como organizador en tus actividades.');

        foreach ($campos as $columna => $etiqueta) {
            filaCuenta($etiqueta, (string) ($ficha[$columna] ?? ''),
                'Solo lo ve el equipo de Omdara. No aparece en tus actividades.');
        }

        filaCuenta('Correo', (string) $ficha['email'], 'Es con lo que entras.');

        if ($correoPendiente !== null) {
            echo '<div class="aviso aviso-info" style="margin-top:10px;">Tienes un cambio de correo pendiente: '
               . 'te mandamos un código a <strong>' . e($correoPendiente) . '</strong>. '
               . '<a href="' . URL_BASE . '/mi-cuenta.php?editar=1">Escríbelo aquí</a> para confirmarlo.</div>';
        }
        ?>

      <?php else: ?>

        <div class="cuenta-caja-cab">
          <h2>Editar tus datos</h2>
        </div>

        <form method="post" novalidate>
          <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">

          <div class="campo">
            <label for="org_nombre">Nombre</label>
            <input id="org_nombre" name="org_nombre" type="text" maxlength="120" autocomplete="name"
                   value="<?= e($nombre) ?>" required>
            <div class="pista">Es el nombre que aparece como organizador en tus actividades.</div>
          </div>

          <?php foreach ($campos as $columna => $etiqueta): ?>
            <?php
            $marcador = [
                'telefono'  => '+52 612 123 4567',
                'instagram' => '@tucuenta',
                'sitio_web' => 'https://tusitio.com',
            ][$columna] ?? '';
            ?>
            <div class="campo">
              <label for="org_<?= e($columna) ?>"><?= e($etiqueta) ?> <span class="opcional">opcional</span></label>
              <input id="org_<?= e($columna) ?>" name="org_<?= e($columna) ?>"
                     type="<?= $columna === 'telefono' ? 'tel' : 'text' ?>"
                     maxlength="<?= $columna === 'sitio_web' ? 500 : 120 ?>"
                     placeholder="<?= e($marcador) ?>" value="<?= e($valorCampo($columna)) ?>">
              <div class="pista">Solo lo ve el equipo de Omdara, para localizarte si hay algo con alguna
                actividad tuya. No se publica en ninguna ficha.</div>
            </div>
          <?php endforeach; ?>

          <div class="campo">
            <label for="correo-fijo">Correo</label>
            <?php /* Deshabilitado y no oculto: es un dato de contacto y hay que
                     poder consultarlo aquí. Al estar disabled no viaja en el
                     POST, así que ni siquiera hay que ignorarlo al guardar. */ ?>
            <input id="correo-fijo" type="email" value="<?= e($ficha['email']) ?>" disabled>
            <div class="pista">Es la dirección a la que llega tu código para entrar.</div>
          </div>

          <?php /*
           * Cambio de correo (punto 18 de docs/pendientes.md): botones de
           * envío con su propio "name", dentro de este mismo <form> —un
           * <form> no puede ir dentro de otro—. "formnovalidate" para que no
           * los bloquee el "required" del nombre de arriba: pedir un código
           * o confirmarlo no tiene nada que ver con guardar el resto de la
           * ficha.
           */ ?>
          <?php if ($avisoCorreo !== ''): ?>
            <div class="aviso aviso-ok"><?= e($avisoCorreo) ?></div>
          <?php endif; ?>
          <?php if ($errorCorreo !== ''): ?>
            <div class="aviso aviso-error"><?= e($errorCorreo) ?></div>
          <?php endif; ?>

          <?php if ($correoPendiente !== null): ?>
            <div class="campo">
              <p class="pista">Te mandamos un código a <strong><?= e($correoPendiente) ?></strong>. Escríbelo
                aquí para confirmarlo:</p>
              <label for="codigo_cambio_correo">Código de 6 dígitos</label>
              <input id="codigo_cambio_correo" name="codigo_cambio_correo" type="text" inputmode="numeric"
                     autocomplete="one-time-code" maxlength="6" placeholder="000000">
            </div>
            <div class="cuenta-acciones">
              <button class="btn-principal" type="submit" name="confirmar_codigo_cambio_correo" value="1"
                      formnovalidate>Confirmar</button>
              <button class="actionbtn" type="submit" name="cancelar_codigo_cambio_correo" value="1"
                      formnovalidate>Cancelar</button>
            </div>
          <?php else: ?>
            <div class="campo">
              <label for="correo_nuevo">Cambiar correo <span class="opcional">opcional</span></label>
              <input id="correo_nuevo" name="correo_nuevo" type="email" maxlength="190"
                     placeholder="nuevo@correo.com">
              <div class="pista">Te mandaremos un código a esa dirección para confirmarlo antes de
                activarlo. Mientras no lo confirmes, sigues entrando con el de siempre.</div>
            </div>
            <div class="cuenta-acciones">
              <button class="actionbtn" type="submit" name="enviar_codigo_cambio_correo" value="1"
                      formnovalidate>Enviar código de verificación</button>
            </div>
          <?php endif; ?>

          <div class="cuenta-acciones">
            <button class="btn-principal" type="submit">Guardar cambios</button>
            <a class="actionbtn" href="<?= URL_BASE ?>/mi-cuenta.php">Cancelar</a>
          </div>
        </form>

      <?php endif; ?>
    </div>

    <?php /* Contexto de la cuenta: no se edita, pero es lo que alguien viene a
             mirar cuando algo no cuadra —desde cuándo está, si su correo quedó
             verificado, si consta la aceptación—. */ ?>
    <div class="cuenta-caja">
      <div class="cuenta-caja-cab"><h2>Tu cuenta</h2></div>
      <?php
      $roles = ['visitante' => 'Visitante', 'organizador' => 'Organizador', 'admin' => 'Administración'];

      filaCuenta('Tipo de cuenta', $roles[$ficha['rol']] ?? (string) $ficha['rol'],
          $ficha['rol'] === 'visitante'
              ? 'Pasa a organizador sola en cuanto publiques tu primera actividad.'
              : '');
      filaCuenta('Alta', !empty($ficha['creado_en']) ? fechaDeCuenta((string) $ficha['creado_en']) : '');
      filaCuenta('Correo verificado', !empty($ficha['email_verificado_en'])
          ? fechaDeCuenta((string) $ficha['email_verificado_en']) : 'Todavía no');

      if (array_key_exists('acepto_legal_en', $ficha)) {
          filaCuenta('Términos y Aviso de Privacidad',
              !empty($ficha['acepto_legal_en'])
                  ? 'Aceptados el ' . fechaDeCuenta((string) $ficha['acepto_legal_en'])
                  : 'Sin constancia',
              empty($ficha['acepto_legal_en'])
                  ? 'Tu cuenta es anterior a que se pidiera aceptarlos.' : '');
      }
      ?>
    </div>

    <div class="evergreen-note">
      Nada de esta página se publica. Lo que se ve en una actividad es tu nombre y
      lo que hayas escrito en su ficha, y nada más.
      <a href="<?= e(url('privacidad')) ?>">Aviso de Privacidad</a>.
    </div>
  </div>
</div>

<?php pie(); ?>
