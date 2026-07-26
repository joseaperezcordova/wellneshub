<?php
/**
 * Sesión de usuario, registro, login con contraseña, CSRF y freno de fuerza
 * bruta.
 */

declare(strict_types=1);

const LOGIN_MAX_INTENTOS = 5;   // fallos permitidos…
const LOGIN_VENTANA_MIN  = 15;  // …dentro de esta ventana, en minutos


// ---------------------------------------------------------------- sesión ----

function usuarioActual(): ?array
{
    static $cache = null;

    if ($cache !== null)            return $cache ?: null;
    if (empty($_SESSION['uid']))    return null;

    $st = db()->prepare(
        'SELECT id, nombre, email, avatar_url, rol, estado
           FROM usuarios
          WHERE id = ? LIMIT 1'
    );
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();

    // Si la cuenta se borró o se suspendió mientras la sesión seguía viva, la
    // sesión deja de valer en ese mismo momento y no en el siguiente login.
    if (!$u || $u['estado'] !== 'activo') {
        cerrarSesion();
        return null;
    }

    $cache = $u;
    return $u;
}

function haySesion(): bool
{
    return usuarioActual() !== null;
}

function iniciarSesion(int $usuarioId): void
{
    // Renovar el identificador al entrar cierra la puerta a la fijación de
    // sesión: si alguien plantó un id de sesión antes del login, deja de servir.
    session_regenerate_id(true);
    $_SESSION['uid'] = $usuarioId;

    db()->prepare('UPDATE usuarios SET ultimo_acceso_en = NOW() WHERE id = ?')
        ->execute([$usuarioId]);
}

