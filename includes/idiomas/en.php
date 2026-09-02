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
    'marca.nombre'    => 'Omdara',
    'marca.subtitulo' => 'Wellness directory MX',

    // ---- Cabecera ----
    'nav.inicio'      => 'Home',
    'nav.actividades' => 'Search activities',
    'nav.como_funciona' => 'How it works',
    'nav.blog'        => 'Blog',
    'nav.publicar'    => 'Post an activity',
    'nav.publicar_corto'  => 'Post',
    'nav.publicar_sufijo' => 'an activity',
    'nav.entrar'      => 'Sign in',
    'nav.entrar_aria' => 'Sign in to my account',
    'nav.menu'        => 'Menu',

    // ---- Selector de idioma ----
    // Cada idioma se nombra en su propio idioma, no traducido: quien busca
    // "Español" en un sitio que no entiende, busca esa palabra y no "Spanish".
    'idioma.cambiar' => 'Change language',
    'idioma.es'      => 'Español',
    'idioma.en'      => 'English',

    // ---- Pie ----
    'pie.explora'      => 'Explore',
    'pie.sobre_omdara' => 'About Omdara',
    'pie.organizadores'=> 'For organizers',
    'pie.ayuda'        => 'Help',
    'pie.legal'        => 'Legal',
    'pie.actividades'  => 'Activities',
    'pie.publicar'     => 'Post your activity',
    'pie.como_funciona'=> 'How it works',
    'pie.faq'          => 'Frequently asked questions',
    'pie.contacto'     => 'Contact',
    'pie.correo_general_label' => 'General:',
    'pie.correo_soporte_label' => 'Support:',
    'pie.terminos'     => 'Terms and Conditions',
    'pie.privacidad'   => 'Privacy Notice',
    'pie.cookies'      => 'Cookie Policy',
    'pie.beta'         => 'Beta version: we are continuously improving the platform. If you run into a problem,',
    'pie.beta_enlace'  => 'get in touch',
    'pie.instagram'    => 'Omdara on Instagram',
    'pie.facebook'     => 'Omdara on Facebook',
    'pie.whatsapp'     => 'Omdara on WhatsApp',

    // 'pie.lema' — PENDIENTE. Es texto de marca; lo entrega producto.

    // ---- Títulos de las páginas estáticas ----
    // Los títulos sí, porque sin ellos la pestaña del navegador saldría en
    // español en una página inglesa. Las meta descriptions quedan pendientes:
    // son texto SEO y el requerimiento las declara como entregable aparte.
    'pagina.inicio.titulo'        => 'Wellness activities directory in Mexico',
    'pagina.buscar.titulo'        => 'Search activities',
    'pagina.como_funciona.titulo' => 'How it works',
    'pagina.sobre_omdara.titulo'  => 'About Omdara',
    'pagina.faq.titulo'           => 'Frequently asked questions',
    'pagina.terminos.titulo'      => 'Terms and Conditions',
    'pagina.privacidad.titulo'    => 'Privacy Notice',
    'pagina.cookies.titulo'       => 'Cookie Policy',
    'pagina.404.titulo'           => 'Page not found',

    // ---- Homepage ----
    'inicio.hero.eyebrow'      => 'Activity directory · Mexico',
    'inicio.hero.imagen_anterior' => 'Previous image',
    'inicio.hero.imagen_siguiente' => 'Next image',
    'inicio.hero.ver_imagen'   => 'View image',
    'inicio.hero.slide1_titulo' => 'Discover wellness activities all across Mexico.',
    'inicio.hero.slide1_sub'    => 'Find experiences to connect with a healthier, more mindful life.',
    'inicio.hero.slide2_titulo' => 'All of wellness in one place.',
    'inicio.hero.slide2_sub'    => 'Events, classes, workshops, retreats and experiences to discover new ways to care for yourself.',
    'inicio.hero.slide3_titulo' => 'Search. Discover. Connect.',
    'inicio.hero.slide3_sub'    => 'Find activities by category, date and location, and connect directly with their organizers.',
    'inicio.hero.slide4_titulo' => 'Do you run a wellness experience?',
    'inicio.hero.slide4_sub'    => 'List it on Omdara and connect with new people.',

    'inicio.buscador.donde_label'       => 'Where',
    'inicio.buscador.donde_placeholder' => 'Tulum, Mexico City, Oaxaca…',
    'inicio.buscador.cuando_label'      => 'When',
    'inicio.buscador.cuando_cualquiera' => 'Any date',
    'inicio.buscador.cuando_finde'      => 'This weekend',
    'inicio.buscador.cuando_7dias'      => 'Next 7 days',
    'inicio.buscador.cuando_mes'        => 'This month',
    'inicio.buscador.que_label'         => 'What',
    'inicio.buscador.que_cualquiera'    => 'Any practice',
    'inicio.buscador.boton_aria'        => 'Search activities',

    'inicio.publica.enlace'      => 'Do you host activities? Post yours →',
    'inicio.categorias.eyebrow'  => 'Explore by category',
    'inicio.categorias.mas_aria' => 'See more categories',
    'inicio.proximas.titulo'     => 'Upcoming activities',
    'inicio.proximas.ver_todas'  => 'See all activities →',
    'inicio.proximas.mas_aria'   => 'See more activities',

    // ---- Search (buscar.php) ----
    'buscar.eyebrow'                 => 'Search activities',
    'buscar.filtros.nombre'          => 'Name',
    'buscar.filtros.nombre_placeholder' => 'Retreat, cenote, moon…',
    'buscar.filtros.estado'          => 'State',
    'buscar.filtros.todos_estados'   => 'All states',
    'buscar.filtros.ciudad'          => 'City',
    'buscar.filtros.todas_ciudades'  => 'All cities',
    'buscar.filtros.fecha'           => 'Date',
    'buscar.filtros.categoria'       => 'Category',
    'buscar.filtros.precio'          => 'Price',
    'buscar.filtros.solo_gratuitas'  => 'Free only',
    'buscar.filtros.quitar'          => 'Clear filters',
    'buscar.orden_aria'              => 'Sort results',
    'buscar.cargar_mas'              => 'Load more activities',

    'buscar.js.todas'            => 'All activities',
    'buscar.js.actividades'      => 'Activities',
    'buscar.js.gratuitas'        => 'Free activities',
    'buscar.js.en'               => ' in ',
    'buscar.js.finde'            => ' this weekend',
    'buscar.js.7dias'            => ' in the next 7 days',
    'buscar.js.mes'              => ' this month',
    'buscar.js.sin_coincidencias'=> 'No matching activities',
    'buscar.js.una_encontrada'   => '1 activity found',
    'buscar.js.de_total'         => ' of ',
    'buscar.js.actividades_sufijo' => ' activities',
    'buscar.js.encontradas_sufijo' => ' activities found',
    'buscar.js.vacio_directorio' => 'No activities have been published yet.',
    'buscar.js.sin_resultados'   => 'No activities match your search.',
    'buscar.js.buscando'         => 'Searching…',
    'buscar.js.cargando'         => 'Loading…',
    'buscar.js.error'            => 'We could not load the search. Please try again.',

    'tarjetas.publicar_primera'  => 'Post the first one',

    // ---- Activity form (includes/form-evento.php) ----
    'campo.opcional'    => 'optional',
    'campo.obligatorio' => 'required',

    'evento.form.titulo_label'       => 'Activity title',
    'evento.form.categorias_label'   => 'Categories',
    'evento.form.descripcion_label'  => 'Description',
    'evento.form.estado_label'       => 'State',
    'evento.form.ciudad_label'       => 'City',
    'evento.form.lugar_label'        => 'Venue name',
    'evento.form.direccion_label'    => 'Address',
    'evento.form.organizador_nombre_label' => 'Organizer name',
    'evento.form.fecha_label'        => 'Date',
    'evento.form.hora_inicio_label'  => 'Start time',
    'evento.form.hora_fin_label'     => 'End time',
    'evento.form.fecha_inicio_label' => 'Start date',
    'evento.form.fecha_fin_label'    => 'End date',
    'evento.form.frecuencia_label'   => 'Frequency',
    'evento.form.forma_pago_label'   => 'Payment type',
    'evento.form.url_compra_label'   => 'Purchase URL',
    'evento.form.url_reserva_label'  => 'Booking URL',

    'evento.campo.mapa_url'          => 'Google Maps link',
    'evento.campo.fecha_fin_unica'   => 'End date',
    'evento.campo.precio'            => 'Price',
    'evento.campo.cupo_maximo'       => 'Maximum capacity',
    'evento.campo.sitio_web'         => 'Website or link',
    'evento.campo.accion_principal'  => 'Main action',
    'evento.campo.imagen'            => 'Image',

    'evento.form.seccion1'           => '1. General information',
    'evento.form.titulo_placeholder' => 'Cenote Sunrise — Yoga and Sound',
    'evento.form.categorias_pista'   => 'pick up to',
    'evento.form.categorias_ayuda'   => 'Whichever you pick, the one highest on the list is the main one: the one shown on the homepage card.',
    'evento.form.descripcion_placeholder' => 'Describe your activity. Include what attendees will learn, who it is for, what is included, and any other important details.',
    'evento.form.descripcion_ayuda'  => 'Shown exactly as written on the activity page. Line breaks are kept.',

    'evento.form.titulo_en_label'    => 'Title in English',
    'evento.form.titulo_en_placeholder' => 'Cenote Sunrise — Yoga and Sound',
    'evento.form.titulo_en_ayuda'    => 'Only shown to people viewing the site in English. If left blank, they\'ll see the Spanish title.',
    'evento.form.descripcion_en_label' => 'Description in English',
    'evento.form.descripcion_en_placeholder' => 'Describe your activity in English...',
    'evento.form.descripcion_en_ayuda' => 'Only shown to people viewing the site in English. If left blank, they\'ll see the Spanish description.',

    'evento.form.seccion2'           => '2. Date and time',
    'evento.form.dia_unico'          => 'One-day activity',
    'evento.form.termina_otro_dia'   => 'Ends on a different day',
    'evento.form.termina_otro_dia_ayuda' => 'For multi-day retreats. If left blank, it is assumed to end the same day.',
    'evento.form.recurrente'         => 'Recurring activity',
    'evento.form.frecuencia_placeholder' => 'Choose a frequency',
    'evento.frecuencia.diaria'    => 'Daily',
    'evento.frecuencia.semanal'   => 'Weekly',
    'evento.frecuencia.quincenal' => 'Biweekly',
    'evento.frecuencia.mensual'   => 'Monthly',

    'evento.form.seccion3'           => '3. Location',
    'evento.form.estado_placeholder' => 'Choose a state',
    'evento.form.ciudad_placeholder' => 'Type to search…',
    'evento.form.lugar_placeholder'  => 'E.g. Luz Holistic Center',
    'evento.form.direccion_placeholder' => 'Street, number, neighborhood',
    'evento.form.direccion_ayuda'    => 'Filled in automatically when you move the pin; correct it if needed.',
    'evento.form.mapa_ayuda'         => 'Drag the pin to set the venue\'s exact location. City, state and address fill in on their own.',
    'evento.form.maps_enlace_label'  => 'Have the venue\'s Google Maps link? Paste it and we\'ll move the pin for you',
    'evento.form.maps_enlace_placeholder' => 'https://maps.app.goo.gl/…',
    'evento.form.maps_usar_enlace'   => 'Use link',

    'evento.form.seccion4'           => '4. Price',
    'evento.form.sin_costo'          => 'Free',
    'evento.form.de_pago'            => 'Paid',
    'evento.form.forma_pago_placeholder' => 'Choose an option',
    'evento.form.forma_pago_completa'=> 'Whole activity',
    'evento.form.forma_pago_sesion'  => 'Per session',
    'evento.form.precio_label'       => 'Price per person (MXN)',

    'evento.form.seccion5'           => '5. Maximum capacity',
    'evento.form.cupo_label'         => 'Maximum number of participants',
    'evento.form.cupo_placeholder'   => 'E.g. 20',

    'evento.form.seccion6'           => '6. Activity image',
    'evento.form.imagen_alt'         => 'Activity image',
    'evento.form.imagen_elegida'     => 'This is the one you just picked. It stays as is — no need to look for it again.',
    'evento.form.quitar_imagen'      => 'Remove this image',
    'evento.form.subir_imagen'       => 'Upload image',
    'evento.form.imagen_pista_1'     => 'JPG, PNG or WebP. Max.',
    'evento.form.imagen_pista_2'     => 'MB. Recommended: 1200 × 800 px.',
    'evento.form.imagen_pista_cambiar' => 'Only pick another one if you want to change it.',
    'evento.form.imagen_pista_sin'   => 'If you don\'t add an image, a default background color is shown on the public card.',

    'evento.form.seccion7'           => '7. Contact information',
    'evento.form.datos_organizador'  => 'Organizer details',
    'evento.form.usar_guardado'      => 'Use the saved information',
    'evento.form.nombre_label'       => 'Name',
    'evento.form.organizador_nombre_placeholder' => 'Yoga Baja',
    'evento.form.organizador_nombre_ayuda' => 'This is the name shown as the organizer on your activities.',
    'evento.form.editar_btn'         => 'Edit',
    'evento.form.telefono_placeholder' => '+52 612 123 4567',
    'evento.form.instagram_placeholder' => '@youraccount',
    'evento.form.sitio_web_placeholder' => 'https://yoursite.com',
    'evento.form.sitio_web_org_ayuda'   => 'Yours, not this activity\'s — that one goes below.',
    'evento.form.contacto_nota_1'    => 'This information will be saved to speed up your next posts. You can change it anytime from',
    'evento.form.contacto_nota_cuenta' => 'My account',
    'evento.form.sitio_web_actividad_label' => 'Website or link for this activity',
    'evento.form.sitio_web_actividad_ayuda' => 'Share a website or social profile so interested people can learn more about this activity — not yours, that one goes above.',

    // Per-activity contact email (migration 24, client 2026-09-02)
    'evento.correo_contacto.titulo'       => 'Contact email for this activity',
    'evento.correo_contacto.explicacion'  => '"Contact organizer" messages for this activity land here. It defaults to your account email; you can use another one once you confirm it.',
    'evento.correo_contacto.actual_cuenta'=> 'Right now it uses your account email:',
    'evento.correo_contacto.actual_propio'=> 'Right now it uses:',
    'evento.correo_contacto.quitar_btn'   => 'Use my account email',
    'evento.correo_contacto.nuevo_label'  => 'Use another email',
    'evento.correo_contacto.nuevo_placeholder' => 'another@email.com',
    'evento.correo_contacto.enviar_btn'   => 'Send code',
    'evento.correo_contacto.pendiente_texto' => 'We sent a code to %s. Enter it here to confirm it:',
    'evento.correo_contacto.codigo_label' => '6-digit code',
    'evento.correo_contacto.codigo_placeholder' => '000000',
    'evento.correo_contacto.confirmar_btn'=> 'Confirm',
    'evento.correo_contacto.cancelar_btn' => 'Cancel',
    'evento.correo_contacto.error_invalido'      => 'That email doesn\'t look right. Check it.',
    'evento.correo_contacto.error_espera'        => 'We just sent a code for this activity. Wait a minute before requesting another.',
    'evento.correo_contacto.error_demasiados'    => 'Too many codes requested for this activity. Try again in a while.',
    'evento.correo_contacto.error_demasiadas_ip' => 'Too many requests from this connection. Try again later.',
    'evento.correo_contacto.enviado'      => 'We sent a code to %s. Check your inbox.',
    'evento.correo_contacto.confirmado'   => 'Done: %s is now this activity\'s contact email.',

    'evento.form.seccion8'           => '8. Main action',
    'evento.form.accion_ayuda'       => 'Choose the main action people will see on your activity.',
    'evento.form.accion_contactar'   => 'Contact the organizer',
    'evento.form.accion_comprar'     => 'Buy tickets',
    'evento.form.accion_reservar'    => 'Book a spot',
    'evento.form.color_label'        => 'Card color',
    'evento.form.color_ayuda'        => 'Used when there is no image, same as on the homepage design.',

    'evento.mapa.pegar_enlace'       => 'Paste a Google Maps link first.',
    'evento.mapa.buscando'           => 'Searching…',
    'evento.mapa.completo'           => 'We filled in city, state and/or address from the map. Check that they\'re right.',
    'evento.mapa.incompleto'         => 'We couldn\'t guess the exact location from the map. Fill it in by hand.',
    'evento.mapa.no_ubicada'         => 'We couldn\'t locate that address on the map. Adjust the pin by hand.',
    'evento.mapa.encontrada'         => 'We found the address and moved the pin. Adjust it if it\'s not quite right.',
    'evento.mapa.error_direccion'    => 'We couldn\'t check that address. Adjust the pin by hand if needed.',
    'evento.mapa.enlace_listo'       => 'Done, we moved the pin to that location. Adjust it if needed.',
    'evento.mapa.enlace_error_generico' => 'We couldn\'t read that link.',
    'evento.mapa.enlace_error_comprobar' => 'We couldn\'t check the link. Try again.',
    'evento.mapa.usar_enlace'        => 'Use link',

    // ---- evento-nuevo.php / evento-editar.php ----
    'evento.nuevo.titulo'    => 'Post an activity',
    'evento.nuevo.sub'       => 'Fill in the form. Before it publishes you\'ll preview it exactly as people will see it.',
    'evento.nuevo.boton'     => 'See the preview',
    'evento.editar.titulo'   => 'Edit activity',
    'evento.editar.boton'    => 'Save changes',
    'evento.editar.borrador_sub'  => 'This is a draft: no one but you can see it until you publish it.',
    'evento.editar.oculto_sub'    => 'Hidden by moderation. It won\'t show in listings, but you can still fix it.',
    'evento.editar.publicado_sub' => 'Published. Last updated on',
    'evento.editar.no_puede_titulo' => 'You can\'t edit this activity',
    'evento.editar.no_puede_aviso'  => 'Only its organizer or an admin can make changes.',
    'evento.editar.no_puede_texto'  => 'If it\'s yours and you think this is a mistake, sign in with the same account you used to post it. If you want to request a change to someone else\'s activity, contact the organizer from its page.',
    'evento.editar.volver_ficha'    => 'Back to the activity',
    'evento.editar.volver_admin'    => 'Back to the admin panel',
    'evento.editar.volver_publicar' => 'Publish again',
    'evento.editar.eliminar'        => 'Delete activity',
    'evento.editar.confirmar_eliminar' => 'Delete "%s"? This can\'t be undone.',
    'evento.editar.no_encontrada_titulo' => 'Activity not found',
    'evento.editar.no_encontrada_h1'     => 'That activity doesn\'t exist',
    'evento.editar.no_encontrada_texto'  => 'It may have been deleted.',
    'evento.editar.volver_inicio'        => 'Back to home',

    'evento.error.imagen_pesada'    => 'The image is heavier than the server allows. Try a lighter one.',
    'evento.error.sesion_caducada'  => 'Your session expired. Send it again.',
    'evento.error.duplicado'        => 'You already have another "%s" activity in %s, %s on that same day. If this is an accidental repeat, check your activities; if not, change the date, city or category.',
    'evento.error.falta_organizador'=> 'The organizer\'s name is missing.',

    // ---- validarEvento(), includes/eventos.php ----
    'evento.valida.titulo_corto'      => 'The title needs at least 5 characters.',
    'evento.valida.titulo_largo'      => 'The title can\'t be longer than 160 characters.',
    'evento.valida.descripcion_corta' => 'Add a fuller description (at least 50 characters).',
    'evento.valida.descripcion_larga' => 'The description can\'t be longer than 2,000 characters.',
    'evento.valida.categoria_falta'   => 'Choose at least one category from the list.',
    'evento.valida.categoria_max'     => 'Choose at most %d categories.',
    'evento.valida.estado_falta'      => 'Choose a state from the list.',
    'evento.valida.ciudad_sin_estado' => 'Choose a state first.',
    'evento.valida.ciudad_falta'      => 'Choose a city from the list.',
    'evento.valida.lugar_falta'       => 'The venue is missing.',
    'evento.valida.direccion_larga'   => 'The address can\'t be longer than 255 characters.',
    'evento.valida.frecuencia_falta'  => 'Choose how often it repeats.',
    'evento.valida.hora_inicio_sesion_falta' => 'Set the time each session starts.',
    'evento.valida.hora_fin_sesion_falta'    => 'Set the time each session ends.',
    'evento.valida.hora_fin_antes_inicio'    => 'The end can\'t be before the start.',
    'evento.valida.fecha_inicio_rec_falta'   => 'Set the date it starts repeating.',
    'evento.valida.fecha_fin_rec_falta'      => 'Set the date it stops repeating.',
    'evento.valida.fecha_fin_antes_inicio'   => 'The end can\'t be before the start.',
    'evento.valida.fecha_falta'       => 'Set the activity\'s date.',
    'evento.valida.hora_inicio_falta' => 'Set the start time.',
    'evento.valida.hora_fin_falta'    => 'Set the end time.',
    'evento.valida.fecha_pasada'      => 'That date has already passed, so the activity wouldn\'t show up in listings.',
    'evento.valida.precio_falta'      => 'Set a price, or mark it as free.',
    'evento.valida.precio_invalido'   => 'The price has to be a number.',
    'evento.valida.forma_pago_falta'  => 'Choose whether the price is for the whole activity or per session.',
    'evento.valida.cupo_invalido'     => 'The capacity has to be a whole number greater than zero.',
    'evento.valida.accion_falta'      => 'Choose what you want people to do on the activity page.',
    'evento.valida.url_invalida'      => 'That address doesn\'t look valid. Start with https://',
    'evento.valida.boletos_falta'     => 'Add the link where tickets are purchased.',
    'evento.valida.reserva_falta'     => 'Add the link where the spot is booked.',
    'evento.valida.titulo_en_largo'      => 'The English title can\'t be longer than 160 characters.',
    'evento.valida.descripcion_en_larga' => 'The English description can\'t be longer than 2,000 characters.',

    // ---- includes/aviso-errores.php ----
    'evento.aviso.falta_uno'     => 'You still need to review',
    'evento.aviso.faltan_varios' => 'You still need to review',
    'evento.aviso.y'             => ' and ',

    // ---- includes/auth.php ----
    'evento.campo.sitio_web_organizador' => 'Website',

    // ---- includes/guia-accion.php ----
    'evento.guia.titulo'            => 'Quick guide',
    'evento.guia.contactar_titulo'  => 'Contact the organizer',
    'evento.guia.contactar_texto'   => 'Centralizes requests from people interested. They\'ll fill out a form with their questions and you\'ll get it straight to your email.',
    'evento.guia.contactar_ejemplos'=> 'Examples: certifications, programs, retreats, free activities.',
    'evento.guia.comprar_titulo'    => 'Buy tickets',
    'evento.guia.comprar_texto'     => 'Use it when there\'s an external link where the participant can pay or register.',
    'evento.guia.comprar_ejemplos'  => 'Examples: Eventbrite, Boletia, Ticketmaster, your own website, a payment landing page.',
    'evento.guia.reservar_titulo'   => 'Book a spot',
    'evento.guia.reservar_texto'    => 'Ideal for activities with limited capacity or advance registration, even without a direct ticket purchase.',
    'evento.guia.reservar_ejemplos' => 'Examples: Google Forms, WhatsApp, Calendly, your own form, a registration landing page.',
    'evento.guia.traduccion_titulo' => 'Important note about translation',
    'evento.guia.traduccion_texto1' => 'Omdara\'s translation does not modify or automatically translate the text you enter in your activity\'s description. The description, instructions, terms and any other custom content stay exactly as you wrote them.',
    'evento.guia.traduccion_texto2' => 'If your activity targets both Spanish- and English-speaking participants, consider providing the relevant information in both languages within the description.',
    'evento.guia.nota'              => 'With that in mind, choose the <strong>main action</strong> below: decide what people ready to take the next step will see.',

    // ---- Activity page (evento.php) ----
    'ficha.no_encontrada.titulo'    => 'Activity not found',
    'ficha.no_encontrada.h1'        => 'That activity doesn\'t exist',
    'ficha.no_encontrada.texto'     => 'It may have been deleted, or it might not be published yet.',
    'ficha.no_encontrada.ver_todas' => 'See the ones that are',

    'ficha.error.sesion_caducada'   => 'Your session expired. Try again.',
    'ficha.error.no_permiso'        => 'You can\'t do that.',
    'ficha.error.plazo_eliminar'    => 'The deadline to delete this activity has passed. Ask an admin.',
    'ficha.aviso.publicado'         => 'Published! It\'s now on the homepage.',
    'ficha.aviso.oculto'            => 'Activity hidden. It no longer shows in listings.',
    'ficha.aviso.eliminado'         => 'Activity deleted.',

    'ficha.volver.admin'       => 'Back to the admin panel',
    'ficha.volver.resultados'  => 'Back to the results',
    'ficha.volver.todas'       => 'See all activities',

    'ficha.barra.vista_previa_tit'   => 'Preview.',
    'ficha.barra.vista_previa_texto' => 'This is how your page will look. No one else can see it yet.',
    'ficha.barra.oculta_tit'         => 'Hidden.',
    'ficha.barra.oculta_texto'       => 'It doesn\'t show in listings.',
    'ficha.barra.publicada_tit'      => 'Published.',
    'ficha.barra.puedes_eliminar'    => 'You can delete it for another',
    'ficha.barra.mas'                => 'more.',
    'ficha.barra.admin_sin_plazo'    => 'You\'re an admin: you can delete it even past the deadline.',
    'ficha.barra.plazo_pasado'       => 'The deadline to delete it has passed; ask an admin if needed.',

    'ficha.btn.publicar'         => 'Publish',
    'ficha.btn.volver_publicar'  => 'Publish again',
    'ficha.btn.editar'           => 'Edit',
    'ficha.btn.ocultar'          => 'Hide',
    'ficha.btn.eliminar'         => 'Delete',
    'ficha.confirmar_eliminar'   => 'Delete "%s"? This can\'t be undone.',

    'ficha.compartir'        => '↗ Share',
    'ficha.enlace_copiado'   => 'Link copied.',

    'ficha.dato.cuando'    => 'When',
    'ficha.dato.donde'     => 'Where',
    'ficha.dato.precio'    => 'Price',
    'ficha.dato.organiza'  => 'Organizer',
    'ficha.dato.cupo'      => 'Capacity',
    'ficha.dato.personas'  => 'people',
    'ficha.dato.mas_info'  => 'More information',
    'ficha.ver_sitio'      => 'View site or profile →',

    'ficha.del'        => 'From',
    'ficha.al'         => 'to',
    'ficha.hasta_el'   => 'until',
    'ficha.horario_de' => 'from',
    'ficha.horario_a'  => 'to',

    'ficha.precio.gratis'         => 'Free',
    'ficha.precio.por_confirmar'  => 'To be confirmed',
    'ficha.precio.por_sesion'     => ' / session',

    // ---- Shared activity cards (assets/js/tarjetas.js) ----
    'tarjeta.desde'         => 'From ',
    'tarjeta.ver_actividad' => 'See activity →',

    'ficha.btn.comprar_boletos' => 'Buy tickets',
    'ficha.btn.reservar'        => 'Book my spot',
    'ficha.btn.contactar'       => 'Contact the organizer',

    'ficha.mapa_titulo_prefijo' => 'Map showing the location of',
    'ficha.como_llegar'         => 'Get directions →',
    'ficha.reportar'            => 'Report this activity',
    'ficha.varias_fechas'       => 'Multiple dates',

    'evento.editar.cambios_guardados' => 'Changes saved.',

    // ---- Contact the organizer (contactar.php) ----
    'contactar.no_encontrada.texto' => 'It may have already been taken down.',
    'contactar.error.formulario_caducado' => 'The form expired. Reload it.',
    'contactar.error.nombre_falta'  => 'Write your name so the organizer knows who\'s asking.',
    'contactar.error.correo_invalido' => 'That email doesn\'t look valid.',
    'contactar.error.mensaje_falta' => 'Write your message: it\'s what the organizer will read.',
    'contactar.error.privacidad'    => 'Check the Privacy Notice box to send your message.',
    'contactar.error.repetido'      => 'You already wrote to this organizer a moment ago. Give them time to respond before writing again.',

    'contactar.pagina.titulo' => 'Contact the organizer',
    'contactar.enviado.titulo' => 'Message sent!',
    'contactar.enviado.texto1' => 'We\'ve received your message and will send it to this activity\'s organizer.',
    'contactar.enviado.texto2' => 'The response depends on the organizer.',
    'contactar.enviado.volver' => '← Back to the activity',

    'contactar.cab.titulo' => 'Contact the organizer',
    'contactar.cab.sub'    => 'Fill out the form and we\'ll send your message to the organizer.',
    'contactar.cerrar_aria' => 'Close and go back to the activity',
    'contactar.actividad_label' => 'Activity',

    'contactar.campo.nombre' => 'Your name',
    'contactar.campo.nombre_placeholder' => 'Write your name',
    'contactar.campo.correo' => 'Your email',
    'contactar.campo.correo_placeholder' => 'example@email.com',
    'contactar.campo.correo_ayuda' => 'This is where the organizer will reply.',
    'contactar.campo.telefono' => 'Your phone / WhatsApp',
    'contactar.campo.telefono_placeholder' => 'E.g. +52 612 123 4567',
    'contactar.campo.mensaje' => 'Your message',
    'contactar.campo.mensaje_placeholder' => 'Write your message here...',

    'contactar.nota_titulo' => 'What happens with your message?',
    'contactar.nota_texto'  => 'We\'ll receive your message and send it to this activity\'s organizer. The response depends on the organizer.',

    'contactar.privacidad_texto' => 'I have read and accept the',
    'contactar.enviar_btn'       => '✈ Send message',
    'contactar.pie'              => 'Your email and phone are only shared with this activity\'s organizer. They are never made public.',

    // ---- Shared captcha (includes/captcha.php) ----
    'captcha.error.trampa'       => 'The form couldn\'t be sent. Try again.',
    'captcha.error.caducado'     => 'The form expired. Reload it.',
    'captcha.error.muy_rapido'   => 'Take a moment to review it and send it again.',
    'captcha.error.falta_token'  => 'Confirm you\'re not a robot. If you don\'t see the checkbox, reload the page.',
    'captcha.error.no_verificado'=> 'We couldn\'t verify you\'re a person. Try again.',
    'captcha.fallo_carga'        => 'The security check couldn\'t load. Reload the page; if it happens again, let us know and we\'ll look into it.',
    'captcha.trampa_label'       => 'Don\'t fill this in',

    // ---- Site contact (contacto.php) ----
    'contacto.pagina.titulo' => 'Contact Omdara',

    'contacto.error.campo_obligatorio' => 'This field is required.',
    'contacto.error.correo_invalido'   => 'Enter a valid email address.',
    'contacto.error.mensaje_corto'     => 'Tell us a bit more: with %d characters there isn\'t much to respond to.',
    'contacto.error.repetido'          => 'You already wrote to us a moment ago. Give us time to respond before writing again.',

    'contacto.enviado.h1'    => 'We\'ve received your message.',
    'contacto.enviado.sub'   => 'Thanks for reaching out. We\'ll get back to you soon.',
    'contacto.enviado.aviso' => 'We\'ll reply to the email you left us. If you don\'t hear back in a few days, check your spam folder.',

    'contacto.form.sub'            => 'Have a question, need help, or want to report a problem? Fill out the form and we\'ll get in touch.',
    'contacto.correo_directo'      => 'If you prefer, email us directly at',
    'contacto.form.revisa_campos'  => 'Check the marked fields.',

    'contacto.campo.nombre' => 'Name',
    'contacto.campo.nombre_placeholder' => 'Your name',
    'contacto.campo.correo' => 'Email',
    'contacto.campo.correo_placeholder' => 'you@email.com',
    'contacto.campo.correo_ayuda' => 'This is where we\'ll reply.',
    'contacto.campo.motivo' => 'Reason for contacting us',
    'contacto.campo.motivo_placeholder' => 'Choose one…',
    'contacto.campo.actividad' => 'Which activity is this about?',
    'contacto.campo.actividad_placeholder' => 'Activity name',
    'contacto.campo.actividad_ayuda' => 'Tell us the name of the activity you\'re writing about.',
    'contacto.campo.mensaje' => 'Message',
    'contacto.campo.mensaje_placeholder' => 'Tell us how we can help...',
    'contacto.enviar_btn' => 'Send message',
    'contacto.enviando'   => 'Sending…',
    'contacto.pie' => 'Is this about a published activity and you want to talk to whoever organizes it? Go to its page and use "Contact the organizer": it reaches that person directly and gets you a faster reply.',

    'contacto.motivo.general'     => 'General question',
    'contacto.motivo.actividad'   => 'Problem with an activity',
    'contacto.motivo.cuenta'      => 'Problem with my account',
    'contacto.motivo.organizador' => 'I\'m an organizer',
    'contacto.motivo.reporte'     => 'Report content',
    'contacto.motivo.otro'        => 'Other',

    // ---- Report activity (reportar.php) ----
    'reportar.no_encontrada.texto' => 'It may have already been taken down.',
    'reportar.error.motivo_falta'  => 'Help us keep the community safe. Select the reason you want to report this activity.',
    'reportar.error.repetido'      => 'You already reported this activity. We\'re reviewing it.',

    'reportar.pagina.titulo' => 'Report activity',
    'reportar.enviado.h1'    => 'Thanks for your report',
    'reportar.enviado.sub'   => 'We\'ll review it as soon as possible.',
    'reportar.enviado.aviso' => 'The activity stays published in the meantime. We don\'t take it down over an automatic notice: a person reviews it.',
    'reportar.volver_actividad' => '← Back to the activity',
    'reportar.enviado.volver'   => 'Back to the activity',

    'reportar.form.pregunta' => 'What\'s wrong with this activity?',
    'reportar.form.comentario' => 'Tell us more',
    'reportar.form.comentario_placeholder' => 'What you saw exactly. The more specific, the faster it gets resolved.',
    'reportar.enviar_btn' => 'Send report',
    'reportar.pie' => 'Your report doesn\'t hide the activity. It just adds it to the list of things to review.',

    'reportar.motivo.inapropiado'    => 'Offensive or inappropriate content',
    'reportar.motivo.enganoso'       => 'False or misleading information',
    'reportar.motivo.spam'           => 'Spam or advertising',
    'reportar.motivo.no_es_wellness' => 'Not a wellness activity',
    'reportar.motivo.duplicado'      => 'Posted twice',
    'reportar.motivo.otro'           => 'Other reason',

    // ---- Sign in / create account (login.php) ----
    'login.error.google'    => 'We couldn\'t complete sign-in with Google.',
    'login.error.state'     => 'The request couldn\'t be verified. Try again.',
    'login.error.cancelado' => 'You canceled signing in with Google.',
    'login.error.generico'  => 'Something went wrong. Try again.',

    'login.pagina.titulo' => 'Sign in',
    'login.h1'  => 'Sign in or create an account',
    'login.sub' => 'No passwords: we send a code to your email. If it\'s your first time, the account is created automatically.',
    'login.google_btn' => 'Continue with Google',
    'login.separador'  => 'or with your email',
    'login.campo.correo' => 'Email',
    'login.campo.correo_placeholder' => 'youremail@example.com',
    'login.continuar_btn' => 'Continue',

    // ---- Access code (codigo.php) ----
    'codigo.error.sesion_caducada' => 'Your session expired. Start over.',
    'codigo.pagina.titulo' => 'Check your email',
    'codigo.sub' => 'We sent you a one-time code. It expires in %d minutes.',
    'codigo.editar' => 'Edit',
    'codigo.campo.codigo' => 'One-time code',
    'codigo.enviar_btn'   => 'Submit',
    'codigo.reenviar_btn' => 'Resend code',
    'codigo.pie' => 'Not arriving? Check your spam folder before requesting another one.',

    // ---- Access code email (includes/correo.php) ----
    'correo.codigo.asunto' => '{codigo} is your code to sign in to {marca}',
    'correo.codigo.cuerpo' => "Your code to sign in to {marca} is:\n\n    {codigo}\n\nIt expires in {minutos} minutes and works only once.\n\nIf you didn't request this code, you can ignore this message: without it\nno one can sign in, and the code will expire on its own. No one from\n{marca} will ever ask you for it by phone, WhatsApp, or email.\n\n--\n{marca} · Wellness activity directory in Mexico",

    // ---- Email to confirm a contact email (migration 24) ----
    'correo.confirmar_contacto.asunto' => '{codigo} is your code to confirm the contact email for "{actividad}"',
    'correo.confirmar_contacto.cuerpo' => "Someone — hopefully you — wants this email to receive \"Contact organizer\" messages for the activity \"{actividad}\" on {marca}.\n\nYour code is:\n\n    {codigo}\n\nEnter it on the page where that activity is edited. It expires in {minutos} minutes and works only once.\n\nIf you don't recognize this, you can ignore it: without that code nothing\nis activated, and the code will expire on its own.\n\n--\n{marca} · Wellness activity directory in Mexico",

    // ---- Complete registration (completar-registro.php) ----
    'registro.error.acepta_legal' => 'To create your account you must accept the Terms and Conditions and the Privacy Notice.',
    'registro.error.no_creada'    => 'We couldn\'t create your account. Try again.',
    'registro.pagina.titulo' => 'Complete your registration',
    'registro.sub_google' => 'Google confirmed your email. One step left to create your account.',
    'registro.sub_correo' => 'We verified your email. One step left to create your account.',
    'registro.se_creara'  => 'The account will be created for',
    'registro.crear_btn'  => 'Create my account',
    'registro.pie_pregunta' => 'Wasn\'t you?',
    'registro.pie_cancelar' => 'Cancel and sign out',

    // ---- Shared legal checkbox (includes/casilla-legal.php) ----
    'legal.acepto_1'   => 'I have read and accept the',
    'legal.y'          => 'and the',
    'legal.de_omdara'  => 'of OMDARA.',

    // ---- includes/auth.php: email code and Google ----
    'auth.correo_invalido'       => 'That email doesn\'t look right. Check it.',
    'auth.espera_minuto'         => 'We just sent you one. Wait a minute before asking for another.',
    'auth.demasiados_codigos'    => 'You\'ve requested too many codes. Try again in a while or sign in with Google.',
    'auth.demasiadas_peticiones' => 'Too many requests from this connection. Try again later.',
    'auth.error_envio'           => 'We couldn\'t send the email. Try again or sign in with Google.',
    'auth.codigo_enviado'        => 'We sent a code to',
    'auth.codigo_formato'        => 'The code is six digits.',
    'auth.codigo_caducado'       => 'That code expired or was already used. Request a new one.',
    'auth.demasiados_intentos'   => 'Too many attempts with that code. Request a new one.',
    'auth.codigo_incorrecto_quedan' => 'Incorrect code. You have %d attempts left.',
    'auth.codigo_incorrecto_final'  => 'Incorrect code. Request a new one.',
    'auth.cuenta_suspendida'     => 'This account is suspended.',
    'auth.google_sin_datos'      => 'Google didn\'t return the required data.',
    'auth.correo_ya_registrado'  => 'That email is already registered. Sign in by requesting a code.',

    // ---- Consentimiento de cookies (REQ-00003) ----
    // Sí se traduce, aunque roce lo legal: es lo PRIMERO que ve quien entra, y
    // un aviso en español en la versión inglesa deja a esa persona decidiendo
    // sobre sus datos en un idioma que no eligió. El texto describe lo que hace
    // el sitio —no promete nada ni fija condiciones—, así que traducirlo no
    // inventa obligaciones. La redacción legal larga sigue en la Política de
    // Cookies, y esa sí la entrega producto.
    'cookies.banner.titulo' => 'We use cookies',
    'cookies.banner.texto'  => 'We use cookies and similar technologies to keep OMDARA working properly, to analyse how the platform is used and, where applicable, to measure our marketing campaigns. You can accept all, reject non-essential ones, or set your preferences.',
    'cookies.aceptar'       => 'Accept all',
    'cookies.rechazar'      => 'Reject non-essential',
    'cookies.configurar'    => 'Set preferences',
    'cookies.cerrar'        => 'Close',

    'cookies.panel.titulo'        => 'Cookie preferences',
    'cookies.necesarias.titulo'   => 'Essential',
    'cookies.necesarias.estado'   => 'Always on',
    'cookies.necesarias.texto'    => 'They are required for OMDARA to work.',
    'cookies.analiticas.titulo'   => 'Analytics',
    'cookies.analiticas.texto'    => 'They help us understand how OMDARA is used and improve the platform.',
    'cookies.marketing.titulo'    => 'Marketing',
    'cookies.marketing.texto'     => 'They let us measure advertising campaigns and improve our marketing.',
    'cookies.incluye'             => 'Includes:',
    'cookies.activadas'           => 'On',
    'cookies.desactivadas'        => 'Off',
    'cookies.guardar'             => 'Save preferences',
    'cookies.politica'            => 'Read the Cookie Policy',
    'cookies.abrir_preferencias'  => 'Change my cookie preferences',

    // ---- Aviso de contenido pendiente ----
    'pendiente.titulo' => 'Content pending.',
    'pendiente.texto'  => 'This page is already published and linked from the footer, but its text has not been written yet.',
];
