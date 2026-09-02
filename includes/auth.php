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
 * La consecuencia práctica era que no había "registro" separado: la primera vez
 * que alguien entraba con un correo desconocido, la cuenta se creaba sola.
 *
 * REQ-00008 metió un paso en medio y no se puede saltar: nadie tiene cuenta sin
 * haber aceptado los Términos y el Aviso de Privacidad. Así que identificarse y
 * darse de alta dejaron de ser lo mismo —ver el bloque de resolverPorCorreo()—,
 * y la cuenta la crea completar-registro.php, después de la casilla.
 */

declare(strict_types=1);

const CODIGO_VIGENCIA_MIN = 15;  // minutos que vale un código
const CODIGO_MAX_INTENTOS = 5;   // fallos sobre un mismo código antes de anularlo
const CODIGO_ESPERA_SEG   = 60;  // entre dos envíos al mismo correo
const CODIGO_MAX_POR_HORA = 5;   // envíos por correo y hora
const CODIGO_MAX_IP_HORA  = 15;  // envíos por IP y hora, sea cual sea el correo

/**
 * Cambio de correo de la cuenta (punto 18 de docs/pendientes.md): mismos
 * números que CODIGO_* de arriba, por el mismo motivo que ya se explicó
 * para el correo de contacto por actividad —es el mismo riesgo, un código
 * de un solo uso de seis cifras por correo, y no hay motivo para que la
 * ventana de intentos o de espera sea distinta aquí—.
 */
const CAMBIO_CORREO_VIGENCIA_MIN = 15;
const CAMBIO_CORREO_MAX_INTENTOS = 5;
const CAMBIO_CORREO_ESPERA_SEG   = 60;
const CAMBIO_CORREO_MAX_POR_HORA = 5;
const CAMBIO_CORREO_MAX_IP_HORA  = 15;


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

    // Rastros de los flujos de entrada y de alta: ya no hacen falta y no deben
    // sobrevivir. 'alta_pendiente' sobre todo — es lo que autoriza a crear una
    // cuenta, y una vez dentro no puede quedarse esperando en la sesión.
    // 'volver_a' no se toca aquí: lo lee la página que redirige justo después.
    unset(
        $_SESSION['codigo_email'], $_SESSION['codigo_error'], $_SESSION['codigo_aviso'],
        $_SESSION['alta_pendiente'], $_SESSION['acepta_legal']
    );

    db()->prepare('UPDATE usuarios SET ultimo_acceso_en = NOW() WHERE id = ?')
        ->execute([$usuarioId]);

    // Se llega aquí justo antes de un redirect (código o Google), así que el
    // evento no puede dispararse en esta misma respuesta: queda en cola para
    // que layout.php lo dispare en la página de destino.
    $_SESSION['eventos_ga'] = [['nombre' => 'login', 'params' => []]];
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
        // pulsa «Publicar actividad» sin sesión aterriza en la portada después del
        // login y tiene que volver a buscar el botón.
        guardarDestinoLogin((string) ($_SERVER['REQUEST_URI'] ?? '/'));
        redirigir(url('login'));
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
 * IDENTIFICAR NO ES DAR DE ALTA (REQ-00008)
 *
 * Antes esto era una sola función, buscarOCrearUsuario(): comprobabas el código
 * y salías con una cuenta, existiera antes o no. El requerimiento parte eso en
 * dos, porque entre «he demostrado que este correo es mío» y «tengo cuenta»
 * tiene que caber una pantalla: la de aceptar los Términos y el Aviso de
 * Privacidad.
 *
 * De ahí este contrato, que usan los dos caminos de entrada —el código por
 * correo y Google—:
 *
 *   ['entra', id]        ya tiene cuenta: adentro
 *   ['nueva', '']        no la tiene: hay que pasar por completar-registro.php
 *   ['error', mensaje]   no puede entrar, y esto es lo que hay que decirle
 *
 * Que los dos caminos hablen el mismo idioma es lo que evita que la puerta
 * legal quede puesta en uno y olvidada en el otro, que es exactamente lo que el
 * requerimiento se preocupa de prohibir para Google.
 */

