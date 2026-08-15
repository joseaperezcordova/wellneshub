<?php
/**
 * El banner y el panel de preferencias de cookies (REQ-00003).
 *
 * Lo pinta pie(), en todas las páginas. Sale con hidden puesto y lo enseña
 * assets/js/consentimiento.js: si no hay JavaScript no se carga ninguna
 * herramienta de analítica, así que tampoco hay nada que consentir y un aviso
 * que no controla nada solo estorbaría.
 *
 * EL TEXTO VIVE EN LOS CATÁLOGOS, NO AQUÍ
 *
 * Es lo primero que ve quien entra, y en el idioma equivocado es lo peor que
 * puede verse primero. Sale de t() como el resto de la interfaz.
 *
 * LAS CASILLAS SON CASILLAS DE VERDAD
 *
 * Un <input type="checkbox"> con estilos encima, y no un <div> con clases y
 * JavaScript. Así el teclado, los lectores de pantalla y el "activado/
 * desactivado" que anuncian funcionan sin que haya que reimplementarlos —que
 * es donde estos paneles suelen romperse—. El value de cada casilla es el
 * nombre de la categoría: lo lee el script tal cual.
 */

declare(strict_types=1);

if (!hayQueConsentir()) return;
?>

<div class="cookies-banner" id="cookies-banner" role="dialog" aria-modal="false"
     aria-labelledby="cookies-banner-titulo" aria-describedby="cookies-banner-texto" hidden>
  <div class="cookies-banner-texto">
    <h2 id="cookies-banner-titulo"><?= et('cookies.banner.titulo') ?></h2>
    <p id="cookies-banner-texto"><?= et('cookies.banner.texto') ?></p>
  </div>
  <div class="cookies-banner-botones">
    <button type="button" class="cookies-btn cookies-btn-principal" data-cookies="todas"><?= et('cookies.aceptar') ?></button>
    <button type="button" class="cookies-btn" data-cookies="ninguna"><?= et('cookies.rechazar') ?></button>
    <button type="button" class="cookies-btn cookies-btn-plano" data-cookies="configurar"><?= et('cookies.configurar') ?></button>
  </div>
</div>

<div class="cookies-fondo" id="cookies-panel" hidden>
  <div class="cookies-panel" role="dialog" aria-modal="true" aria-labelledby="cookies-panel-titulo">
    <div class="cookies-panel-cab">
      <h2 id="cookies-panel-titulo"><?= et('cookies.panel.titulo') ?></h2>
      <button type="button" class="cookies-cerrar" data-cookies="cerrar"
              aria-label="<?= et('cookies.cerrar') ?>">&times;</button>
    </div>

    <div class="cookies-grupo">
      <div class="cookies-grupo-cab">
        <h3 id="cookies-necesarias"><?= et('cookies.necesarias.titulo') ?></h3>
        <span class="cookies-siempre"><?= et('cookies.necesarias.estado') ?></span>
      </div>
      <p><?= et('cookies.necesarias.texto') ?></p>
    </div>

    <div class="cookies-grupo">
      <div class="cookies-grupo-cab">
        <h3 id="cookies-analiticas"><?= et('cookies.analiticas.titulo') ?></h3>
        <?php /* aria-labelledby y no el texto de al lado: el nombre que anuncia
                 el lector de pantalla tiene que ser «Analíticas», no
                 «Activadas Desactivadas», que es lo que se leería si la etiqueta
                 fuera el rótulo del interruptor. */ ?>
        <label class="cookies-switch">
          <input type="checkbox" value="analiticas" aria-labelledby="cookies-analiticas">
          <span class="cookies-pista" aria-hidden="true"></span>
          <span class="cookies-estado" aria-hidden="true">
            <span class="cookies-si"><?= et('cookies.activadas') ?></span>
            <span class="cookies-no"><?= et('cookies.desactivadas') ?></span>
          </span>
        </label>
      </div>
      <p><?= et('cookies.analiticas.texto') ?></p>
      <p class="cookies-incluye"><?= et('cookies.incluye') ?> Google Analytics 4, Microsoft Clarity</p>
    </div>

    <div class="cookies-grupo">
      <div class="cookies-grupo-cab">
        <h3 id="cookies-marketing"><?= et('cookies.marketing.titulo') ?></h3>
        <label class="cookies-switch">
          <input type="checkbox" value="marketing" aria-labelledby="cookies-marketing">
          <span class="cookies-pista" aria-hidden="true"></span>
          <span class="cookies-estado" aria-hidden="true">
            <span class="cookies-si"><?= et('cookies.activadas') ?></span>
            <span class="cookies-no"><?= et('cookies.desactivadas') ?></span>
          </span>
        </label>
      </div>
      <p><?= et('cookies.marketing.texto') ?></p>
      <p class="cookies-incluye"><?= et('cookies.incluye') ?> Meta Pixel</p>
    </div>

    <div class="cookies-panel-pie">
      <a href="<?= e(url('cookies')) ?>"><?= et('cookies.politica') ?></a>
      <button type="button" class="cookies-btn cookies-btn-principal" data-cookies="guardar"><?= et('cookies.guardar') ?></button>
    </div>
  </div>
</div>