function cerrarSesion(): void
{
    $_SESSION = [];

    if (ini_get('use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function exigirSesion(): array
{
    $u = usuarioActual();
    if (!$u) redirigir('/login.php');
    return $u;
}


// ------------------------------------------------------------------ CSRF ----

function tokenCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfValido(?string $enviado): bool
{
    // hash_equals y no === : la comparación normal de cadenas se corta en el
    // primer carácter distinto, y ese tiempo distinto es medible.
    return !empty($_SESSION['csrf'])
        && is_string($enviado)
        && hash_equals($_SESSION['csrf'], $enviado);
}


// ------------------------------------------------- freno de fuerza bruta ----

function ipBinaria(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return inet_pton($ip) ?: inet_pton('0.0.0.0');
}

function registrarIntento(string $email, bool $exito): void
{
    db()->prepare('INSERT INTO intentos_login (email, ip, exito) VALUES (?, ?, ?)')
        ->execute([mb_strtolower($email), ipBinaria(), $exito ? 1 : 0]);
}

/** ¿Está frenado este correo o esta IP ahora mismo? */
function loginBloqueado(string $email): bool
{
    $st = db()->prepare(
        'SELECT COUNT(*) FROM intentos_login
          WHERE exito = 0
            AND creado_en > DATE_SUB(NOW(), INTERVAL ? MINUTE)
            AND (email = ? OR ip = ?)'
    );
    $st->execute([LOGIN_VENTANA_MIN, mb_strtolower($email), ipBinaria()]);

    return (int) $st->fetchColumn() >= LOGIN_MAX_INTENTOS;
}

function limpiarIntentos(string $email): void
{
    db()->prepare('DELETE FROM intentos_login WHERE email = ? OR ip = ?')
        ->execute([mb_strtolower($email), ipBinaria()]);
}


// -------------------------------------------------------------- usuarios ----

function buscarUsuarioPorEmail(string $email): ?array
{
    $st = db()->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
    $st->execute([mb_strtolower(trim($email))]);
    return $st->fetch() ?: null;
}

/**
 * Alta con contraseña.
 *
 * @return array{0:bool,1:string} [ok, mensaje o id como texto]
 */
function registrarUsuario(string $nombre, string $email, string $password): array
{
    $nombre = trim($nombre);
    $email  = mb_strtolower(trim($email));

    if (mb_strlen($nombre) < 2)                      return [false, 'Escribe tu nombre.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  return [false, 'Ese correo no tiene buena pinta.'];
    if (mb_strlen($password) < 8)                    return [false, 'La contraseña necesita al menos 8 caracteres.'];

    $existente = buscarUsuarioPorEmail($email);

    if ($existente) {
        // Cuenta creada con Google que ahora quiere contraseña: en vez de
        // rechazarla, se le añade la contraseña a la cuenta que ya tiene. Si no,
        // quedaría atrapada sin poder registrarse ni recuperar nada.
        if ($existente['password_hash'] === null) {
            db()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $existente['id']]);
            return [true, (string) $existente['id']];
        }
        return [false, 'Ya hay una cuenta con ese correo. Inicia sesión.'];
    }

    $st = db()->prepare(
        'INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)'
    );
    $st->execute([$nombre, $email, password_hash($password, PASSWORD_DEFAULT)]);

    return [true, (string) db()->lastInsertId()];
}

/**
 * Login con correo y contraseña.
 *
 * @return array{0:bool,1:string} [ok, mensaje o id como texto]
 */
function autenticar(string $email, string $password): array
{
    $email = mb_strtolower(trim($email));

    if (loginBloqueado($email)) {
        return [false, 'Demasiados intentos fallidos. Espera ' . LOGIN_VENTANA_MIN . ' minutos.'];
    }

    $u = buscarUsuarioPorEmail($email);

    // Cuenta que solo existe vía Google: decírselo es más útil que un "datos
    // incorrectos" que le haría probar contraseñas que nunca existieron. No
    // revela nada que Google no confirme ya en su propia pantalla.
    if ($u && $u['password_hash'] === null) {
        return [false, 'Esta cuenta se creó con Google. Entra con el botón de Google.'];
    }

    if (!$u || !password_verify($password, $u['password_hash'])) {
        registrarIntento($email, false);
        // Mismo mensaje exista o no el correo: distinguirlos convierte el
        // formulario en un comprobador de qué correos están registrados.
        return [false, 'Correo o contraseña incorrectos.'];
    }

    if ($u['estado'] !== 'activo') {
        return [false, 'Esta cuenta está suspendida.'];
    }

    // Rehash si el coste por defecto de PHP subió desde que se creó la cuenta.
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }

    limpiarIntentos($email);
    return [true, (string) $u['id']];
}

/**
 * Entrada por Google: encuentra la cuenta, la enlaza o la crea.
 *
 * @param array $perfil sub, email, email_verified, name, picture
 * @return array{0:bool,1:string} [ok, mensaje o id como texto]
 */
function entrarConGoogle(array $perfil): array
{
    $sub   = (string) ($perfil['sub'] ?? '');
    $email = mb_strtolower(trim((string) ($perfil['email'] ?? '')));

    if ($sub === '' || $email === '') {
        return [false, 'Google no devolvió los datos necesarios.'];
    }

    $pdo = db();

    // 1. ¿Ya conocemos este "sub"? Es el camino normal a partir del segundo
    //    inicio de sesión.
    $st = $pdo->prepare(
        'SELECT u.* FROM identidades_oauth i
           JOIN usuarios u ON u.id = i.usuario_id
          WHERE i.proveedor = "google" AND i.proveedor_uid = ? LIMIT 1'
    );
    $st->execute([$sub]);

    if ($u = $st->fetch()) {
        if ($u['estado'] !== 'activo') return [false, 'Esta cuenta está suspendida.'];
        return [true, (string) $u['id']];
    }

    // 2. Primera vez con Google, pero quizá ya existe una cuenta con ese correo.
    //
    //    Solo se enlazan si Google confirma que el correo está verificado. Sin
    //    esa comprobación, cualquiera que cree una cuenta de Google con el
    //    correo de otra persona se quedaría con su cuenta de aquí.
    $existente = buscarUsuarioPorEmail($email);
    $verificado = !empty($perfil['email_verified']);

    if ($existente && !$verificado) {
        return [false, 'Ese correo ya está registrado. Entra con tu contraseña.'];
    }

    $pdo->beginTransaction();
    try {
        if ($existente) {
            $usuarioId = (int) $existente['id'];

            // Aprovechamos para dar el correo por verificado y poner avatar si
            // la cuenta no tenía.
            $pdo->prepare(
                'UPDATE usuarios
                    SET email_verificado_en = COALESCE(email_verificado_en, NOW()),
                        avatar_url          = COALESCE(avatar_url, ?)
                  WHERE id = ?'
            )->execute([$perfil['picture'] ?? null, $usuarioId]);
        } else {
            $pdo->prepare(
                'INSERT INTO usuarios (nombre, email, email_verificado_en, avatar_url)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                trim((string) ($perfil['name'] ?? 'Sin nombre')),
                $email,
                $verificado ? date('Y-m-d H:i:s') : null,
                $perfil['picture'] ?? null,
            ]);
            $usuarioId = (int) $pdo->lastInsertId();
        }

        $pdo->prepare(
            'INSERT INTO identidades_oauth (usuario_id, proveedor, proveedor_uid, email_proveedor)
             VALUES (?, "google", ?, ?)'
        )->execute([$usuarioId, $sub, $email]);

        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        error_log('Alta con Google fallida: ' . $ex->getMessage());
        return [false, 'No se pudo completar el acceso con Google.'];
    }

    return [true, (string) $usuarioId];
}
