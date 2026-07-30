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

function crearContacto(int $eventoId, string $nombre, string $email, ?string $mensaje): void
{
    db()->prepare(
        'INSERT INTO contactos (evento_id, nombre, email, mensaje, ip)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([
        $eventoId,
        mb_substr($nombre, 0, 120),
        mb_substr($email, 0, 190),
        $mensaje !== null ? mb_substr($mensaje, 0, 1000) : null,
        ipBinaria(),
    ]);
}

/**
 * Avisa al organizador de que alguien quiere contactarlo.
 *
 * A diferencia de avisarAdministradores(), aquí SÍ va un correo por cada
 * mensaje: cada uno es una persona distinta con una pregunta distinta, no
 * hay nada que agrupar. El Reply-To queda puesto al correo de quien
 * escribió, así que el organizador solo tiene que darle "Responder".
 */
function avisarOrganizador(array $ev, string $nombre, string $email, ?string $mensaje): void
{
    $cuerpo = "Alguien quiere contactarte por tu actividad publicada en Rueda.\n\n"
            . 'Actividad:  ' . $ev['titulo'] . "\n"
            . 'Nombre:     ' . $nombre . "\n"
            . 'Correo:     ' . $email . "\n"
            . ($mensaje !== null && $mensaje !== '' ? "Mensaje:\n" . $mensaje . "\n\n" : "\n")
            . "Para responder, contesta directamente este correo: llega a $email.\n\n"
            . URL_BASE . '/evento.php?id=' . (int) $ev['id'] . "\n";

    enviarCorreo(
        $ev['organizador_email'],
        $nombre . ' quiere contactarte por «' . $ev['titulo'] . '»',
        $cuerpo,
        $email
    );
}
