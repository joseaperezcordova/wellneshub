<?php
/**
 * Moderación: filtro de palabras, reportes y aviso al administrador.
 *
 * EL MODELO
 *
 * Los eventos se publican solos. Se revisa lo que alguien señala, no todo por
 * si acaso: revisar 99 eventos correctos para encontrar uno malo es trabajo que
 * no se sostiene con una sola persona.
 *
 * Reportar NO esconde el evento. Si un reporte bastara para tumbar una ficha,
 * tumbar a la competencia costaría un clic. El reporte avisa; ocultar o borrar
 * lo decide un administrador.
 */

declare(strict_types=1);

const REPORTE_ESPERA_H     = 24;  // entre dos reportes de la misma IP al mismo evento
const REPORTE_AVISO_ESPERA_H = 12;  // entre dos correos al admin por el mismo evento

/** Los motivos, con su etiqueta. La clave es lo que guarda la base. */
function motivosReporte(): array
{
    return [
        'inapropiado'    => 'Contenido ofensivo o inapropiado',
        'enganoso'       => 'Información falsa o engañosa',
        'spam'           => 'Spam o publicidad',
        'no_es_wellness' => 'No es una actividad de bienestar',
        'duplicado'      => 'Está publicado dos veces',
        'otro'           => 'Otro motivo',
    ];
}


// ------------------------------------------------- filtro de palabras ------

/**
 * Palabras que hacen que un evento se marque para revisión.
 *
 * DELIBERADAMENTE CORTA, y no bloquea nada.
 *
 * Las listas largas de palabras prohibidas envejecen mal y se equivocan mucho.
 * En un directorio de bienestar mexicano, media lista de "términos sospechosos"
 * son palabras del oficio: temazcal, cacao, ceremonia, medicina ancestral. Una
 * lista agresiva aquí no filtra spam, filtra a los organizadores de verdad.
 *
 * Por eso esto solo levanta la mano: el evento se publica igual y el
 * administrador recibe el aviso. Un falso positivo cuesta treinta segundos de
 * lectura; un falso positivo que bloquease la publicación costaría un
 * organizador que no vuelve y que además no entendería por qué.
 */
function palabrasVigiladas(): array
{
    return [
        // Señales de estafa y venta que no pinta nada en una ficha de evento
        'viagra', 'cialis', 'casino', 'apuestas', 'bitcoin gratis',
        'dinero facil', 'dinero rapido', 'prestamo urgente', 'multinivel',
        'gana desde casa', 'inversion garantizada', 'criptomoneda garantizada',

        // Sustancias cuya venta abierta no queremos anunciar. Ojo: se busca la
        // forma de VENTA, no la palabra suelta, porque una charla sobre
        // reducción de daños es un evento legítimo.
        'venta de droga', 'vendo droga',
    ];
}

/**
 * Busca palabras vigiladas en el texto de un evento.
 *
 * Compara sin acentos y sin mayúsculas para que "Dinero Fácil" no se escape por
 * la tilde, que es el truco más viejo del mundo.
 *
 * @return array las palabras encontradas
 */
function palabrasEncontradas(array $ev): array
{
    $texto = normalizarParaBuscar(
        ($ev['titulo'] ?? '') . ' ' . ($ev['descripcion'] ?? '') . ' ' . ($ev['lugar'] ?? '')
    );

    $encontradas = [];

    foreach (palabrasVigiladas() as $palabra) {
        if (strpos($texto, normalizarParaBuscar($palabra)) !== false) {
            $encontradas[] = $palabra;
        }
    }

    return $encontradas;
}

/** Minúsculas, sin acentos y con los espacios apretados. */
function normalizarParaBuscar(string $texto): string
{
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = strtr($texto, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
    ]);

    return (string) preg_replace('/\s+/', ' ', $texto);
}

/**
 * Pasa el filtro al publicar. Si algo suena mal, deja un reporte automático.
 *
 * El evento se publica igualmente: ver el comentario de palabrasVigiladas().
 */
function revisarAlPublicar(array $ev): void
{
    $encontradas = palabrasEncontradas($ev);
    if (!$encontradas) return;

    crearReporte(
        (int) $ev['id'],
        'inapropiado',
        'Filtro automático. Coincidencias: ' . implode(', ', $encontradas),
        'automatico'
    );

    avisarAdministradores((int) $ev['id']);
}


// ---------------------------------------------------------- reportes ------

/** ¿Ya reportó esta IP este evento hace poco? */
function reporteRepetido(int $eventoId): bool
{
    $st = db()->prepare(
        'SELECT COUNT(*) FROM reportes
          WHERE evento_id = ? AND ip = ?
            AND creado_en > DATE_SUB(NOW(), INTERVAL ' . REPORTE_ESPERA_H . ' HOUR)'
    );
    $st->execute([$eventoId, ipBinaria()]);

    return (int) $st->fetchColumn() > 0;
}

function crearReporte(int $eventoId, string $motivo, ?string $comentario, string $origen = 'visitante'): void
{
    if (!isset(motivosReporte()[$motivo])) $motivo = 'otro';

    db()->prepare(
        'INSERT INTO reportes (evento_id, motivo, comentario, origen, ip)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([
        $eventoId,
        $motivo,
        $comentario !== null ? mb_substr($comentario, 0, 1000) : null,
        $origen,
        ipBinaria(),
    ]);
}

