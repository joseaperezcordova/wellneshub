<?php
/**
 * El blog.
 *
 * Sigue siendo maqueta: no hay tabla de artículos ni forma de escribirlos, así
 * que las seis tarjetas son de ejemplo y no llevan a ninguna parte. Se queda
 * porque el menú tiene tres entradas y una de ellas es esta; una página que
 * enseña lo que va a haber es mejor que un enlace que no va a ningún sitio.
 *
 * Lo que cambia respecto a antes es que ahora tiene dirección propia
 * —/blog.php— en vez de ser una vista escondida dentro de la portada.
 */

declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$titulo     = 'Blog';
$seccion    = 'blog';
$anchoLibre = true;

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="block-head">
    <div>
      <div class="eyebrow">SEO · Contenido evergreen</div>
      <h2 style="margin-top:6px;">Blog</h2>
    </div>
  </div>

  <div class="grid-blog">
    <div class="card-blog">
      <div class="b-img" style="background-color:#3E6375;"></div>
      <div class="b-body"><div class="eyebrow">Guía</div><h3>Los mejores retiros de yoga en Oaxaca</h3></div>
    </div>
    <div class="card-blog">
      <div class="b-img" style="background-color:#89A67D;"></div>
      <div class="b-body"><div class="eyebrow">Agenda</div><h3>Eventos wellness en CDMX este fin de semana</h3></div>
    </div>
    <div class="card-blog">
      <div class="b-img" style="background-color:#2F4E5D;"></div>
      <div class="b-body"><div class="eyebrow">Guía</div><h3>Festivales holísticos en México</h3></div>
    </div>
    <div class="card-blog">
      <div class="b-img" style="background-color:#496B52;"></div>
      <div class="b-body"><div class="eyebrow">Evergreen</div><h3>Centros de bienestar por estado</h3></div>
    </div>
    <div class="card-blog">
      <div class="b-img" style="background-color:#C76E43;"></div>
      <div class="b-body"><div class="eyebrow">Evergreen</div><h3>Calendario wellness 2026</h3></div>
    </div>
    <div class="card-blog">
      <div class="b-img" style="background-color:#E9DDC9;"></div>
      <div class="b-body"><div class="eyebrow">Guía</div><h3>Qué llevar a tu primer retiro de silencio</h3></div>
    </div>
  </div>

  <div class="evergreen-note">
    Todavía no hay artículos de verdad: estas seis tarjetas son un ejemplo de lo que iría aquí.
    Las páginas evergreen (guías por estado, calendario anual) se indexan aparte del listado de
    eventos: siguen generando tráfico de búsqueda aunque no haya eventos nuevos publicados esa semana.
  </div>
</section>

<?php pie(); ?>
