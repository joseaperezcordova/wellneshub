/* ============================================================================
   Las tarjetas de actividad, en el navegador.

   Las pinta el navegador y no PHP porque la lista se filtra y se reordena sin
   recargar: en buscar.php cada casilla que se marca vuelve a componer la
   rejilla entera. Los datos llegan desde PHP en un array —eventoParaTarjeta()
   en includes/eventos.php decide qué campos— y aquí solo se dibujan.

   Estas funciones las usan la portada (el carril de próximos), buscar.php (la
   rejilla de resultados) y la ficha (los relacionados), así que viven en un
   archivo compartido en vez de repetidas en cada una.
   ========================================================================== */

/* Todo lo que sale de la base pasa por aquí antes de entrar en el HTML. Los
   títulos y las descripciones los escribe cualquiera que publique una actividad,
   así que llegan sin depurar: sin esto, un título con etiquetas dentro se
   ejecutaría en la portada. */
function esc(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

/* El fondo de la tarjeta: la foto si la hay, y si no el color de la paleta que
   eligió quien publicó.
   La foto no entra aquí como background-image directo: eso la descarga de
   inmediato, esté o no a la vista, y una rejilla de resultados puede traer
   veinticuatro. Se deja en data-bg y arranca() la pone cuando la tarjeta se
   acerca a la pantalla —ver activarLazyBg(), más abajo—. Mientras tanto se ve
   el color de la actividad, que ya hace de fondo cuando no hay foto. */
function fondoTarjeta(e) {
  var base = 'background-color:' + esc(e.color) + ';';
  return e.img ? base + ' background-size:cover; background-position:center;' : base;
}

function atributoLazyBg(e) {
  return e.img ? ' data-bg="' + esc(e.img) + '"' : '';
}

function precioTexto(e, prefijo) {
  return e.free ? 'Gratis' : (e.price ? prefijo + '$' + esc(e.price) + ' MXN' : 'Por confirmar');
}

/* Tarjeta grande del carril de próximas actividades: fecha sobre la imagen,
   categoría, título, ubicación y, al pie, quién la organiza y desde cuánto. */
function evCardHTML(e) {
  return '<a class="ev-card" href="' + esc(e.url) + '">'
    + '<div class="ev-img" style="' + fondoTarjeta(e) + '"' + atributoLazyBg(e) + '>'
    +   '<div class="ev-date"><span class="d">' + esc(e.d) + '</span><span class="m">' + esc(e.m) + '</span></div>'
    + '</div>'
    + '<div class="ev-body">'
    +   '<div class="ev-cat">' + esc(e.cat) + '</div>'
    +   '<h3>' + esc(e.t) + '</h3>'
    +   '<div class="ev-loc">' + esc(e.city) + '</div>'
    +   '<div class="ev-foot">'
    +     '<span class="ev-org">' + esc(e.org) + '</span>'
    +     '<span class="ev-price ' + (e.free ? 'free' : '') + '">' + precioTexto(e, 'Desde ') + '</span>'
    +   '</div>'
    + '</div>'
    + '</a>';
}

/* La tarjeta de la rejilla de resultados y de los relacionados.
   El segundo argumento se le pega a la dirección: buscar.php lo usa para
   llevarse los filtros a la ficha y que desde allí se pueda volver. */
function cardHTML(e, cola) {
  var url = esc(e.url) + (cola ? '&amp;' + cola : '');

  return '<a class="card-event" href="' + url + '">'
    + '<div class="card-img" style="' + fondoTarjeta(e) + '"' + atributoLazyBg(e) + '>'
    +   '<span class="cat-tag">' + esc(e.cat) + '</span>'
    + '</div>'
    + '<div class="card-body">'
    +   '<div class="card-date">' + esc(e.date) + '</div>'
    +   '<h3>' + esc(e.t) + '</h3>'
    +   '<div class="card-city">' + esc(e.city) + '</div>'
    +   '<div class="card-foot">'
    +     '<span class="price ' + (e.free ? 'free' : '') + '">' + precioTexto(e, '') + '</span>'
    +     '<span style="font-size:12px; color:var(--jungle);">Ver actividad →</span>'
    +   '</div>'
    + '</div>'
    + '</a>';
}

/* Con la base vacía, un carril sin nada parece la página rota. Se dice que no
   hay actividades y se invita a publicar, que es justo lo que hace falta al
   principio. */
function vacioHTML(mensaje) {
  return '<div class="rail-vacio">'
    + '<p>' + esc(mensaje) + '</p>'
    + '<a class="btn-vacio" href="' + RUEDA.base + '/evento-nuevo.php">Publicar la primera</a>'
    + '</div>';
}

/* Pone la foto real cuando la tarjeta se acerca a la pantalla —350px antes,
   para que ya esté lista al llegar y no se vea aparecer de golpe—. Un solo
   observer para todo el sitio: cada pintar() solo agrega tarjetas a lo que ya
   está mirando, no crea uno nuevo por carril o por página de resultados. */
var lazyBgObserver = ('IntersectionObserver' in window)
  ? new IntersectionObserver(function (entradas) {
      entradas.forEach(function (entrada) {
        if (!entrada.isIntersecting) return;
        var el = entrada.target;
        el.style.backgroundImage = "url('" + el.dataset.bg + "')";
        el.removeAttribute('data-bg');
        lazyBgObserver.unobserve(el);
      });
    }, {rootMargin: '350px'})
  : null;

function activarLazyBg(caja) {
  var tarjetas = caja.querySelectorAll('[data-bg]');
  if (!lazyBgObserver) {
    // Sin soporte para IntersectionObserver: mejor la foto de una vez que
    // ninguna, así que se pone directo.
    tarjetas.forEach(function (el) {
      el.style.backgroundImage = "url('" + el.dataset.bg + "')";
      el.removeAttribute('data-bg');
    });
    return;
  }
  tarjetas.forEach(function (el) { lazyBgObserver.observe(el); });
}

function pintar(id, trozos, mensajeVacio) {
  var caja = document.getElementById(id);
  if (!caja) return;
  caja.innerHTML = trozos.length ? trozos.join('') : (mensajeVacio ? vacioHTML(mensajeVacio) : '');
  activarLazyBg(caja);
}

/* ---------- carriles horizontales ----------
   El menú de categorías y el de actividades comparten comportamiento: avanzan una
   pantalla y, al llegar al final, vuelven al principio. Una flecha que se queda
   muerta al final parece rota; que cicle es lo que se espera. */
function carril(idRail, idBoton) {
  var rail  = document.getElementById(idRail);
  var boton = document.getElementById(idBoton);
  if (!rail || !boton) return;

  boton.addEventListener('click', function () {
    var final = rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 8;
    if (final) rail.scrollTo({left: 0, behavior: 'smooth'});
    else rail.scrollBy({left: rail.clientWidth * 0.72, behavior: 'smooth'});
  });

  /* Con el ratón no hay barra de desplazamiento visible (se oculta a
     propósito), así que si todo cabe en pantalla la flecha no pinta nada. */
  function revisar() { boton.hidden = rail.scrollWidth <= rail.clientWidth + 4; }
  revisar();
  window.addEventListener('resize', revisar);
}
