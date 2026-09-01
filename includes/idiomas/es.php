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
    // En la cabecera «Buscar actividades» y en el pie «Actividades» (pie.actividades),
    // las dos hacia /actividades. Lo pide REQ-00006 y tiene sentido: arriba se
    // ofrece una acción entre otras acciones, abajo se nombra una sección
    // dentro de un índice donde ya se sabe que todo son enlaces.
    'nav.actividades' => 'Buscar actividades',
    // En la cabecera y en el pie, las dos a /como-funciona (REQ-00013).
    'nav.como_funciona' => '¿Cómo funciona?',
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
    'pagina.inicio.titulo'        => 'Directorio de actividades wellness en México',
    'pagina.inicio.meta'          => 'Encuentra retiros, festivales y círculos de bienestar en todo México: yoga, breathwork, sound healing, temazcal y más. Publica tu actividad gratis.',
    'pagina.buscar.titulo'        => 'Buscar actividades',
    'pagina.buscar.meta'          => 'Busca actividades de bienestar en México por ciudad, fecha y categoría: retiros, festivales, yoga, breathwork y más.',
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

    // ---- Portada ----
    'inicio.hero.eyebrow'      => 'Directorio de actividades · México',
    'inicio.hero.titulo'       => 'Encuentra tu próximo <em>retiro, festival o círculo</em> de bienestar',
    'inicio.hero.sub'          => 'Retiros de yoga, breathwork, sound healing y festivales holísticos, reunidos en un solo lugar — sin buscar por veinte cuentas de Instagram distintas.',
    'inicio.hero.imagen_anterior' => 'Imagen anterior',
    'inicio.hero.imagen_siguiente' => 'Imagen siguiente',
    'inicio.hero.ver_imagen'   => 'Ver imagen',
    // Las cuatro escenas del carrusel: son ambientación, no actividades reales
    // —ver el comentario al inicio de index.php—, así que su texto se traduce
    // como cualquier otro, sin depender de datos.
    'inicio.hero.slide1_cat'   => 'Sound Healing',
    'inicio.hero.slide1_texto' => 'Amanecer en el Cenote · Tulum',
    'inicio.hero.slide2_cat'   => 'Festival',
    'inicio.hero.slide2_texto' => 'Festival Holístico Raíz · CDMX',
    'inicio.hero.slide3_cat'   => 'Breathwork',
    'inicio.hero.slide3_texto' => 'Bajo las estrellas · San Miguel',
    'inicio.hero.slide4_cat'   => 'Retiro',
    'inicio.hero.slide4_texto' => 'Silencio Vipassana · Oaxaca',

    'inicio.buscador.donde_label'       => 'Dónde',
    'inicio.buscador.donde_placeholder' => 'Tulum, CDMX, Oaxaca…',
    'inicio.buscador.cuando_label'      => 'Cuándo',
    'inicio.buscador.cuando_cualquiera' => 'Cualquier fecha',
    'inicio.buscador.cuando_finde'      => 'Este fin de semana',
    'inicio.buscador.cuando_7dias'      => 'Próximos 7 días',
    'inicio.buscador.cuando_mes'        => 'Este mes',
    'inicio.buscador.que_label'         => 'Qué',
    'inicio.buscador.que_cualquiera'    => 'Cualquier práctica',
    'inicio.buscador.boton_aria'        => 'Buscar actividades',

    'inicio.publica.enlace'      => '¿Organizas actividades? Publica la tuya →',
    'inicio.categorias.eyebrow'  => 'Explora por categoría',
    'inicio.categorias.mas_aria' => 'Ver más categorías',
    'inicio.proximas.titulo'     => 'Próximas actividades',
    'inicio.proximas.ver_todas'  => 'Ver todas las actividades →',
    'inicio.proximas.mas_aria'   => 'Ver más actividades',

    // ---- Buscar (buscar.php) ----
    'buscar.eyebrow'                 => 'Buscar actividades',
    'buscar.filtros.nombre'          => 'Nombre',
    'buscar.filtros.nombre_placeholder' => 'Retiro, cenote, luna…',
    'buscar.filtros.estado'          => 'Estado',
    'buscar.filtros.todos_estados'   => 'Todos los estados',
    'buscar.filtros.ciudad'          => 'Ciudad',
    'buscar.filtros.todas_ciudades'  => 'Todas las ciudades',
    'buscar.filtros.fecha'           => 'Fecha',
    'buscar.filtros.categoria'       => 'Categoría',
    'buscar.filtros.precio'          => 'Precio',
    'buscar.filtros.solo_gratuitas'  => 'Solo gratuitas',
    'buscar.filtros.quitar'          => 'Quitar filtros',
    'buscar.orden_aria'              => 'Ordenar resultados',
    'buscar.cargar_mas'              => 'Cargar más actividades',

    // Las que sigue lee assets/js/buscar.js —vía el objeto BUSCAR_T que
    // imprime buscar.php—, no PHP: el título de resultados, el contador y
    // los mensajes de "sin resultados" se arman en el navegador según lo que
    // se vaya filtrando, sin recargar la página.
    'buscar.js.todas'            => 'Todas las actividades',
    'buscar.js.actividades'      => 'Actividades',
    'buscar.js.gratuitas'        => 'Actividades gratuitas',
    'buscar.js.en'               => ' en ',
    'buscar.js.finde'            => ' este fin de semana',
    'buscar.js.7dias'            => ' en los próximos 7 días',
    'buscar.js.mes'              => ' este mes',
    'buscar.js.sin_coincidencias'=> 'Ninguna actividad coincide',
    'buscar.js.una_encontrada'   => '1 actividad encontrada',
    'buscar.js.de_total'         => ' de ',
    'buscar.js.actividades_sufijo' => ' actividades',
    'buscar.js.encontradas_sufijo' => ' actividades encontradas',
    'buscar.js.vacio_directorio' => 'Todavía no hay actividades publicadas.',
    'buscar.js.sin_resultados'   => 'Ninguna actividad coincide con lo que buscas.',
    'buscar.js.buscando'         => 'Buscando…',
    'buscar.js.cargando'         => 'Cargando…',
    'buscar.js.error'            => 'No se pudo cargar la búsqueda. Inténtalo de nuevo.',

    // El botón del aviso "todavía no hay actividades" —vacioHTML() en
    // tarjetas.js—, compartido por la portada y el buscador.
    'tarjetas.publicar_primera'  => 'Publicar la primera',

    // ---- Consentimiento de cookies (REQ-00003) ----
    // El requerimiento escribe la marca en minúsculas; aquí va OMDARA, que es
    // como se escribe en el resto del sitio. Un banner que llama a la marca de
    // otra forma parece de otro sitio, que es justo lo contrario de lo que
    // tiene que transmitir el primer aviso que alguien ve.
    'cookies.banner.titulo' => 'Usamos cookies',
    'cookies.banner.texto'  => 'Utilizamos cookies y tecnologías similares para que OMDARA funcione correctamente, analizar cómo se utiliza la plataforma y, cuando corresponda, medir nuestras campañas de marketing. Puedes aceptar todas, rechazar las no necesarias o configurar tus preferencias.',
    'cookies.aceptar'       => 'Aceptar todas',
    'cookies.rechazar'      => 'Rechazar no necesarias',
    'cookies.configurar'    => 'Configurar preferencias',
    'cookies.cerrar'        => 'Cerrar',

    'cookies.panel.titulo'        => 'Preferencias de cookies',
    'cookies.necesarias.titulo'   => 'Necesarias',
    'cookies.necesarias.estado'   => 'Siempre activas',
    'cookies.necesarias.texto'    => 'Son necesarias para el funcionamiento de OMDARA.',
    'cookies.analiticas.titulo'   => 'Analíticas',
    'cookies.analiticas.texto'    => 'Nos ayudan a entender cómo se utiliza OMDARA y mejorar la plataforma.',
    'cookies.marketing.titulo'    => 'Marketing',
    'cookies.marketing.texto'     => 'Nos permiten medir campañas publicitarias y mejorar nuestras acciones de marketing.',
    'cookies.incluye'             => 'Incluye:',
    'cookies.activadas'           => 'Activadas',
    'cookies.desactivadas'        => 'Desactivadas',
    'cookies.guardar'             => 'Guardar preferencias',
    'cookies.politica'            => 'Leer la Política de Cookies',
    'cookies.abrir_preferencias'  => 'Cambiar mis preferencias de cookies',

    // ---- Aviso de contenido pendiente ----
    'pendiente.titulo' => 'Contenido pendiente.',
    'pendiente.texto'  => 'Esta página ya está publicada y enlazada desde el pie, pero su texto todavía no está escrito.',
];
