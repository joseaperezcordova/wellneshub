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
    'marca.nombre'    => 'Omdara',
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
    'nav.entrar_aria' => 'Entrar a mi cuenta',
    'nav.menu'        => 'Menú',

    // ---- Selector de idioma ----
    'idioma.cambiar' => 'Cambiar idioma',
    'idioma.es'      => 'Español',
    'idioma.en'      => 'English',

    // ---- Pie ----
    'pie.lema'         => 'Tu guía de experiencias de bienestar en México. Conecta con actividades que nutren cuerpo, mente y alma.',
    'pie.explora'      => 'Explora',
    'pie.sobre_omdara' => 'Sobre Omdara',
    'pie.organizadores'=> 'Para organizadores',
    'pie.ayuda'        => 'Ayuda',
    'pie.legal'        => 'Legal',
    'pie.actividades'  => 'Actividades',
    'pie.publicar'     => 'Publicar tu actividad',
    'pie.como_funciona'=> '¿Cómo funciona?',
    'pie.faq'          => 'Preguntas frecuentes',
    'pie.contacto'     => 'Contacto',
    'pie.correo_general_label' => 'General:',
    'pie.correo_soporte_label' => 'Soporte:',
    'pie.terminos'     => 'Términos y Condiciones',
    'pie.privacidad'   => 'Aviso de Privacidad',
    'pie.cookies'      => 'Política de Cookies',
    'pie.beta'         => 'Versión beta: estamos mejorando continuamente la plataforma. Si encuentras algún problema,',
    'pie.beta_enlace'  => 'contáctanos',
    'pie.instagram'    => 'Omdara en Instagram',
    'pie.facebook'     => 'Omdara en Facebook',
    'pie.whatsapp'     => 'Omdara en WhatsApp',

    // ---- Títulos y descripciones de las páginas estáticas ----
    'pagina.inicio.titulo'        => 'Directorio de actividades wellness en México',
    'pagina.inicio.meta'          => 'Encuentra retiros, festivales y círculos de bienestar en todo México: yoga, breathwork, sound healing, temazcal y más. Publica tu actividad gratis.',
    'pagina.buscar.titulo'        => 'Buscar actividades',
    'pagina.buscar.meta'          => 'Busca actividades de bienestar en México por ciudad, fecha y categoría: retiros, festivales, yoga, breathwork y más.',
    'pagina.como_funciona.titulo' => 'Cómo funciona',
    'pagina.como_funciona.meta'   => 'Cómo encontrar actividades de bienestar en Omdara y cómo publicar la tuya si eres organizador.',
    'pagina.sobre_omdara.titulo'  => 'Sobre Omdara',
    'pagina.sobre_omdara.meta'    => 'Qué es Omdara, nuestra misión, nuestra visión y en qué creemos.',
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
    'inicio.hero.imagen_anterior' => 'Imagen anterior',
    'inicio.hero.imagen_siguiente' => 'Imagen siguiente',
    'inicio.hero.ver_imagen'   => 'Ver imagen',
    // Las cuatro diapositivas del carrusel: texto de cliente (2026-09-02). El
    // titular y el subtexto rotan junto con la imagen de fondo —los lee
    // assets/js/inicio.js desde el data-titulo/data-sub de cada .slide—; la
    // cuarta va dirigida a quien organiza, no a quien busca, a propósito.
    'inicio.hero.slide1_titulo' => 'Descubre actividades de bienestar en todo México.',
    'inicio.hero.slide1_sub'    => 'Encuentra experiencias para conectar con una vida más saludable y consciente.',
    'inicio.hero.slide2_titulo' => 'Todo el bienestar en un solo lugar.',
    'inicio.hero.slide2_sub'    => 'Eventos, clases, talleres, retiros y experiencias para descubrir nuevas formas de cuidarte.',
    'inicio.hero.slide3_titulo' => 'Busca. Descubre. Conecta.',
    'inicio.hero.slide3_sub'    => 'Encuentra actividades por categoría, fecha y ubicación y conecta directamente con sus organizadores.',
    'inicio.hero.slide4_titulo' => '¿Organizas una experiencia de bienestar?',
    'inicio.hero.slide4_sub'    => 'Publícala en Omdara y conecta con nuevas personas.',

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

    // ---- Formulario de actividad (includes/form-evento.php) ----
    // Etiquetas que forma y etiquetasCampos() —el aviso de errores— comparten
    // por ser el mismo texto en los dos sitios. Donde el texto real difiere
    // —«Fecha de fin» en el aviso vs. «Termina otro día» en el campo—, cada
    // uno tiene la suya, más abajo.
    // Usadas junto a cualquier etiqueta de campo, en cualquier formulario.
    'campo.opcional'    => 'opcional',
    'campo.obligatorio' => 'obligatorio',

    'evento.form.titulo_label'       => 'Título de la actividad',
    'evento.form.categorias_label'   => 'Categorías',
    'evento.form.descripcion_label'  => 'Descripción',
    'evento.form.estado_label'       => 'Estado',
    'evento.form.ciudad_label'       => 'Ciudad',
    'evento.form.lugar_label'        => 'Nombre del lugar',
    'evento.form.direccion_label'    => 'Dirección',
    'evento.form.fecha_label'        => 'Fecha',
    'evento.form.hora_inicio_label'  => 'Hora de inicio',
    'evento.form.hora_fin_label'     => 'Hora de fin',
    'evento.form.fecha_inicio_label' => 'Fecha de inicio',
    'evento.form.fecha_fin_label'    => 'Fecha de fin',
    'evento.form.frecuencia_label'   => 'Frecuencia',
    'evento.form.forma_pago_label'   => 'Forma de pago',
    'evento.form.url_compra_label'   => 'URL de compra',
    'evento.form.url_reserva_label'  => 'URL de reserva',

    // Solo para el aviso de errores: el texto real del campo es distinto.
    'evento.campo.mapa_url'          => 'Enlace de Google Maps',
    'evento.campo.fecha_fin_unica'   => 'Fecha de fin',
    'evento.campo.precio'            => 'Precio',
    'evento.campo.cupo_maximo'       => 'Cupo máximo',
    'evento.campo.sitio_web'         => 'Sitio web o enlace',
    'evento.campo.accion_principal'  => 'Acción principal',
    'evento.campo.imagen'            => 'Imagen',

    // Sección 1
    'evento.form.seccion1'           => '1. Información general',
    'evento.form.titulo_placeholder' => 'Amanecer en el Cenote — Yoga y Sonido',
    'evento.form.categorias_pista'   => 'elige hasta',
    'evento.form.categorias_ayuda'   => 'De las que marques, la que quede más arriba en la lista es la principal: la que se ve en la tarjeta de la portada.',
    'evento.form.descripcion_placeholder' => 'Describe tu actividad. Incluye qué aprenderán los asistentes, a quién está dirigida, qué incluye y cualquier información importante.',
    'evento.form.descripcion_ayuda'  => 'Se muestra tal cual en la ficha. Los saltos de línea se respetan.',

    // Versión en inglés, opcional (REQ-00002 fase 5). No se traduce sola: si
    // se deja en blanco, quien vea la ficha en inglés lee el título y la
    // descripción en español.
    'evento.form.titulo_en_label'    => 'Título en inglés',
    'evento.form.titulo_en_placeholder' => 'Cenote Sunrise — Yoga and Sound',
    'evento.form.titulo_en_ayuda'    => 'Solo se muestra a quien vea el sitio en inglés. Si lo dejas vacío, verá el título en español.',
    'evento.form.descripcion_en_label' => 'Descripción en inglés',
    'evento.form.descripcion_en_placeholder' => 'Describe your activity in English...',
    'evento.form.descripcion_en_ayuda' => 'Solo se muestra a quien vea el sitio en inglés. Si la dejas vacía, verá la descripción en español.',

    // Sección 2
    'evento.form.seccion2'           => '2. Fecha y horario',
    'evento.form.dia_unico'          => 'Actividad de un día',
    'evento.form.termina_otro_dia'   => 'Termina otro día',
    'evento.form.termina_otro_dia_ayuda' => 'Para retiros de varios días. Si no se pone, se asume el mismo día.',
    'evento.form.recurrente'         => 'Actividad recurrente',
    'evento.form.frecuencia_placeholder' => 'Selecciona una frecuencia',
    'evento.frecuencia.diaria'    => 'Diaria',
    'evento.frecuencia.semanal'   => 'Semanal',
    'evento.frecuencia.quincenal' => 'Quincenal',
    'evento.frecuencia.mensual'   => 'Mensual',

    // Sección 3
    'evento.form.seccion3'           => '3. Ubicación',
    'evento.form.estado_placeholder' => 'Selecciona un estado',
    'evento.form.ciudad_placeholder' => 'Escribe para buscar…',
    'evento.form.lugar_placeholder'  => 'Ej. Centro Holístico Luz',
    'evento.form.direccion_placeholder' => 'Calle, número, colonia',
    'evento.form.direccion_ayuda'    => 'Se completa sola al mover el pin; corrígela si hace falta.',
    'evento.form.mapa_ayuda'         => 'Arrastra el pin para ajustar la ubicación exacta del lugar. Ciudad, estado y dirección se completan solos.',
    'evento.form.maps_enlace_label'  => '¿Tienes el enlace de Google Maps del lugar? Pégalo y movemos el pin por ti',
    'evento.form.maps_enlace_placeholder' => 'https://maps.app.goo.gl/…',
    'evento.form.maps_usar_enlace'   => 'Usar enlace',

    // Sección 4
    'evento.form.seccion4'           => '4. Precio',
    'evento.form.sin_costo'          => 'Sin costo',
    'evento.form.de_pago'            => 'De pago',
    'evento.form.forma_pago_placeholder' => 'Selecciona una opción',
    'evento.form.forma_pago_completa'=> 'Actividad completa',
    'evento.form.forma_pago_sesion'  => 'Por sesión',
    'evento.form.precio_label'       => 'Precio por persona (MXN)',

    // Sección 5
    'evento.form.seccion5'           => '5. Cupo máximo',
    'evento.form.cupo_label'         => 'Número máximo de participantes',
    'evento.form.cupo_placeholder'   => 'Ej. 20',

    // Sección 6
    'evento.form.seccion6'           => '6. Imagen de la actividad',
    'evento.form.imagen_alt'         => 'Imagen de la actividad',
    'evento.form.imagen_elegida'     => 'Esta es la que acabas de elegir. Sigue puesta: no hace falta que la busques otra vez.',
    'evento.form.quitar_imagen'      => 'Quitar esta imagen',
    'evento.form.subir_imagen'       => 'Subir imagen',
    'evento.form.imagen_pista_1'     => 'JPG, PNG o WebP. Máx.',
    'evento.form.imagen_pista_2'     => 'MB. Recomendado: 1200 × 800 px.',
    'evento.form.imagen_pista_cambiar' => 'Elige otra solo si quieres cambiarla.',
    'evento.form.imagen_pista_sin'   => 'Si no agregas imagen, se muestra un color de fondo predeterminado en la tarjeta pública.',

    // Sección 7
    'evento.form.seccion7'           => '7. Información de contacto',
    'evento.form.sitio_web_placeholder' => 'https://tusitio.com',
    'evento.form.sitio_web_actividad_label' => 'Sitio web o red social',
    'evento.form.sitio_web_actividad_ayuda' => 'Comparte un sitio web o perfil de redes sociales para que los interesados conozcan más sobre ti o esta actividad.',

    // Correo de contacto por actividad (migración 24, cliente 2026-09-02)
    'evento.correo_contacto.campo_label'  => 'Correo de contacto',
    'evento.correo_contacto.usar_cuenta'  => 'Usar el correo de mi cuenta',
    'evento.correo_contacto.info_texto'   => 'Las personas interesadas podrán enviarte una solicitud de información desde Omdara. Recibirás sus datos y mensaje en este correo.',
    'evento.correo_contacto.nuevo_placeholder' => 'otro@correo.com',
    'evento.correo_contacto.enviar_btn'   => 'Enviar código',
    'evento.correo_contacto.pendiente_texto' => 'Te mandamos un código a %s. Escríbelo aquí para confirmarlo:',
    'evento.correo_contacto.codigo_label' => 'Código de 6 dígitos',
    'evento.correo_contacto.codigo_placeholder' => '000000',
    'evento.correo_contacto.confirmar_btn'=> 'Confirmar',
    'evento.correo_contacto.cancelar_btn' => 'Cancelar',
    'evento.correo_contacto.error_invalido'      => 'Ese correo no tiene buena pinta. Revísalo.',
    'evento.correo_contacto.error_espera'        => 'Acabamos de enviar un código para esta actividad. Espera un minuto antes de pedir otro.',
    'evento.correo_contacto.error_demasiados'    => 'Demasiados códigos pedidos para esta actividad. Prueba dentro de un rato.',
    'evento.correo_contacto.error_demasiadas_ip' => 'Demasiadas peticiones desde esta conexión. Prueba más tarde.',
    'evento.correo_contacto.enviado'      => 'Te enviamos un código a %s. Revisa tu bandeja.',
    'evento.correo_contacto.confirmado'   => 'Listo: %s ya es el correo de contacto de esta actividad.',
    'evento.correo_contacto.quitado'      => 'Listo: vuelve a usar el correo de tu cuenta.',

    // Sección 8
    'evento.form.seccion8'           => '8. Acción principal',
    'evento.form.accion_ayuda'       => 'Elige la acción principal que verán las personas en tu actividad.',
    'evento.form.accion_contactar'   => 'Contactar al organizador',
    'evento.form.accion_comprar'     => 'Comprar boletos',
    'evento.form.accion_reservar'    => 'Reservar lugar',
    'evento.form.color_label'        => 'Color de la tarjeta',
    'evento.form.color_ayuda'        => 'Se usa cuando no hay imagen, igual que en el diseño de la portada.',

    // Mensajes dinámicos del mapa interactivo (JS)
    'evento.mapa.pegar_enlace'       => 'Pega primero un enlace de Google Maps.',
    'evento.mapa.buscando'           => 'Buscando…',
    'evento.mapa.completo'           => 'Completamos ciudad, estado y/o dirección a partir del mapa. Revisa que estén bien.',
    'evento.mapa.incompleto'         => 'No pudimos adivinar la ubicación exacta desde el mapa. Complétala a mano.',
    'evento.mapa.no_ubicada'         => 'No pudimos ubicar esa dirección en el mapa. Ajusta el pin a mano.',
    'evento.mapa.encontrada'         => 'Encontramos la dirección y movimos el pin. Ajústalo si no quedó exacto.',
    'evento.mapa.error_direccion'    => 'No pudimos comprobar esa dirección. Ajusta el pin a mano si hace falta.',
    'evento.mapa.enlace_listo'       => 'Listo, movimos el pin a esa ubicación. Ajústalo si hace falta.',
    'evento.mapa.enlace_error_generico' => 'No se pudo leer ese enlace.',
    'evento.mapa.enlace_error_comprobar' => 'No se pudo comprobar el enlace. Inténtalo de nuevo.',
    'evento.mapa.usar_enlace'        => 'Usar enlace',

    // ---- evento-nuevo.php / evento-editar.php ----
    'evento.nuevo.titulo'    => 'Publicar una actividad',
    'evento.nuevo.sub'       => 'Rellena la ficha. Antes de publicarla la vas a ver tal como la verá la gente.',
    'evento.nuevo.boton'     => 'Ver la vista previa',
    'evento.editar.titulo'   => 'Editar actividad',
    'evento.editar.boton'    => 'Guardar cambios',
    'evento.editar.borrador_sub'  => 'Es un borrador: no la ve nadie más que tú hasta que la publiques.',
    'evento.editar.oculto_sub'    => 'Oculta por moderación. No aparece en el listado, pero puedes corregirla igual.',
    'evento.editar.publicado_sub' => 'Publicada. Se actualizó por última vez el',
    'evento.editar.no_puede_titulo' => 'No puedes editar esta actividad',
    'evento.editar.no_puede_aviso'  => 'Solo quien la organiza o un administrador pueden corregirla.',
    'evento.editar.no_puede_texto'  => 'Si es tuya y crees que esto es un error, entra con la misma cuenta con la que la publicaste. Si quieres pedir un cambio en una actividad de otra persona, contacta al organizador desde su ficha.',
    'evento.editar.volver_ficha'    => 'Volver a la ficha',
    'evento.editar.volver_admin'    => 'Volver al panel admin',
    'evento.editar.volver_publicar' => 'Volver a publicar',
    'evento.editar.eliminar'        => 'Eliminar actividad',
    'evento.editar.confirmar_eliminar' => '¿Eliminar «%s»? No se puede deshacer.',
    'evento.editar.no_encontrada_titulo' => 'Actividad no encontrada',
    'evento.editar.no_encontrada_h1'     => 'Esa actividad no existe',
    'evento.editar.no_encontrada_texto'  => 'Puede que se haya borrado.',
    'evento.editar.volver_inicio'        => 'Volver al inicio',

    // Mensajes generales de error, compartidos por alta y edición
    'evento.error.imagen_pesada'    => 'La imagen pesa más de lo que admite el servidor. Prueba con una más ligera.',
    'evento.error.sesion_caducada'  => 'La sesión caducó. Vuelve a enviarlo.',
    'evento.error.duplicado'        => 'Ya tienes otra actividad de "%s" en %s, %s para ese mismo día. Si es una repetición sin querer, revisa tus actividades; si es otra cosa, cambia la fecha, la ciudad o la categoría.',

    // ---- validarEvento(), includes/eventos.php ----
    'evento.valida.titulo_corto'      => 'El título necesita al menos 5 caracteres.',
    'evento.valida.titulo_largo'      => 'El título no puede pasar de 160 caracteres.',
    'evento.valida.descripcion_corta' => 'Agrega una descripción más completa (mínimo 50 caracteres).',
    'evento.valida.descripcion_larga' => 'La descripción no puede pasar de 2,000 caracteres.',
    'evento.valida.categoria_falta'   => 'Elige al menos una categoría de la lista.',
    'evento.valida.categoria_max'     => 'Elige como máximo %d categorías.',
    'evento.valida.estado_falta'      => 'Elige un estado de la lista.',
    'evento.valida.ciudad_sin_estado' => 'Elige primero el estado.',
    'evento.valida.ciudad_falta'      => 'Elige una ciudad de la lista.',
    'evento.valida.lugar_falta'       => 'Falta el lugar donde se realiza.',
    'evento.valida.direccion_larga'   => 'La dirección no puede pasar de 255 caracteres.',
    'evento.valida.frecuencia_falta'  => 'Elige cada cuánto se repite.',
    'evento.valida.hora_inicio_sesion_falta' => 'Pon la hora a la que empieza cada sesión.',
    'evento.valida.hora_fin_sesion_falta'    => 'Pon la hora a la que termina cada sesión.',
    'evento.valida.hora_fin_antes_inicio'    => 'El final no puede ser antes que el inicio.',
    'evento.valida.fecha_inicio_rec_falta'   => 'Pon la fecha en la que empieza a repetirse.',
    'evento.valida.fecha_fin_rec_falta'      => 'Pon la fecha en la que termina de repetirse.',
    'evento.valida.fecha_fin_antes_inicio'   => 'El final no puede ser anterior al principio.',
    'evento.valida.fecha_falta'       => 'Pon la fecha de la actividad.',
    'evento.valida.hora_inicio_falta' => 'Pon la hora de inicio.',
    'evento.valida.hora_fin_falta'    => 'Pon la hora de fin.',
    'evento.valida.fecha_pasada'      => 'Esa fecha ya pasó, así que la actividad no aparecería en el listado.',
    'evento.valida.precio_falta'      => 'Pon el precio, o marca que es sin costo.',
    'evento.valida.precio_invalido'   => 'El precio tiene que ser un número.',
    'evento.valida.forma_pago_falta'  => 'Elige si el precio es por toda la actividad o por sesión.',
    'evento.valida.cupo_invalido'     => 'El cupo tiene que ser un número entero mayor que cero.',
    'evento.valida.accion_falta'      => 'Elige qué esperas que haga quien vea la ficha.',
    'evento.valida.url_invalida'      => 'Esa dirección no parece válida. Empieza por https://',
    'evento.valida.boletos_falta'     => 'Agrega el enlace donde se compran los boletos.',
    'evento.valida.reserva_falta'     => 'Agrega el enlace donde se reserva el lugar.',
    'evento.valida.titulo_en_largo'      => 'El título en inglés no puede pasar de 160 caracteres.',
    'evento.valida.descripcion_en_larga' => 'La descripción en inglés no puede pasar de 2,000 caracteres.',

    // ---- includes/aviso-errores.php ----
    'evento.aviso.falta_uno'     => 'Falta revisar',
    'evento.aviso.faltan_varios' => 'Faltan por revisar',
    'evento.aviso.y'             => ' y ',

    // ---- includes/auth.php ----
    'evento.campo.sitio_web_organizador' => 'Sitio web',

    // ---- includes/guia-accion.php ----
    'evento.guia.titulo'            => 'Guía rápida',
    'evento.guia.contactar_titulo'  => 'Contactar al organizador',
    'evento.guia.contactar_texto'   => 'Centraliza las solicitudes de tus interesados. Los usuarios completarán un formulario con sus dudas y recibirás la información directamente en tu correo electrónico.',
    'evento.guia.contactar_ejemplos'=> 'Ejemplos: certificaciones, programas, retiros, actividades gratuitas.',
    'evento.guia.comprar_titulo'    => 'Comprar boletos',
    'evento.guia.comprar_texto'     => 'Utilízalo cuando exista un enlace externo donde el participante pueda realizar el pago o la inscripción.',
    'evento.guia.comprar_ejemplos'  => 'Ejemplos: Eventbrite, Boletia, Ticketmaster, sitio web propio, landing page de pago.',
    'evento.guia.reservar_titulo'   => 'Reservar lugar',
    'evento.guia.reservar_texto'    => 'Ideal para actividades con cupo limitado o registro previo, aunque no exista compra directa de boletos.',
    'evento.guia.reservar_ejemplos' => 'Ejemplos: Google Forms, WhatsApp, Calendly, formulario propio, landing page de registro.',
    'evento.guia.traduccion_titulo' => 'Importante sobre la traducción',
    'evento.guia.traduccion_texto1' => 'La traducción de Omdara no modifica ni traduce automáticamente los textos que tú ingreses en la descripción de tu actividad. La descripción, instrucciones, condiciones y demás contenido personalizado permanecerán tal como los hayas escrito.',
    'evento.guia.traduccion_texto2' => 'Si tu actividad está dirigida a participantes en español e inglés, considera proporcionar la información relevante en ambos idiomas dentro de la descripción.',
    'evento.guia.nota'              => 'Con esto en mente, elige la <strong>acción principal</strong> más abajo: decide qué verán quienes quieran dar el siguiente paso.',

    // ---- Ficha de actividad (evento.php) ----
    'ficha.no_encontrada.titulo'    => 'Actividad no encontrada',
    'ficha.no_encontrada.h1'        => 'Esa actividad no existe',
    'ficha.no_encontrada.texto'     => 'Puede que se haya borrado o que todavía no esté publicada.',
    'ficha.no_encontrada.ver_todas' => 'Ver las que sí',

    'ficha.error.sesion_caducada'   => 'La sesión caducó. Vuelve a intentarlo.',
    'ficha.error.no_permiso'        => 'No puedes hacer eso.',
    'ficha.error.plazo_eliminar'    => 'Ya pasó el plazo para borrar esta actividad. Pídeselo al administrador.',
    'ficha.aviso.publicado'         => '¡Publicado! Ya aparece en la portada.',
    'ficha.aviso.oculto'            => 'Actividad oculta. Ya no aparece en el listado.',
    'ficha.aviso.eliminado'         => 'Actividad eliminada.',

    'ficha.volver.admin'       => 'Volver al panel admin',
    'ficha.volver.resultados'  => 'Volver a los resultados',
    'ficha.volver.todas'       => 'Ver todas las actividades',

    'ficha.barra.vista_previa_tit'   => 'Vista previa.',
    'ficha.barra.vista_previa_texto' => 'Así queda tu ficha. Todavía no la ve nadie más.',
    'ficha.barra.oculta_tit'         => 'Oculta.',
    'ficha.barra.oculta_texto'       => 'No aparece en el listado.',
    'ficha.barra.publicada_tit'      => 'Publicada.',
    'ficha.barra.puedes_eliminar'    => 'Puedes eliminarla durante',
    'ficha.barra.mas'                => 'más.',
    'ficha.barra.admin_sin_plazo'    => 'Eres administrador: puedes eliminarla aunque pasara el plazo.',
    'ficha.barra.plazo_pasado'       => 'Pasó el plazo para eliminarla; pídeselo al administrador si hace falta.',

    'ficha.btn.publicar'         => 'Publicar',
    'ficha.btn.volver_publicar'  => 'Volver a publicar',
    'ficha.btn.editar'           => 'Editar',
    'ficha.btn.ocultar'          => 'Ocultar',
    'ficha.btn.eliminar'         => 'Eliminar',
    'ficha.confirmar_eliminar'   => '¿Eliminar «%s»? No se puede deshacer.',

    'ficha.compartir'        => '↗ Compartir',
    'ficha.enlace_copiado'   => 'Enlace copiado.',

    'ficha.dato.cuando'    => 'Cuándo',
    'ficha.dato.donde'     => 'Dónde',
    'ficha.dato.precio'    => 'Precio',
    'ficha.dato.organiza'  => 'Organiza',
    'ficha.dato.cupo'      => 'Cupo',
    'ficha.dato.personas'  => 'personas',
    'ficha.dato.mas_info'  => 'Más información',
    'ficha.ver_sitio'      => 'Ver sitio o perfil →',

    'ficha.del'        => 'Del',
    'ficha.al'         => 'al',
    'ficha.hasta_el'   => 'hasta el',
    'ficha.horario_de' => 'de',
    'ficha.horario_a'  => 'a',

    'ficha.precio.gratis'         => 'Gratis',
    'ficha.precio.por_confirmar'  => 'Por confirmar',
    'ficha.precio.por_sesion'     => ' / sesión',

    // ---- Tarjetas de actividad, compartidas (assets/js/tarjetas.js) ----
    // Portada, buscador y (a futuro) relacionados: un solo archivo JS, así
    // que necesita su traducción global y no una por página —ver TARJETA_T
    // en includes/layout.php—.
    'tarjeta.desde'         => 'Desde ',
    'tarjeta.ver_actividad' => 'Ver actividad →',

    'ficha.btn.comprar_boletos' => 'Comprar boletos',
    'ficha.btn.reservar'        => 'Reservar mi lugar',
    'ficha.btn.contactar'       => 'Contactar al organizador',

    'ficha.mapa_titulo_prefijo' => 'Mapa con la ubicación de',
    'ficha.como_llegar'         => 'Cómo llegar →',
    'ficha.reportar'            => 'Reportar esta actividad',
    'ficha.varias_fechas'       => 'Varias fechas',

    // Mensaje de la sesión que evento-editar.php deja para cuando se vuelve
    // a la ficha tras guardar cambios.
    'evento.editar.cambios_guardados' => 'Cambios guardados.',

    // ---- Contactar al organizador (contactar.php) ----
    'contactar.no_encontrada.texto' => 'Puede que ya se haya retirado.',
    'contactar.error.formulario_caducado' => 'El formulario caducó. Vuelve a cargarlo.',
    'contactar.error.nombre_falta'  => 'Escribe tu nombre para que el organizador sepa quién pregunta.',
    'contactar.error.correo_invalido' => 'Ese correo no parece válido.',
    'contactar.error.mensaje_falta' => 'Escribe tu mensaje: es lo que el organizador va a leer.',
    'contactar.error.privacidad'    => 'Marca la casilla del Aviso de Privacidad para poder enviar tu mensaje.',
    'contactar.error.repetido'      => 'Ya le escribiste a este organizador hace un momento. Dale tiempo a responder antes de volver a escribir.',

    'contactar.pagina.titulo' => 'Contactar al organizador',
    'contactar.enviado.titulo' => '¡Mensaje enviado!',
    'contactar.enviado.texto1' => 'Hemos recibido tu mensaje y lo enviaremos al organizador de esta actividad.',
    'contactar.enviado.texto2' => 'La respuesta dependerá del organizador.',
    'contactar.enviado.volver' => '← Volver a la actividad',

    'contactar.cab.titulo' => 'Contacta al organizador',
    'contactar.cab.sub'    => 'Completa el formulario y enviaremos tu mensaje al organizador.',
    'contactar.cerrar_aria' => 'Cerrar y volver a la actividad',
    'contactar.actividad_label' => 'Actividad',

    'contactar.campo.nombre' => 'Tu nombre',
    'contactar.campo.nombre_placeholder' => 'Escribe tu nombre',
    'contactar.campo.correo' => 'Tu correo electrónico',
    'contactar.campo.correo_placeholder' => 'ejemplo@correo.com',
    'contactar.campo.correo_ayuda' => 'Aquí te va a responder el organizador.',
    'contactar.campo.telefono' => 'Tu teléfono / WhatsApp',
    'contactar.campo.telefono_placeholder' => 'Ej. +52 612 123 4567',
    'contactar.campo.mensaje' => 'Tu mensaje',
    'contactar.campo.mensaje_placeholder' => 'Escribe aquí tu mensaje...',

    'contactar.nota_titulo' => '¿Qué pasa con tu mensaje?',
    'contactar.nota_texto'  => 'Recibiremos tu mensaje y lo enviaremos al organizador de esta actividad. La respuesta dependerá del organizador.',

    'contactar.privacidad_texto' => 'He leído y acepto el',
    'contactar.enviar_btn'       => '✈ Enviar mensaje',
    'contactar.pie'              => 'Tu correo y tu teléfono solo los recibe el organizador de esta actividad. No se hacen públicos en ningún lado.',

    // ---- Captcha compartido (includes/captcha.php) ----
    // Usado por contactar.php, contacto.php y reportar.php.
    'captcha.error.trampa'       => 'No se pudo enviar el formulario. Inténtalo otra vez.',
    'captcha.error.caducado'     => 'El formulario caducó. Vuelve a cargarlo.',
    'captcha.error.muy_rapido'   => 'Tómate un momento para revisarlo y vuelve a enviarlo.',
    'captcha.error.falta_token'  => 'Confirma que no eres un robot. Si no ves la casilla, recarga la página.',
    'captcha.error.no_verificado'=> 'No se pudo verificar que seas una persona. Inténtalo otra vez.',
    'captcha.fallo_carga'        => 'No se pudo cargar la comprobación de seguridad. Recarga la página; si vuelve a pasar, avísanos y lo revisamos.',
    'captcha.trampa_label'       => 'No rellenes esto',

    // ---- Contacto general del sitio (contacto.php) ----
    'contacto.pagina.titulo' => 'Contacta a Omdara',
    'contacto.pagina.meta'   => 'Escríbenos: dudas, ayuda, problemas con una actividad o cualquier cosa que necesites consultarnos.',

    'contacto.error.campo_obligatorio' => 'Este campo es obligatorio.',
    'contacto.error.correo_invalido'   => 'Introduce un correo electrónico válido.',
    'contacto.error.mensaje_corto'     => 'Cuéntanos un poco más: con %d caracteres no hay mucho a lo que responder.',
    'contacto.error.repetido'          => 'Ya nos escribiste hace un momento. Danos tiempo para responder antes de volver a escribir.',

    'contacto.enviado.h1'    => 'Hemos recibido tu mensaje.',
    'contacto.enviado.sub'   => 'Gracias por contactarnos. Te responderemos a la brevedad.',
    'contacto.enviado.aviso' => 'Te responderemos al correo que nos dejaste. Si no tienes noticias en unos días, revisa tu carpeta de spam.',

    'contacto.form.sub'            => '¿Tienes alguna pregunta, necesitas ayuda o quieres reportar un problema? Completa el formulario y nos pondremos en contacto contigo.',
    'contacto.correo_directo'      => 'Si lo prefieres, escríbenos directo a',
    'contacto.form.revisa_campos'  => 'Revisa los campos marcados.',

    'contacto.campo.nombre' => 'Nombre',
    'contacto.campo.nombre_placeholder' => 'Tu nombre',
    'contacto.campo.correo' => 'Correo electrónico',
    'contacto.campo.correo_placeholder' => 'tu@email.com',
    'contacto.campo.correo_ayuda' => 'Aquí te vamos a responder.',
    'contacto.campo.motivo' => 'Motivo del contacto',
    'contacto.campo.motivo_placeholder' => 'Elige uno…',
    'contacto.campo.actividad' => '¿Qué actividad está relacionada?',
    'contacto.campo.actividad_placeholder' => 'Nombre de la actividad',
    'contacto.campo.actividad_ayuda' => 'Indica el nombre de la actividad sobre la que nos escribes.',
    'contacto.campo.mensaje' => 'Mensaje',
    'contacto.campo.mensaje_placeholder' => 'Cuéntanos cómo podemos ayudarte...',
    'contacto.enviar_btn' => 'Enviar mensaje',
    'contacto.enviando'   => 'Enviando…',
    'contacto.pie' => '¿Es sobre una actividad publicada y quieres hablar con quien la organiza? Ve a su ficha y usa «Contactar al organizador»: llega directo a esa persona y te responde antes.',

    'contacto.motivo.general'     => 'Pregunta general',
    'contacto.motivo.actividad'   => 'Problema con una actividad',
    'contacto.motivo.cuenta'      => 'Problema con mi cuenta',
    'contacto.motivo.organizador' => 'Soy organizador',
    'contacto.motivo.reporte'     => 'Reportar contenido',
    'contacto.motivo.otro'        => 'Otro',

    // ---- Reportar actividad (reportar.php) ----
    'reportar.no_encontrada.texto' => 'Puede que ya se haya retirado.',
    'reportar.error.motivo_falta'  => 'Ayúdanos a mantener una comunidad segura. Selecciona el motivo por el que deseas reportar esta actividad.',
    'reportar.error.repetido'      => 'Ya reportaste esta actividad. La estamos revisando.',

    'reportar.pagina.titulo' => 'Reportar actividad',
    'reportar.enviado.h1'    => 'Gracias por tu reporte',
    'reportar.enviado.sub'   => 'Lo revisaremos lo antes posible.',
    'reportar.enviado.aviso' => 'La actividad sigue publicada mientras tanto. No la retiramos por un aviso automático: lo revisa una persona.',
    'reportar.volver_actividad' => '← Volver a la actividad',
    'reportar.enviado.volver'   => 'Volver a la actividad',

    'reportar.form.pregunta' => '¿Qué le pasa a esta actividad?',
    'reportar.form.comentario' => 'Cuéntanos más',
    'reportar.form.comentario_placeholder' => 'Qué viste exactamente. Cuanto más concreto, más rápido se resuelve.',
    'reportar.enviar_btn' => 'Enviar reporte',
    'reportar.pie' => 'Tu aviso no oculta la actividad. Solo la pone en la lista de lo que hay que revisar.',

    'reportar.motivo.inapropiado'    => 'Contenido ofensivo o inapropiado',
    'reportar.motivo.enganoso'       => 'Información falsa o engañosa',
    'reportar.motivo.spam'           => 'Spam o publicidad',
    'reportar.motivo.no_es_wellness' => 'No es una actividad de bienestar',
    'reportar.motivo.duplicado'      => 'Está publicado dos veces',
    'reportar.motivo.otro'           => 'Otro motivo',

    // ---- Entrar / crear cuenta (login.php) ----
    'login.error.google'    => 'No se pudo completar el acceso con Google.',
    'login.error.state'     => 'La petición no se pudo verificar. Inténtalo otra vez.',
    'login.error.cancelado' => 'Cancelaste el acceso con Google.',
    'login.error.generico'  => 'Algo salió mal. Inténtalo otra vez.',

    'login.pagina.titulo' => 'Entrar',
    'login.h1'  => 'Entrar o crear cuenta',
    'login.sub' => 'Sin contraseñas: te mandamos un código al correo. Si es tu primera vez, la cuenta se crea sola.',
    'login.google_btn' => 'Continuar con Google',
    'login.separador'  => 'o con tu correo',
    'login.campo.correo' => 'Correo',
    'login.campo.correo_placeholder' => 'tucorreo@ejemplo.com',
    'login.continuar_btn' => 'Continuar',

    // ---- Código de acceso (codigo.php) ----
    'codigo.error.sesion_caducada' => 'La sesión caducó. Vuelve a empezar.',
    'codigo.pagina.titulo' => 'Revisa tu correo',
    'codigo.sub' => 'Te enviamos un código de un solo uso. Caduca en %d minutos.',
    'codigo.editar' => 'Editar',
    'codigo.campo.codigo' => 'Código de un solo uso',
    'codigo.enviar_btn'   => 'Enviar',
    'codigo.reenviar_btn' => 'Reenviar código',
    'codigo.pie' => '¿No llega? Mira en la carpeta de spam antes de pedir otro.',

    // ---- Correo del código de acceso (includes/correo.php) ----
    // La página que lo pide (login.php, codigo.php) ya sabe su propio idioma
    // por tener ruta /en propia; este correo va a cualquier visitante, no a
    // un organizador o admin fijo, así que sí sigue el idioma de quien lo pidió
    // —al revés que motivosContacto() y los demás avisos internos—.
    'correo.codigo.asunto' => '{codigo} es tu código para entrar en {marca}',
    'correo.codigo.cuerpo' => "Tu código para entrar en {marca} es:\n\n    {codigo}\n\nCaduca en {minutos} minutos y sirve una sola vez.\n\nSi no has pedido este código, no hagas nada: sin él nadie entra, y el\ncódigo deja de valer solo. Nadie de {marca} te lo va a pedir por teléfono,\npor WhatsApp ni por correo.\n\n--\n{marca} · Directorio de actividades wellness en México",

    // ---- Correo para confirmar un correo de contacto (migración 24) ----
    // A diferencia del código de acceso, este puede llegarle a alguien que no
    // pidió nada —quien edita la actividad puede escribir cualquier
    // dirección—, así que el cuerpo lo dice de frente.
    'correo.confirmar_contacto.asunto' => '{codigo} es tu código para confirmar el correo de contacto de «{actividad}»',
    'correo.confirmar_contacto.cuerpo' => "Alguien —esperamos que tú— quiere que este correo reciba los mensajes de «Contactar al organizador» de la actividad «{actividad}» en {marca}.\n\nTu código es:\n\n    {codigo}\n\nEscríbelo en la página donde se edita esa actividad. Caduca en {minutos} minutos y sirve una sola vez.\n\nSi no reconoces esto, no hagas nada: sin ese código no se activa nada, y el\ncódigo deja de valer solo.\n\n--\n{marca} · Directorio de actividades wellness en México",

    // ---- Completar registro (completar-registro.php) ----
    'registro.error.acepta_legal' => 'Para crear tu cuenta debes aceptar los Términos y Condiciones y el Aviso de Privacidad.',
    'registro.error.no_creada'    => 'No se pudo crear tu cuenta. Inténtalo otra vez.',
    'registro.pagina.titulo' => 'Completa tu registro',
    'registro.sub_google' => 'Google confirmó tu correo. Falta un paso para crear tu cuenta.',
    'registro.sub_correo' => 'Verificamos tu correo. Falta un paso para crear tu cuenta.',
    'registro.se_creara'  => 'Se creará la cuenta de',
    'registro.crear_btn'  => 'Crear mi cuenta',
    'registro.pie_pregunta' => '¿No eras tú?',
    'registro.pie_cancelar' => 'Cancelar y salir',

    // ---- Casilla legal, compartida (includes/casilla-legal.php) ----
    'legal.acepto_1'   => 'He leído y acepto los',
    'legal.y'          => 'y el',
    'legal.de_omdara'  => 'de OMDARA.',

    // ---- includes/auth.php: código por correo y Google ----
    'auth.correo_invalido'       => 'Ese correo no tiene buena pinta. Revísalo.',
    'auth.espera_minuto'         => 'Acabamos de enviarte uno. Espera un minuto antes de pedir otro.',
    'auth.demasiados_codigos'    => 'Has pedido demasiados códigos. Prueba dentro de un rato o entra con Google.',
    'auth.demasiadas_peticiones' => 'Demasiadas peticiones desde esta conexión. Prueba más tarde.',
    'auth.error_envio'           => 'No pudimos enviar el correo. Inténtalo otra vez o entra con Google.',
    'auth.codigo_enviado'        => 'Te enviamos un código a',
    'auth.codigo_formato'        => 'El código son seis cifras.',
    'auth.codigo_caducado'       => 'Ese código caducó o ya se usó. Pide uno nuevo.',
    'auth.demasiados_intentos'   => 'Demasiados intentos con ese código. Pide uno nuevo.',
    'auth.codigo_incorrecto_quedan' => 'Código incorrecto. Te quedan %d intentos.',
    'auth.codigo_incorrecto_final'  => 'Código incorrecto. Pide uno nuevo.',
    'auth.cuenta_suspendida'     => 'Esta cuenta está suspendida.',
    'auth.google_sin_datos'      => 'Google no devolvió los datos necesarios.',
    'auth.correo_ya_registrado'  => 'Ese correo ya está registrado. Entra pidiendo un código.',

    // ---- Consentimiento de cookies (REQ-00003) ----
    // OMDARA en mayúsculas aquí, a propósito: el cliente pidió (2026-09-02)
    // bajar "OMDARA" a "Omdara" en todo el sitio EXCEPTO en los textos de la
    // sección legal —Términos, Aviso de Privacidad, Política de Cookies y
    // Preguntas Frecuentes—, y este banner es contenido de esa misma familia
    // —el resumen del consentimiento de cookies—, aunque se pinte flotando
    // sobre cualquier página. Ver docs/pendientes.md.
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
