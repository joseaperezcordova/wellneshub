<?php
/**
 * El punto en el mapa de cada evento.
 *
 * El organizador no dibuja nada: busca el sitio en Google Maps —donde ya sabe
 * moverse—, pulsa «Compartir» y pega aquí el enlace. De ese enlace sacamos la
 * latitud y la longitud, y eso es lo único que se guarda del mapa.
 *
 * Guardar las coordenadas y no el enlace tiene tres consecuencias que importan:
 *
 *   · La ficha no depende de que ese enlace siga vivo dentro de dos años.
 *   · El mapa se puede pintar con quien queramos. Hoy es OpenStreetMap, que no
 *     pide clave ni tarjeta; el botón de «Cómo llegar» sí abre Google Maps,
 *     porque es lo que la gente lleva en el teléfono.
 *   · El día que haya un buscador por cercanía, los datos ya están.
 *
 * No se usa la API de Google. Para incrustar un mapa suyo hace falta un
 * proyecto con facturación activa, y una clave publicada en una página abierta
 * es una factura esperando a que alguien la encuentre.
 */

declare(strict_types=1);

/**
 * Los acortadores cuyo destino aceptamos ir a mirar.
 *
 * Esta lista es lo que impide que el formulario se convierta en un ariete: sin
 * ella, cualquiera podría pegar la dirección de una máquina interna de la red
 * del hosting y usar nuestro servidor para llamar a su puerta.
 */
function hostsCortosDeMapa(): array
{
    return ['maps.app.goo.gl', 'goo.gl', 'g.co'];
}

/**
 * Latitud y longitud dentro de un texto, o null si no hay nada legible.
 *
 * El orden de los cuatro intentos no es casual: van de más fiable a menos.
 */
function coordenadasEnTexto(string $texto): ?array
{
    // 1. !3d<lat>!4d<lng>. Es el sitio de verdad, el que lleva la chincheta.
    if (preg_match('~!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)~', $texto, $m)) {
        return coordenadasValidas((float) $m[1], (float) $m[2]);
    }

    // 2. Parámetros con el punto dentro: ?q=, ?ll=, ?destination=…
    if (preg_match('~[?&](?:q|ll|sll|daddr|destination|center)=(?:loc:)?(-?\d+(?:\.\d+)?)(?:,|%2C)(-?\d+(?:\.\d+)?)~i', $texto, $m)) {
        return coordenadasValidas((float) $m[1], (float) $m[2]);
    }

    // 3. /@<lat>,<lng>,<zoom>z. Esto es hacia dónde mira la cámara, que no
    //    siempre coincide con el sitio: si has movido el mapa antes de
    //    compartir, cae unos metros al lado. Por eso va después de las otras.
    if (preg_match('~/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)~', $texto, $m)) {
        return coordenadasValidas((float) $m[1], (float) $m[2]);
    }

    // 4. Las coordenadas pegadas a pelo: «20.2114, -87.4654». Hay quien las
    //    tiene apuntadas y no le vamos a obligar a dar el rodeo.
    if (preg_match('~^\s*(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*$~', $texto, $m)) {
        return coordenadasValidas((float) $m[1], (float) $m[2]);
    }

    return null;
}

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
 * Sigue un enlace corto de Google hasta el largo.
 *
 * Es imprescindible porque el botón «Compartir» de Google Maps da justamente un
 * maps.app.goo.gl, que por dentro no lleva ninguna coordenada.
 *
 * Solo se sale a la red si el enlace es de un acortador de la lista de arriba, y
 * con tiempos cortos: esto pasa mientras alguien espera a que se guarde su
 * ficha, así que es mejor fallar rápido y explicarlo que dejarle la pantalla
 * colgada.
 */
function resolverEnlaceCortoDeMapa(string $enlace): ?string
{
    $partes = parse_url($enlace);

    if (!is_array($partes)) return null;
    if (($partes['scheme'] ?? '') !== 'https') return null;
    if (!in_array(strtolower($partes['host'] ?? ''), hostsCortosDeMapa(), true)) return null;

    if (!function_exists('curl_init')) return null;

    $leido = 0;

    $ch = curl_init($enlace);
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_USERAGENT      => 'Rueda/1.0 (+' . URL_BASE . ')',

        /*
         * Del destino no queremos el contenido, solo saber cuál es. Se corta en
         * cuanto se llega al límite: la página de Google Maps pesa más de un
         * mega y descargarla entera por una coordenada sería absurdo.
         *
         * Devolver 0 aborta la descarga. curl_exec devolverá false por eso, y no
         * es un fallo: la dirección final ya está registrada.
         */
        CURLOPT_WRITEFUNCTION => function ($ch, $trozo) use (&$leido) {
            $leido += strlen($trozo);
            return $leido > 65536 ? 0 : strlen($trozo);
        },
    ]);

    curl_exec($ch);
    $final = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    return ($final !== '' && $final !== $enlace) ? $final : null;
}

/** El punto de un enlace, resolviendo el acortador si hace falta. */
function coordenadasDeEnlace(?string $enlace): ?array
{
    $enlace = trim((string) $enlace);
    if ($enlace === '') return null;

    $directas = coordenadasEnTexto($enlace);
    if ($directas !== null) return $directas;

    $largo = resolverEnlaceCortoDeMapa($enlace);

    return $largo !== null ? coordenadasEnTexto($largo) : null;
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
