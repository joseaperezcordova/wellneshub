<?php
/**
 * Contactar al organizador: el formulario público de la ficha para la
 * acción "informacion".
 *
 * Sigue el mismo patrón que includes/moderacion.php para reportes: sin
 * cuenta, con límite por IP, y un aviso por correo que el organizador
 * puede responder directamente porque el Reply-To ya trae el correo de
 * quien escribió.
 */

declare(strict_types=1);

const CONTACTO_ESPERA_MIN = 15; // entre dos mensajes de la misma IP al mismo evento

/** ¿Ya escribió esta IP a este organizador hace poco? */
function contactoRepetido(int $eventoId): bool
{
    $st = db()->prepare(
        'SELECT COUNT(*) FROM contactos
          WHERE evento_id = ? AND ip = ?
            AND creado_en > DATE_SUB(NOW(), INTERVAL ' . CONTACTO_ESPERA_MIN . ' MINUTE)'
    );
    $st->execute([$eventoId, ipBinaria()]);

    return (int) $st->fetchColumn() > 0;
}

/**
 * Guarda el mensaje.
 *
 * EL TELÉFONO SE GUARDA SI LA COLUMNA ESTÁ, Y SI NO, NO.
 *
 * Lo añade migracion-15, y las migraciones de este proyecto se ejecutan a mano
 * en phpMyAdmin: entre publicar el código y aplicarlas pasa un rato. Sin este
 * rodeo, ese rato sería el formulario de contacto devolviendo un error 500 a
 * todo el mundo —ya pasó una vez, con el sitio entero—.
 *
 * Perder el teléfono en la base mientras tanto no rompe nada: al organizador le
 * llega igual en el correo, que es para lo que se pide. Lo que no puede pasar
 * es que el mensaje no llegue.
 *
 * Cuando la migración esté aplicada en pruebas y en producción, esto vuelve a
 * ser un único INSERT con las seis columnas.
 */
function crearContacto(int $eventoId, string $nombre, string $email, ?string $telefono, ?string $mensaje): void
{
    $conTelefono = columnaExiste('contactos', 'telefono');

    $columnas = 'evento_id, nombre, email, ' . ($conTelefono ? 'telefono, ' : '') . 'mensaje, ip';
    $huecos   = $conTelefono ? '?, ?, ?, ?, ?, ?' : '?, ?, ?, ?, ?';

    $valores = [$eventoId, mb_substr($nombre, 0, 120), mb_substr($email, 0, 190)];
    if ($conTelefono) {
        $valores[] = ($telefono !== null && $telefono !== '') ? mb_substr($telefono, 0, 30) : null;
    }
    $valores[] = ($mensaje !== null && $mensaje !== '') ? mb_substr($mensaje, 0, 1000) : null;
    $valores[] = ipBinaria();

    db()->prepare("INSERT INTO contactos ($columnas) VALUES ($huecos)")->execute($valores);

    if (!$conTelefono && $telefono !== null && $telefono !== '') {
        error_log('contactos.telefono no existe todavía: falta ejecutar '
            . 'database/migracion-15-telefono-en-contactos.sql. El mensaje se guardó sin teléfono.');
    }
}

/**
 * Avisa al organizador de que alguien quiere contactarlo.
 *
 * A diferencia de avisarAdministradores(), aquí SÍ va un correo por cada
 * mensaje: cada uno es una persona distinta con una pregunta distinta, no
 * hay nada que agrupar. El Reply-To queda puesto al correo de quien
 * escribió, así que el organizador solo tiene que darle "Responder".
 */
