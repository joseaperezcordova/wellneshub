<?php
/**
 * Portada de la aplicación.
 *
 * Por ahora es un banco de pruebas del acceso: sirve para comprobar que la
 * sesión funciona de punta a punta. El diseño de la v6 del prototipo se irá
 * trayendo aquí sección por sección.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$u = usuarioActual();
$titulo = 'Inicio';
require __DIR__ . '/includes/layout.php';
?>

<div class="auth-caja">
  <?php if ($u): ?>
    <h1>Hola, <?= e(explode(' ', $u['nombre'])[0]) ?></h1>
    <p class="sub">La sesión está activa.</p>

    <div class="aviso aviso-ok">
      Entraste como <strong><?= e($u['email']) ?></strong> · rol <strong><?= e($u['rol']) ?></strong>
    </div>

    <p style="font-size:13.5px; opacity:.75;">
      Arriba a la derecha está tu avatar: ahí se cierra la sesión.
    </p>
  <?php else: ?>
    <h1>Wellneshub</h1>
    <p class="sub">Directorio de eventos wellness en México.</p>
    <p style="font-size:13.5px; opacity:.75;">
      No hay sesión iniciada. Usa el botón <strong>Entrar</strong> de arriba a la derecha.
    </p>
  <?php endif; ?>
</div>

<?php pie(); ?>