/**
 * ¿Este correo ya tiene cuenta?
 *
 * Solo se llama después de comprobar un código, así que llegar aquí ya prueba
 * que quien lo pide abre ese buzón. Por eso el correo se da por verificado.
 *
 * @return array{0:string,1:string}
 */
function resolverPorCorreo(string $email): array
{
    $email = mb_strtolower(trim($email));
    $u     = buscarUsuarioPorEmail($email);

    if (!$u) return ['nueva', ''];

    if ($u['estado'] !== 'activo') {
        return ['error', t('auth.cuenta_suspendida')];
    }

    // Cuenta que venía de Google sin verificar, o de antes de esto.
    if (empty($u['email_verificado_en'])) {
        db()->prepare('UPDATE usuarios SET email_verificado_en = NOW() WHERE id = ?')
            ->execute([$u['id']]);
    }

    return ['entra', (string) $u['id']];
}

/**
 * Crea la cuenta de quien acaba de aceptar los documentos.
 *
 * Solo se llama desde completar-registro.php, y solo con la casilla marcada.
 */
function crearUsuarioPorCorreo(string $email): int
{
    $email = mb_strtolower(trim($email));

    db()->prepare(
        'INSERT INTO usuarios (nombre, email, email_verificado_en) VALUES (?, ?, NOW())'
    )->execute([nombreDesdeCorreo($email), $email]);

    return (int) db()->lastInsertId();
}

/**
 * La ficha completa de quien tiene la sesión abierta, para «Mi cuenta».
 *
 * usuarioActual() no sirve aquí: trae solo las columnas que necesitan la
 * cabecera y los permisos, y a propósito —es una consulta que se hace en TODAS
 * las páginas—. Esta es para la única pantalla que enseña la cuenta entera.
 */
function fichaDeUsuario(int $usuarioId): ?array
{
    $st = db()->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
    $st->execute([$usuarioId]);

    return $st->fetch() ?: null;
}

/**
 * Los campos de contacto del organizador, y en qué columna vive cada uno.
 *
 * Una sola lista para que la pantalla de «Mi cuenta», la sección del formulario
 * de publicar y el guardado hablen de lo mismo. Escritos tres veces, un día uno
 * de los tres se queda corto.
 *
 * 'telefono' aparece como WhatsApp al publicar y como «Teléfono / WhatsApp» en
 * Mi cuenta: es el mismo número. Dos columnas para el teléfono de una misma
 * persona acaban diciendo cosas distintas y nadie sabe cuál vale.
 *
 * @return array<string, string> columna => etiqueta
 */
function camposContactoOrganizador(): array
{
    return [
        'telefono'  => 'WhatsApp',
        'instagram' => 'Instagram',
        'sitio_web' => t('evento.campo.sitio_web_organizador'),
    ];
}

/** Deja el Instagram como @cuenta, venga como venga. */
function normalizarInstagram(string $valor): string
{
    $valor = trim($valor);
    if ($valor === '') return '';

    // Quien copia su perfil desde el navegador pega la URL entera. Guardarla
    // así deja «https://instagram.com/yogabaja/» donde debería leerse
    // «@yogabaja», y encima no se puede comparar con lo que escribió otro.
    if (preg_match('~instagram\.com/([^/?#\s]+)~i', $valor, $m)) $valor = $m[1];

    $valor = ltrim($valor, '@');
    $valor = preg_replace('/[^A-Za-z0-9._]/', '', $valor);

    return $valor === '' ? '' : '@' . mb_substr((string) $valor, 0, 119);
}

/** Una dirección web utilizable: sin esquema, un navegador la lee como ruta. */
function normalizarSitioWeb(string $valor): string
{
    $valor = trim($valor);
    if ($valor === '') return '';

    if (!preg_match('~^https?://~i', $valor)) $valor = 'https://' . $valor;

    return mb_substr($valor, 0, 500);
}

/**
 * Guarda la información de contacto del organizador.
 *
 * Hoy solo la llama mi-cuenta.php —hasta el 2026-09-02 también la llamaban
 * evento-nuevo.php/evento-editar.php, con su propio acordeón «Datos del
 * organizador» duplicando estos mismos campos; se quitó de ahí a pedido del
 * cliente, porque «Mi cuenta» ya es donde se editan—.
 *
 * El nombre solo se cambia si viene con algo. Los demás sí se pueden vaciar a
 * propósito —quien borra su Instagram quiere borrarlo—, pero borrar el nombre
 * dejaría la cuenta sin cómo llamarla y sus actividades sin organizador.
 */
