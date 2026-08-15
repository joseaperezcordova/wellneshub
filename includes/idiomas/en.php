<?php
/**
 * Catálogo de textos en inglés.
 *
 * QUÉ HAY AQUÍ Y QUÉ NO
 *
 * Están traducidos los textos de INTERFAZ: navegación, botones, etiquetas,
 * encabezados de sección. Son funcionales —«Activities», «Contact»— y su
 * traducción no admite mucha interpretación.
 *
 * NO está el texto editorial ni el legal: el lema de la marca, la redacción de
 * las páginas legales, los textos SEO definitivos. Eso lo entrega quien lleva
 * el producto (es la Dependencia declarada en el propio REQ-00002), y una
 * traducción inventada de un texto legal es peor que no tenerlo.
 *
 * Lo que falte aquí NO rompe la página: t() cae al español y deja aviso en el
 * log. Así el sitio en inglés se puede ir completando sin que ninguna versión
 * quede a medias en pantalla.
 */

declare(strict_types=1);

return [
    // ---- Marca ----
    'marca.nombre'    => 'OMDARA',
    'marca.subtitulo' => 'Wellness directory MX',

    // ---- Cabecera ----
    'nav.inicio'      => 'Home',
    'nav.actividades' => 'Activities',
    'nav.blog'        => 'Blog',
    'nav.publicar'    => 'Post an activity',
    'nav.publicar_corto'  => 'Post',
    'nav.publicar_sufijo' => 'an activity',
    'nav.entrar'      => 'Sign in',
    'nav.menu'        => 'Menu',

    // ---- Selector de idioma ----
    // Cada idioma se nombra en su propio idioma, no traducido: quien busca
    // "Español" en un sitio que no entiende, busca esa palabra y no "Spanish".
    'idioma.cambiar' => 'Change language',
    'idioma.es'      => 'Español',
    'idioma.en'      => 'English',

    // ---- Pie ----
    'pie.explora'      => 'Explore',
    'pie.organizadores'=> 'For organizers',
    'pie.ayuda'        => 'Help',
    'pie.legal'        => 'Legal',
    'pie.actividades'  => 'Activities',
    'pie.publicar'     => 'Post your activity',
    'pie.como_funciona'=> 'How it works',
    'pie.faq'          => 'Frequently asked questions',
    'pie.contacto'     => 'Contact',
    'pie.terminos'     => 'Terms and Conditions',
    'pie.privacidad'   => 'Privacy Notice',
    'pie.cookies'      => 'Cookie Policy',
    'pie.beta'         => 'Beta version: we are continuously improving the platform. If you run into a problem,',
    'pie.beta_enlace'  => 'get in touch',
    'pie.instagram'    => 'OMDARA on Instagram',
    'pie.facebook'     => 'OMDARA on Facebook',
    'pie.whatsapp'     => 'OMDARA on WhatsApp',

    // 'pie.lema' — PENDIENTE. Es texto de marca; lo entrega producto.

    // ---- Títulos de las páginas estáticas ----
    // Los títulos sí, porque sin ellos la pestaña del navegador saldría en
    // español en una página inglesa. Las meta descriptions quedan pendientes:
    // son texto SEO y el requerimiento las declara como entregable aparte.
    'pagina.como_funciona.titulo' => 'How it works',
    'pagina.faq.titulo'           => 'Frequently asked questions',
    'pagina.terminos.titulo'      => 'Terms and Conditions',
    'pagina.privacidad.titulo'    => 'Privacy Notice',
    'pagina.cookies.titulo'       => 'Cookie Policy',
    'pagina.404.titulo'           => 'Page not found',

    // ---- Aviso de contenido pendiente ----
    'pendiente.titulo' => 'Content pending.',
    'pendiente.texto'  => 'This page is already published and linked from the footer, but its text has not been written yet.',
];
