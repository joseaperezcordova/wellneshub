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
        return ['error', 'Esta cuenta está suspendida.'];
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
        'sitio_web' => 'Sitio web',
    ];
}

/**
 * Los que existen de verdad en la base ahora mismo.
 *
 * Las migraciones 17 y 18 se aplican a mano, así que entre publicar el código y
 * ejecutarlas hay un rato en el que alguna columna no está. Preguntando aquí,
 * el formulario enseña lo que puede guardar y nada más: un campo que se rellena
 * y se pierde es peor que un campo que no aparece.
 */
function camposContactoDisponibles(): array
{
    $vivos = [];

    foreach (camposContactoOrganizador() as $columna => $etiqueta) {
        if (columnaExiste('usuarios', $columna)) $vivos[$columna] = $etiqueta;
    }

    return $vivos;
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
 * ¿Trae el formulario un nombre de organizador con algo escrito?
 *
 * REQ-000-XX lo volvió obligatorio. No hace falta consultar la cuenta: el
 * campo siempre llega precargado con el nombre que ya hubiera guardado (ver
 * form-evento.php), así que si llega vacío es porque alguien lo borró a
 * propósito —y guardarContactoOrganizador() lo dejaría tal cual estaba, sin
 * avisar de que no se guardó nada—.
 */
function nombreOrganizadorValido(array $post): bool
{
    return trim((string) ($post['org_nombre'] ?? '')) !== '';
}

/**
 * Guarda la información de contacto del organizador (REQ-00012).
 *
 * Se llama al publicar y al editar una actividad: el requerimiento pide que
 * «Publicar» haga las dos cosas, crear la actividad y dejar estos datos en la
 * cuenta para no tener que volver a escribirlos.
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

    foreach (array_keys(camposContactoDisponibles()) as $columna) {
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
 *
 * La comprobación de columna es la misma historia que el teléfono de la
 * migración 15: las migraciones de este proyecto se ejecutan a mano, y entre
 * publicar el código y aplicarlas pasa un rato. Sin esto, ese rato sería
 * «nadie puede crear cuenta». Se prefiere perder el registro de la aceptación
 * —que se sigue exigiendo en pantalla— antes que cerrar la puerta.
 */
function registrarAceptacionLegal(int $usuarioId): void
{
    if (!columnaExiste('usuarios', 'acepto_legal_en')) {
        error_log('usuarios.acepto_legal_en no existe todavía: falta ejecutar '
            . 'database/migracion-16-aceptacion-legal.sql. La aceptación no quedó registrada.');
        return;
    }

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
        return ['error', 'El código son seis cifras.'];
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
        return ['error', 'Ese código caducó o ya se usó. Pide uno nuevo.'];
    }

    if ((int) $fila['intentos'] >= CODIGO_MAX_INTENTOS) {
        $pdo->prepare('UPDATE codigos_acceso SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);
        return ['error', 'Demasiados intentos con ese código. Pide uno nuevo.'];
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
        return ['error', 'Google no devolvió los datos necesarios.'];
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
        if ($u['estado'] !== 'activo') return ['error', 'Esta cuenta está suspendida.'];
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
        return ['error', 'Ese correo ya está registrado. Entra pidiendo un código.'];
    }

    if (!$existente) return ['nueva', ''];

    if ($existente['estado'] !== 'activo') return ['error', 'Esta cuenta está suspendida.'];

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
        return ['error', 'No se pudo completar el acceso con Google.'];
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
