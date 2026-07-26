<?php
/**
 * Sesión de usuario, acceso por código de un solo uso, alta por Google y CSRF.
 *
 * Aquí no hay contraseñas. Se entra de dos maneras y ninguna las necesita:
 *
 *   · con Google, que ya sabe quién eres;
 *   · pidiendo un código de seis cifras al correo, que demuestra lo mismo que
 *     demostraría una contraseña —control del buzón— sin obligar a nadie a
 *     inventarse una, ni a nosotros a custodiarla.
 *
 * La consecuencia práctica es que no hay "registro" separado. La primera vez
 * que alguien entra con un correo desconocido, la cuenta se crea sola.
 */

declare(strict_types=1);

const CODIGO_VIGENCIA_MIN = 15;  // minutos que vale un código
const CODIGO_MAX_INTENTOS = 5;   // fallos sobre un mismo código antes de anularlo
const CODIGO_ESPERA_SEG   = 60;  // entre dos envíos al mismo correo
const CODIGO_MAX_POR_HORA = 5;   // envíos por correo y hora
const CODIGO_MAX_IP_HORA  = 15;  // envíos por IP y hora, sea cual sea el correo


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

    // Rastros del flujo del código: ya no hacen falta y no deben sobrevivir.
    // 'volver_a' no se toca aquí: lo lee la página que redirige justo después.
    unset($_SESSION['codigo_email'], $_SESSION['codigo_error'], $_SESSION['codigo_aviso']);

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

    if (!$u) {
        // Se recuerda a dónde iba para devolverlo ahí al entrar. Sin esto, quien
        // pulsa «Publicar evento» sin sesión aterriza en la portada después del
        // login y tiene que volver a buscar el botón.
        guardarDestinoLogin((string) ($_SERVER['REQUEST_URI'] ?? '/'));
        redirigir('/login.php');
    }

    return $u;
}

/**
 * Guarda a dónde volver tras identificarse.
 *
 * Solo acepta rutas internas. Una redirección abierta —"//otrositio.com" o una
 * URL completa— es un clásico de phishing: el enlace enseña nuestro dominio, la
 * persona entra confiada y acaba en otro sitio ya identificada, creyendo que
 * sigue aquí.
 */
function guardarDestinoLogin(string $ruta): void
{
    // REQUEST_URI trae la carpeta desde la que se sirve la aplicación
    // (/wellneshub en XAMPP, nada en el dominio). redirigir() vuelve a
    // anteponer URL_BASE, así que aquí hay que quitarla o se duplicaría.
    $base = (string) parse_url(URL_BASE, PHP_URL_PATH);
    if ($base !== '' && strpos($ruta, $base) === 0) {
        $ruta = substr($ruta, strlen($base));
    }

    if ($ruta === '' || $ruta[0] !== '/') return;
    if (strpos($ruta, '//') === 0)        return;   // //otrositio.com
    if (strpos($ruta, '\\') !== false)    return;   // algunos navegadores lo leen como /

    $_SESSION['volver_a'] = $ruta;
}

