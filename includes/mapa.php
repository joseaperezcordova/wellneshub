<?php
/**
 * El punto en el mapa de cada evento.
 *
 * El organizador ya no pega un enlace: arrastra un pin sobre un mapa
 * interactivo (Leaflet + OpenStreetMap, en includes/form-evento.php y
 * assets/js/mapa-evento.js) y la latitud/longitud quedan escritas solas.
 * Guardar las coordenadas y no un enlace tiene dos consecuencias que
 * importan:
 *
 *   · El mapa se puede pintar con quien queramos. Hoy es OpenStreetMap, que
 *     no pide clave ni tarjeta; el botón de «Cómo llegar» sí abre Google
 *     Maps, porque es lo que la gente lleva en el teléfono.
 *   · El día que haya un buscador por cercanía, los datos ya están.
 *
 * No se usa la API de Google. Para incrustar un mapa suyo hace falta un
 * proyecto con facturación activa, y una clave publicada en una página abierta
 * es una factura esperando a que alguien la encuentre.
 */

declare(strict_types=1);

/** Descarta lo que no puede ser un punto de la Tierra —ni de México—. */
function coordenadasValidas(float $lat, float $lng): ?array
{
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) return null;

    // 0,0 cae en el golfo de Guinea. Cuando sale ese par casi siempre significa
    // «no se pudo leer», no «el evento es en mitad del Atlántico».
    if (abs($lat) < 0.0001 && abs($lng) < 0.0001) return null;

    // Siete decimales son once milímetros. Más es ruido, y la columna tampoco
    // guarda más.
    return [round($lat, 7), round($lng, 7)];
}

/**
 * El mapa que se incrusta en la ficha.
 *
 * OpenStreetMap y no Google: este no pide clave, no pide tarjeta y no cuenta
 * visitas. El recuadro se calcula alrededor del punto, más ancho que alto para
 * que cuadre con la forma del hueco.
 */
function urlMapaEmbebido(float $lat, float $lng): string
{
    $dLat = 0.004;   // unos 450 m arriba y abajo
    $dLng = 0.008;   // el doble a los lados, que el hueco es apaisado

    $bbox = ($lng - $dLng) . ',' . ($lat - $dLat) . ','
          . ($lng + $dLng) . ',' . ($lat + $dLat);

    return 'https://www.openstreetmap.org/export/embed.html'
         . '?bbox=' . $bbox . '&layer=mapnik&marker=' . $lat . ',' . $lng;
}

/**
 * «Cómo llegar», que abre Google Maps.
 *
 * Es la dirección documentada de Google para esto y no necesita clave. Se manda
 * a Google y no a OpenStreetMap a propósito: el mapa incrustado solo hay que
 * mirarlo, pero para conducir hasta allí la gente usa la aplicación que ya tiene
 * en el teléfono.
 */
function urlComoLlegar(float $lat, float $lng): string
{
    return 'https://www.google.com/maps/dir/?api=1&destination=' . $lat . ',' . $lng;
}

/** ¿Tiene este evento un punto que enseñar? */
function eventoTienePunto(array $ev): bool
{
    return isset($ev['latitud'], $ev['longitud'])
        && $ev['latitud'] !== null && $ev['longitud'] !== null;
}

/**
 * Lee una latitud/longitud a partir de un enlace de Google Maps pegado a mano.
 *
 * El pin del formulario no sabe nada de esto —sigue siendo la fuente de
 * verdad—; esto solo lo coloca por quien llega con un enlace ya copiado del
 * teléfono. Un enlace completo (con "@lat,lng", "!3d!4d" o "?q=lat,lng") ya
 * trae las coordenadas escritas. El enlace corto que comparte la app
 * (maps.app.goo.gl) no las trae: solo aparecen al seguir la redirección, así
 * que aquí se sigue una a la vez —sin descargar la página entera, con la
 * cabecera basta— hasta encontrarlas o quedarse sin saltos.
 *
 * Solo se siguen enlaces de dominios de Google: seguir redirecciones a
 * cualquier URL convertiría esto en un proxy abierto hacia direcciones que,
 * de otro modo, nadie desde fuera podría pedirle al servidor que visite.
 */
function resolverEnlaceMaps(string $url): ?array
{
    $url = trim($url);
    if ($url === '' || !esDominioDeGoogleMaps($url)) return null;

    for ($salto = 0; $salto < 5; $salto++) {
        $punto = coordenadasDeUrlMaps($url);
        if ($punto) return $punto;

        $siguiente = siguienteRedireccion($url);
        if ($siguiente === null || !esDominioDeGoogleMaps($siguiente)) return null;

        $url = $siguiente;
    }

    return coordenadasDeUrlMaps($url);
}

function esDominioDeGoogleMaps(string $url): bool
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '') return false;

    if ($host === 'goo.gl' || $host === 'maps.app.goo.gl') return true;

    return $host === 'google.com' || $host === 'google.com.mx'
        || str_ends_with($host, '.google.com') || str_ends_with($host, '.google.com.mx');
}

function coordenadasDeUrlMaps(string $url): ?array
{
    // El pin exacto de un lugar: lo más preciso que trae un enlace.
    if (preg_match('/!3d(-?\d{1,3}(?:\.\d+)?)!4d(-?\d{1,3}(?:\.\d+)?)/', $url, $m)) {
        return coordenadasValidas((float) $m[1], (float) $m[2]);
    }
    if (preg_match('/[?&](?:q|query)=(-?\d{1,3}(?:\.\d+)?),(-?\d{1,3}(?:\.\d+)?)/', $url, $m)) {
        return coordenadasValidas((float) $m[1], (float) $m[2]);
    }
    // El centro del mapa: puede ser donde alguien lo dejó tras moverlo, pero
    // es lo único que traen muchos enlaces de "compartir ubicación".
    if (preg_match('#/@(-?\d{1,3}(?:\.\d+)?),(-?\d{1,3}(?:\.\d+)?)#', $url, $m)) {
        return coordenadasValidas((float) $m[1], (float) $m[2]);
    }

    return null;
}

/** La URL a la que redirige un enlace, sin descargar el cuerpo. Null si no redirige. */
function siguienteRedireccion(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Wellneshub/1.0)',
        ]);
        curl_exec($ch);
        $codigo  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $destino = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        return ($codigo >= 300 && $codigo < 400 && $destino !== '') ? $destino : null;
    }

    if (!ini_get('allow_url_fopen')) return null;

    $contexto = stream_context_create(['http' => [
        'method'          => 'HEAD',
        'timeout'         => 8,
        'follow_location' => 0,
        'ignore_errors'   => true,
        'user_agent'      => 'Mozilla/5.0 (compatible; Wellneshub/1.0)',
    ]]);

    $cabeceras = @get_headers($url, true, $contexto);
    if (!$cabeceras || !isset($cabeceras['Location'])) return null;

    return is_array($cabeceras['Location']) ? end($cabeceras['Location']) : $cabeceras['Location'];
}
