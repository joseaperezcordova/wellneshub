<?php
/**
 * PLANTILLA. Copia este archivo como config.local.php y rellénalo.
 *
 * config.local.php NO se sube al repositorio ni se sincroniza por FTP: está
 * excluido en .gitignore y en el workflow de deploy. Eso es a propósito — las
 * credenciales de producción no viven en git, y si el deploy lo sincronizara,
 * tu copia local pisaría la del servidor en cada push.
 *
 * Por eso hay que crearlo DOS veces, una en cada sitio:
 *   · en local:    includes/config.local.php  (con los datos de XAMPP)
 *   · en el server: subirlo a mano por FTP una sola vez
 */

return [

    // ---- Base de datos ----------------------------------------------------
    // En XAMPP por defecto: host 127.0.0.1, usuario root, contraseña vacía.
    // En el hosting, cPanel antepone el nombre de la cuenta tanto a la base
    // como al usuario (algo como "jpcore_wellnes").
    'db' => [
        'host'   => '127.0.0.1',
        'nombre' => 'wellneshub',
        'usuario'=> 'root',
        'password' => '',
        'charset'=> 'utf8mb4',
    ],

    // ---- URL base ---------------------------------------------------------
    // DÉJALO VACÍO. La aplicación la deduce de la propia petición, y así este
    // archivo sirve igual en local y en el servidor.
    //
    // Ponerlo a mano fue la causa de un fallo tonto: al copiar este archivo al
    // servidor se quedó apuntando a localhost, y el sitio publicado generaba
    // todos sus enlaces —incluido el del CSS— hacia la máquina del visitante.
    //
    // Solo hace falta rellenarlo si el servidor está detrás de un proxy que no
    // manda X-Forwarded-Proto y hace falta forzar https. Sin barra final, y
    // entonces tiene que coincidir EXACTAMENTE con lo registrado en Google
    // Cloud Console o el login con Google falla con redirect_uri_mismatch.
    'url_base' => '',

    // ---- Correo saliente --------------------------------------------------
    // Desde aquí salen los códigos de acceso. RELLÉNALO: el valor por defecto
    // —no-responder@ más el dominio de la petición— no sirve si el sitio vive
    // en un subdominio, por lo que se explica abajo.
    //
    // 'remitente' tiene que cumplir DOS cosas, y la segunda es la que se olvida:
    //
    //   1. Ser una dirección de tu propio dominio. Gmail y Outlook comprueban
    //      que quien firma el correo tenga permiso sobre el dominio del
    //      remitente; mandar desde un @gmail.com a través del servidor del
    //      hosting es el patrón de la suplantación, y acaba en spam.
    //
    //   2. Ser un buzón que EXISTA y pueda recibir correo. Los filtros de
    //      salida hacen "callout verification": antes de aceptar tu mensaje se
    //      conectan al servidor de correo del remitente para comprobar que esa
    //      dirección es real. Si el dominio no tiene registro MX, o el buzón no
    //      está creado, el correo se rechaza sin salir siquiera:
    //
    //          550 Verification failed for <no-responder@…>
    //          550 Invalid sender
    //
    //      Aquí pasó exactamente eso. El subdominio wellnesshubmx.jpcorelab.com
    //      no tiene MX, así que ninguna dirección suya supera el callout. Se
    //      manda desde el dominio principal, que sí lo tiene, y con el buzón
    //      creado en cPanel → Email Accounts.
    //
    // Ojo si alguna vez falla: el filtro CACHEA el resultado negativo. Después
    // de crear un buzón que ya había fallado, sigue rechazando un buen rato.
    // Por eso conviene probar con una dirección nueva en vez de insistir con la
    // que ya está en la lista negra.
    'correo' => [
        'remitente' => 'no-responder@tudominio.com',
        'nombre'    => 'OMDARA',
    ],

    // ---- Captcha del formulario de reportes -------------------------------
    // OPCIONAL. Sin claves, el formulario sigue defendido por el campo trampa y
    // el reloj, que no dependen de nadie. Estas claves son una capa encima.
    //
    // Turnstile (recomendado): dash.cloudflare.com → Turnstile → Add site.
    // Es gratis, no rastrea a quien lo usa y no obliga a poner un aviso de
    // cookies de Google en un formulario donde alguien solo quiere denunciar.
    //
    // reCAPTCHA se deja como alternativa por si ya lo tienes en otro sitio. Si
    // rellenas los dos, gana Turnstile.
    'captcha' => [
        'turnstile' => ['site_key' => '', 'secret' => ''],
        'recaptcha' => ['site_key' => '', 'secret' => ''],
    ],

    // ---- Google OAuth -----------------------------------------------------
    // Se sacan de Google Cloud Console → APIs y servicios → Credenciales →
    // Crear credenciales → ID de cliente de OAuth → Aplicación web.
    // Ver el README para los pasos completos.
    'google' => [
        'client_id'     => '',
        'client_secret' => '',

        // Déjalo en false mientras el hosting mantenga la regla de mod_security
        // que devuelve 403 ante cualquier URL con la cadena ".profile": Google
        // la incluye siempre en el callback si se pide el permiso 'profile', y
        // el login se vuelve imposible. Ver googleScope() en google.php.
        //
        // Cuando soporte desactive esa regla, ponlo en true y se recuperan el
        // nombre y la foto de la cuenta de Google.
        'pedir_perfil'  => false,
    ],

    // ---- Secciones ocultas ------------------------------------------------
    // OPCIONAL, y normalmente no se toca. Blog y Newsletter salen ocultos en el
    // MVP (REQ-00004); la lista manda desde includes/secciones.php, que viaja en
    // git y por tanto vale igual en pruebas y en producción.
    //
    // Esto de aquí solo sirve para ENSEÑAR en un entorno algo que sigue oculto
    // en el otro —revisar el blog en pruebas, por ejemplo—. Publicar de verdad
    // se decide en el código, donde queda un commit que revisar:
    //
    //     'secciones' => ['blog' => true],

    // ---- Analítica -----------------------------------------------------
    // Las cuatro llaves de abajo son independientes: cada una enciende su
    // propia herramienta si tiene algo puesto, y no hace nada si está vacía.
    // includes/layout.php no imprime NINGÚN script de analítica en local
    // —ni con los IDs puestos—, para que probar el sitio en la máquina de
    // quien programa no ensucie los datos reales.
    //
    // Los IDs no están atados al dominio: siguen funcionando igual el día
    // que el sitio se mude del subdominio de pruebas al dominio final, sin
    // tocar nada aquí. Lo que sí toca rehacer en ese momento es la
    // verificación de Search Console (es por dominio) y, si se usa
    // Conversions API de Meta, la verificación de dominio del píxel —esas
    // dos son aparte y no viven en este archivo.
    'analytics' => [
        // analytics.google.com → Administrar → Flujos de datos → el flujo
        // web → "ID de medición" (algo como "G-XXXXXXXXXX").
        'ga4_id' => '',

        // clarity.microsoft.com → el proyecto → Configuración → "ID del
        // proyecto de Clarity" (letras y números, sin prefijo).
        'clarity_id' => '',

        // business.facebook.com → Administrador de eventos → el píxel →
        // "ID del píxel" (solo números).
        'meta_pixel_id' => '',

        // search.google.com/search-console → Agregar propiedad → tipo
        // "Prefijo de URL" → método "Etiqueta HTML" → el valor del atributo
        // content del <meta name="google-site-verification"> que te den
        // (NO la etiqueta completa, solo el valor de content).
        //
        // Alternativa: verificar por DNS (registro TXT) en vez de con esto.
        // Esa vía cubre el dominio entero de una sola vez —con y sin
        // "www."—, que es justo el problema que ya tuvimos con el login de
        // Google. Si tienes acceso al DNS del subdominio, es la mejor
        // opción; si no, esta etiqueta funciona igual de bien.
        'search_console_verificacion' => '',
    ],

];