function guardarContactoOrganizador(int $usuarioId, array $datos): void
{
    $nombre = trim((string) ($datos['org_nombre'] ?? ''));
    if ($nombre !== '') {
        db()->prepare('UPDATE usuarios SET nombre = ? WHERE id = ?')
            ->execute([mb_substr($nombre, 0, 120), $usuarioId]);
    }

    foreach (array_keys(camposContactoOrganizador()) as $columna) {
        $valor = trim((string) ($datos['org_' . $columna] ?? ''));

        if ($columna === 'instagram') $valor = normalizarInstagram($valor);
        if ($columna === 'sitio_web') $valor = normalizarSitioWeb($valor);
        if ($columna === 'telefono')  $valor = mb_substr($valor, 0, 30);

        db()->prepare("UPDATE usuarios SET `$columna` = ? WHERE id = ?")
            ->execute([$valor !== '' ? $valor : null, $usuarioId]);
    }
}

/**
 * Deja constancia de que aceptó los Términos y el Aviso de Privacidad.
 *
 * COALESCE y no NOW() a secas: vale la primera vez que aceptó, no la última vez
 * que se le preguntó. Si alguna vez se vuelve a pedir —porque cambien los
 * documentos— la fecha original es la que prueba el alta.
 */
function registrarAceptacionLegal(int $usuarioId): void
{
    db()->prepare(
        'UPDATE usuarios SET acepto_legal_en = COALESCE(acepto_legal_en, NOW()) WHERE id = ?'
    )->execute([$usuarioId]);
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
        return [false, t('auth.correo_invalido')];
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
        return [false, t('auth.espera_minuto')];
    }
    if ((int) $porCorreo['total'] >= CODIGO_MAX_POR_HORA) {
        return [false, t('auth.demasiados_codigos')];
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
        return [false, t('auth.demasiadas_peticiones')];
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
        return [false, t('auth.error_envio')];
    }

    return [true, t('auth.codigo_enviado') . ' ' . $email];
}

/**
 * Comprueba el código.
 *
 * Ya NO crea la cuenta (REQ-00008): eso pasó a completar-registro.php, después
 * de aceptar los documentos. Devuelve el contrato de tres estados que describe
 * el bloque de resolverPorCorreo(); los errores de código propios de aquí
 * también salen como ['error', mensaje].
 *
 * @return array{0:string,1:string} ['entra'|'nueva'|'error', id o mensaje]
 */
