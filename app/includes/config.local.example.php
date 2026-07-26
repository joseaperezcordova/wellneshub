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
 *   · en local:    app/includes/config.local.php  (con los datos de XAMPP)
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
    // Sin barra final. Tiene que coincidir EXACTAMENTE con lo que registres en
    // Google Cloud Console, incluido http/https: si allí pones https y aquí
    // http, Google rechaza el callback con redirect_uri_mismatch.
    //
    //   local:      http://localhost/wellneshub/app
    //   producción: https://wellnesshubmx.jpcorelab.com/app
    'url_base' => 'http://localhost/wellneshub/app',

    // ---- Google OAuth -----------------------------------------------------
    // Se sacan de Google Cloud Console → APIs y servicios → Credenciales →
    // Crear credenciales → ID de cliente de OAuth → Aplicación web.
    // Ver el README para los pasos completos.
    'google' => [
        'client_id'     => '',
        'client_secret' => '',
    ],

];