/** Los reportes pendientes, agrupados por evento. */
function reportesPendientes(int $limite = 100): array
{
    $st = db()->query(
        'SELECT e.id, e.titulo, e.situacion, e.categoria, e.ciudad,
                u.nombre AS organizador, u.email AS organizador_email,
                COUNT(r.id)     AS total,
                MAX(r.creado_en) AS ultimo,
                MAX(r.origen = "automatico") AS tiene_automatico
           FROM reportes r
           JOIN eventos  e ON e.id = r.evento_id
           JOIN usuarios u ON u.id = e.usuario_id
          WHERE r.situacion = "pendiente"
       GROUP BY e.id, e.titulo, e.situacion, e.categoria, e.ciudad, u.nombre, u.email
       ORDER BY total DESC, ultimo DESC
          LIMIT ' . (int) $limite
    );

    return $st->fetchAll();
}

/** El detalle de los reportes de un evento. */
function reportesDeEvento(int $eventoId): array
{
    $st = db()->prepare(
        'SELECT * FROM reportes WHERE evento_id = ? ORDER BY creado_en DESC LIMIT 60'
    );
    $st->execute([$eventoId]);

    return $st->fetchAll();
}

/**
 * Cuántos eventos tienen avisos sin revisar. Es el número del menú.
 *
 * Va envuelto en un try porque esto se ejecuta en la cabecera de TODAS las
 * páginas cuando quien mira es administrador. Si la consulta falla —la causa
 * típica es que falte por ejecutar la migración de la tabla en el servidor— sin
 * este try se cae el sitio entero para el administrador, y encima con un 500
 * mudo que no dice qué pasa.
 *
 * Un contador decorativo no puede tener ese poder. Si no se puede contar, se
 * enseña cero, no se pinta insignia, y el motivo real queda en el log.
 */
function contarReportesPendientes(): int
{
    try {
        return (int) db()->query(
            'SELECT COUNT(DISTINCT evento_id) FROM reportes WHERE situacion = "pendiente"'
        )->fetchColumn();

    } catch (Throwable $ex) {
        error_log('No se pudieron contar los reportes pendientes. '
            . '¿Está creada la tabla "reportes"? Ejecuta database/migracion-03-reportes.sql. '
            . 'Detalle: ' . $ex->getMessage());

        return 0;
    }
}

/** Da por vistos todos los reportes de un evento. */
function marcarReportesRevisados(int $eventoId, int $adminId): void
{
    db()->prepare(
        'UPDATE reportes
            SET situacion = "revisado", revisado_por = ?, revisado_en = NOW()
          WHERE evento_id = ? AND situacion = "pendiente"'
    )->execute([$adminId, $eventoId]);
}


// ------------------------------------------------- aviso por correo -------

/**
 * Avisa por correo a los administradores de que hay un evento reportado.
 *
 * NO manda un correo por reporte. Manda uno por evento cada REPORTE_AVISO_ESPERA_H
 * horas como mucho.
 *
 * Un correo por reporte parece más atento y es peor: diez personas que reportan
 * el mismo evento spam producen diez correos idénticos, y quien los recibe deja
 * de leerlos. Además este dominio manda a través de un filtro que ya nos ha
 * rechazado envíos una vez; un pico de correo idéntico es justo lo que hace que
 * un proveedor empiece a mirarte mal.
 *
 * El aviso dice cuántos reportes van, así que la información no se pierde.
 */
function avisarAdministradores(int $eventoId): void
{
    $pdo = db();

    // ¿Ya se avisó de este evento hace poco? Se mira contra el reporte anterior
    // al recién creado: si hay más de uno reciente, el aviso ya salió.
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM reportes
          WHERE evento_id = ?
            AND creado_en > DATE_SUB(NOW(), INTERVAL ' . REPORTE_AVISO_ESPERA_H . ' HOUR)'
    );
    $st->execute([$eventoId]);

    if ((int) $st->fetchColumn() > 1) return;

    $st = $pdo->prepare(
        'SELECT e.titulo, u.nombre AS organizador,
                (SELECT COUNT(*) FROM reportes WHERE evento_id = e.id AND situacion = "pendiente") AS pendientes
           FROM eventos e JOIN usuarios u ON u.id = e.usuario_id
          WHERE e.id = ? LIMIT 1'
    );
    $st->execute([$eventoId]);
    $ev = $st->fetch();

    if (!$ev) return;

    $admins = $pdo->query(
        'SELECT email FROM usuarios WHERE rol = "admin" AND estado = "activo"'
    )->fetchAll();

    if (!$admins) {
        error_log('Evento ' . $eventoId . ' reportado y no hay ningún administrador a quien avisar.');
        return;
    }

    $enlace  = URL_BASE . '/moderacion.php';
    $cuerpo  = "Han reportado una actividad publicada en el directorio.\n\n"
             . 'Actividad:  ' . $ev['titulo'] . "\n"
             . 'Organiza:   ' . $ev['organizador'] . "\n"
             . 'Reportes:   ' . $ev['pendientes'] . " sin revisar\n\n"
             . "La actividad sigue publicada. Nadie la ha ocultado: eso lo decides tú.\n\n"
             . $enlace . "\n";

    foreach ($admins as $a) {
        enviarCorreo($a['email'], 'Actividad reportada: ' . $ev['titulo'], $cuerpo);
    }
}
