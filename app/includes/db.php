<?php
/**
 * Conexión PDO única, perezosa.
 */

declare(strict_types=1);

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
