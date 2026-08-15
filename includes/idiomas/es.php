<?php
/**
 * Catálogo de textos en español. Es el idioma por defecto y, por tanto, la
 * referencia: cuando a en.php le falta una clave, se cae aquí.
 *
 * LAS CLAVES DESCRIBEN DÓNDE VA EL TEXTO, NO QUÉ DICE
 *
 * 'pie.legal' y no 'legal' ni 'textos_legales'. Así se lee de un vistazo qué
 * parte de la interfaz se rompe si una clave falta, y dos sitios que hoy dicen
 * lo mismo pueden dejar de decirlo sin tener que separarlos primero.
 *
 * FASE 1: aquí están el armazón global —cabecera, pie, selector de idioma— y
 * los títulos de las páginas estáticas. El texto del cuerpo de cada página se
 * va incorporando por fases; ver docs/pendientes.md.
 */

declare(strict_types=1);

return [
    // ---- Marca ----
    'marca.nombre'    => 'OMDARA',
    'marca.subtitulo' => 'Directorio wellness MX',

    // ---- Cabecera ----
    'nav.inicio'      => 'Inicio',
    'nav.actividades' => 'Actividades',
    'nav.blog'        => 'Blog',
    'nav.publicar'    => 'Publicar actividad',
    'nav.publicar_corto'  => 'Publicar',
    'nav.publicar_sufijo' => 'actividad',
    'nav.entrar'      => 'Entrar',
    'nav.menu'        => 'Menú',

    // ---- Selector de idioma ----
    'idioma.cambiar' => 'Cambiar idioma',
    'idioma.es'      => 'Español',
    'idioma.en'      => 'English',

    // ---- Pie ----
    'pie.lema'         => 'Tu guía de experiencias de bienestar en México. Conecta con actividades que nutren cuerpo, mente y alma.',
    'pie.explora'      => 'Explora',
    'pie.organizadores'=> 'Para organizadores',
    'pie.ayuda'        => 'Ayuda',
    'pie.legal'        => 'Legal',
    'pie.actividades'  => 'Actividades',
    'pie.publicar'     => 'Publicar tu actividad',
    'pie.como_funciona'=> '¿Cómo funciona?',
    'pie.faq'          => 'Preguntas frecuentes',
    'pie.contacto'     => 'Contacto',
    'pie.terminos'     => 'Términos y Condiciones',
    'pie.privacidad'   => 'Aviso de Privacidad',
    'pie.cookies'      => 'Política de Cookies',
    'pie.beta'         => 'Versión beta: estamos mejorando continuamente la plataforma. Si encuentras algún problema,',
    'pie.beta_enlace'  => 'contáctanos',
    'pie.instagram'    => 'OMDARA en Instagram',
    'pie.facebook'     => 'OMDARA en Facebook',
    'pie.whatsapp'     => 'OMDARA en WhatsApp',

    // ---- Títulos y descripciones de las páginas estáticas ----
    'pagina.como_funciona.titulo' => 'Cómo funciona',
    'pagina.como_funciona.meta'   => 'Cómo encontrar actividades de bienestar en OMDARA y cómo publicar la tuya si eres organizador.',
    'pagina.faq.titulo'           => 'Preguntas frecuentes',
    'pagina.faq.meta'             => 'Dudas habituales sobre cómo publicar una actividad en OMDARA, contactar a un organizador y cómo se revisa lo que se publica.',
    'pagina.terminos.titulo'      => 'Términos y Condiciones',
    'pagina.terminos.meta'        => 'Condiciones de uso de OMDARA para visitantes y organizadores.',
    'pagina.privacidad.titulo'    => 'Aviso de Privacidad',
    'pagina.privacidad.meta'      => 'Qué datos personales trata OMDARA, con qué finalidad y durante cuánto tiempo.',
    'pagina.cookies.titulo'       => 'Política de Cookies',
    'pagina.cookies.meta'         => 'Qué cookies usa OMDARA, para qué sirven y cómo desactivarlas.',
    'pagina.404.titulo'           => 'Página no encontrada',

    // ---- Aviso de contenido pendiente ----
    'pendiente.titulo' => 'Contenido pendiente.',
    'pendiente.texto'  => 'Esta página ya está publicada y enlazada desde el pie, pero su texto todavía no está escrito.',
];
