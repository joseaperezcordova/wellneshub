<?php
/**
 * Manda al usuario a Google. Nada más.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/google.php';

if (haySesion())          redirigir('/');
if (!googleConfigurado()) redirigir('/login.php?error=google');

header('Location: ' . googleUrlAutorizacion());
exit;
