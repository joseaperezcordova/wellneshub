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