function avisarOrganizador(array $ev, string $nombre, string $email, ?string $telefono, ?string $mensaje): void
{
    $cuerpo = "Alguien quiere contactarte por tu actividad publicada en OMDARA.\n\n"
            . 'Actividad:  ' . $ev['titulo'] . "\n"
            . 'Nombre:     ' . $nombre . "\n"
            . 'Correo:     ' . $email . "\n"
            // El teléfono solo aparece si lo dieron: una línea "Teléfono: —" en
            // cada correo enseña a saltarse ese renglón, y el día que sí venga
            // uno tampoco se leerá.
            . ($telefono !== null && $telefono !== '' ? 'Teléfono:   ' . $telefono . "\n" : '')
            . ($mensaje !== null && $mensaje !== '' ? "\nMensaje:\n" . $mensaje . "\n\n" : "\n")
            . "Para responder, contesta directamente este correo: llega a $email.\n\n"
            . urlEvento($ev) . "\n";

    enviarCorreo(
        $ev['organizador_email'],
        $nombre . ' quiere contactarte por «' . $ev['titulo'] . '»',
        $cuerpo,
        $email
    );
}


// -------------------------------------------- contacto general del sitio ---

const CONTACTO_SITIO_ESPERA_MIN = 15; // entre dos mensajes de la misma IP

/** ¿Ya escribió esta IP hace poco? Mismo criterio que contactoRepetido(). */
function contactoSitioRepetido(): bool
{
    $st = db()->prepare(
        'SELECT COUNT(*) FROM mensajes_contacto
          WHERE ip = ? AND creado_en > DATE_SUB(NOW(), INTERVAL ' . CONTACTO_SITIO_ESPERA_MIN . ' MINUTE)'
    );
    $st->execute([ipBinaria()]);

    return (int) $st->fetchColumn() > 0;
}

/**
 * Por qué puede escribir alguien: clave interna => lo que se lee en el menú.
 *
 * La clave es lo que se guarda y lo que se compara; la etiqueta, lo que se
 * enseña. Separadas porque cambiar cómo se lee un motivo no debería obligar a
 * tocar lo que hay guardado en filas anteriores, que es el mismo criterio de
 * categoriasMenu() y de ordenesBusqueda().
 */
function motivosContacto(?string $idioma = null): array
{
    return [
        'general'    => t('contacto.motivo.general', $idioma),
        'actividad'  => t('contacto.motivo.actividad', $idioma),
        'cuenta'     => t('contacto.motivo.cuenta', $idioma),
        'organizador'=> t('contacto.motivo.organizador', $idioma),
        'reporte'    => t('contacto.motivo.reporte', $idioma),
        'otro'       => t('contacto.motivo.otro', $idioma),
    ];
}

/**
 * ¿Este motivo obliga a decir de qué actividad se habla?
 *
 * Los dos que se refieren a una en concreto. Sin el nombre, un «problema con
 * una actividad» llega a administración sin decir con cuál, y la primera
 * respuesta tiene que ser preguntarlo.
 */
function motivoPideActividad(string $motivo): bool
{
    return in_array($motivo, ['actividad', 'reporte'], true);
}

/** Los cuatro estados del hilo. Solo se leen: no hay pantalla para cambiarlos. */
function estadosContacto(): array
{
    return [
        'nuevo'      => 'Nuevo',
        'revision'   => 'En revisión',
        'respondido' => 'Respondido',
        'cerrado'    => 'Cerrado',
    ];
}

/**
 * Guarda el mensaje.
 *
 * El motivo y la actividad se escriben solo si existen sus columnas
 * (migración 19), por lo mismo que las cuatro migraciones anteriores: se
 * aplican a mano, y entre publicar el código y ejecutarlas hay un rato en el
 * que el formulario tiene que seguir funcionando. Los dos datos van igual al
 * correo del administrador, que es lo que hace que alguien actúe.
 */
