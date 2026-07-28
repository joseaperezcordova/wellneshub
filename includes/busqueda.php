<?php
/**
 * Los filtros de la búsqueda, leídos y escritos siempre igual.
 *
 * Los filtros viajan en la dirección —buscar.php?ciudad=Tulum&cat=Yoga— y no
 * solo en la memoria del navegador. Esto es lo que hace que funcionen tres
 * cosas que antes no funcionaban:
 *
 *   · Volver atrás desde la ficha de un evento y encontrarse la misma búsqueda.
 *   · Compartir o guardar una búsqueda.
 *   · Que el botón «atrás» del navegador haga lo que se espera.
 *
 * Y hay una cuarta razón, menos visible: la dirección viene de fuera, así que
 * todo lo que llega por ahí se valida contra las listas de verdad antes de
 * usarse. Una categoría inventada se descarta, no se busca.
 *
 * Este archivo lo comparten buscar.php —que arma el panel— y evento.php —que
 * reconstruye el enlace de vuelta—, para que no se les vaya la lista de campos
 * a cada uno por su lado.
 */

declare(strict_types=1);

/** Las opciones de «Cuándo». La cadena vacía es «cualquier fecha». */
function fechasBusqueda(): array
{
    return ['', 'finde', '7dias', 'mes'];
}

/** Las formas de ordenar. La primera es la de por defecto. */
function ordenesBusqueda(): array
{
    return ['fecha', 'precio', 'nuevos'];
}

/**
 * Los filtros que trae la dirección, ya limpios.
 *
 * Devuelve siempre las mismas claves, con lo que quien lo use no tiene que
 * andar comprobando si existen.
 */
function filtrosDesdePeticion(array $get): array
{
    $texto = trim((string) ($get['q'] ?? ''));

    // 100 caracteres es más de lo que nadie escribe en un buscador; el tope
    // está para que no llegue una dirección de cien mil.
    if (mb_strlen($texto) > 100) $texto = mb_substr($texto, 0, 100);

    $fecha = (string) ($get['fecha'] ?? '');
    if (!in_array($fecha, fechasBusqueda(), true)) $fecha = '';

    $orden = (string) ($get['orden'] ?? '');
    if (!in_array($orden, ordenesBusqueda(), true)) $orden = ordenesBusqueda()[0];

    // Las categorías llegan separadas por comas y se cotejan con el catálogo:
    // lo que no esté en él se cae. array_values para que el JSON salga como
    // lista y no como objeto con huecos.
    $validas = array_keys(categoriasMenu());
    $cats    = array_values(array_intersect(
        array_filter(array_map('trim', explode(',', (string) ($get['cat'] ?? '')))),
        $validas
    ));

    return [
        'texto'   => $texto,
        'entidad' => trim((string) ($get['estado'] ?? '')),
        'ciudad'  => trim((string) ($get['ciudad'] ?? '')),
        'fecha'   => $fecha,
        'cats'    => $cats,
        'gratis'  => !empty($get['gratis']),
        'orden'   => $orden,
    ];
}

/**
 * De filtros a cadena de consulta.
 *
 * Solo salen los campos puestos: una dirección con siete parámetros vacíos es
 * ilegible y no dice nada más que una limpia.
 */
function consultaBusqueda(array $f): string
{
    $partes = [];

    if ($f['texto']   !== '') $partes['q']      = $f['texto'];
    if ($f['entidad'] !== '') $partes['estado'] = $f['entidad'];
    if ($f['ciudad']  !== '') $partes['ciudad'] = $f['ciudad'];
    if ($f['fecha']   !== '') $partes['fecha']  = $f['fecha'];
    if ($f['cats'])           $partes['cat']    = implode(',', $f['cats']);
    if ($f['gratis'])         $partes['gratis'] = '1';

    // El orden por defecto no se escribe: no aporta y ensucia la dirección.
    if ($f['orden'] !== ordenesBusqueda()[0]) $partes['orden'] = $f['orden'];

    return http_build_query($partes);
}

/** La dirección completa de una búsqueda. */
function urlBuscar(array $f): string
{
    $consulta = consultaBusqueda($f);

    return URL_BASE . '/buscar.php' . ($consulta !== '' ? '?' . $consulta : '');
}

/**
 * El enlace de vuelta a los resultados que lleva una ficha.
 *
 * La ficha recibe la búsqueda en el parámetro «volver». No se usa tal cual: se
 * deshace, se pasa por el mismo filtrado de arriba y se vuelve a armar. Así lo
 * que acaba en el href lo hemos escrito nosotros, y no hay forma de colar por
 * ahí una dirección a otro sitio.
 */
function urlVolverABuscar(?string $volver): string
{
    if ($volver === null || $volver === '') return URL_BASE . '/buscar.php';

    parse_str($volver, $partes);

    return urlBuscar(filtrosDesdePeticion(is_array($partes) ? $partes : []));
}
