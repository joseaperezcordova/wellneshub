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
 *
 * FUERA DEL MVP (REQ-00004). La página entera se queda escrita; lo que hay es
 * una puerta cerrada delante. Tiene que estar aquí y no solo en el enrutador
 * porque este archivo existe de verdad: el .htaccess sirve /blog.php directo
 * sin pasar por router.php, así que sin esta comprobación la dirección seguiría
 * abierta para quien la conociera.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

if (!seccionVisible('blog')) {
    http_response_code(404);

    $titulo = t('pagina.404.titulo');
    require __DIR__ . '/includes/layout.php';
    echo '<div class="auth-caja"><h1>' . et('pagina.404.titulo') . '</h1>'
       . '<p class="sub">Puede que el enlace esté mal escrito o que la página se haya movido.</p>'
       . '<a class="btn-principal" style="text-decoration:none; display:block; text-align:center;"'
       . ' href="' . e(URL_BASE) . '/">Ir al inicio</a></div>';
    pie();
    exit;
}

$titulo      = 'Blog';
$descripcion = 'Guías y agenda de bienestar en México: retiros de yoga, festivales holísticos y consejos para tu próxima actividad.';
$seccion     = 'blog';
$anchoLibre  = true;

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
      <div class="b-img" style="background-color:var(--petroleo-suave);"></div>
      <div class="b-body"><div class="eyebrow">Guía</div><h3>Los mejores retiros de yoga en Oaxaca</h3></div>
    </div>
    <div class="card-blog">
      <div class="b-img" style="background-color:var(--verde-claro);"></div>
      <div class="b-body"><div class="eyebrow">Agenda</div><h3>Actividades wellness en CDMX este fin de semana</h3></div>
    </div>
    <div class="card-blog">
      <div class="b-img" style="background-color:var(--verde);"></div>
      <div class="b-body"><div class="eyebrow">Guía</div><h3>Festivales holísticos en México</h3></div>
    </div>
    <div class="card-blog">
      <div class="b-img" style="background-color:var(--verde-claro);"></div>
      <div class="b-body"><div class="eyebrow">Evergreen</div><h3>Centros de bienestar por estado</h3></div>
    </div>
    <div class="card-blog">
      <div class="b-img" style="background-color:var(--terracota);"></div>
      <div class="b-body"><div class="eyebrow">Evergreen</div><h3>Calendario wellness 2026</h3></div>
    </div>
    <div class="card-blog">
      <div class="b-img" style="background-color:var(--arena);"></div>
      <div class="b-body"><div class="eyebrow">Guía</div><h3>Qué llevar a tu primer retiro de silencio</h3></div>
    </div>
  </div>

  <div class="evergreen-note">
    Todavía no hay artículos de verdad: estas seis tarjetas son un ejemplo de lo que iría aquí.
    Las páginas evergreen (guías por estado, calendario anual) se indexan aparte del listado de
    actividades: siguen generando tráfico de búsqueda aunque no haya actividades nuevas publicadas
    esa semana.
  </div>
</section>

<?php pie(); ?>
