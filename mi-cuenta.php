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
 * EL CORREO NO SE EDITA AQUÍ, Y NO ES UN DESCUIDO
 *
 * Es la credencial con la que se entra: aquí no hay contraseñas, y el código de
 * acceso va justo a ese buzón. Cambiarlo sin verificar antes el nuevo deja a
 * alguien fuera de su cuenta para siempre, sin ninguna forma de recuperarla —un
 * dedazo basta—. Hacerlo bien es un flujo propio: mandar un código al correo
 * nuevo, confirmarlo, cambiarlo y avisar al viejo por si no fue su dueño quien
 * lo pidió. Está anotado en docs/pendientes.md.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$u     = exigirSesion();
$ficha = fichaDeUsuario((int) $u['id']);

if (!$ficha) redirigir('/logout.php');

/* El teléfono solo existe después de la migración 17. Hasta entonces el campo
   sencillamente no aparece, en vez de enseñar uno que no se puede guardar. */
$hayTelefono = columnaExiste('usuarios', 'telefono');

$editando = !empty($_GET['editar']);
$error    = '';
$aviso    = '';
$nombre   = (string) $ficha['nombre'];
$telefono = (string) ($ficha['telefono'] ?? '');

// Aviso que dejó el guardado antes de redirigir (POST-redirect-GET: sin él,
// recargar volvería a mandar el formulario).
if (!empty($_SESSION['cuenta_aviso'])) {
    $aviso = (string) $_SESSION['cuenta_aviso'];
    unset($_SESSION['cuenta_aviso']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = (string) ($_POST['nombre'] ?? '');
    $telefono = (string) ($_POST['telefono'] ?? '');
    $editando = true;

    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'La sesión caducó. Vuelve a intentarlo.';

    } elseif (trim($nombre) === '') {
        // Sin nombre, la cabecera de su propia cuenta se lee como un error de
        // la página, y en el panel admin aparece una fila en blanco.
        $error = 'Escribe tu nombre: es como te ven en el sitio.';

    } else {
        guardarContactoUsuario((int) $u['id'], $nombre, $hayTelefono ? $telefono : null);

        $_SESSION['cuenta_aviso'] = 'Datos guardados.';
        redirigir('/mi-cuenta.php');
    }
}

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
        filaCuenta('Nombre', (string) $ficha['nombre']);
        if ($hayTelefono) {
            filaCuenta('Teléfono de contacto', (string) ($ficha['telefono'] ?? ''),
                'Solo lo ve el equipo de OMDARA. No aparece en tus actividades.');
        }
        filaCuenta('Correo', (string) $ficha['email'],
            'Es con lo que entras. Para cambiarlo hay que verificar el nuevo buzón, y eso todavía no está hecho: escríbenos y lo cambiamos.');
        ?>

      <?php else: ?>

        <div class="cuenta-caja-cab">
          <h2>Editar tus datos</h2>
        </div>

        <form method="post" novalidate>
          <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">

          <div class="campo">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" type="text" maxlength="120" autocomplete="name"
                   value="<?= e($nombre) ?>" required>
            <div class="pista">Es el nombre que aparece como organizador en tus actividades.</div>
          </div>

          <?php if ($hayTelefono): ?>
            <div class="campo">
              <label for="telefono">Teléfono de contacto <span class="opcional">opcional</span></label>
              <input id="telefono" name="telefono" type="tel" maxlength="30" autocomplete="tel"
                     placeholder="Ej. +52 612 123 4567" value="<?= e($telefono) ?>">
              <div class="pista">Solo lo ve el equipo de OMDARA, para localizarte si hay algo con alguna
                actividad tuya. No se publica en ninguna ficha.</div>
            </div>
          <?php endif; ?>

          <div class="campo">
            <label for="correo-fijo">Correo</label>
            <?php /* Deshabilitado y no oculto: es un dato de contacto y hay que
                     poder consultarlo aquí. Al estar disabled no viaja en el
                     POST, así que ni siquiera hay que ignorarlo al guardar. */ ?>
            <input id="correo-fijo" type="email" value="<?= e($ficha['email']) ?>" disabled>
            <div class="pista">No se puede cambiar desde aquí: es la dirección a la que llega tu código
              para entrar, y un cambio sin verificar te dejaría fuera de tu propia cuenta.</div>
          </div>

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
