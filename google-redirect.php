<?php
/**
 * Manda al usuario a Google. Nada más.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/google.php';

/*
 * Esta dirección no pasa por router.php —.htaccess la sirve directa, es un
 * archivo real—, así que idiomaActual() no tiene de dónde resolver el idioma
 * de quien venía en login.php. Viaja como parámetro y se guarda en sesión
 * para que google-callback.php, a la vuelta, sepa a qué idioma mandar de
 * regreso: incluso ese archivo, con dirección fija por Google Console, no
 * tiene otra forma de saberlo.
 */
$idiomaLogin = (string) ($_GET['idioma'] ?? '');
if (!in_array($idiomaLogin, idiomasDisponibles(), true)) $idiomaLogin = IDIOMA_POR_DEFECTO;

if (haySesion())          redirigir(url('inicio', $idiomaLogin));
if (!googleConfigurado()) redirigir(url('login', $idiomaLogin) . '?error=google');

$_SESSION['idioma_login'] = $idiomaLogin;

header('Location: ' . googleUrlAutorizacion());
exit;