function verificarCodigo(string $email, string $codigo): array
{
    $email = mb_strtolower(trim($email));

    // Que se pueda pegar "123 456" o "123-456" tal como venga del correo.
    $codigo = preg_replace('/\D+/', '', $codigo);

    if (strlen((string) $codigo) !== 6) {
        return ['error', t('auth.codigo_formato')];
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
        return ['error', t('auth.codigo_caducado')];
    }

    if ((int) $fila['intentos'] >= CODIGO_MAX_INTENTOS) {
        $pdo->prepare('UPDATE codigos_acceso SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);
        return ['error', t('auth.demasiados_intentos')];
    }

    // El intento se cuenta ANTES de comprobar. Contarlo después deja la puerta
    // abierta a quien corte la conexión al ver que falla: el contador nunca
    // subiría y el millón de combinaciones estaría disponible entero.
    $pdo->prepare('UPDATE codigos_acceso SET intentos = intentos + 1 WHERE id = ?')->execute([$fila['id']]);

    if (!password_verify((string) $codigo, $fila['codigo_hash'])) {
        $quedan = CODIGO_MAX_INTENTOS - ((int) $fila['intentos'] + 1);
        return [false, $quedan > 0
            ? sprintf(t('auth.codigo_incorrecto_quedan'), $quedan)
            : t('auth.codigo_incorrecto_final')];
    }

    $pdo->prepare('UPDATE codigos_acceso SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);

    // El código ya está gastado: a partir de aquí, quien siga adelante es quien
    // abre ese buzón. Lo que devuelve resolverPorCorreo() decide si entra o si
    // tiene que pasar antes por la casilla legal.
    return resolverPorCorreo($email);
}


// ---------------------------------------------------------------- Google ----

/**
 * Google dice quién eres. No dice que hayas aceptado nada (REQ-00008).
 *
 * Esto era una sola función que resolvía y creaba de una vez, y ahí estaba el
 * problema que el requerimiento nombra: pulsar «Continuar con Google» y aceptar
 * la pantalla de Google creaba la cuenta, así que autenticarse EQUIVALÍA a
 * aceptar los documentos. Ahora resolver y crear son dos pasos, y entre medias
 * cabe la casilla.
 *
 * Enlazar una cuenta que ya existe SÍ se hace aquí, y no es una excepción a lo
 * anterior: esa cuenta ya está creada, no se está dando de alta a nadie. Lo
 * único que cambia es que a partir de ahora también se puede entrar con Google.
 *
 * @param array $perfil sub, email, email_verified, name, picture
 * @return array{0:string,1:string} ['entra'|'nueva'|'error', id o mensaje]
 */
function resolverGoogle(array $perfil): array
{
    $sub   = (string) ($perfil['sub'] ?? '');
    $email = mb_strtolower(trim((string) ($perfil['email'] ?? '')));

    if ($sub === '' || $email === '') {
        return ['error', t('auth.google_sin_datos')];
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
        if ($u['estado'] !== 'activo') return ['error', t('auth.cuenta_suspendida')];
        return ['entra', (string) $u['id']];
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
        return ['error', t('auth.correo_ya_registrado')];
    }

    if (!$existente) return ['nueva', ''];

    if ($existente['estado'] !== 'activo') return ['error', t('auth.cuenta_suspendida')];

    $usuarioId = (int) $existente['id'];

    $pdo->beginTransaction();
    try {
        // Aprovechamos para dar el correo por verificado y poner avatar si la
        // cuenta no tenía.
        $pdo->prepare(
            'UPDATE usuarios
                SET email_verificado_en = COALESCE(email_verificado_en, NOW()),
                    avatar_url          = COALESCE(avatar_url, ?)
              WHERE id = ?'
        )->execute([$perfil['picture'] ?? null, $usuarioId]);

        enlazarIdentidadGoogle($usuarioId, $sub, $email);

        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        error_log('Enlace con Google fallido: ' . $ex->getMessage());
        return ['error', t('login.error.google')];
    }

    return ['entra', (string) $usuarioId];
}

function enlazarIdentidadGoogle(int $usuarioId, string $sub, string $email): void
{
    db()->prepare(
        'INSERT INTO identidades_oauth (usuario_id, proveedor, proveedor_uid, email_proveedor)
         VALUES (?, "google", ?, ?)'
    )->execute([$usuarioId, $sub, $email]);
}

/**
 * Crea la cuenta de quien llegó por Google y ya aceptó los documentos.
 *
 * Solo se llama desde completar-registro.php, y solo con la casilla marcada.
 * Devuelve null si algo falló; el mensaje queda en el log.
 */
function crearUsuarioConGoogle(array $perfil): ?int
{
    $sub   = (string) ($perfil['sub'] ?? '');
    $email = mb_strtolower(trim((string) ($perfil['email'] ?? '')));
    $pdo   = db();

    // Sin el permiso 'profile' Google no manda nombre (ver googleScope() en
    // google.php y por qué está desactivado): se deriva del correo, igual que
    // en el acceso por código.
    $nombre = trim((string) ($perfil['name'] ?? ''));
    if ($nombre === '') $nombre = nombreDesdeCorreo($email);

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'INSERT INTO usuarios (nombre, email, email_verificado_en, avatar_url)
             VALUES (?, ?, ?, ?)'
        )->execute([
            $nombre,
            $email,
            !empty($perfil['email_verified']) ? date('Y-m-d H:i:s') : null,
            $perfil['picture'] ?? null,
        ]);
        $usuarioId = (int) $pdo->lastInsertId();

        enlazarIdentidadGoogle($usuarioId, $sub, $email);

        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        error_log('Alta con Google fallida: ' . $ex->getMessage());
        return null;
    }

    return $usuarioId;
}