/** A dónde mandar a alguien que acaba de entrar. De un solo uso. */
function destinoTrasLogin(): string
{
    $ruta = (string) ($_SESSION['volver_a'] ?? '/');
    unset($_SESSION['volver_a']);

    return $ruta !== '' ? $ruta : '/';
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


// -------------------------------------------------------------- usuarios ----

function ipBinaria(): string
{
    // ipCliente() y no REMOTE_ADDR a secas: detrás del proxy, REMOTE_ADDR es
    // 127.0.0.1 para todo el mundo y el límite "por IP" de los códigos pasaría
    // a valer para el sitio entero. Ver config.php.
    return inet_pton(ipCliente()) ?: inet_pton('0.0.0.0');
}

function buscarUsuarioPorEmail(string $email): ?array
{
    $st = db()->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
    $st->execute([mb_strtolower(trim($email))]);
    return $st->fetch() ?: null;
}

/**
 * Un nombre presentable a partir del correo: ana.perez@… -> "Ana Perez".
 *
 * Nadie escribe su nombre en este flujo —el formulario es solo el correo—, y
 * "Sin nombre" en la cabecera de tu propia cuenta se lee como un error de la
 * página. Esto acierta lo bastante a menudo, y quien no se reconozca podrá
 * cambiarlo cuando haya perfil editable.
 */
function nombreDesdeCorreo(string $email): string
{
    $local  = explode('@', $email)[0];
    $nombre = trim(mb_convert_case(
        str_replace(['.', '_', '-', '+'], ' ', $local),
        MB_CASE_TITLE,
        'UTF-8'
    ));

    return $nombre !== '' ? mb_substr($nombre, 0, 120) : 'Sin nombre';
}

/**
 * Encuentra la cuenta de ese correo o la crea.
 *
 * Solo se llama después de comprobar un código, así que llegar aquí ya prueba
 * que quien lo pide abre ese buzón. Por eso el correo se da por verificado.
 *
 * @return array{0:bool,1:string} [ok, id como texto o mensaje de error]
 */
function buscarOCrearUsuario(string $email): array
{
    $email = mb_strtolower(trim($email));
    $u     = buscarUsuarioPorEmail($email);

    if ($u) {
        if ($u['estado'] !== 'activo') {
            return [false, 'Esta cuenta está suspendida.'];
        }

        // Cuenta que venía de Google sin verificar, o de antes de esto.
        if (empty($u['email_verificado_en'])) {
            db()->prepare('UPDATE usuarios SET email_verificado_en = NOW() WHERE id = ?')
                ->execute([$u['id']]);
        }

        return [true, (string) $u['id']];
    }

    $st = db()->prepare(
        'INSERT INTO usuarios (nombre, email, email_verificado_en) VALUES (?, ?, NOW())'
    );
    $st->execute([nombreDesdeCorreo($email), $email]);

    return [true, (string) db()->lastInsertId()];
}


// ------------------------------------------------- códigos de un solo uso ----

/**
 * Borra códigos caducados de vez en cuando.
 *
 * Uno de cada cincuenta accesos hace la limpieza. No hay cron en este hosting y
 * la tabla crece sola; borrar en cada petición sería una escritura de más el
 * 100% de las veces para conseguir exactamente lo mismo.
 */
function purgarCodigosViejos(): void
{
    if (random_int(1, 50) !== 1) return;

    db()->prepare('DELETE FROM codigos_acceso WHERE expira_en < DATE_SUB(NOW(), INTERVAL 1 DAY)')
        ->execute();
}

/**
 * Genera un código, lo guarda y lo manda por correo.
 *
 * @return array{0:bool,1:string} [ok, mensaje para enseñar]
 */
function solicitarCodigo(string $email): array
{
    $email = mb_strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        return [false, 'Ese correo no tiene buena pinta. Revísalo.'];
    }

    $pdo = db();
    purgarCodigosViejos();

    // Freno por correo. Las dos cuentas salen del reloj de MySQL y no del de
    // PHP: si las zonas horarias de los dos no coinciden —y en hosting
    // compartido pasa— comparar fechas entre ambos da resultados absurdos.
    $espera = CODIGO_ESPERA_SEG;
    $st = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(creado_en > DATE_SUB(NOW(), INTERVAL $espera SECOND)), 0) AS recientes
           FROM codigos_acceso
          WHERE email = ? AND creado_en > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    $st->execute([$email]);
    $porCorreo = $st->fetch();

    if ((int) $porCorreo['recientes'] > 0) {
        return [false, 'Acabamos de enviarte uno. Espera un minuto antes de pedir otro.'];
    }
    if ((int) $porCorreo['total'] >= CODIGO_MAX_POR_HORA) {
        return [false, 'Has pedido demasiados códigos. Prueba dentro de un rato o entra con Google.'];
    }

    // Freno por IP: sin esto, el de arriba se esquiva pidiendo códigos a mil
    // correos distintos, que es como se usa un formulario así para inundar de
    // mensajes buzones ajenos con nuestro dominio en el remitente.
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM codigos_acceso
          WHERE ip = ? AND creado_en > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
    );
    $st->execute([ipBinaria()]);

    if ((int) $st->fetchColumn() >= CODIGO_MAX_IP_HORA) {
        return [false, 'Demasiadas peticiones desde esta conexión. Prueba más tarde.'];
    }

    // Un código vivo por correo: al pedir uno nuevo, el anterior deja de valer.
    // Si no, quien pide tres seguidos tiene tres códigos buenos a la vez y
    // acaba probando el del primer correo, que es el que tiene más a mano.
    $pdo->prepare('UPDATE codigos_acceso SET usado_en = NOW() WHERE email = ? AND usado_en IS NULL')
        ->execute([$email]);

    // random_int y no rand(): este número es la credencial entera.
    $codigo   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $vigencia = CODIGO_VIGENCIA_MIN;

    $pdo->prepare(
        "INSERT INTO codigos_acceso (email, codigo_hash, expira_en, ip)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL $vigencia MINUTE), ?)"
    )->execute([$email, password_hash($codigo, PASSWORD_DEFAULT), ipBinaria()]);

    $id = (int) $pdo->lastInsertId();

    if (!enviarCodigoAcceso($email, $codigo, CODIGO_VIGENCIA_MIN)) {
        // El envío falló, así que este código no existe para nadie. Se borra
        // para que no cuente contra el límite por hora: si no, un problema del
        // servidor de correo dejaría a la persona sin poder reintentar.
        $pdo->prepare('DELETE FROM codigos_acceso WHERE id = ?')->execute([$id]);
        return [false, 'No pudimos enviar el correo. Inténtalo otra vez o entra con Google.'];
    }

    return [true, 'Te enviamos un código a ' . $email];
}