function crearContactoSitio(string $nombre, string $email, string $mensaje,
                            string $motivo = 'general', ?string $actividad = null): void
{
    $conMotivo = columnaExiste('mensajes_contacto', 'motivo');

    $columnas = 'nombre, email, ' . ($conMotivo ? 'motivo, actividad_nombre, ' : '') . 'mensaje, ip';
    $huecos   = $conMotivo ? '?, ?, ?, ?, ?, ?' : '?, ?, ?, ?';

    $valores = [mb_substr($nombre, 0, 120), mb_substr($email, 0, 190)];

    if ($conMotivo) {
        $valores[] = isset(motivosContacto()[$motivo]) ? $motivo : 'general';
        $valores[] = ($actividad !== null && trim($actividad) !== '')
            ? mb_substr(trim($actividad), 0, 200)
            : null;
    }

    $valores[] = mb_substr($mensaje, 0, 1000);
    $valores[] = ipBinaria();

    db()->prepare("INSERT INTO mensajes_contacto ($columnas) VALUES ($huecos)")->execute($valores);

    if (!$conMotivo) {
        error_log('mensajes_contacto.motivo no existe todavía: falta ejecutar '
            . 'database/migracion-19-contacto-motivo.sql. El mensaje se guardó sin motivo ni actividad.');
    }
}

/**
 * Los mensajes recibidos, para el panel de administración.
 *
 * El requerimiento pide guardarlos «para tener un historial»: un historial que
 * nadie puede leer no es un historial, así que hay una pestaña que los enseña.
 * Cambiar el estado de uno es otra cosa y no está hecho —ver docs/pendientes.md.
 */
function mensajesContactoRecientes(int $limite = 100): array
{
    // SELECT * y no una lista de columnas: hasta que se aplique la migración 19
    // faltan tres, y una lista explícita reventaría la pestaña entera.
    $st = db()->prepare(
        'SELECT * FROM mensajes_contacto ORDER BY creado_en DESC LIMIT ' . (int) $limite
    );
    $st->execute();

    return $st->fetchAll();
}

/**
 * Avisa a los administradores de un mensaje del contacto general.
 *
 * Sin el filtro de avisarAdministradores() —agrupar y espaciar—: ese existe
 * porque muchas personas distintas pueden reportar el MISMO evento, y ahí
 * agrupar tiene sentido. Aquí cada mensaje es una persona con un asunto
 * propio; agruparlos sería perder el mensaje de alguien por llegar el mismo
 * día que el de otro.
 */
function avisarAdminsContactoSitio(string $nombre, string $email, string $mensaje,
                                   string $motivo = 'general', ?string $actividad = null): void
{
    $admins = db()->query(
        'SELECT email FROM usuarios WHERE rol = "admin" AND estado = "activo"'
    )->fetchAll();

    if (!$admins) {
        error_log('Mensaje de contacto general recibido y no hay ningún administrador a quien avisar.');
        return;
    }

    // Español fijo: el resto del correo también lo está, y no hay mecanismo
    // de idioma para correos todavía (ver docs/pendientes.md, fase 4).
    $motivoTexto = motivosContacto('es')[$motivo] ?? $motivo;

    $cuerpo = "Alguien escribió desde el formulario de contacto de OMDARA.\n\n"
            . 'Motivo:  ' . $motivoTexto . "\n"
            . 'Nombre:  ' . $nombre . "\n"
            . 'Correo:  ' . $email . "\n"
            // Solo si viene. Una línea "Actividad: —" en cada correo enseña a
            // saltarse ese renglón, y el día que sí traiga una tampoco se leerá.
            . ($actividad !== null && trim($actividad) !== ''
                ? 'Actividad: ' . trim($actividad) . "\n" : '')
            . "\nMensaje:\n" . $mensaje . "\n\n"
            . "Para responder, contesta directamente este correo: llega a $email.\n";

    // El motivo va también en el asunto: quien abre la bandeja decide qué mirar
    // primero por ahí, y «te escribió desde OMDARA» no distingue una alianza de
    // un reporte de contenido.
    $asunto = '[' . $motivoTexto . '] ' . $nombre . ' te escribió desde OMDARA';

    foreach ($admins as $a) {
        enviarCorreo($a['email'], $asunto, $cuerpo, $email);
    }
}
