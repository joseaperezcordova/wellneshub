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
      <a class="btn-add" style="background:var(--azul); color:var(--tinta-boton);"
         href="<?= URL_BASE ?>/evento-nuevo.php">+ Nueva actividad</a>
    </div>

    <?php if (!$misEventos): ?>
      <div class="evergreen-note">
        Todavía no has creado ninguna actividad. Con «+ Nueva actividad» escribes
        la ficha, la ves como la verá la gente y decides si publicarla.
      </div>
    <?php else: ?>
      <table class="admtable" style="background:var(--paper); color:var(--ink);">
        <thead><tr><th>Actividad</th><th>Fecha</th><th>Actualizado</th><th>Estado</th><th>Acción</th></tr></thead>
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
                <?php if ($me['situacion'] === 'publicado' && fechaActualizadaReciente($me['fecha_cambiada_en'] ?? null)): ?>
                  <?php /* Req. 17092026 punto 3: el MVP del dashboard distingue
                           PUBLICADA de FECHA ACTUALIZADA en esta misma columna
                           —es la ventana de EVENTO_FECHA_ACTUALIZADA_DIAS días,
                           la misma que ya usan la ficha y las tarjetas. */ ?>
                  <span class="badge on" style="color:var(--jungle); background:rgba(22,22,22,0.08);"><?= et('tarjeta.fecha_actualizada') ?></span>
                <?php elseif ($me['situacion'] === 'publicado'): ?>
                  <span class="badge on" style="color:var(--jungle); background:rgba(22,22,22,0.08);">Publicada</span>
                <?php elseif ($me['situacion'] === 'borrador'): ?>
                  <span class="badge-pending">Borrador · sin publicar</span>
                <?php elseif ($me['situacion'] === 'cancelado'): ?>
                  <span class="badge off">Cancelada</span>
                <?php else: ?>
                  <span class="badge off">Oculta</span>
                <?php endif; ?>
              </td>
              <td>
                <?php /* «Gestionar» (Req. 17092026 punto 3): <details> otra vez
                         —mismo patrón sin JavaScript que el menú de «Mi cuenta»
                         en includes/layout.php—, con las acciones reales que ya
                         existen en la ficha (evento.php) en vez de duplicar su
                         lógica de permisos aquí. Las de cancelar/retirar/reactivar
                         postean directo contra la ficha, así que el resultado es
                         el mismo que pulsar el botón ahí. */ ?>
                <details class="gestionar">
                  <summary class="actionbtn" style="color:var(--ink); border-color:var(--line);">Gestionar</summary>
                  <div class="gestionar-menu">
                    <a class="actionbtn" style="color:var(--ink); border-color:var(--line);" href="<?= e(urlEvento($me)) ?>">Información</a>
                    <?php if (puedeEditarEvento($me, $u)): ?>
                      <a class="actionbtn" style="color:var(--ink); border-color:var(--line);" href="<?= e(urlEditarEvento($me)) ?>">Editar actividad</a>
                      <a class="actionbtn" style="color:var(--ink); border-color:var(--line);" href="<?= e(urlEditarEvento($me)) ?>#fecha_unica">Cambiar fecha y hora</a>
                    <?php endif; ?>
                    <?php if ($me['situacion'] === 'publicado'): ?>
                      <form method="post" action="<?= e(urlEvento($me)) ?>" onsubmit="
                        var info = prompt(<?= json_encode(t('ficha.prompt_info_cancelacion')) ?>, '');
                        if (info === null) return false;
                        this.elements['info_cancelacion'].value = info;
                        return confirm(<?= json_encode(sprintf(t('ficha.confirmar_cancelar'), $me['titulo'])) ?>);
                      ">
                        <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
                        <input type="hidden" name="info_cancelacion" value="">
                        <button class="actionbtn" style="color:var(--ink); border-color:var(--line);" type="submit" name="cancelar" value="1">Cancelar actividad</button>
                      </form>
                    <?php endif; ?>
                    <?php if ($me['situacion'] === 'cancelado'): ?>
                      <form method="post" action="<?= e(urlEvento($me)) ?>">
                        <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
                        <button class="actionbtn" style="color:var(--ink); border-color:var(--line);" type="submit" name="publicar" value="1">Reactivar</button>
                      </form>
                    <?php endif; ?>
                    <?php if (puedeRetirarEvento($me, $u)): ?>
                      <form method="post" action="<?= e(urlEvento($me)) ?>"
                            onsubmit="return confirm(<?= json_encode(sprintf(t('ficha.confirmar_retirar'), $me['titulo'])) ?>);">
                        <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
                        <button class="actionbtn" style="color:var(--ink); border-color:var(--line);" type="submit" name="retirar" value="1">Retirar</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="evergreen-note" style="margin-top:18px;">
        Puedes editar una actividad publicada cuando quieras. Retirarla —deja de verse, pero no se
        borra— se puede desde su ficha, y solo dentro de las <?= EVENTO_MARGEN_RETIRO_H ?> horas
        siguientes a publicarla; pasado ese plazo, pídeselo al administrador.
      </div>
    <?php endif; ?>

    <?php /*
     * Requerimiento del cliente, «Configuración y uso de correos de Omdara»
     * (2026-09-02): Dashboard del organizador → Ayuda / Soporte. Este es el
     * panel de quien publica, así que es donde se topa con problemas para
     * publicar o editar —justo lo que soporte@ está pensado para atender—.
     * Sin buzón configurado no se pinta nada, mismo patrón que el resto.
     */ ?>
    <?php if (correoSoporte() !== ''): ?>
    <div class="evergreen-note" style="margin-top:18px;">
      ¿Problemas para publicar, editar o algo no funciona? Escríbenos a
      <a href="mailto:<?= e(correoSoporte()) ?>"><?= e(correoSoporte()) ?></a>.
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