/**
 * Comprueba el código y devuelve el usuario, creándolo si es la primera vez.
 *
 * @return array{0:bool,1:string} [ok, id como texto o mensaje de error]
 */
function verificarCodigo(string $email, string $codigo): array
{
    $email = mb_strtolower(trim($email));

    // Que se pueda pegar "123 456" o "123-456" tal como venga del correo.
    $codigo = preg_replace('/\D+/', '', $codigo);

    if (strlen((string) $codigo) !== 6) {
        return [false, 'El código son seis cifras.'];
    }

    $pdo = db();

    $st = $pdo->prepare(
        'SELECT id, codigo_hash, intentos
           FROM codigos_acceso
          WHERE email = ? AND usado_en IS NULL AND expira_en > NOW()
       ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$email]);
    $fila = $st->fetch();

    if (!$fila) {
        return [false, 'Ese código caducó o ya se usó. Pide uno nuevo.'];
    }

    if ((int) $fila['intentos'] >= CODIGO_MAX_INTENTOS) {
        $pdo->prepare('UPDATE codigos_acceso SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);
        return [false, 'Demasiados intentos con ese código. Pide uno nuevo.'];
    }

    // El intento se cuenta ANTES de comprobar. Contarlo después deja la puerta
    // abierta a quien corte la conexión al ver que falla: el contador nunca
    // subiría y el millón de combinaciones estaría disponible entero.
    $pdo->prepare('UPDATE codigos_acceso SET intentos = intentos + 1 WHERE id = ?')->execute([$fila['id']]);

    if (!password_verify((string) $codigo, $fila['codigo_hash'])) {
        $quedan = CODIGO_MAX_INTENTOS - ((int) $fila['intentos'] + 1);
        return [false, $quedan > 0
            ? "Código incorrecto. Te quedan $quedan intentos."
            : 'Código incorrecto. Pide uno nuevo.'];
    }

    $pdo->prepare('UPDATE codigos_acceso SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);

    return buscarOCrearUsuario($email);
}


// ---------------------------------------------------------------- Google ----

/**
 * Entrada por Google: encuentra la cuenta, la enlaza o la crea.
 *
 * @param array $perfil sub, email, email_verified, name, picture
 * @return array{0:bool,1:string} [ok, id como texto o mensaje de error]
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

    // 2. Primera vez con Google, pero quizá ya existe la cuenta —creada al
    //    entrar con un código a ese mismo correo.
    //
    //    Solo se enlazan si Google confirma que el correo está verificado. Sin
    //    esa comprobación, cualquiera que cree una cuenta de Google con el
    //    correo de otra persona se quedaría con su cuenta de aquí.
    $existente  = buscarUsuarioPorEmail($email);
    $verificado = !empty($perfil['email_verified']);

    if ($existente && !$verificado) {
        return [false, 'Ese correo ya está registrado. Entra pidiendo un código.'];
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
            // Sin el permiso 'profile' Google no manda nombre (ver googleScope()
            // en google.php y por qué está desactivado): se deriva del correo,
            // igual que en el acceso por código.
            $nombre = trim((string) ($perfil['name'] ?? ''));
            if ($nombre === '') {
                $nombre = nombreDesdeCorreo($email);
            }

            $pdo->prepare(
                'INSERT INTO usuarios (nombre, email, email_verificado_en, avatar_url)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $nombre,
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