// ------------------------------------------- cambio de correo de la cuenta --
// Punto 18 de docs/pendientes.md, migración 25. Mismo patrón que
// solicitarCodigoCorreoContacto()/confirmarCodigoCorreoContacto()
// (includes/eventos.php) para el correo de contacto por actividad, pero
// aquí el código confirma un cambio de EMAIL DE CUENTA —la credencial con la
// que se entra—, así que hay dos comprobaciones de más que allá no hacían
// falta: que el correo nuevo no tenga ya cuenta, y avisar al correo viejo
// cuando el cambio se confirma, por si no fue su dueño quien lo pidió.

/** ¿Hay un código pedido y todavía vivo para esta cuenta? Si lo hay, a qué
 *  correo se mandó. */
function correoCambioPendiente(int $usuarioId): ?string
{
    $st = db()->prepare(
        'SELECT email_nuevo FROM codigos_cambio_correo
          WHERE usuario_id = ? AND usado_en IS NULL AND expira_en > NOW()
       ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$usuarioId]);
    $email = $st->fetchColumn();

    return $email !== false ? (string) $email : null;
}

/** Limpieza de códigos caducados. Mismo criterio que purgarCodigosViejos(). */
function purgarCambiosCorreoViejos(): void
{
    if (random_int(1, 50) !== 1) return;

    db()->prepare('DELETE FROM codigos_cambio_correo WHERE expira_en < DATE_SUB(NOW(), INTERVAL 1 DAY)')
        ->execute();
}

/**
 * Genera un código, lo guarda y lo manda al correo NUEVO.
 *
 * @return array{0:bool,1:string} [ok, mensaje para enseñar]
 */
function solicitarCambioCorreo(int $usuarioId, string $emailNuevo): array
{
    $emailNuevo = mb_strtolower(trim($emailNuevo));

    if (!filter_var($emailNuevo, FILTER_VALIDATE_EMAIL) || mb_strlen($emailNuevo) > 190) {
        return [false, t('auth.correo_invalido')];
    }

    $pdo = db();

    $st = $pdo->prepare('SELECT email FROM usuarios WHERE id = ?');
    $st->execute([$usuarioId]);
    $actual = (string) $st->fetchColumn();

    if ($emailNuevo === mb_strtolower($actual)) {
        return [false, t('cuenta.cambio_correo.error_mismo')];
    }

    // Que no tenga ya cuenta: si dos personas acabaran compartiendo un
    // correo, el código de acceso ya no sabría a cuál de las dos entrar.
    $st = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE email = ?');
    $st->execute([$emailNuevo]);
    if ((int) $st->fetchColumn() > 0) {
        return [false, t('cuenta.cambio_correo.error_registrado')];
    }

    purgarCambiosCorreoViejos();

    $espera = CAMBIO_CORREO_ESPERA_SEG;
    $st = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(creado_en > DATE_SUB(NOW(), INTERVAL $espera SECOND)), 0) AS recientes
           FROM codigos_cambio_correo
          WHERE usuario_id = ? AND creado_en > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    $st->execute([$usuarioId]);
    $porCuenta = $st->fetch();

    if ((int) $porCuenta['recientes'] > 0) {
        return [false, t('auth.espera_minuto')];
    }
    if ((int) $porCuenta['total'] >= CAMBIO_CORREO_MAX_POR_HORA) {
        return [false, t('auth.demasiados_codigos')];
    }

    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM codigos_cambio_correo
          WHERE ip = ? AND creado_en > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
    );
    $st->execute([ipBinaria()]);
    if ((int) $st->fetchColumn() >= CAMBIO_CORREO_MAX_IP_HORA) {
        return [false, t('auth.demasiadas_peticiones')];
    }

    // Un código vivo por cuenta: pedir uno nuevo invalida el anterior.
    $pdo->prepare('UPDATE codigos_cambio_correo SET usado_en = NOW() WHERE usuario_id = ? AND usado_en IS NULL')
        ->execute([$usuarioId]);

    $codigo   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $vigencia = CAMBIO_CORREO_VIGENCIA_MIN;

    $pdo->prepare(
        "INSERT INTO codigos_cambio_correo (usuario_id, email_nuevo, codigo_hash, expira_en, ip)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL $vigencia MINUTE), ?)"
    )->execute([$usuarioId, $emailNuevo, password_hash($codigo, PASSWORD_DEFAULT), ipBinaria()]);

    $id = (int) $pdo->lastInsertId();

    if (!enviarCodigoCambioCorreo($emailNuevo, $codigo, $vigencia)) {
        $pdo->prepare('DELETE FROM codigos_cambio_correo WHERE id = ?')->execute([$id]);
        return [false, t('auth.error_envio')];
    }

    return [true, sprintf(t('cuenta.cambio_correo.enviado'), $emailNuevo)];
}

