<?php
/**
 * Secciones que existen pero todavía no se enseñan.
 *
 * El MVP sale sin Blog y sin Newsletter (REQ-00004). El requerimiento es
 * explícito en que NO se borren: la funcionalidad se queda entera y volver a
 * enseñarla no puede significar volver a programarla.
 *
 * POR QUÉ UN INTERRUPTOR Y NO COMENTAR EL CÓDIGO
 *
 * Comentar bloques —o borrarlos «que ya están en git»— es lo que convierte
 * «ocultar» en «rehacer»: seis meses después nadie recuerda qué se quitó, en
 * cuántos archivos, ni si lo que quedó sigue encajando. Aquí hay una lista de
 * una línea; enseñar el blog otra vez es quitar una palabra de esa lista.
 *
 * DÓNDE SE PREGUNTA
 *
 *   · includes/layout.php  → el enlace del menú
 *   · index.php            → los dos bloques de la portada
 *   · router.php           → /blog deja de resolver
 *   · blog.php             → puerta propia, por si alguien escribe /blog.php
 *   · sitemap.php          → no se le ofrece a Google lo que no se ve
 *
 * Las cinco preguntan lo mismo, así que una sección oculta lo está de verdad y
 * no a medias: sin enlace pero indexada, o invisible pero abriéndose por su
 * dirección directa.
 */

declare(strict_types=1);

/**
 * Lo que el MVP no enseña.
 *
 * 'blog'       — blog.php, el bloque «Artículos» de la portada y el enlace del
 *                menú. Los artículos son maqueta: no hay tabla ni forma de
 *                escribirlos, así que enseñarlo prometía algo que no existe.
 * 'newsletter' — el bloque de suscripción de la portada. El formulario dice
 *                «gracias» pero no guarda el correo en ninguna parte, que es
 *                peor que no pedirlo.
 */
const SECCIONES_OCULTAS = ['blog', 'newsletter'];

/**
 * ¿Se enseña esta sección?
 *
 * config.local.php puede forzar el valor por entorno, para poder revisar en
 * pruebas algo que en producción sigue oculto:
 *
 *     'secciones' => ['blog' => true],
 *
 * Sin esa clave manda la lista de arriba, que es la que viaja en git y por
 * tanto la que vale en los dos entornos por defecto. El interruptor de
 * configuración es para mirar, no para publicar: lo que se publica se decide
 * en el código y se revisa en un commit.
 */
function seccionVisible(string $clave): bool
{
    $forzado = $GLOBALS['CONFIG']['secciones'][$clave] ?? null;

    if (is_bool($forzado)) return $forzado;

    return !in_array($clave, SECCIONES_OCULTAS, true);
}
