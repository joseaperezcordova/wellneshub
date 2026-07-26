<?php
/**
 * Cabecera y pie compartidos. La cabecera lleva el acceso a la cuenta arriba a
 * la derecha: un icono de persona si no hay sesión, y el avatar con un menú si
 * la hay.
 *
 * Uso:
 *   $titulo = 'Entrar';
 *   require __DIR__ . '/includes/layout.php';   // abre el documento
 *   ... contenido ...
 *   pie();                                       // lo cierra
 */

declare(strict_types=1);

$u = usuarioActual();
?><!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo ?? 'Wellneshub') ?> · Rueda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(assetUrl('assets/css/app.css')) ?>">
</head>
<body>

<div class="topbar">
  <div class="topbar-inner">
    <a class="logo" href="<?= URL_BASE ?>/">
      <span class="logo-mark"></span>
      <span class="logo-text">Rueda<small>Directorio wellness MX</small></span>
    </a>

    <nav class="mainnav">
      <a href="<?= URL_BASE ?>/">Inicio</a>
      <a href="<?= URL_BASE ?>/">Buscar eventos</a>
      <a href="<?= URL_BASE ?>/">Blog</a>
    </nav>

    <div class="topbar-right">
      <!-- Mismo botón que en la portada, y por lo mismo: lo ve todo el mundo.
           Quien no tenga sesión pasa por el login y vuelve al formulario solo.
           Quien guarda la puerta es exigirSesion() en evento-nuevo.php. -->
      <a class="btn-publicar" href="<?= URL_BASE ?>/evento-nuevo.php">
        Publicar<span class="btn-publicar-extra"> evento</span>
      </a>

      <?php if ($u): ?>
        <details class="cuenta">
          <summary aria-label="Mi cuenta">
            <?php if (!empty($u['avatar_url'])): ?>
              <img class="avatar" src="<?= e($u['avatar_url']) ?>" alt="" referrerpolicy="no-referrer">
            <?php else: ?>
              <span class="avatar avatar-letra"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
            <?php endif; ?>
          </summary>
          <div class="cuenta-menu">
            <div class="cuenta-quien">
              <strong><?= e($u['nombre']) ?></strong>
              <span><?= e($u['email']) ?></span>
            </div>
            <!-- Estas dos vistas viven dentro de la portada y se conmutan con
                 JavaScript, así que desde aquí se llega por el ancla: index.php
                 la lee al cargar y abre la vista que toque. Sin esto, quien
                 estaba en moderación o en una ficha no tenía manera de volver
                 al panel. -->
            <a href="<?= URL_BASE ?>/#panel-organizador">Mis eventos</a>
            <?php if ($u['rol'] === 'admin'): ?>
              <a href="<?= URL_BASE ?>/#admin">Panel admin</a>
              <?php $pend = contarReportesPendientes(); ?>
              <a href="<?= URL_BASE ?>/moderacion.php">
                Moderación<?php if ($pend > 0): ?> <span class="pendientes"><?= $pend ?></span><?php endif; ?>
              </a>
            <?php endif; ?>
            <a href="<?= URL_BASE ?>/logout.php">Cerrar sesión</a>
          </div>
        </details>
      <?php else: ?>
        <!-- El icono es un enlace, no un botón con JavaScript: así funciona con
             el clic central, con "abrir en pestaña nueva" y sin JS. -->
        <a class="btn-cuenta" href="<?= URL_BASE ?>/login.php" aria-label="Entrar a mi cuenta">
          <svg viewBox="0 0 24 24" width="19" height="19" aria-hidden="true"
               fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
            <circle cx="12" cy="8.2" r="3.8"/>
            <path d="M4.8 20c.6-3.7 3.6-5.9 7.2-5.9s6.6 2.2 7.2 5.9"/>
          </svg>
          <span>Entrar</span>
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<main class="contenido">
<?php
/** Cierra el documento. */
function pie(): void
{
    ?>
</main>

<footer>
  <div class="foot-bottom">© <?= date('Y') ?> Rueda — Directorio de eventos wellness MX.</div>
</footer>

</body>
</html>
    <?php
}
