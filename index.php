<?php
/**
 * Portada del sitio: el diseño de la v6 del prototipo, ya conectado a la
 * sesión. El botón de «Publicar evento» del topbar deja su sitio al control de
 * cuenta, que es la puerta de entrada a todo lo que requiere identificarse.
 *
 * Sigue siendo el prototipo completo en una sola página: todas las vistas viven
 * en el documento y se conmutan con JavaScript. Se irá partiendo en páginas
 * reales a medida que cada sección tenga back detrás.
 */
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/eventos.php';

$u = usuarioActual();

/*
 * Los eventos de verdad, en la forma que ya espera el JavaScript de esta
 * página. El prototipo traía un array de ejemplo escrito a mano dentro del
 * script; ahora ese array se rellena desde la base y el render se queda igual.
 *
 * Es deliberado no reescribir las funciones de tarjeta: el diseño está cerrado
 * y aprobado, y cambiarlo para conectar los datos habría mezclado dos cosas que
 * conviene poder revisar por separado.
 */
$eventosJs = array_map('eventoParaTarjeta', eventosPublicados());

// Los del panel del organizador y los del panel de administración.
$misEventos    = $u ? eventosDeUsuario((int) $u['id']) : [];
$eventosAdmin  = esAdmin($u) ? eventosTodos() : [];

