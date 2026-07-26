<?php
/**
 * Peticiones HTTP salientes.
 *
 * Vivía dentro de google.php, que es donde nació, pero ya la usan también las
 * comprobaciones de captcha y no tiene sentido cargar todo el OAuth de Google
 * para hacer un POST.
 *
 * Con cURL si el hosting lo trae, y con file_get_contents si no. En hosting
 * compartido no se puede dar por hecho ninguno de los dos: aquí allow_url_fopen
 * está en la lista de funciones desactivadas, así que el respaldo importa tanto
 * como el camino principal.
 */

declare(strict_types=1);

/**
 * @param array|null $post null para GET, un array para POST con formulario.
 * @return array{0:int,1:string} [código http, cuerpo]. Código 0 si ni siquiera
 *                               se pudo conectar.
 */
function peticionHttp(string $url, ?array $post = null, array $cabeceras = []): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,

            // Sin verificar el certificado, cifrar no sirve de nada: cualquiera
            // en medio puede presentarse como el otro extremo.
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,

            CURLOPT_HTTPHEADER     => $cabeceras,
        ]);

        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        $cuerpo = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($cuerpo === false) {
            error_log('cURL falló contra ' . $url . ': ' . curl_error($ch));
            curl_close($ch);
            return [0, ''];
        }

        curl_close($ch);
        return [$codigo, (string) $cuerpo];
    }

    $opciones = [
        'http' => [
            'method'  => $post === null ? 'GET' : 'POST',
            'header'  => implode("\r\n", array_merge(
                $post === null ? [] : ['Content-Type: application/x-www-form-urlencoded'],
                $cabeceras
            )),
            'content' => $post === null ? null : http_build_query($post),
            'timeout' => 15,

            // Para que un 400 devuelva su cuerpo en vez de false: el mensaje de
            // error del otro extremo es justo lo que hace falta para saber qué
            // pasó.
            'ignore_errors' => true,
        ],
    ];

    $cuerpo = @file_get_contents($url, false, stream_context_create($opciones));
    if ($cuerpo === false) return [0, ''];

    $codigo = 0;
    foreach ($http_response_header ?? [] as $linea) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $linea, $m)) $codigo = (int) $m[1];
    }

    return [$codigo, $cuerpo];
}
