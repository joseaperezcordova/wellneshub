<?php
/**
 * Mis actividades — el panel de quien publica.
 *
 * Era la vista «panel-organizador» dentro de la portada, a la que se llegaba
 * por /#panel-organizador porque no tenía dirección propia. Ahora la tiene.
 *
 * Antes traía su propia pantalla de «entra para publicar» cuando no había
 * sesión. Ya no hace falta: exigirSesion() manda al login de verdad y devuelve
 * aquí al terminar, que es lo que se espera de un enlace protegido.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/eventos.php';

$u          = exigirSesion();
$misEventos = eventosDeUsuario((int) $u['id']);

$titulo     = 'Mis actividades';
$anchoLibre = true;

require __DIR__ . '/includes/layout.php';
?>

<div class="wrap">
  <div class="op-shell">
    <div class="op-header">
      <div class="who">
        <?php if (!empty($u['avatar_url'])): ?>
          <img class="avatar" style="border-radius:50%; object-fit:cover;"
               src="<?= e($u['avatar_url']) ?>" alt="" referrerpolicy="no-referrer">
        <?php else: ?>
          <div class="avatar" style="border-radius:50%;"></div>
        <?php endif; ?>
        <div>
          <div class="eyebrow">Sesión de organizador</div>
          <h1 style="font-size:22px;"><?= e($u['nombre']) ?></h1>
        </div>
      </div>
      <a class="btn-add" style="background:var(--terracota); color:var(--tinta-boton);"
         href="<?= URL_BASE ?>/evento-nuevo.php">+ Nueva actividad</a>
    </div>

    <?php if (!$misEventos): ?>
      <div class="evergreen-note">
        Todavía no has creado ninguna actividad. Con «+ Nueva actividad» escribes
        la ficha, la ves como la verá la gente y decides si publicarla.
      </div>
    <?php else: ?>
      <table class="admtable" style="background:var(--paper); color:var(--ink);">
        <thead><tr><th>Título</th><th>Fecha</th><th>Actualizado</th><th>Situación</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($misEventos as $me): $p = fechaPartes($me['fecha_inicio']); $pu = fechaPartes($me['actualizado_en']); ?>
            <tr>
              <td><?= e($me['titulo']) ?></td>
              <td><?= e($p['d'] . ' ' . $p['m'] . ' ' . date('Y', strtotime($me['fecha_inicio']))) ?></td>
              <?php /* REQ-000-XX: con la edición sin plazo, esto es lo que le
                       dice al organizador si una ficha sigue vigente o lleva
                       meses sin tocarse —antes esa pregunta no hacía falta,
                       porque pasadas 24 horas ya no se podía tocar—. */ ?>
              <td><?= e($pu['d'] . ' ' . $pu['m'] . ', ' . $pu['hora']) ?></td>
              <td>
                <?php if ($me['situacion'] === 'publicado'): ?>
                  <span class="badge on" style="color:var(--jungle); background:rgba(47,78,93,0.12);">Publicada</span>
                <?php elseif ($me['situacion'] === 'borrador'): ?>
                  <span class="badge-pending">Borrador · sin publicar</span>
                <?php else: ?>
                  <span class="badge off">Oculta</span>
                <?php endif; ?>
              </td>
              <td>
                <a class="actionbtn" style="color:var(--ink); border-color:var(--line);"
                   href="<?= e(urlEvento($me)) ?>">Ver</a>
                <?php if (puedeEditarEvento($me, $u)): ?>
                  <a class="actionbtn" style="color:var(--ink); border-color:var(--line);"
                     href="<?= URL_BASE ?>/evento-editar.php?id=<?= (int) $me['id'] ?>">Editar</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="evergreen-note" style="margin-top:18px;">
        Puedes editar una actividad publicada cuando quieras. Eliminarla se puede desde su ficha,
        y solo dentro de las <?= EVENTO_MARGEN_ELIMINACION_H ?> horas siguientes a publicarla;
        pasado ese plazo, pídeselo al administrador.
      </div>
    <?php endif; ?>

    <?php /* La pestaña «Editar perfil» que había aquí no se movió: era un
             formulario del prototipo con los datos de otra persona escritos a
             mano —«Raíz Colectivo»— que no guardaba nada y que salía igual
             para cualquiera que entrara. No hay tabla de perfil todavía. */ ?>
    <div class="scope-banner">
      <b>Fuera de alcance del MVP</b> — visible para orientar, no funcional todavía.
      <div class="scope-list">
        <span>Perfil público de organizador</span>·<span>Venta de boletos</span>·<span>Pagos en línea</span>·<span>Chat organizador-usuario</span>·<span>Reseñas</span>·<span>Notificaciones push</span>·<span>Programa de afiliados</span>·<span>Integraciones (Stripe, Eventbrite, Google Calendar)</span>·<span>Recomendaciones con IA</span>
      </div>
    </div>
  </div>
</div>

<?php pie(); ?>
