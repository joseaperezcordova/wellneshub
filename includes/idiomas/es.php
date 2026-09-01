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
    'evento.form.organizador_nombre_label' => 'Nombre del organizador',
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
    'evento.form.datos_organizador'  => 'Datos del organizador',
    'evento.form.usar_guardado'      => 'Usar la información guardada',
    'evento.form.nombre_label'       => 'Nombre',
    'evento.form.organizador_nombre_placeholder' => 'Yoga Baja',
    'evento.form.organizador_nombre_ayuda' => 'Es el nombre que aparece como organizador en tus actividades.',
    'evento.form.editar_btn'         => 'Editar',
    'evento.form.telefono_placeholder' => '+52 612 123 4567',
    'evento.form.instagram_placeholder' => '@tucuenta',
    'evento.form.sitio_web_placeholder' => 'https://tusitio.com',
    'evento.form.sitio_web_org_ayuda'   => 'El tuyo, no el de esta actividad — ese va aquí abajo.',
    'evento.form.contacto_nota_1'    => 'Esta información se guardará para facilitar tus próximas publicaciones. Puedes cambiarla cuando quieras desde',
    'evento.form.contacto_nota_cuenta' => 'Mi cuenta',
    'evento.form.sitio_web_actividad_label' => 'Sitio web o enlace de la actividad',
    'evento.form.sitio_web_actividad_ayuda' => 'Comparte un sitio web o perfil de redes sociales para que los interesados conozcan más sobre esta actividad —no el tuyo, ese va arriba—.',

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
    'evento.error.falta_organizador'=> 'Falta el nombre del organizador.',

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
    'evento.guia.traduccion_texto1' => 'La traducción de OMDARA no modifica ni traduce automáticamente los textos que tú ingreses en la descripción de tu actividad. La descripción, instrucciones, condiciones y demás contenido personalizado permanecerán tal como los hayas escrito.',
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

    'ficha.btn.comprar_boletos' => 'Comprar boletos',
    'ficha.btn.reservar'        => 'Reservar mi lugar',
    'ficha.btn.contactar'       => 'Contactar al organizador',

    'ficha.mapa_titulo_prefijo' => 'Mapa con la ubicación de',
    'ficha.como_llegar'         => 'Cómo llegar →',
    'ficha.reportar'            => 'Reportar esta actividad',

    // Mensaje de la sesión que evento-editar.php deja para cuando se vuelve
    // a la ficha tras guardar cambios.
    'evento.editar.cambios_guardados' => 'Cambios guardados.',

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
