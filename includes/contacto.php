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
 * Recibe la actividad entera y no solo su id (migración 22, requerimiento del
 * cliente de 2026-09-02: medir la interacción real entre usuarios y
 * actividades) porque organizador_id, ciudad y categoria se guardan como una
 * FOTO del momento del contacto, no como un JOIN en vivo —si el organizador
 * edita la actividad después, la fila ya escrita no debe cambiar con
 * retroactividad—. tipo_cta siempre vale 'informacion' aquí: es el único CTA
 * que llega a este formulario: la variedad la trae contactar.php mismo, que
 * ya redirige fuera si accion_principal es otro.
 */
function crearContacto(array $ev, string $nombre, string $email, ?string $telefono, ?string $mensaje): void
{
    $valores = [
        (int) $ev['id'],
        (int) $ev['usuario_id'],
        (string) $ev['ciudad'],
        (string) $ev['categoria'],
        mb_substr($nombre, 0, 120),
        mb_substr($email, 0, 190),
        ($telefono !== null && $telefono !== '') ? mb_substr($telefono, 0, 30) : null,
        ($mensaje !== null && $mensaje !== '') ? mb_substr($mensaje, 0, 1000) : null,
        ipBinaria(),
    ];

    db()->prepare(
        'INSERT INTO contactos (evento_id, organizador_id, ciudad, categoria, nombre, email, telefono, mensaje, ip)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute($valores);
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
    $cuerpo = "Alguien quiere contactarte por tu actividad publicada en Omdara.\n\n"
            . 'Actividad:  ' . $ev['titulo'] . "\n"
            . 'Nombre:     ' . $nombre . "\n"
            . 'Correo:     ' . $email . "\n"
            // El teléfono solo aparece si lo dieron: una línea "Teléfono: —" en
            // cada correo enseña a saltarse ese renglón, y el día que sí venga
            // uno tampoco se leerá.
            . ($telefono !== null && $telefono !== '' ? 'Teléfono:   ' . $telefono . "\n" : '')
            . ($mensaje !== null && $mensaje !== '' ? "\nMensaje:\n" . $mensaje . "\n\n" : "\n")
            . "Para responder, contesta directamente este correo: llega a $email.\n\n"
            // Español fijo, como el resto del correo: este mensaje no tiene
            // mecanismo de idioma propio.
            . urlEvento($ev, 'es') . "\n";

    // correoContactoEvento() (includes/eventos.php): el que confirmó el
    // organizador para ESTA actividad (migración 24), o el de su cuenta si no
    // ha puesto ninguno.
    enviarCorreo(
        correoContactoEvento($ev),
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
    $valores = [
        mb_substr($nombre, 0, 120),
        mb_substr($email, 0, 190),
        isset(motivosContacto()[$motivo]) ? $motivo : 'general',
        ($actividad !== null && trim($actividad) !== '') ? mb_substr(trim($actividad), 0, 200) : null,
        mb_substr($mensaje, 0, 1000),
        ipBinaria(),
    ];

    db()->prepare('INSERT INTO mensajes_contacto (nombre, email, motivo, actividad_nombre, mensaje, ip) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute($valores);
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
    $st = db()->prepare(
        'SELECT * FROM mensajes_contacto ORDER BY creado_en DESC LIMIT ' . (int) $limite
    );
    $st->execute();

    return $st->fetchAll();
}

/**
 * Los motivos cuyo mensaje va SOLO a hola@omdara, sin avisar a los
 * administradores —«Pregunta general» y «Soy organizador», confirmado por el
 * cliente el 2026-09-02—. Los demás son el resto de motivosContacto(): van a
 * los administradores Y, si ya está configurado, también a soporte@omdara
 * —confirmado el mismo día, en un segundo mensaje—.
 */
function motivosAHola(): array
{
    return ['general', 'organizador'];
}

/**
 * Avisa del mensaje del contacto general: a hola@ si el motivo es de los
 * suyos y ya está configurado, o a los administradores —y de paso a
 * soporte@, si está configurado— en cualquier otro caso.
 *
 * ANTES avisaba siempre a los administradores, motivo el que fuera —el
 * requerimiento del cliente es precisamente enrutar por hola@/soporte@ según
 * lo que elija quien escribe—. Los dos buzones institucionales pueden no
 * estar configurados todavía (config.local.php vacío), así que nunca se deja
 * de avisar a los administradores salvo en el único caso que el cliente
 * marcó como «solo hola@»: mientras ese buzón no exista, ahí también cae en
 * los administradores, para que el mensaje nunca se pierda.
 *
 * Sin el filtro de avisarAdministradores() —agrupar y espaciar—: ese existe
 * porque muchas personas distintas pueden reportar el MISMO evento, y ahí
 * agrupar tiene sentido. Aquí cada mensaje es una persona con un asunto
 * propio; agruparlos sería perder el mensaje de alguien por llegar el mismo
 * día que el de otro.
 */
function avisarContactoSitio(string $nombre, string $email, string $mensaje,
                             string $motivo = 'general', ?string $actividad = null): void
{
    if (in_array($motivo, motivosAHola(), true) && correoContacto() !== '') {
        $destinatarios = [correoContacto()];
    } else {
        $destinatarios = array_column(
            db()->query('SELECT email FROM usuarios WHERE rol = "admin" AND estado = "activo"')->fetchAll(),
            'email'
        );

        // Solo para los motivos que NO son de hola@: soporte@ se suma a los
        // administradores, no los sustituye —el cliente pidió «además de»—.
        if (!in_array($motivo, motivosAHola(), true) && correoSoporte() !== '') {
            $destinatarios[] = correoSoporte();
        }

        $destinatarios = array_unique($destinatarios);
    }

    if (!$destinatarios) {
        error_log('Mensaje de contacto general recibido y no hay ningún administrador a quien avisar.');
        return;
    }

    // Español fijo: el resto del correo también lo está, y no hay mecanismo
    // de idioma para correos todavía (ver docs/pendientes.md, fase 4).
    $motivoTexto = motivosContacto('es')[$motivo] ?? $motivo;

    $cuerpo = "Alguien escribió desde el formulario de contacto de Omdara.\n\n"
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
    // primero por ahí, y «te escribió desde Omdara» no distingue una alianza de
    // un reporte de contenido.
    $asunto = '[' . $motivoTexto . '] ' . $nombre . ' te escribió desde Omdara';

    foreach ($destinatarios as $destinatario) {
        enviarCorreo($destinatario, $asunto, $cuerpo, $email);
    }
}