/**
 * Comprueba el código y, si es el bueno, cambia el correo de la cuenta.
 *
 * Avisa al correo VIEJO justo después de cambiarlo —no antes—: es el único
 * paso de los cuatro que detecta un secuestro (alguien que no es el dueño
 * pidió el cambio), y solo tiene sentido si el correo viejo sigue siendo el
 * dueño real cuando lo lee, cosa que ya no se puede comprobar después.
 *
 * @return array{0:bool,1:string} [ok, mensaje para enseñar]
 */
function confirmarCambioCorreo(int $usuarioId, string $codigo): array
{
    $codigo = preg_replace('/\D+/', '', $codigo);

    if (strlen((string) $codigo) !== 6) {
        return [false, t('auth.codigo_formato')];
    }

    $pdo = db();

    $st = $pdo->prepare(
        'SELECT id, email_nuevo, codigo_hash, intentos
           FROM codigos_cambio_correo
          WHERE usuario_id = ? AND usado_en IS NULL AND expira_en > NOW()
       ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$usuarioId]);
    $fila = $st->fetch();

    if (!$fila) {
        return [false, t('auth.codigo_caducado')];
    }

    if ((int) $fila['intentos'] >= CAMBIO_CORREO_MAX_INTENTOS) {
        $pdo->prepare('UPDATE codigos_cambio_correo SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);
        return [false, t('auth.demasiados_intentos')];
    }

    $pdo->prepare('UPDATE codigos_cambio_correo SET intentos = intentos + 1 WHERE id = ?')->execute([$fila['id']]);

    if (!password_verify((string) $codigo, $fila['codigo_hash'])) {
        $quedan = CAMBIO_CORREO_MAX_INTENTOS - ((int) $fila['intentos'] + 1);
        return [false, $quedan > 0
            ? sprintf(t('auth.codigo_incorrecto_quedan'), $quedan)
            : t('auth.codigo_incorrecto_final')];
    }

    // Que no se haya registrado nadie más con ese correo mientras el código
    // estaba pendiente —podían pasar hasta CAMBIO_CORREO_VIGENCIA_MIN
    // minutos entre pedirlo y confirmarlo—.
    $st = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?');
    $st->execute([$fila['email_nuevo'], $usuarioId]);
    if ((int) $st->fetchColumn() > 0) {
        $pdo->prepare('UPDATE codigos_cambio_correo SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);
        return [false, t('cuenta.cambio_correo.error_registrado')];
    }

    $st = $pdo->prepare('SELECT email FROM usuarios WHERE id = ?');
    $st->execute([$usuarioId]);
    $correoViejo = (string) $st->fetchColumn();

    $pdo->prepare('UPDATE codigos_cambio_correo SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);
    $pdo->prepare('UPDATE usuarios SET email = ?, email_verificado_en = NOW() WHERE id = ?')
        ->execute([$fila['email_nuevo'], $usuarioId]);

    if ($correoViejo !== '' && $correoViejo !== $fila['email_nuevo']) {
        enviarAvisoCambioCorreo($correoViejo, (string) $fila['email_nuevo']);
    }

    return [true, sprintf(t('cuenta.cambio_correo.confirmado'), $fila['email_nuevo'])];
}

/** Cancela el código pendiente, sin esperar a que caduque. */
function cancelarCambioCorreo(int $usuarioId): void
{
    db()->prepare('UPDATE codigos_cambio_correo SET usado_en = NOW() WHERE usuario_id = ? AND usado_en IS NULL')
        ->execute([$usuarioId]);
}
