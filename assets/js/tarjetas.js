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

/* El color de la paleta que eligió quien publicó: hace de fondo mientras la
   foto carga y de fondo fijo cuando no hay foto. */
function fondoTarjeta(e) {
  return 'background-color:' + esc(e.color) + ';';
}

/* La foto, si la hay, como <img> de verdad y no como background-image: un
   background-image no lo indexa Google Images —no tiene src ni alt— y una
   rejilla de resultados con veinticuatro no debería descargarlas todas de
   golpe. loading="lazy" resuelve las dos cosas a la vez: el navegador no pide
   la imagen hasta que la tarjeta se acerca a la pantalla, y sigue siendo un
   <img> que un buscador puede indexar. */
function imgTarjeta(e) {
  return e.img
    ? '<img class="img-cubre" src="' + esc(e.img) + '" alt="' + esc(e.t || '') + '" loading="lazy" decoding="async">'
    : '';
}

function precioTexto(e, prefijo) {
  return e.free ? 'Gratis' : (e.price ? prefijo + '$' + esc(e.price) + ' MXN' : 'Por confirmar');
}

/* Tarjeta grande del carril de próximas actividades: fecha sobre la imagen,
   categoría, título, ubicación y, al pie, quién la organiza y desde cuánto. */
function evCardHTML(e) {
  return '<a class="ev-card" href="' + esc(e.url) + '">'
    + '<div class="ev-img" style="' + fondoTarjeta(e) + '">'
    +   imgTarjeta(e)
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
   El segundo argumento se le pega a la dirección: el listado lo usa para
   llevarse los filtros a la ficha y que desde allí se pueda volver. */
function cardHTML(e, cola) {
  /* «?» y no «&», desde REQ-00006. La dirección de una ficha era
     /evento.php?id=7 —ya traía su interrogante— y ahora es /actividad/{slug},
     que no trae ninguno. Pegarle «&volver=…» no añadía un parametro: alargaba
     la RUTA, el slug dejaba de terminar en el numero del que sale el id, y
     cada clic desde los resultados acababa en un 404. */
  var url = esc(e.url) + (cola ? '?' + cola : '');

  return '<a class="card-event" href="' + url + '">'
    + '<div class="card-img" style="' + fondoTarjeta(e) + '">'
    +   imgTarjeta(e)
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
function vacioHTML(mensaje, boton) {
  return '<div class="rail-vacio">'
    + '<p>' + esc(mensaje) + '</p>'
    + '<a class="btn-vacio" href="' + RUEDA.base + '/evento-nuevo.php">' + esc(boton) + '</a>'
    + '</div>';
}

function pintar(id, trozos, mensajeVacio, botonVacio) {
  var caja = document.getElementById(id);
  if (!caja) return;
  caja.innerHTML = trozos.length ? trozos.join('') : (mensajeVacio ? vacioHTML(mensajeVacio, botonVacio) : '');
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
