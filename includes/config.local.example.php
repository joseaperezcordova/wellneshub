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
    // Desde aquí salen los códigos de acceso.
    //
    // 'remitente' TIENE que ser una dirección de tu propio dominio. Gmail y
    // Outlook comprueban que quien firma el correo tenga permiso sobre el
    // dominio del remitente; mandar desde un @gmail.com a través del servidor
    // del hosting es exactamente el patrón de la suplantación, y acaba en spam.
    //
    // Déjalo vacío y se usa no-responder@ + el dominio de la petición, que es
    // lo correcto en la mayoría de los casos. No hace falta que ese buzón
    // exista para enviar, pero créalo en cPanel si quieres leer las respuestas
    // y los rebotes.
    'correo' => [
        'remitente' => '',
        'nombre'    => 'Rueda',
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

];