$avisoPortada = '';
if (!empty($_SESSION['evento_aviso'])) {
    $avisoPortada = (string) $_SESSION['evento_aviso'];
    unset($_SESSION['evento_aviso']);
}
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rueda — Prototipo final (directorio + estructura Eventbrite)</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root{
    /* ---- La paleta, los seis tal cual ---- */
    --fondo:#FAF8F4;        /* Fondo         */
    --verde:#496B52;        /* Verde         */
    --verde-claro:#89A67D;  /* Verde claro   */
    --arena:#E9DDC9;        /* Arena         */
    --terracota:#C76E43;    /* Terracota     */
    --petroleo:#2F4E5D;     /* Azul petroleo */

    /* ---- Derivados ----
       Cada uno existe por un motivo concreto, casi siempre de legibilidad. */

    /* El fondo ya no es blanco puro, asi que las tarjetas se despegan subiendo
       hacia el blanco en vez de bajando hacia el gris. */
    --blanco:#FFFFFF;
    /* Arena a plena saturacion como fondo de fila al pasar el raton pesa
       demasiado; esta version aclarada marca el hover sin gritar. */
    --arena-suave:#F4EDE1;
    /* Las tarjetas del panel admin, sobre el fondo petroleo. */
    --petroleo-suave:#3E6375;

    /* Terracota tiene un problema de contraste en las dos direcciones, y por
       eso necesita tres variantes:
       · como fondo de boton funciona, pero el texto encima tiene que ser casi
         negro (--tinta-boton): con blanco da 3.6:1 y no llega a AA;
       · como texto sobre blanco da 3.6:1, tampoco llega → --terracota-texto;
       · como texto sobre los fondos oscuros (hero, footer, admin) da 2.4:1,
         que es peor → --terracota-palida. */
    --terracota-viva:#D4805A;   /* hover del boton: aclara en vez de oscurecer,
                                   porque oscurecer hunde el contraste del texto */
    --terracota-texto:#A0522D;  /* terracota legible sobre fondo claro   5.6:1 */
    --terracota-palida:#F8D6C0; /* terracota legible sobre fondo oscuro  4.8:1 */
    --tinta-boton:#0F1A21;      /* texto sobre terracota, los dos estados      */

    /* ---- Roles ----
       Los nombres vienen del diseño original y los usan ~400 reglas mas abajo.
       Cambiar la paleta es reasignar aqui, no tocar cada regla. */
    --ink:var(--petroleo);
    --jungle:var(--verde);
    --jungle-deep:var(--petroleo);
    --stone:var(--fondo);
    --paper:var(--blanco);
    --marigold:var(--terracota);
    --marigold-deep:var(--terracota-viva);
    --clay:var(--verde);
    --line: rgba(47,78,93,0.18);
    --line-soft: rgba(47,78,93,0.10);
    --radius: 3px;
  }
  *{box-sizing:border-box;}
  html{scroll-behavior:smooth;}
  body{
    margin:0; background:var(--stone); color:var(--ink);
    font-family:'Inter',sans-serif; font-size:15px; line-height:1.55;
    -webkit-font-smoothing:antialiased;
  }
  h1,h2,h3,h4{font-family:'Fraunces',serif; font-weight:500; margin:0; letter-spacing:-0.01em;}
  a{color:inherit; text-decoration:none;}
  button{font-family:inherit; cursor:pointer;}
  input,select,textarea{font-family:inherit; font-size:14px;}
  :focus-visible{outline:2px solid var(--marigold-deep); outline-offset:2px;}
  .mono{font-family:'IBM Plex Mono',monospace; letter-spacing:0.02em;}
  .eyebrow{font-family:'IBM Plex Mono',monospace; font-size:11px; letter-spacing:0.14em; text-transform:uppercase; color:var(--jungle); opacity:0.7;}
  .wrap{max-width:1180px; margin:0 auto; padding:0 28px;}
  @media(max-width:640px){.wrap{padding:0 18px;}}

  /* ---------- topbar ---------- */
  .topbar{
    position:sticky; top:0; z-index:40; background:var(--paper);
    border-bottom:1px solid var(--line);
  }
  .topbar-inner{display:flex; align-items:center; justify-content:space-between; padding:16px 28px; max-width:1180px; margin:0 auto;}
  .logo{display:flex; align-items:center; gap:10px;}
  /* La marca es una rueda de cuatro cuartos: es el sitio donde la paleta se
     presenta entera, asi que lleva los cuatro colores con caracter propio en
     vez de dos tonos del mismo caramelo. */
  .logo-mark{width:30px; height:30px; border-radius:50%; background:conic-gradient(var(--terracota) 0deg 90deg, var(--petroleo) 90deg 180deg, var(--verde-claro) 180deg 270deg, var(--arena) 270deg 360deg); flex-shrink:0;}
  .logo-text{font-family:'Fraunces',serif; font-weight:600; font-size:19px;}
  .logo-text small{display:block; font-family:'IBM Plex Mono',monospace; font-weight:400; font-size:10px; letter-spacing:0.12em; text-transform:uppercase; color:var(--jungle); opacity:0.65; margin-top:1px;}
  nav.mainnav{display:flex; align-items:center; gap:4px;}
  nav.mainnav button{
    background:none; border:none; padding:8px 14px; font-size:13.5px; color:var(--ink);
    border-radius:20px; transition:background .15s;
  }
  nav.mainnav button:hover{background:var(--line-soft);}
  nav.mainnav button.active{background:var(--jungle); color:var(--paper);}
  /* .admin-toggle se fue con el boton de «Panel admin» del menu publico. */
  .navburger{display:none; background:none;border:none;padding:6px;}
  .navburger span{display:block; width:20px; height:2px; background:var(--ink); margin:4px 0;}
  @media(max-width:900px){
    nav.mainnav{position:fixed; top:64px; left:0; right:0; background:var(--paper); flex-direction:column; align-items:stretch; padding:8px 18px 16px; border-bottom:1px solid var(--line); display:none; gap:2px;}
    nav.mainnav.open{display:flex;}
    nav.mainnav button{text-align:left;}
    .navburger{display:block;}
  }

  /* ---------- views ---------- */
  .view{display:none;}
  .view.active{display:block;}

  /* ---------- hero: banner ----------
     El hero deja de ser una franja de color a sangre y pasa a ser una caja con
     esquinas redondeadas y aire a los lados, con la pastilla de busqueda
     montada sobre su borde inferior. Ese solape es lo que hace reconocible el
     patron: sin el, es un banner cualquiera con un buscador debajo. */
  .hero{padding:0;}
  .hero-inner{position:relative; max-width:1180px; margin:0 auto; padding:0 28px;}

  /* Banner a sangre: ocupa todo el ancho de la ventana, sin esquinas ni
     margenes. Al perder el redondeo pierde tambien la sombra —una sombra en un
     bloque que llega a los bordes no cae sobre nada—. El titulo sigue alineado
     con el resto de la pagina gracias a .hero-content, que si esta limitado. */
  .hero-banner{
    position:relative; overflow:hidden;
    min-height:520px; display:flex; align-items:flex-end;
  }
  @media(max-width:900px){ .hero-banner{min-height:440px;} }
  @media(max-width:640px){ .hero-banner{min-height:400px;} }

  /* Carrusel: las diapositivas se apilan y se cruzan por opacidad. No se
     desplazan lateralmente a proposito — un desplazamiento obliga a medir
     anchos y se rompe en cuanto el banner cambia de tamaño; el fundido no
     depende de la geometria. */
  .carrusel{position:absolute; inset:0;}
  .slide{position:absolute; inset:0; opacity:0; transition:opacity .6s ease;}
  .slide.activa{opacity:1;}

  /* Etiqueta de la diapositiva: nombra lo que se esta viendo. Va arriba a la
     izquierda porque abajo esta el titulo y el centro lo tapa la pastilla. */
  /* En un banner a sangre, pegar los controles al borde de la ventana los deja
     lejisimos del contenido en pantallas anchas. max() los engancha a la misma
     columna que el titulo cuando hay sitio, y a 28px del borde cuando no. */
  .slide-chip{
    position:absolute; top:20px; left:max(28px, calc((100% - 1180px) / 2 + 28px)); z-index:2;
    display:inline-flex; align-items:center; gap:9px; max-width:calc(100% - 200px);
    background:rgba(21,33,40,.45); border:1px solid rgba(255,255,255,.28);
    color:#fff; border-radius:999px; padding:6px 15px; font-size:12.5px;
    backdrop-filter:blur(3px);
  }
  .slide-chip .cat{
    font-family:'IBM Plex Mono',monospace; font-size:10px; letter-spacing:.1em;
    text-transform:uppercase; color:var(--terracota-palida);
  }
  @media(max-width:640px){
    .slide-chip{max-width:calc(100% - 40px); font-size:11.5px; top:14px; left:14px;}
    .slide-chip .cat{display:none;}
  }

  /* Controles arriba a la derecha, la unica esquina que queda libre. */
  .cctrl{position:absolute; top:20px; right:max(28px, calc((100% - 1180px) / 2 + 28px)); z-index:4; display:flex; align-items:center; gap:10px;}
  .cdots{display:flex; align-items:center; gap:6px;}
  .cdots button{
    width:8px; height:8px; padding:0; border:none; border-radius:50%;
    background:rgba(255,255,255,.45); transition:width .2s, background .2s;
  }
  .cdots button[aria-current="true"]{background:#fff; width:22px; border-radius:999px;}
  .cnav{
    width:32px; height:32px; border-radius:50%; border:1px solid rgba(255,255,255,.5);
    background:rgba(21,33,40,.38); color:#fff; font-size:16px; line-height:1;
    display:flex; align-items:center; justify-content:center;
  }
  .cnav:hover{background:rgba(21,33,40,.65);}
  .cctrl :focus-visible{outline:2px solid #fff; outline-offset:2px;}

  /* Pseudo-imagen: la fotografia todavia no existe, asi que cada escena se
     compone con gradientes —cielo, sol, dos lineas de colinas—. Cuando haya
     foto real basta con cambiar el background de cada .mN por la imagen. */
  .hero-media{position:absolute; inset:0;}
  .m1{background:
      radial-gradient(circle at 78% 24%, rgba(233,221,201,.95) 0 52px, rgba(233,221,201,0) 53px),
      linear-gradient(178deg, #2F4E5D 0%, #3E6375 44%, #547B6A 72%, #496B52 100%);}
  .m2{background:linear-gradient(176deg, #3E6375 0%, #496B52 46%, #35513C 100%);}
  .m3{background:
      radial-gradient(circle at 24% 30%, rgba(199,110,67,.9) 0 66px, rgba(199,110,67,0) 67px),
      linear-gradient(180deg, #E9DDC9 0%, #C76E43 40%, #2F4E5D 100%);}
  .m4{background:linear-gradient(180deg, #2F4E5D 0%, #547B6A 58%, #89A67D 100%);}
  .hero-media::before{
    content:""; position:absolute; left:-8%; right:-8%; bottom:0; height:46%;
    background:var(--verde); border-radius:50% 50% 0 0 / 26% 26% 0 0; opacity:.92;
  }
  .hero-media::after{
    content:""; position:absolute; left:-14%; right:24%; bottom:0; height:32%;
    background:var(--verde-claro); border-radius:50% 50% 0 0 / 34% 34% 0 0; opacity:.55;
  }

  /* Velo oscuro de abajo hacia arriba. No es decoracion: sin el, el titulo
     blanco cae sobre el verde claro de las colinas y se queda en 2.6:1. */
  .hero-scrim{
    position:absolute; inset:0;
    background:linear-gradient(to top, rgba(21,33,40,.86) 0%, rgba(21,33,40,.58) 34%, rgba(21,33,40,.20) 72%, rgba(21,33,40,0) 100%);
  }

  /* Limitado y centrado para que el titulo caiga sobre la misma columna que el
     resto de la pagina, aunque el banner llegue a los bordes de la ventana. */
  .hero-content{
    position:relative; z-index:3; width:100%; max-width:1180px;
    margin:0 auto; padding:40px 28px 78px;
  }
  .hero-content .eyebrow, .hero-content h1, .hero-content p.sub{max-width:680px;}
  @media(max-width:640px){ .hero-content{padding:26px 18px 56px;} }
  .hero .eyebrow{color:var(--terracota-palida); opacity:1;}
  .hero h1{font-size:44px; margin-top:10px; color:#fff; text-shadow:0 2px 18px rgba(21,33,40,.4);}
  .hero h1 em{font-style:italic; color:var(--terracota-palida);}
  .hero p.sub{max-width:520px; color:rgba(255,255,255,0.88); margin-top:12px; font-size:15.5px;}
  @media(max-width:640px){.hero h1{font-size:30px;}}

  /* Pastilla de busqueda segmentada: tres campos separados por linea fina y un
     boton circular. Va fuera de .hero-banner —que recorta por overflow— y sube
     con margin negativo para montarse encima. */
  .buscador{
    position:relative; z-index:2; margin:-34px auto 0; max-width:920px;
    background:var(--paper); border:1px solid var(--line); border-radius:999px;
    display:flex; align-items:center; padding:7px 7px 7px 10px;
    box-shadow:0 14px 38px rgba(47,78,93,0.20);
  }
  .bcampo{flex:1 1 0; min-width:0; padding:8px 16px; border-radius:999px;}
  .bcampo:hover{background:var(--arena-suave);}
  .bcampo label{
    display:block; font-family:'IBM Plex Mono',monospace; font-size:10px;
    letter-spacing:.1em; text-transform:uppercase; color:var(--jungle); margin-bottom:2px;
  }
  .bcampo input, .bcampo select{
    width:100%; border:none; background:none; padding:0; font-size:14px; color:var(--ink);
  }
  .bcampo input:focus, .bcampo select:focus{outline:none;}
  .bsep{width:1px; height:34px; background:var(--line); flex-shrink:0;}
  .buscador button{
    flex-shrink:0; width:48px; height:48px; border-radius:50%; border:none;
    background:var(--marigold); color:var(--tinta-boton);
    display:flex; align-items:center; justify-content:center;
  }
  .buscador button:hover{background:var(--marigold-deep);}
  /* En movil la pastilla se rompe en filas: tres campos de 90px de ancho no se
     pueden rellenar, y el circulo del boton deja de tener sentido. */
  @media(max-width:760px){
    .buscador{flex-wrap:wrap; border-radius:18px; margin-top:-26px; padding:8px;}
    .bcampo{flex:1 1 100%;}
    .bcampo + .bcampo{border-top:1px solid var(--line-soft);}
    .bsep{display:none;}
    .buscador button{width:100%; height:44px; border-radius:999px; margin-top:6px;}
  }

  /* ---------- explorar por categoria: menu lineal ----------
     Sustituye a la rueda del bienestar. La rueda tenia tres problemas: solo
     cabian ocho categorias de las trece, obligaba a mantener una version
     distinta en movil (los chips) porque un circulo de 520px no entra en una
     pantalla estrecha, y el tabulador la recorria en un orden que no coincidia
     con lo que se veia. Una fila que se desplaza acepta las trece, es la misma
     en cualquier ancho y se tabula en el orden en que se lee. */
  .catbar{
    background:var(--stone); border-top:1px solid var(--line-soft);
    border-bottom:1px solid var(--line-soft); padding:26px 0 22px;
  }
  .catbar-inner{max-width:1180px; margin:0 auto; padding:0 28px;}
  @media(max-width:640px){ .catbar-inner{padding:0 18px;} }
  .catbar .eyebrow{margin-bottom:16px; display:block;}
  .catrail-wrap{position:relative;}
  .catrail{
    display:flex; gap:26px; overflow-x:auto; scroll-behavior:smooth;
    scroll-snap-type:x proximity; padding-bottom:2px;
    scrollbar-width:none; -ms-overflow-style:none;
  }
  .catrail::-webkit-scrollbar{display:none;}
  /* Botón de publicar del topbar. En pantalla estrecha se queda en «Publicar»
     a secas en vez de desaparecer: es la acción que da de comer al directorio y
     la mitad del tráfico llega desde el móvil. */
  .btn-publicar{
    background:var(--terracota); color:var(--tinta-boton); border-radius:999px;
    padding:9px 18px; font-size:13.5px; font-weight:600; white-space:nowrap;
    transition:background .15s;
  }
  .btn-publicar:hover{background:var(--terracota-viva);}
  @media(max-width:760px){
    .btn-publicar{padding:8px 14px; font-size:13px;}
    .btn-publicar-extra{display:none;}
  }

  /* Carril o rejilla sin nada que enseñar. Un hueco vacío parece un fallo de
     carga; esto dice qué pasa y ofrece la única acción que lo arregla. */
  .rail-vacio{
    width:100%; padding:34px 22px; text-align:center; background:var(--paper);
    border:1px dashed var(--line); border-radius:14px;
  }
  .rail-vacio p{margin:0 0 14px; font-size:14px; opacity:.7;}
  .btn-vacio{
    display:inline-block; background:var(--terracota); color:var(--tinta-boton);
    border-radius:999px; padding:10px 20px; font-size:13.5px; font-weight:600;
  }
  .aviso-portada{
    background:#E7F0E9; border:1px solid #A9C4AF; color:#2C4A35;
    border-radius:10px; padding:12px 15px; font-size:13.5px;
  }

  .catitem{
    flex:0 0 auto; width:88px; background:none; border:none; padding:0;
    display:flex; flex-direction:column; align-items:center; gap:9px; scroll-snap-align:start;
  }
  .catitem .ic{
    width:58px; height:58px; border-radius:50%; border:1px solid var(--line);
    background:var(--paper); display:flex; align-items:center; justify-content:center;
    font-size:22px; transition:background .15s, border-color .15s, transform .15s;
  }
  .catitem:hover .ic{background:var(--arena); border-color:var(--jungle); transform:translateY(-2px);}
  .catitem .lbl{font-size:12.5px; color:var(--ink); text-align:center; line-height:1.25;}
  /* A la altura del circulo, no del bloque entero: si se centra con el texto
     incluido queda descolgada respecto a la fila de iconos. */
  .catnext{
    position:absolute; right:0; top:29px; transform:translateY(-50%);
    width:34px; height:34px; border-radius:50%; border:1px solid var(--line);
    background:var(--paper); color:var(--jungle); font-size:17px; line-height:1;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 4px 14px rgba(47,78,93,.14);
  }
  .catnext:hover{background:var(--arena);}

  /* ---------- proximos eventos: tarjetas en carril ---------- */
  .evrail-wrap{position:relative;}
  .evrail{
    display:flex; gap:20px; overflow-x:auto; scroll-behavior:smooth;
    scroll-snap-type:x proximity; padding:4px 2px 14px;
    scrollbar-width:none; -ms-overflow-style:none;
  }
  .evrail::-webkit-scrollbar{display:none;}
  .ev-card{
    flex:0 0 300px; scroll-snap-align:start; background:var(--paper);
    border:1px solid var(--line); border-radius:12px; overflow:hidden;
    display:flex; flex-direction:column; cursor:pointer;
    transition:transform .15s, box-shadow .15s;
  }
  /* En movil la tarjeta no llena el ancho: el borde de la siguiente asomando
     es lo que delata que la fila se desplaza. */
  @media(max-width:640px){ .ev-card{flex-basis:82%;} }
  .ev-card:hover{transform:translateY(-3px); box-shadow:0 16px 32px rgba(47,78,93,.14);}
  .ev-img{position:relative; height:210px; background-size:cover; background-position:center;}
  .ev-date{
    position:absolute; top:12px; left:12px; background:rgba(255,255,255,.94);
    border-radius:8px; padding:6px 10px 5px; text-align:center;
    font-family:'IBM Plex Mono',monospace; line-height:1.15;
  }
  .ev-date .d{display:block; font-size:16px; font-weight:600; color:var(--ink);}
  .ev-date .m{display:block; font-size:9.5px; letter-spacing:.08em; color:var(--jungle);}
  .ev-fav{
    position:absolute; top:12px; right:12px; width:32px; height:32px; border-radius:50%;
    border:none; background:rgba(255,255,255,.92); color:var(--jungle); font-size:15px;
    display:flex; align-items:center; justify-content:center;
  }
  .ev-fav:hover{background:#fff;}
  .ev-body{padding:16px 18px 18px; display:flex; flex-direction:column; gap:5px; flex:1;}
  .ev-cat{
    font-family:'IBM Plex Mono',monospace; font-size:9.5px; letter-spacing:.12em;
    text-transform:uppercase; color:var(--terracota-texto);
  }
  .ev-body h3{font-size:19px; line-height:1.25;}
  .ev-loc{font-size:12.5px; color:var(--ink); opacity:.7;}
  .ev-foot{
    margin-top:auto; padding-top:12px; border-top:1px solid var(--line-soft);
    display:flex; justify-content:space-between; align-items:center; gap:10px; font-size:11.5px;
  }
  .ev-org{color:var(--ink); opacity:.65; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
  .ev-price{font-family:'IBM Plex Mono',monospace; color:var(--jungle); white-space:nowrap;}
  .ev-price.free{color:var(--terracota-texto);}
  .evnext{
    position:absolute; right:8px; top:86px;
    width:38px; height:38px; border-radius:50%; border:1px solid var(--line);
    background:var(--paper); color:var(--jungle); font-size:18px; line-height:1;
    display:flex; align-items:center; justify-content:center; z-index:2;
    box-shadow:0 5px 16px rgba(47,78,93,.18);
  }
  .evnext:hover{background:var(--arena);}

  /* ---------- section shells ---------- */
  section.block{padding:50px 0;}
  .block-head{display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:22px; gap:16px; flex-wrap:wrap;}
  .block-head h2{font-size:26px;}
  .block-head a.more{font-size:13px; color:var(--jungle); border-bottom:1px solid var(--jungle); flex-shrink:0;}

  /* ---------- chips (ciudades) ---------- */
  /* .chiprow se fue con la seccion de «Ciudades populares»: era su unico uso.
     La busqueda por ciudad sigue disponible en el campo «Donde» del buscador y
     en el filtro de la vista de resultados. */

  /* ---------- event cards ---------- */
  .grid-events{display:grid; grid-template-columns:repeat(3,1fr); gap:20px;}
  @media(max-width:900px){.grid-events{grid-template-columns:repeat(2,1fr);}}
  @media(max-width:640px){.grid-events{grid-template-columns:1fr;}}
  .card-event{background:var(--paper); border:1px solid var(--line); border-radius:8px; overflow:hidden; display:flex; flex-direction:column; transition:transform .15s, box-shadow .15s; cursor:pointer;}
  .card-event:hover{transform:translateY(-3px); box-shadow:0 14px 28px rgba(47,78,93,0.16);}
  .card-img{height:150px; position:relative; background-size:cover; background-position:center;}
  .card-img .cat-tag{position:absolute; top:10px; left:10px; background:rgba(255,255,255,0.92); color:var(--jungle-deep); font-size:10.5px; padding:4px 9px; border-radius:20px; font-family:'IBM Plex Mono',monospace; letter-spacing:0.05em; text-transform:uppercase;}
  .card-img .fav{position:absolute; top:10px; right:10px; width:28px; height:28px; border-radius:50%; background:rgba(47,78,93,0.55); color:#fff; border:none; display:flex; align-items:center; justify-content:center; font-size:14px;}
  .card-body{padding:14px 16px 16px; display:flex; flex-direction:column; gap:6px; flex:1;}
  .card-date{font-family:'IBM Plex Mono',monospace; font-size:11px; color:var(--clay); letter-spacing:0.04em;}
  .card-body h3{font-size:16.5px; line-height:1.3;}
  .card-city{font-size:12.5px; color:var(--ink); opacity:0.65;}
  .card-foot{margin-top:auto; padding-top:10px; border-top:1px solid var(--line-soft); display:flex; justify-content:space-between; align-items:center;}
  .price{font-family:'IBM Plex Mono',monospace; font-size:13px; font-weight:500;}
  .price.free{color:var(--jungle);}

  /* proximos eventos - filas */
  /* La lista de filas (.rowlist / .datebadge / .rowmeta) se fue con el cambio a
     tarjetas en carril: era el unico sitio que la usaba. */

  /* ---------- newsletter ---------- */
  .newsletter{background:var(--jungle); color:var(--paper); border-radius:10px; padding:36px 32px; display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap;}
  .newsletter h3{font-size:22px; color:var(--paper); max-width:360px;}
  .newsletter p{font-size:13px; opacity:0.75; margin-top:6px;}
  .nform{display:flex; gap:8px; flex:1; min-width:260px; max-width:400px;}
  .nform input{flex:1; border:none; border-radius:5px; padding:11px 13px; font-size:13.5px;}
  .nform button{background:var(--marigold); border:none; color:var(--tinta-boton); font-weight:600; border-radius:5px; padding:0 18px; font-size:13.5px;}
  .nform button:hover{background:var(--marigold-deep);}
  .toast{font-size:12.5px; color:var(--terracota-palida); margin-top:8px; display:none;}
  .toast.show{display:block;}

  /* ---------- resultados / filtros ---------- */
  .results-layout{display:grid; grid-template-columns:230px 1fr; gap:32px; align-items:flex-start;}
  @media(max-width:820px){.results-layout{grid-template-columns:1fr;}}
  .filters{background:var(--paper); border:1px solid var(--line); border-radius:8px; padding:20px;}
  .filters h4{font-size:11px; text-transform:uppercase; letter-spacing:0.1em; font-family:'IBM Plex Mono',monospace; color:var(--jungle); margin:18px 0 8px;}
  .filters h4:first-child{margin-top:0;}
  .filters select{width:100%; padding:9px 10px; border:1px solid var(--line); border-radius:5px; background:var(--arena);}
  .checklist label{display:flex; align-items:center; gap:8px; font-size:13px; padding:4px 0;}
  .results-head{display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;}
  .results-head .count{font-size:13px; opacity:0.65;}
  .results-grid{display:grid; grid-template-columns:repeat(2,1fr); gap:18px;}
  @media(max-width:1000px){.results-grid{grid-template-columns:1fr;}}
  .sortsel{border:1px solid var(--line); background:var(--paper); border-radius:5px; padding:7px 10px; font-size:13px;}

  /* ---------- evento detalle ---------- */
  .evento-top{display:grid; grid-template-columns:1.15fr 0.85fr; gap:28px;}
  @media(max-width:900px){.evento-top{grid-template-columns:1fr;}}
  .gallery{display:grid; grid-template-columns:1.4fr 1fr; grid-template-rows:1fr 1fr; gap:8px; height:340px; border-radius:8px; overflow:hidden;}
  .gallery > div{background-size:cover; background-position:center;}
  .gallery > div:first-child{grid-row:1/3;}
  .evento-meta-box{background:var(--paper); border:1px solid var(--line); border-radius:8px; padding:22px;}
  .evento-meta-box .cat-tag{display:inline-block; background:var(--arena); font-size:11px; padding:4px 10px; border-radius:20px; font-family:'IBM Plex Mono',monospace; text-transform:uppercase; letter-spacing:0.05em; color:var(--jungle); margin-bottom:12px;}
  .evento-meta-box h1{font-size:26px; margin-bottom:14px;}
  .metaline{display:flex; gap:10px; font-size:13.5px; padding:8px 0; border-top:1px solid var(--line-soft);}
  .metaline .k{width:78px; flex-shrink:0; opacity:0.55; font-family:'IBM Plex Mono',monospace; font-size:11px; text-transform:uppercase; letter-spacing:.05em; padding-top:2px;}
  .price-big{font-family:'IBM Plex Mono',monospace; font-size:22px; margin:14px 0 4px;}
  .btn-primary{display:block; text-align:center; width:100%; background:var(--marigold); color:var(--tinta-boton); border:none; padding:14px; border-radius:6px; font-weight:600; font-size:14.5px; margin-top:14px;}
  .btn-primary:hover{background:var(--marigold-deep);}
  .socialrow{display:flex; gap:8px; margin-top:14px;}
  .socialrow a{width:34px; height:34px; border-radius:50%; border:1px solid var(--line); display:flex; align-items:center; justify-content:center; font-size:14px;}
  .socialrow a:hover{background:var(--jungle); color:var(--paper); border-color:var(--jungle);}

  .evento-body{margin-top:32px; display:grid; grid-template-columns:1.15fr 0.85fr; gap:28px;}
  @media(max-width:900px){.evento-body{grid-template-columns:1fr;}}
  .desc h3{font-size:18px; margin-bottom:10px;}
  .desc p{font-size:14.5px; opacity:0.85; margin-bottom:14px;}
  .infolist{list-style:none; padding:0; margin:0; display:grid; grid-template-columns:1fr 1fr; gap:8px 18px; font-size:13px;}
  .infolist li{display:flex; justify-content:space-between; border-bottom:1px dashed var(--line); padding-bottom:6px;}
  .infolist li span:first-child{opacity:0.55;}
  .mapbox{height:220px; border-radius:8px; border:1px solid var(--line); background:
      repeating-linear-gradient(45deg, var(--blanco) 0 10px, var(--arena) 10px 20px);
    display:flex; align-items:center; justify-content:center; font-family:'IBM Plex Mono',monospace; font-size:11px; color:var(--jungle); text-align:center; padding:16px; margin-top:16px;}
  .orgcard{background:var(--paper); border:1px solid var(--line); border-radius:8px; padding:18px; display:flex; gap:14px; align-items:center; margin-top:16px; cursor:pointer;}
  .orgcard:hover{border-color:var(--jungle);}
  .avatar{width:52px; height:52px; border-radius:50%; background-size:cover; background-position:center; flex-shrink:0;}
  .orgcard .oname{font-size:14.5px; font-weight:600; font-family:'Fraunces',serif;}
  .orgcard .osub{font-size:12px; opacity:0.6;}

  /* ---------- organizador ---------- */
  .org-hero{display:flex; gap:22px; align-items:center; flex-wrap:wrap; padding:44px 0 8px;}
  .org-hero .avatar{width:96px; height:96px;}
  .org-hero h1{font-size:28px;}
  .org-hero .desc-sm{font-size:13.5px; opacity:0.7; max-width:520px; margin-top:8px;}

  /* ---------- blog ---------- */
  .grid-blog{display:grid; grid-template-columns:repeat(3,1fr); gap:22px;}
  @media(max-width:900px){.grid-blog{grid-template-columns:1fr;}}
  .card-blog{background:var(--paper); border:1px solid var(--line); border-radius:8px; overflow:hidden;}
  .card-blog .b-img{height:130px; background-size:cover; background-position:center;}
  .card-blog .b-body{padding:16px;}
  .card-blog .eyebrow{margin-bottom:6px;}
  .card-blog h3{font-size:16px; line-height:1.35;}
  .evergreen-note{margin-top:30px; border-left:3px solid var(--marigold); padding:14px 18px; background:var(--paper); font-size:13px; opacity:0.85; border-radius:0 6px 6px 0;}

  /* ---------- footer ---------- */
  footer{background:var(--jungle-deep); color:rgba(255,255,255,0.85); margin-top:20px;}
  .foot-inner{max-width:1180px; margin:0 auto; padding:48px 28px 30px; display:grid; grid-template-columns:1.4fr 1fr 1fr 1fr; gap:30px;}
  @media(max-width:760px){.foot-inner{grid-template-columns:1fr 1fr;}}
  footer h5{font-family:'IBM Plex Mono',monospace; font-size:11px; text-transform:uppercase; letter-spacing:0.1em; color:var(--terracota-palida); margin-bottom:12px;}
  footer a{display:block; font-size:13px; padding:4px 0; opacity:0.75;}
  footer a:hover{opacity:1;}
  .foot-bottom{border-top:1px solid rgba(255,255,255,0.12); text-align:center; font-size:11.5px; opacity:0.5; padding:16px; font-family:'IBM Plex Mono',monospace;}

  /* ---------- ADMIN ---------- */
  .admin-shell{background:var(--ink); min-height:100vh; padding-bottom:60px;}
  .admin-header{padding:26px 0 6px;}
  .admin-header .eyebrow{color:var(--terracota-palida);}
  .admin-header h1{color:var(--paper); font-size:26px; margin-top:6px;}
  .stat-grid{display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin:26px 0 36px;}
  @media(max-width:900px){.stat-grid{grid-template-columns:repeat(2,1fr);}}
  .stat-card{background:var(--petroleo-suave); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:16px;}
  .stat-card .num{font-family:'Fraunces',serif; font-size:26px; color:var(--terracota-palida);}
  .stat-card .lbl{font-size:11.5px; color:rgba(255,255,255,0.6); margin-top:4px; font-family:'IBM Plex Mono',monospace; text-transform:uppercase; letter-spacing:0.04em;}

  .admin-tabs{display:flex; gap:4px; border-bottom:1px solid rgba(255,255,255,0.12); margin-bottom:22px; overflow-x:auto;}
  .admin-tabs button{background:none; border:none; color:rgba(255,255,255,0.6); padding:11px 14px; font-size:13px; white-space:nowrap; border-bottom:2px solid transparent;}
  .admin-tabs button.active{color:var(--paper); border-bottom-color:var(--terracota-palida);}

  .admin-panel{display:none;}
  .admin-panel.active{display:block;}
  .panel-toolbar{display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; gap:12px; flex-wrap:wrap;}
  .panel-toolbar input[type=text]{background:var(--petroleo-suave); border:1px solid rgba(255,255,255,0.14); color:var(--paper); padding:9px 12px; border-radius:5px; font-size:13px; min-width:220px;}
  .btn-add{background:var(--marigold); color:var(--tinta-boton); border:none; padding:10px 16px; border-radius:5px; font-size:13px; font-weight:600;}
  .btn-add:hover{background:var(--marigold-deep);}

  table.admtable{width:100%; border-collapse:collapse; background:var(--petroleo-suave); border-radius:8px; overflow:hidden; font-size:13px; color:var(--paper);}
  table.admtable th{text-align:left; font-family:'IBM Plex Mono',monospace; font-size:10.5px; text-transform:uppercase; letter-spacing:0.05em; color:rgba(255,255,255,0.5); padding:11px 14px; border-bottom:1px solid rgba(255,255,255,0.1);}
  table.admtable td{padding:11px 14px; border-bottom:1px solid rgba(255,255,255,0.06); vertical-align:middle;}
  table.admtable tr:last-child td{border-bottom:none;}
  .badge{font-family:'IBM Plex Mono',monospace; font-size:10.5px; padding:3px 9px; border-radius:20px; text-transform:uppercase; letter-spacing:0.04em;}
  .badge.on{background:rgba(199,110,67,0.20); color:var(--terracota-palida);}
  .badge.off{background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.5);}
  .badge.star{background:rgba(233,221,201,0.18); color:var(--arena);}
  .actionbtn{background:none; border:1px solid rgba(255,255,255,0.16); color:rgba(255,255,255,0.75); font-size:11px; padding:5px 9px; border-radius:4px; margin-right:4px;}
  .actionbtn:hover{border-color:var(--terracota-palida); color:var(--terracota-palida);}

  .catchip-admin{display:inline-flex; align-items:center; gap:8px; background:var(--petroleo-suave); border:1px solid rgba(255,255,255,0.12); color:var(--paper); padding:8px 14px; border-radius:20px; font-size:12.5px; margin:0 8px 8px 0;}
  .catchip-admin .n{font-family:'IBM Plex Mono',monospace; opacity:0.5; font-size:11px;}

  .twocol-admin{display:grid; grid-template-columns:1fr 1fr; gap:22px;}
  @media(max-width:760px){.twocol-admin{grid-template-columns:1fr;}}
  .admin-card{background:var(--petroleo-suave); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:18px; color:var(--paper);}
  .admin-card h4{font-family:'IBM Plex Mono',monospace; font-size:11px; text-transform:uppercase; letter-spacing:0.06em; color:rgba(255,255,255,0.55); margin-bottom:10px;}
  .admin-card ul{list-style:none; padding:0; margin:0;}
  .admin-card li{display:flex; justify-content:space-between; font-size:13px; padding:7px 0; border-bottom:1px solid rgba(255,255,255,0.06);}

  /* ---------- modal ---------- */
  .modal-overlay{position:fixed; inset:0; background:rgba(47,78,93,0.60); display:none; align-items:flex-start; justify-content:center; padding:40px 18px; overflow-y:auto; z-index:100;}
  .modal-overlay.open{display:flex;}
  .modal{background:var(--paper); width:100%; max-width:720px; border-radius:10px; padding:0; overflow:hidden;}
  .modal-head{display:flex; justify-content:space-between; align-items:center; padding:18px 22px; border-bottom:1px solid var(--line); position:sticky; top:0; background:var(--paper);}
  .modal-head h3{font-size:18px;}
  .modal-close{background:none; border:none; font-size:20px; line-height:1;}
  .modal-body{padding:22px;}
  .fgrid{display:grid; grid-template-columns:1fr 1fr; gap:14px;}
  @media(max-width:560px){.fgrid{grid-template-columns:1fr;}}
  .fgrid .full{grid-column:1/-1;}
  .field label{display:block; font-size:11.5px; font-family:'IBM Plex Mono',monospace; text-transform:uppercase; letter-spacing:0.04em; opacity:0.6; margin-bottom:5px;}
  .field input, .field select, .field textarea{width:100%; border:1px solid var(--line); background:var(--arena); border-radius:5px; padding:9px 10px; font-size:13px;}
  .field textarea{resize:vertical; min-height:64px;}
  .switchrow{display:flex; gap:18px; margin-top:4px; flex-wrap:wrap;}
  .switchrow label{display:flex; align-items:center; gap:7px; font-size:13px;}
  .modal-foot{padding:16px 22px; border-top:1px solid var(--line); display:flex; justify-content:flex-end; gap:10px;}
  .btn-ghost{background:none; border:1px solid var(--line); padding:10px 18px; border-radius:5px; font-size:13px;}
  .btn-save{background:var(--jungle); color:var(--paper); border:none; padding:10px 20px; border-radius:5px; font-size:13px; font-weight:600;}

  @media (prefers-reduced-motion: reduce){
    *{scroll-behavior:auto !important; transition:none !important;}
  }

  /* ---------- idioma + publicar evento (topbar) ---------- */
  .topbar-right{display:flex; align-items:center; gap:8px;}
  .langtoggle{display:flex; border:1px solid var(--line); border-radius:20px; overflow:hidden;}
  .langtoggle button{background:none; border:none; padding:7px 12px; font-size:12px; font-family:'IBM Plex Mono',monospace;}
  .langtoggle button.active{background:var(--jungle); color:var(--paper);}
  /* .btn-publish se fue con el botón de «Publicar evento»: ahora esa acción
     vive dentro del menú de cuenta, porque exige estar identificado. */

  /* ---------- control de cuenta (sustituye a «Publicar evento») ----------
     Sin sesión va con la palabra «Entrar» al lado del icono: un icono de
     persona a secas solo se entiende cuando ya sabes qué hace, y aquí es
     justo la primera vez. Con sesión, el avatar ya se explica solo. */
  .btn-cuenta{
    display:flex; align-items:center; gap:7px;
    border:1px solid var(--line); border-radius:20px; padding:7px 15px 7px 12px;
    font-size:13px; color:var(--ink); background:var(--paper);
    transition:border-color .15s, background .15s;
  }
  .btn-cuenta:hover{border-color:var(--jungle); background:var(--arena-suave);}
  @media(max-width:520px){ .btn-cuenta span{display:none;} .btn-cuenta{padding:8px;} }

  .cuenta{position:relative;}
  .cuenta summary{list-style:none; cursor:pointer; display:flex; align-items:center;}
  .cuenta summary::-webkit-details-marker{display:none;}
  .avatar-cuenta{
    width:34px; height:34px; border-radius:50%; object-fit:cover;
    border:1px solid var(--line); display:block;
  }
  .avatar-letra{
    display:flex; align-items:center; justify-content:center;
    background:var(--jungle); color:var(--paper);
    font-family:'Fraunces',serif; font-size:15px;
  }
  .cuenta-menu{
    position:absolute; right:0; top:calc(100% + 10px); min-width:230px;
    background:var(--paper); border:1px solid var(--line); border-radius:12px;
    box-shadow:0 14px 34px rgba(47,78,93,.16); padding:6px; z-index:60;
  }
  .cuenta-quien{padding:10px 12px 12px; border-bottom:1px solid var(--line-soft); margin-bottom:6px;}
  .cuenta-quien strong{display:block; font-size:14px;}
  .cuenta-quien span{display:block; font-size:12px; opacity:.65; overflow:hidden; text-overflow:ellipsis;}
  .cuenta-menu a{display:block; padding:9px 12px; border-radius:8px; font-size:13.5px;}
  .cuenta-menu a:hover{background:var(--arena-suave);}
  /* Antes vivia dentro del hero oscuro y por eso era blanco. Ahora cae debajo
     de la pastilla, sobre el fondo claro: en blanco seria invisible. Va
     centrado porque la pastilla tambien lo esta. */
  .hero-actions{display:flex; align-items:center; justify-content:center; gap:16px; margin-top:20px; flex-wrap:wrap;}
  .hero-actions .ghostlink{font-size:13px; color:var(--jungle); border-bottom:1px solid var(--jungle);}
  .hero-actions .ghostlink:hover{color:var(--terracota-texto); border-bottom-color:var(--terracota-texto);}

  /* ---------- fuera de alcance ---------- */
  .scope-banner{background:#2A4453; border:1px dashed rgba(255,255,255,0.25); color:rgba(255,255,255,0.75); border-radius:8px; padding:16px 18px; font-size:12.5px; margin:26px 0;}
  .scope-banner b{color:var(--terracota-palida);}
  .scope-list{display:flex; flex-wrap:wrap; gap:6px 10px; margin-top:8px;}
  .scope-list span{opacity:0.65;}

  /* ---------- panel organizador ---------- */
  .org-login{max-width:380px; margin:70px auto; background:var(--paper); border:1px solid var(--line); border-radius:10px; padding:32px;}
  .org-login h2{font-size:22px; margin-bottom:6px;}
  .org-login p{font-size:13px; opacity:.65; margin-bottom:20px;}
  .org-login .field{margin-bottom:14px;}
  .org-login .field label{display:block; font-size:11.5px; font-family:'IBM Plex Mono',monospace; text-transform:uppercase; opacity:.6; margin-bottom:5px;}
  .org-login .field input{width:100%; border:1px solid var(--line); background:var(--arena); border-radius:5px; padding:10px 12px; font-size:13.5px;}
  .org-login .btn-primary{margin-top:6px;}
  .org-login .swap{text-align:center; font-size:12.5px; margin-top:16px; color:var(--jungle);}

  .op-shell{display:none;}
  .op-shell.active{display:block;}
  .op-header{display:flex; justify-content:space-between; align-items:center; padding:40px 0 6px; flex-wrap:wrap; gap:12px;}
  .op-header .who{display:flex; align-items:center; gap:12px;}
  .op-header .avatar{width:44px; height:44px; background-color:var(--verde);}
  .op-tabs{display:flex; gap:4px; border-bottom:1px solid var(--line); margin:24px 0 20px; overflow-x:auto;}
  .op-tabs button{background:none; border:none; padding:10px 14px; font-size:13px; color:var(--ink); opacity:.55; border-bottom:2px solid transparent; white-space:nowrap;}
  .op-tabs button.active{opacity:1; border-bottom-color:var(--terracota); font-weight:600;}
  .op-panel{display:none;}
  .op-panel.active{display:block;}
  .op-profile-form{max-width:520px;}

  /* ---------- relacionados / badges ---------- */
  .badge-pending{background:rgba(199,110,67,0.18); color:var(--marigold-deep); font-family:'IBM Plex Mono',monospace; font-size:10.5px; padding:3px 9px; border-radius:20px; text-transform:uppercase;}
  .results-heading{margin-bottom:6px;}
  .results-heading .eyebrow{margin-bottom:6px;}

  /* =========================================================
     FUSION v3 — lo que aporta el prototipo "rueda-eventbrite"
     al directorio: la caja de compra fija de la pagina de evento.

     La barra de filtros persistente, el otro patron que venia de v1, se
     elimino: el buscador del hero y los filtros de la vista de resultados
     cubren la busqueda sin ocupar una franja fija en todas las pantallas.
     ========================================================= */

  /* --topbar-h es la altura real del topbar (16px de padding x2 + 31px de
     contenido). Si cambia el padding del topbar hay que ajustarla o la caja
     sticky se mete debajo. */
  :root{ --topbar-h:63px; }

  /* Caja de compra fija en la pagina de evento.
     v2 ya tenia la caja con precio y CTA, pero se iba con el scroll: el visitante
     lee la descripcion larga y pierde de vista el boton. Sticky = el CTA sigue
     ahi cuando termina de convencerse. */
  .evento-top{align-items:start;}
  .evento-meta-box{
    position:sticky; top:calc(var(--topbar-h) + 16px);
  }
  /* Sticky con max-height evita que una caja mas alta que la ventana se corte
     por arriba y deje el boton inalcanzable. */
  @media(min-width:901px){
    .evento-meta-box{max-height:calc(100vh - var(--topbar-h) - 32px); overflow-y:auto;}
  }
  @media(max-width:900px){
    .evento-meta-box{position:static; max-height:none;}
  }
  .lowstock{font-size:11.5px; color:var(--terracota-texto); margin-top:8px; text-align:center;}

  /* (3) El grid de "Eventos destacados" del inicio se elimino: mostraba los
     mismos eventos que la lista de proximos, justo encima. El CSS del toggle
     tarjetas/lista se fue con el. .grid-events sigue en uso en resultados,
     organizador y eventos relacionados. */
</style>
</head>
<body>

<!-- ================= TOPBAR ================= -->
<div class="topbar">
  <div class="topbar-inner">
    <div class="logo">
      <div class="logo-mark"></div>
      <div class="logo-text">Rueda<small>Directorio wellness MX</small></div>
    </div>
    <nav class="mainnav" id="mainnav">
      <!-- «Organizadores» y «Panel admin» salen del menu publico. Las vistas
           siguen en el archivo: la del organizador se abre desde «Publicar
           evento» y desde la ficha de cada evento; la de admin queda sin enlace
           porque no es algo que un visitante deba encontrar navegando. -->
      <button data-view="inicio" class="active">Inicio</button>
      <button data-view="resultados" onclick="showResults('Buscar eventos','Todos los eventos')">Buscar eventos</button>
      <button data-view="blog">Blog</button>
    </nav>
    <div class="topbar-right">
      <div class="langtoggle" id="langToggle">
        <button data-lang="es" class="active">ES</button>
        <button data-lang="en">EN</button>
      </div>
      <!-- «Publicar evento» lo ve todo el mundo, con sesión o sin ella. Quien no
           la tenga pasa por el login y vuelve aquí solo: esconder el botón a los
           visitantes es esconder justo lo que queremos que hagan, y un directorio
           sin organizadores nuevos no crece. La puerta la guarda el servidor
           —exigirSesion() en evento-nuevo.php—, no la ausencia del enlace. -->
      <a class="btn-publicar" href="<?= URL_BASE ?>/evento-nuevo.php">
        Publicar<span class="btn-publicar-extra"> evento</span>
      </a>

      <?php if ($u): ?>
        <!-- Con sesión: avatar y menú. <details> abre y cierra sin JavaScript y
             el teclado ya sabe manejarlo. -->
        <details class="cuenta">
          <summary aria-label="Mi cuenta">
            <?php if (!empty($u['avatar_url'])): ?>
              <img class="avatar-cuenta" src="<?= e($u['avatar_url']) ?>" alt="" referrerpolicy="no-referrer">
            <?php else: ?>
              <span class="avatar-cuenta avatar-letra"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
            <?php endif; ?>
          </summary>
          <div class="cuenta-menu">
            <div class="cuenta-quien">
              <strong><?= e($u['nombre']) ?></strong>
              <span><?= e($u['email']) ?></span>
            </div>
            <a href="#" onclick="cerrarMenuCuenta(); switchView('panel-organizador'); return false;">Publicar evento</a>
            <?php if ($u['rol'] === 'admin'): ?>
              <a href="#" onclick="cerrarMenuCuenta(); switchView('admin'); return false;">Panel admin</a>
            <?php endif; ?>
            <a href="<?= URL_BASE ?>/logout.php">Cerrar sesión</a>
          </div>
        </details>
      <?php else: ?>
        <!-- Sin sesión: enlace, no botón con JavaScript, para que funcione el
             clic central y el «abrir en pestaña nueva». -->
        <a class="btn-cuenta" href="<?= URL_BASE ?>/login.php" aria-label="Entrar a mi cuenta">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"
               fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
            <circle cx="12" cy="8.2" r="3.8"/>
            <path d="M4.8 20c.6-3.7 3.6-5.9 7.2-5.9s6.6 2.2 7.2 5.9"/>
          </svg>
          <span>Entrar</span>
        </a>
      <?php endif; ?>
      <button class="navburger" id="burger" aria-label="Abrir menú"><span></span><span></span><span></span></button>
    </div>
  </div>
</div>

<?php if ($avisoPortada): ?>
  <!-- Aviso de vuelta desde publicar o eliminar un evento. -->
  <div class="wrap" style="padding-top:18px;">
    <div class="aviso-portada"><?= e($avisoPortada) ?></div>
  </div>
<?php endif; ?>

<!-- ================= INICIO ================= -->
<main id="view-inicio" class="view active">
  <section class="hero">
      <div class="hero-banner">
        <!-- Carrusel de escenas. Cada .hero-media es un marcador de posicion
             compuesto con gradientes; el dia que haya fotografia se cambia cada
             uno por un background-image y ni los controles ni el titulo se
             enteran.

             El titulo de la pagina NO rota: vive fuera de las diapositivas, al
             pie del banner. Solo cambian la imagen y la etiqueta de arriba a la
             izquierda, que nombra el evento que se esta viendo. Asi hay un
             unico h1 y el mensaje del sitio no depende de donde pare el giro. -->
        <div class="carrusel" id="carrusel">
          <div class="slide activa" aria-hidden="false">
            <div class="hero-media m1"></div>
            <div class="hero-scrim"></div>
            <div class="slide-chip"><span class="cat">Sound Healing</span> Amanecer en el Cenote · Tulum</div>
          </div>
          <div class="slide" aria-hidden="true">
            <div class="hero-media m2"></div>
            <div class="hero-scrim"></div>
            <div class="slide-chip"><span class="cat">Festival</span> Festival Holístico Raíz · CDMX</div>
          </div>
          <div class="slide" aria-hidden="true">
            <div class="hero-media m3"></div>
            <div class="hero-scrim"></div>
            <div class="slide-chip"><span class="cat">Breathwork</span> Bajo las estrellas · San Miguel</div>
          </div>
          <div class="slide" aria-hidden="true">
            <div class="hero-media m4"></div>
            <div class="hero-scrim"></div>
            <div class="slide-chip"><span class="cat">Retiro</span> Silencio Vipassana · Oaxaca</div>
          </div>
        </div>

        <div class="cctrl">
          <div class="cdots" id="cdots">
            <button type="button" aria-current="true"  aria-label="Ver imagen 1"></button>
            <button type="button" aria-current="false" aria-label="Ver imagen 2"></button>
            <button type="button" aria-current="false" aria-label="Ver imagen 3"></button>
            <button type="button" aria-current="false" aria-label="Ver imagen 4"></button>
          </div>
          <button type="button" class="cnav" id="cprev" aria-label="Imagen anterior">‹</button>
          <button type="button" class="cnav" id="cnext" aria-label="Imagen siguiente">›</button>
        </div>

        <div class="hero-content">
          <div class="eyebrow">Directorio de eventos · México</div>
          <h1>Encuentra tu próximo <em>retiro, festival o círculo</em> de bienestar</h1>
          <p class="sub">Retiros de yoga, breathwork, sound healing y festivales holísticos, reunidos en un solo lugar — sin buscar por veinte cuentas de Instagram distintas.</p>
        </div>
      </div>

    <div class="hero-inner">
      <form class="buscador" onsubmit="event.preventDefault(); switchView('resultados');">
        <div class="bcampo">
          <label for="bDonde">Dónde</label>
          <input id="bDonde" type="text" placeholder="Tulum, CDMX, Oaxaca…">
        </div>
        <div class="bsep" aria-hidden="true"></div>
        <div class="bcampo">
          <label for="bCuando">Cuándo</label>
          <select id="bCuando">
            <option>Cualquier fecha</option>
            <option>Este fin de semana</option>
            <option>Próximos 7 días</option>
            <option>Este mes</option>
          </select>
        </div>
        <div class="bsep" aria-hidden="true"></div>
        <div class="bcampo">
          <label for="bQue">Qué</label>
          <select id="bQue">
            <option>Cualquier práctica</option>
            <option>Yoga</option>
            <option>Meditación</option>
            <option>Breathwork</option>
            <option>Sound Healing</option>
            <option>Ice Bath</option>
            <option>Retiro</option>
            <option>Festival</option>
          </select>
        </div>
        <button type="submit" aria-label="Buscar eventos">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20.2 20.2l-4-4"/></svg>
        </button>
      </form>

      <div class="hero-actions">
        <?php if ($u): ?>
          <a href="#" class="ghostlink" onclick="switchView('panel-organizador'); return false;">¿Organizas eventos? Publica el tuyo →</a>
        <?php else: ?>
          <!-- Sin sesión no tiene sentido llevarlo al panel: primero hay que
               saber quién es. Va al login, que ya trae la vía de registro. -->
          <a href="<?= URL_BASE ?>/login.php" class="ghostlink">¿Organizas eventos? Publica el tuyo →</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Los emoji son marcadores de posicion, igual que las imagenes: cuando haya
       un juego de iconos de linea se sustituye el contenido de .ic y nada mas
       cambia. -->
  <section class="catbar">
    <div class="catbar-inner">
      <span class="eyebrow">Explora por categoría</span>
      <div class="catrail-wrap">
        <div class="catrail" id="catrail">
          <?php /* Salen de categoriasMenu(), la misma lista que valida el formulario
                   de alta. Escritas a mano en los dos sitios, una categoria nueva
                   aparecia en el menu y no se podia elegir al publicar. */ ?>
          <?php foreach (categoriasMenu() as $catNombre => $catDatos): ?>
            <button class="catitem" data-cat="<?= e($catNombre) ?>"><span class="ic"><?= e($catDatos[0]) ?></span><span class="lbl"><?= e($catDatos[1]) ?></span></button>
          <?php endforeach; ?>
        </div>
        <button type="button" class="catnext" id="catnext" aria-label="Ver más categorías">›</button>
      </div>
    </div>
  </section>

  <!-- Proximos eventos en carril horizontal. La lista de filas anterior daba
       mas densidad, pero la tarjeta con imagen es lo que deja ver de que va
       cada evento antes de entrar — que es lo que se le pide a un directorio. -->
  <section class="block wrap">
    <div class="block-head">
      <h2>Próximos eventos</h2>
      <a class="more" href="#" onclick="switchView('resultados'); return false;">Ver todos los eventos →</a>
    </div>
    <div class="evrail-wrap">
      <div class="evrail" id="proximosRail"></div>
      <button type="button" class="evnext" id="evnext" aria-label="Ver más eventos">›</button>
    </div>
  </section>

  <section class="block wrap">
    <div class="block-head">
      <h2>Artículos</h2>
      <a class="more" href="#" onclick="switchView('blog'); return false;">Ir al blog →</a>
    </div>
    <div class="grid-blog">
      <article class="card-blog" onclick="switchView('blog')">
        <div class="b-img" style="background-color:#496B52;"></div>
        <div class="b-body">
          <div class="eyebrow">Guía</div>
          <h3>Cómo elegir tu primer retiro de silencio</h3>
        </div>
      </article>
      <article class="card-blog" onclick="switchView('blog')">
        <div class="b-img" style="background-color:#C76E43;"></div>
        <div class="b-body">
          <div class="eyebrow">Ciudades</div>
          <h3>Tulum más allá de la playa: dónde se practica de verdad</h3>
        </div>
      </article>
      <article class="card-blog" onclick="switchView('blog')">
        <div class="b-img" style="background-color:#3E6375;"></div>
        <div class="b-body">
          <div class="eyebrow">Prácticas</div>
          <h3>Qué esperar de tu primera ceremonia de cacao</h3>
        </div>
      </article>
    </div>
  </section>

  <section class="block wrap">
    <div class="newsletter">
      <div>
        <h3>Un correo al mes con lo mejor del bienestar en México</h3>
        <p>Sin spam. Solo retiros, festivales y círculos que valen la pena.</p>
      </div>
      <div>
        <form class="nform" onsubmit="showToast(this); return false;">
          <input type="email" required placeholder="tucorreo@ejemplo.com">
          <button type="submit">Suscribirme</button>
        </form>
        <div class="toast">✓ Gracias — revisa tu correo para confirmar.</div>
      </div>
    </div>
  </section>
</main>

<!-- ================= RESULTADOS ================= -->
<main id="view-resultados" class="view">
  <section class="wrap block">
    <div class="results-heading">
      <div class="eyebrow" id="resultsEyebrow">Buscar eventos</div>
      <div class="block-head"><h2 id="resultsTitle">Todos los eventos</h2></div>
    </div>
    <div class="results-layout">
      <aside class="filters">
        <h4>Estado</h4>
        <select><option>Todos los estados</option><option>Quintana Roo</option><option>Ciudad de México</option><option>Oaxaca</option><option>Jalisco</option><option>Guanajuato</option><option>Nuevo León</option></select>
        <h4>Ciudad</h4>
        <select><option>Todas las ciudades</option><option>Tulum</option><option>CDMX</option><option>Oaxaca de Juárez</option><option>Guadalajara</option><option>Puerto Vallarta</option><option>San Miguel de Allende</option><option>Playa del Carmen</option><option>Monterrey</option></select>
        <h4>Fecha</h4>
        <select><option>Cualquier fecha</option><option>Este fin de semana</option><option>Próximos 7 días</option><option>Este mes</option><option>Elegir rango…</option></select>
        <h4>Categoría</h4>
        <div class="checklist">
          <label><input type="checkbox" checked> Yoga</label>
          <label><input type="checkbox"> Meditación</label>
          <label><input type="checkbox"> Breathwork</label>
          <label><input type="checkbox"> Pilates</label>
          <label><input type="checkbox"> Retreat</label>
          <label><input type="checkbox"> Festival</label>
          <label><input type="checkbox"> Sound Healing</label>
          <label><input type="checkbox"> Ice Bath</label>
          <label><input type="checkbox"> Biohacking</label>
          <label><input type="checkbox"> Nutrición</label>
          <label><input type="checkbox"> Conferencia</label>
          <label><input type="checkbox"> Networking</label>
          <label><input type="checkbox"> Otro</label>
        </div>
        <h4>Precio</h4>
        <div class="checklist">
          <label><input type="checkbox"> Gratis</label>
        </div>
        <h4>Modalidad</h4>
        <div class="checklist">
          <label><input type="checkbox" checked> Presencial</label>
          <label><input type="checkbox"> Online</label>
        </div>
      </aside>

      <div>
        <div class="results-head">
          <div class="count">37 eventos encontrados</div>
          <select class="sortsel"><option>Ordenar: más próximos</option><option>Precio: menor a mayor</option><option>Recién publicados</option></select>
        </div>
        <div class="results-grid" id="resultsGrid"></div>
      </div>
    </div>
  </section>
</main>

<!-- ================= EVENTO ================= -->
<main id="view-evento" class="view">
  <section class="wrap block">
    <a href="#" style="font-size:13px; color:var(--jungle);" onclick="switchView('resultados'); return false;">← Volver a resultados</a>

    <div class="evento-top" style="margin-top:18px;">
      <div class="gallery">
        <div style="background-color:#89A67D; background-image:linear-gradient(160deg, rgba(47,78,93,.35), rgba(199,110,67,.28));"></div>
        <div style="background-color:#3E6375;"></div>
        <div style="background-color:#496B52;"></div>
      </div>

      <div class="evento-meta-box">
        <span class="cat-tag">Sound Healing · Yoga</span>
        <h1>Amanecer en el Cenote — Retiro de Yoga y Sonido</h1>
        <div class="metaline"><span class="k">Fecha</span><span>Sáb 16 – Dom 17 de agosto, 2026</span></div>
        <div class="metaline"><span class="k">Hora</span><span>5:30 am – 6:00 pm</span></div>
        <div class="metaline"><span class="k">Ciudad</span><span>Tulum, Quintana Roo</span></div>
        <div class="metaline"><span class="k">Cupo</span><span>18 personas · 11 lugares disponibles</span></div>
        <div class="price-big">$2,450 <span class="mono" style="font-size:12px; opacity:.55;">MXN / persona</span></div>
        <button class="btn-primary" onclick="alert('Prototipo: esto abriría la página del organizador para comprar boleto.')">Comprar boleto →</button>
        <div class="lowstock">Quedan pocos lugares</div>
        <div class="socialrow">
          <a href="#" title="Instagram">IG</a>
          <a href="#" title="Facebook">FB</a>
          <a href="#" title="WhatsApp">WA</a>
          <a href="#" title="Sitio web">🌐</a>
        </div>
      </div>
    </div>

    <div class="evento-body">
      <div class="desc">
        <h3>Descripción</h3>
        <p>Un retiro de 24 horas dentro de la selva de Tulum: apertura con círculo de respiración al amanecer frente al cenote, dos sesiones de yoga (vinyasa suave y yin), baño de sonido con cuencos tibetanos y cierre con temazcal ceremonial. Incluye alimentación vegetariana y hospedaje en cabañas compartidas.</p>
        <p>Ideal para quien busca desconectar del ritmo urbano sin necesitar experiencia previa en meditación o yoga — el ritmo del grupo se ajusta a cada participante.</p>

        <h3 style="margin-top:22px;">Detalles</h3>
        <ul class="infolist">
          <li><span>Nivel</span><span>Todos los niveles</span></li>
          <li><span>Idioma</span><span>Español / Inglés</span></li>
          <li><span>Edad mínima</span><span>18 años</span></li>
          <li><span>Qué llevar</span><span>Ropa cómoda, traje de baño</span></li>
          <li><span>Mascotas</span><span>No permitidas</span></li>
          <li><span>Estacionamiento</span><span>Disponible sin costo</span></li>
        </ul>

        <div class="mapbox">Mapa interactivo — Cenote Zacil-Ha, Tulum, Q. Roo<br>(placeholder de Google Maps)</div>
      </div>

      <div>
        <div class="admin-card" style="background:var(--paper); color:var(--ink); border-color:var(--line);">
          <h4 style="opacity:.6;">Organizado por</h4>
          <div class="orgcard" onclick="switchView('organizador')" style="margin-top:0;">
            <div class="avatar" style="background-color:#496B52;"></div>
            <div>
              <div class="oname">Raíz Colectivo</div>
              <div class="osub">12 eventos activos · Tulum, MX</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="block" style="padding-bottom:0;">
      <div class="block-head"><h2 style="font-size:20px;">Eventos relacionados</h2></div>
      <div class="grid-events" id="relatedGrid"></div>
    </div>
  </section>
</main>

<!-- ================= ORGANIZADOR ================= -->
<main id="view-organizador" class="view">
  <section class="wrap">
    <div class="org-hero">
      <div class="avatar" style="background-color:#496B52;"></div>
      <div>
        <div class="eyebrow">Perfil de organizador</div>
        <h1>Raíz Colectivo</h1>
        <p class="desc-sm">Colectivo de facilitadores de yoga, sonido y ceremonia con base en Tulum. Organizan retiros mensuales enfocados en reconexión con el cuerpo y la selva desde 2019.</p>
        <div class="socialrow" style="margin-top:14px;">
          <a href="#" title="Instagram">IG</a>
          <a href="#" title="Facebook">FB</a>
          <a href="#" title="WhatsApp">WA</a>
          <a href="#" title="Sitio web">🌐</a>
        </div>
      </div>
    </div>
  </section>

  <section class="block wrap">
    <div class="block-head"><h2>Eventos activos</h2></div>
    <div class="grid-events" id="orgEventsGrid"></div>
  </section>
</main>

<!-- ================= BLOG ================= -->
<main id="view-blog" class="view">
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
    <div class="evergreen-note">Las páginas evergreen (guías por estado, calendario anual) se indexan aparte del listado de eventos: siguen generando tráfico de búsqueda aunque no haya eventos nuevos publicados esa semana.</div>
  </section>
</main>

<!-- ================= PANEL DEL ORGANIZADOR ================= -->
<main id="view-panel-organizador" class="view">
  <div class="wrap">

    <?php if (!$u): ?>
      <!-- Sin sesión: puerta al login de verdad.
           Aquí vivía un formulario de correo y contraseña del prototipo que no
           validaba nada: pulsar «Entrar» enseñaba el panel sin más. Con sesión
           real habría dos sitios donde escribir la contraseña y solo uno
           serviría, que es justo la confusión que trajo a José hasta aquí. -->
      <div class="org-login">
        <div class="eyebrow">Panel del organizador</div>
        <h2 style="margin-top:6px;">Entra para publicar</h2>
        <p>Publica y administra tus propios eventos — sin necesitar al equipo de Rueda.</p>
        <a class="btn-primary" style="text-decoration:none;" href="<?= URL_BASE ?>/login.php">Entrar o crear cuenta</a>
        <div class="swap">Con Google o con un código al correo. No hace falta contraseña.</div>
      </div>
    <?php else: ?>

    <!-- panel -->
    <div id="opShell" class="op-shell active">
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
           href="<?= URL_BASE ?>/evento-nuevo.php">+ Nuevo evento</a>
      </div>

      <div class="op-tabs" id="opTabs">
        <button data-op="miseventos" class="active">Mis eventos</button>
        <button data-op="perfil">Editar perfil</button>
      </div>

      <div class="op-panel active" id="op-miseventos">
        <?php if (!$misEventos): ?>
          <div class="evergreen-note">
            Todavía no has creado ningún evento. Con «+ Nuevo evento» escribes la ficha,
            la ves como la verá la gente y decides si publicarla.
          </div>
        <?php else: ?>
          <table class="admtable" style="background:var(--paper); color:var(--ink);">
            <thead><tr><th>Título</th><th>Fecha</th><th>Situación</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($misEventos as $me): $p = fechaPartes($me['fecha_inicio']); ?>
                <tr>
                  <td><?= e($me['titulo']) ?></td>
                  <td><?= e($p['d'] . ' ' . $p['m'] . ' ' . date('Y', strtotime($me['fecha_inicio']))) ?></td>
                  <td>
                    <?php if ($me['situacion'] === 'publicado'): ?>
                      <span class="badge on" style="color:var(--jungle); background:rgba(47,78,93,0.12);">Publicado</span>
                    <?php elseif ($me['situacion'] === 'borrador'): ?>
                      <span class="badge-pending">Borrador · sin publicar</span>
                    <?php else: ?>
                      <span class="badge off">Oculto</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a class="actionbtn" style="color:var(--ink); border-color:var(--line);"
                       href="<?= URL_BASE ?>/evento.php?id=<?= (int) $me['id'] ?>">Ver</a>
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
            Un evento publicado se puede corregir durante <?= EVENTO_MARGEN_EDICION_H ?> horas.
            Después, los cambios se le piden al administrador.
          </div>
        <?php endif; ?>
      </div>

      <div class="op-panel" id="op-perfil">
        <div class="op-profile-form">
          <div class="field" style="margin-bottom:14px;"><label>Nombre</label><input type="text" value="Raíz Colectivo"></div>
          <div class="field" style="margin-bottom:14px;"><label>Descripción (ES)</label><textarea>Colectivo de facilitadores de yoga, sonido y ceremonia con base en Tulum.</textarea></div>
          <div class="field" style="margin-bottom:14px;"><label>Descripción (EN)</label><textarea>Collective of yoga, sound and ceremony facilitators based in Tulum.</textarea></div>
          <div class="field" style="margin-bottom:14px;"><label>Instagram</label><input type="text" value="@raizcolectivo"></div>
          <div class="field" style="margin-bottom:14px;"><label>WhatsApp</label><input type="text" placeholder="+52 984 000 0000"></div>
          <div class="field" style="margin-bottom:14px;"><label>Sitio web</label><input type="text" placeholder="https://raizcolectivo.mx"></div>
          <button class="btn-save">Guardar cambios</button>
        </div>
      </div>

      <div class="scope-banner">
        <b>Fuera de alcance del MVP</b> — visible para orientar, no funcional en este prototipo.
        <div class="scope-list">
          <span>Venta de boletos</span>·<span>Pagos en línea</span>·<span>Chat organizador-usuario</span>·<span>Reseñas</span>·<span>Notificaciones push</span>·<span>Programa de afiliados</span>·<span>Integraciones (Stripe, Eventbrite, Google Calendar)</span>·<span>Recomendaciones con IA</span>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<!-- ================= ADMIN ================= -->
<main id="view-admin" class="view">
  <div class="admin-shell">
    <div class="wrap">
      <div class="admin-header">
        <div class="eyebrow">Panel administrador</div>
        <h1>Dashboard</h1>
      </div>

      <div class="stat-grid">
        <div class="stat-card"><div class="num">142</div><div class="lbl">Eventos publicados</div></div>
        <div class="stat-card"><div class="num">19</div><div class="lbl">Próximos (7 días)</div></div>
        <div class="stat-card"><div class="num">6</div><div class="lbl">Pendientes de aprobación</div></div>
        <div class="stat-card"><div class="num">31</div><div class="lbl">Eventos expirados</div></div>
        <div class="stat-card"><div class="num">58</div><div class="lbl">Organizadores</div></div>
        <div class="stat-card"><div class="num">1,207</div><div class="lbl">Suscriptores newsletter</div></div>
      </div>

      <div class="scope-banner">
        <b>Fuera de alcance del MVP</b> — se diseña la arquitectura para permitirlo después, no se construye ahora.
        <div class="scope-list">
          <span>Procesamiento de pagos</span>·<span>Venta de boletos</span>·<span>App móvil</span>·<span>Chat</span>·<span>Reseñas</span>·<span>Afiliados</span>·<span>Automatizaciones de marketing</span>·<span>Integraciones externas</span>·<span>IA de recomendaciones</span>·<span>Notificaciones push</span>·<span>Favoritos</span>·<span>Calendario personal</span>·<span>Marketplace</span>·<span>Directorio de profesionales / hoteles</span>
        </div>
      </div>

      <div class="admin-tabs" id="adminTabs">
        <button data-panel="eventos" class="active">Eventos</button>
        <button data-panel="organizadores">Organizadores</button>
        <button data-panel="categorias">Categorías</button>
        <button data-panel="ciudades">Ciudades y estados</button>
        <button data-panel="usuarios">Usuarios</button>
        <button data-panel="newsletter">Newsletter</button>
      </div>

      <!-- EVENTOS -->
      <div class="admin-panel active" id="panel-eventos">
        <div class="panel-toolbar">
          <a class="btn-add" href="<?= URL_BASE ?>/evento-nuevo.php">+ Nuevo evento</a>
        </div>
        <table class="admtable">
          <thead><tr><th>Título</th><th>Organiza</th><th>Ciudad</th><th>Fecha</th><th>Situación</th><th></th></tr></thead>
          <tbody>
            <?php if (!$eventosAdmin): ?>
              <tr><td colspan="6" style="opacity:.6;">Todavía no hay eventos.</td></tr>
            <?php endif; ?>
            <?php foreach ($eventosAdmin as $ea): $p = fechaPartes($ea['fecha_inicio']); ?>
              <tr>
                <td><?= e($ea['titulo']) ?></td>
                <td><?= e($ea['organizador']) ?></td>
                <td><?= e($ea['ciudad']) ?></td>
                <td><?= e($p['d'] . ' ' . $p['m'] . ' ' . date('Y', strtotime($ea['fecha_inicio']))) ?></td>
                <td>
                  <span class="badge <?= $ea['situacion'] === 'publicado' ? 'on' : 'off' ?>">
                    <?= e(ucfirst($ea['situacion'])) ?>
                  </span>
                </td>
                <td>
                  <a class="actionbtn" href="<?= URL_BASE ?>/evento.php?id=<?= (int) $ea['id'] ?>">Ver</a>
                  <a class="actionbtn" href="<?= URL_BASE ?>/evento-editar.php?id=<?= (int) $ea['id'] ?>">Editar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="evergreen-note" style="margin-top:18px;">
          Ocultar y eliminar se hacen desde la ficha del evento, con la confirmación delante.
          Un botón «Eliminar» en una fila de tabla se pulsa por error con demasiada facilidad.
        </div>
      </div>

      <!-- ORGANIZADORES -->
      <div class="admin-panel" id="panel-organizadores">
        <div class="panel-toolbar">
          <input type="text" placeholder="Buscar organizador…">
          <button class="btn-add">+ Nuevo organizador</button>
        </div>
        <table class="admtable">
          <thead><tr><th>Nombre</th><th>Contacto</th><th>Eventos</th><th>Redes</th><th></th></tr></thead>
          <tbody>
            <tr><td>Raíz Colectivo</td><td>hola@raizcolectivo.mx</td><td>12</td><td>IG · FB · WA</td><td><button class="actionbtn">Editar</button><button class="actionbtn">Eliminar</button></td></tr>
            <tr><td>Circulo Vivo</td><td>circulovivo@gmail.com</td><td>7</td><td>IG · Web</td><td><button class="actionbtn">Editar</button><button class="actionbtn">Eliminar</button></td></tr>
            <tr><td>Sana Selva</td><td>contacto@sanaselva.mx</td><td>5</td><td>IG · FB</td><td><button class="actionbtn">Editar</button><button class="actionbtn">Eliminar</button></td></tr>
            <tr><td>Amara Wellness</td><td>amara.wellness@gmail.com</td><td>9</td><td>IG · WA · Web</td><td><button class="actionbtn">Editar</button><button class="actionbtn">Eliminar</button></td></tr>
          </tbody>
        </table>
      </div>

      <!-- CATEGORIAS -->
      <div class="admin-panel" id="panel-categorias">
        <div class="panel-toolbar">
          <input type="text" placeholder="Buscar categoría…">
          <button class="btn-add">+ Nueva categoría</button>
        </div>
        <div>
          <span class="catchip-admin">Yoga <span class="n">38</span></span>
          <span class="catchip-admin">Meditación <span class="n">21</span></span>
          <span class="catchip-admin">Breathwork <span class="n">14</span></span>
          <span class="catchip-admin">Pilates <span class="n">9</span></span>
          <span class="catchip-admin">Retreat <span class="n">17</span></span>
          <span class="catchip-admin">Festival <span class="n">6</span></span>
          <span class="catchip-admin">Sound Healing <span class="n">12</span></span>
          <span class="catchip-admin">Ice Bath <span class="n">5</span></span>
          <span class="catchip-admin">Biohacking <span class="n">4</span></span>
          <span class="catchip-admin">Nutrición <span class="n">8</span></span>
          <span class="catchip-admin">Conferencia <span class="n">3</span></span>
          <span class="catchip-admin">Networking <span class="n">2</span></span>
          <span class="catchip-admin">Otro <span class="n">3</span></span>
        </div>
      </div>

      <!-- CIUDADES / ESTADOS -->
      <div class="admin-panel" id="panel-ciudades">
        <div class="twocol-admin">
          <div class="admin-card">
            <h4>Estados</h4>
            <ul>
              <li>Quintana Roo <span class="mono" style="opacity:.5;">2 ciudades</span></li>
              <li>Ciudad de México <span class="mono" style="opacity:.5;">1 ciudad</span></li>
              <li>Oaxaca <span class="mono" style="opacity:.5;">1 ciudad</span></li>
              <li>Jalisco <span class="mono" style="opacity:.5;">2 ciudades</span></li>
              <li>Guanajuato <span class="mono" style="opacity:.5;">1 ciudad</span></li>
              <li>Nuevo León <span class="mono" style="opacity:.5;">1 ciudad</span></li>
            </ul>
            <button class="btn-add" style="margin-top:14px;">+ Nuevo estado</button>
          </div>
          <div class="admin-card">
            <h4>Ciudades</h4>
            <ul>
              <li>Tulum <span class="mono" style="opacity:.5;">24 eventos</span></li>
              <li>CDMX <span class="mono" style="opacity:.5;">41 eventos</span></li>
              <li>Oaxaca de Juárez <span class="mono" style="opacity:.5;">18 eventos</span></li>
              <li>San Miguel de Allende <span class="mono" style="opacity:.5;">15 eventos</span></li>
              <li>Guadalajara <span class="mono" style="opacity:.5;">12 eventos</span></li>
              <li>Puerto Vallarta <span class="mono" style="opacity:.5;">9 eventos</span></li>
            </ul>
            <button class="btn-add" style="margin-top:14px;">+ Nueva ciudad</button>
          </div>
        </div>
      </div>

      <!-- USUARIOS -->
      <div class="admin-panel" id="panel-usuarios">
        <div class="panel-toolbar">
          <input type="text" placeholder="Buscar usuario…">
          <button class="btn-add">+ Nuevo usuario</button>
        </div>
        <table class="admtable">
          <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th></th></tr></thead>
          <tbody>
            <tr><td>José P.</td><td>jose@jpcorelab.com</td><td><span class="badge on">Administrador</span></td><td><button class="actionbtn">Editar</button></td></tr>
            <tr><td>Mariana R.</td><td>mariana@directoriowellness.mx</td><td><span class="badge off">Editor</span></td><td><button class="actionbtn">Editar</button><button class="actionbtn">Eliminar</button></td></tr>
          </tbody>
        </table>
      </div>

      <!-- NEWSLETTER -->
      <div class="admin-panel" id="panel-newsletter">
        <div class="admin-card" style="max-width:420px;">
          <h4>Suscriptores</h4>
          <div class="stat-card" style="margin-bottom:14px;"><div class="num">1,207</div><div class="lbl">Correos capturados</div></div>
          <button class="btn-add">Exportar CSV</button>
        </div>
      </div>

    </div>
  </div>
</main>

<!-- ================= FOOTER (Acerca de / Contacto) ================= -->
<footer>
  <div class="foot-inner">
    <div>
      <div class="logo-text" style="color:var(--blanco);">Rueda</div>
      <p style="font-size:13px; opacity:.7; margin-top:10px; max-width:260px;">El directorio de eventos wellness más completo de México — retiros, festivales y círculos, curados a mano.</p>
    </div>
    <div>
      <h5>Acerca de</h5>
      <a href="#">Nuestra misión</a>
      <a href="#">Cómo curamos los eventos</a>
      <a href="#">Publica tu evento</a>
    </div>
    <div>
      <h5>Contacto</h5>
      <a href="#">hola@ruedawellness.mx</a>
      <a href="#">WhatsApp</a>
      <a href="#">Instagram</a>
    </div>
    <div>
      <h5>Explorar</h5>
      <a href="#" onclick="switchView('resultados'); return false;">Buscar eventos</a>
      <a href="#" onclick="switchView('blog'); return false;">Blog</a>
    </div>
  </div>
  <div class="foot-bottom">© 2026 Rueda — Directorio de eventos wellness MX. Prototipo de interfaz.</div>
</footer>


<script>
/* ---------- los eventos, desde la base de datos ----------
   Antes era un array escrito a mano. Ahora lo rellena PHP con lo que hay
   publicado, con las mismas claves, para que el render de abajo no cambiara. */
const eventos = <?= json_encode($eventosJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

/* Escapa antes de meter texto en las plantillas de tarjeta. Los titulos los
   escribe cualquiera que publique un evento, asi que llegan aqui sin depurar:
   sin esto, un titulo con etiquetas dentro se ejecutaria en la portada. */
function esc(s){
  return String(s == null ? '' : s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

/* Tarjeta grande del carril de proximos eventos: fecha sobre la imagen,
   categoria, titulo, ubicacion y, al pie, quien lo organiza y desde cuanto. */
/* El fondo de la tarjeta: la foto si la hay, y si no el color de la paleta que
   eligio quien publico, que es como lo hacia el prototipo. */
function fondoTarjeta(e){
  return e.img
    ? `background-image:url('${esc(e.img)}'); background-size:cover; background-position:center;`
    : `background-color:${esc(e.color)};`;
}

function precioTexto(e, prefijo){
  return e.free ? 'Gratis' : (e.price ? prefijo + '$' + esc(e.price) + ' MXN' : 'Por confirmar');
}

function evCardHTML(e){
  return `<a class="ev-card" href="${esc(e.url)}">
    <div class="ev-img" style="${fondoTarjeta(e)}">
      <div class="ev-date"><span class="d">${esc(e.d)}</span><span class="m">${esc(e.m)}</span></div>
    </div>
    <div class="ev-body">
      <div class="ev-cat">${esc(e.cat)}</div>
      <h3>${esc(e.t)}</h3>
      <div class="ev-loc">${esc(e.city)}</div>
      <div class="ev-foot">
        <span class="ev-org">${esc(e.org)}</span>
        <span class="ev-price ${e.free?'free':''}">${precioTexto(e,'Desde ')}</span>
      </div>
    </div>
  </a>`;
}

function cardHTML(e){
  return `<a class="card-event" href="${esc(e.url)}">
    <div class="card-img" style="${fondoTarjeta(e)}">
      <span class="cat-tag">${esc(e.cat)}</span>
    </div>
    <div class="card-body">
      <div class="card-date">${esc(e.date)}</div>
      <h3>${esc(e.t)}</h3>
      <div class="card-city">${esc(e.city)}</div>
      <div class="card-foot">
        <span class="price ${e.free?'free':''}">${precioTexto(e,'')}</span>
        <span style="font-size:12px; color:var(--jungle);">Ver evento →</span>
      </div>
    </div>
  </a>`;
}

/* Con la base vacia, un carril sin nada parece la pagina rota. Se dice que no
   hay eventos y se invita a publicar, que es justo lo que hace falta al
   principio. */
function vacioHTML(mensaje){
  return `<div class="rail-vacio">
    <p>${esc(mensaje)}</p>
    <a class="btn-vacio" href="<?= URL_BASE ?>/evento-nuevo.php">Publicar el primero</a>
  </div>`;
}
/* ---------- carrusel del banner ---------- */
(function(){
  var raiz = document.getElementById('carrusel');
  if (!raiz) return;

  var slides = [].slice.call(raiz.querySelectorAll('.slide'));
  var puntos = [].slice.call(document.querySelectorAll('#cdots button'));
  var banner = raiz.parentNode;
  var i = 0, reloj = null;
  var ESPERA = 4000;

  /* Si el sistema pide menos movimiento, el carrusel no gira solo. Las flechas
     y los puntos siguen ahi: se quita el automatismo, no el control. */
  var quieto = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function ir(n){
    i = (n + slides.length) % slides.length;
    slides.forEach(function(s, k){
      s.classList.toggle('activa', k === i);
      s.setAttribute('aria-hidden', k === i ? 'false' : 'true');
    });
    puntos.forEach(function(p, k){
      p.setAttribute('aria-current', k === i ? 'true' : 'false');
    });
  }

  function parar(){ if (reloj) { clearInterval(reloj); reloj = null; } }
  function arrancar(){
    parar();
    if (!quieto) reloj = setInterval(function(){ ir(i + 1); }, ESPERA);
  }

  document.getElementById('cprev').addEventListener('click', function(){ ir(i - 1); arrancar(); });
  document.getElementById('cnext').addEventListener('click', function(){ ir(i + 1); arrancar(); });
  puntos.forEach(function(p, k){
    p.addEventListener('click', function(){ ir(k); arrancar(); });
  });

  /* Se detiene con el raton encima o con el foco dentro. Un carrusel que sigue
     girando mientras lees o mientras tabulas por sus botones es la queja
     clasica de este patron. */
  banner.addEventListener('mouseenter', parar);
  banner.addEventListener('mouseleave', arrancar);
  banner.addEventListener('focusin', parar);
  banner.addEventListener('focusout', arrancar);

  /* En una pestaña de fondo tampoco tiene sentido seguir contando. */
  document.addEventListener('visibilitychange', function(){
    if (document.hidden) parar(); else arrancar();
  });

  ir(0);
  arrancar();
})();
/* Antes se duplicaba la lista en resultsGrid para que la rejilla se viera
   llena en el prototipo. Con datos reales eso serian eventos repetidos. */
pintar('proximosRail', eventos.map(evCardHTML), 'Todavia no hay eventos publicados.');
pintar('resultsGrid',  eventos.map(cardHTML),   'Ningun evento coincide por ahora.');
pintar('relatedGrid',  eventos.slice(0,3).map(cardHTML), '');
pintar('orgEventsGrid', eventos.slice(0,3).map(cardHTML), '');

function pintar(id, trozos, mensajeVacio){
  var caja = document.getElementById(id);
  if (!caja) return;
  caja.innerHTML = trozos.length ? trozos.join('') : (mensajeVacio ? vacioHTML(mensajeVacio) : '');
}

/* ---------- panel del organizador ----------
   enterOrgPanel() ya no existe: quién ve el panel lo decide PHP según la sesión,
   no un clic. Las pestañas de dentro sí siguen siendo cosa del navegador. */
document.querySelectorAll('#opTabs button').forEach(b=>{
  b.addEventListener('click', ()=>{
    document.querySelectorAll('#opTabs button').forEach(x=>x.classList.remove('active'));
    document.querySelectorAll('.op-panel').forEach(x=>x.classList.remove('active'));
    b.classList.add('active');
    document.getElementById('op-'+b.dataset.op).classList.add('active');
  });
});

/* ---------- páginas dinámicas por ciudad / categoría ---------- */
function showResults(eyebrow, title, categoria){
  document.getElementById('resultsEyebrow').textContent = eyebrow;
  document.getElementById('resultsTitle').textContent = title;

  /* El filtro es de verdad: antes el menu de categorias cambiaba el titulo y
     debajo seguian saliendo los mismos eventos de siempre. */
  var lista = categoria ? eventos.filter(function(ev){ return ev.cat === categoria; }) : eventos;

  pintar('resultsGrid', lista.map(cardHTML),
         categoria ? 'Todavia no hay eventos de ' + categoria + '.' : 'Todavia no hay eventos publicados.');

  var cuenta = document.querySelector('#view-resultados .count');
  if (cuenta) cuenta.textContent = lista.length + (lista.length === 1 ? ' evento encontrado' : ' eventos encontrados');

  switchView('resultados');
}
document.querySelectorAll('.catitem').forEach(el=>{
  el.addEventListener('click', ()=>{
    showResults('Página por categoría', 'Eventos de ' + el.dataset.cat, el.dataset.cat);
  });
});

/* ---------- selector de idioma (ES/EN) ---------- */
const i18n = {
  es:{h1:'Encuentra tu próximo <em>retiro, festival o círculo</em> de bienestar', tag:'Directorio de eventos · México'},
  en:{h1:'Find your next <em>retreat, festival or wellness</em> circle', tag:'Event directory · Mexico'}
};
document.querySelectorAll('#langToggle button').forEach(b=>{
  b.addEventListener('click', ()=>{
    document.querySelectorAll('#langToggle button').forEach(x=>x.classList.remove('active'));
    b.classList.add('active');
    const t = i18n[b.dataset.lang];
    document.querySelector('.hero h1').innerHTML = t.h1;
    document.querySelector('.hero .eyebrow').textContent = t.tag;
  });
});

/* La tabla de eventos del panel admin ya no se pinta aqui: la escribe PHP mas
   arriba, con los eventos reales. Pintarla en el navegador obligaba a mandar
   tambien los borradores y los ocultos al HTML de cualquier visitante. */

/* ---------- navegación entre vistas ---------- */
function switchView(name){
  document.querySelectorAll('.view').forEach(v=>v.classList.remove('active'));
  document.getElementById('view-'+name).classList.add('active');
  document.querySelectorAll('nav.mainnav button').forEach(b=>b.classList.toggle('active', b.dataset.view===name));
  document.getElementById('mainnav').classList.remove('open');
  window.scrollTo({top:0, behavior:'auto'});
}
document.querySelectorAll('nav.mainnav button').forEach(b=>{
  b.addEventListener('click', ()=>switchView(b.dataset.view));
});
document.getElementById('burger').addEventListener('click', ()=>{
  document.getElementById('mainnav').classList.toggle('open');
});

/* ---------- admin tabs ---------- */
document.querySelectorAll('#adminTabs button').forEach(b=>{
  b.addEventListener('click', ()=>{
    document.querySelectorAll('#adminTabs button').forEach(x=>x.classList.remove('active'));
    document.querySelectorAll('.admin-panel').forEach(x=>x.classList.remove('active'));
    b.classList.add('active');
    document.getElementById('panel-'+b.dataset.panel).classList.add('active');
  });
});

/* ---------- modal ---------- */
/* El modal de "nuevo evento" del prototipo se fue de aqui: era un formulario
   que no guardaba nada, y ahora el alta de verdad esta en evento-nuevo.php.
   Su maqueta —con los campos que aun no existen: mapa, aforo, galeria,
   etiquetas, idioma— sigue intacta en prototipos/v3-final/versiones/v6.html,
   que es justo para lo que estan congeladas esas versiones. */

/* ---------- newsletter toast ---------- */
function showToast(form){
  const t = form.parentElement.querySelector('.toast');
  t.classList.add('show');
  form.reset();
}

/* ---------- carriles horizontales ----------
   El menu de categorias y el de eventos comparten comportamiento: avanzan una
   pantalla y, al llegar al final, vuelven al principio. Una flecha que se queda
   muerta al final parece rota; que ciclee es lo que el usuario espera. */
function carril(idRail, idBoton){
  var rail = document.getElementById(idRail);
  var boton = document.getElementById(idBoton);
  if (!rail || !boton) return;

  boton.addEventListener('click', function(){
    var final = rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 8;
    if (final) rail.scrollTo({left:0, behavior:'smooth'});
    else rail.scrollBy({left: rail.clientWidth * 0.72, behavior:'smooth'});
  });

  /* Con el raton no hay barra de desplazamiento visible (se oculta a proposito),
     asi que si todo cabe en pantalla la flecha no pinta nada. */
  function revisar(){ boton.hidden = rail.scrollWidth <= rail.clientWidth + 4; }
  revisar();
  window.addEventListener('resize', revisar);
}
carril('catrail', 'catnext');
carril('proximosRail', 'evnext');

/* ---------- menu de cuenta ----------
   Las opciones del menu conmutan de vista sin recargar, asi que hay que
   cerrarlo a mano: si no, se queda abierto flotando sobre la vista nueva. */
function cerrarMenuCuenta(){
  var d = document.querySelector('details.cuenta');
  if (d) d.removeAttribute('open');
}

/* Un menu que solo se cierra pulsando su propia pestaña resulta pegajoso: se
   cierra tambien al pulsar fuera o con Escape, que es lo que se espera. */
document.addEventListener('click', function(ev){
  var d = document.querySelector('details.cuenta');
  if (d && d.open && !d.contains(ev.target)) d.removeAttribute('open');
});
document.addEventListener('keydown', function(ev){
  if (ev.key === 'Escape') cerrarMenuCuenta();
});
</script>

</body>
</html>
