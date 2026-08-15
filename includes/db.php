<?php
/**
 * Conexión PDO única, perezosa.
 */

declare(strict_types=1);

/**
 * ¿Existe esta columna?
 *
 * Las migraciones de este proyecto se ejecutan a mano en phpMyAdmin, y entre
 * que se publica el código y alguien las aplica pasa un rato —una vez pasó un
 * día entero, con el sitio caído sin explicación aparente—. Esto permite
 * escribir código que funciona a los dos lados de una migración: guarda el dato
 * si la columna está, y si no, sigue sin romperse.
 *
 * NO es para usarlo en todas partes. Solo donde perder el dato es aceptable y
 * caerse no lo es. Una vez aplicada la migración en los dos entornos, la
 * comprobación se quita y el código se queda derecho.
 *
 * El resultado se cachea por petición: SHOW COLUMNS es barato, pero no hay
 * razón para repetirlo.
 */
function columnaExiste(string $tabla, string $columna): bool
{
    static $vistas = [];

    $clave = $tabla . '.' . $columna;
    if (isset($vistas[$clave])) return $vistas[$clave];

    // El nombre de tabla no puede ir como parámetro preparado, así que se
    // limita a lo que puede ser un identificador. Hoy solo lo llaman con
    // literales del propio código, pero eso es de hoy.
    if (!preg_match('/^[A-Za-z0-9_]+$/', $tabla)) return $vistas[$clave] = false;

    try {
        $st = db()->prepare("SHOW COLUMNS FROM `$tabla` LIKE ?");
        $st->execute([$columna]);
        return $vistas[$clave] = ($st->fetch() !== false);
    } catch (PDOException $ex) {
        error_log("No se pudo comprobar si existe $clave: " . $ex->getMessage());
        return $vistas[$clave] = false;
    }
}

function db(): PDO
{
    /** @var array $CONFIG */
    global $CONFIG;
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = $CONFIG['db'];
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $c['host'], $c['nombre'], $c['charset']);

    try {
        $pdo = new PDO($dsn, $c['usuario'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Sentencias preparadas de verdad, no emuladas por el driver. Con la
            // emulación activada PDO interpola los valores él mismo, y ahí es
            // donde han aparecido históricamente los agujeros de inyección.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $ex) {
        // El mensaje de PDO lleva usuario y host de la base. Se registra, pero
        // al visitante no se le enseña.
        error_log('Fallo de conexión a la base: ' . $ex->getMessage());
        http_response_code(500);
        exit('No se pudo conectar con la base de datos.');
    }

    return $pdo;
}
