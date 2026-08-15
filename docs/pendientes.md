# Pendientes

Lo que quedó a medias y por qué. Un pendiente vive aquí cuando **el código ya
está preparado y solo falta un dato o una decisión que no depende de programar**:
una URL que nadie ha creado todavía, un texto que tiene que redactar otra
persona, una cuenta que hay que dar de alta.

No entra aquí lo que simplemente no se ha hecho. Para eso están los
requerimientos.

Cada punto dice **qué falta**, **dónde se aplica** y **qué hay que hacer para
cerrarlo**, para que cerrarlo sea sustituir un valor y no volver a investigar
dónde iba.

---

## Bloqueado por un dato que falta

### 1. URLs de las redes sociales

**Qué falta:** las direcciones de los perfiles de OMDARA en Instagram, Facebook
y WhatsApp. El requerimiento REQ-00003 las deja como
`[Agregar URLs definitivas de cada red social]`.

**Dónde:** `includes/layout.php`, en el bloque `.foot-redes` del pie. Los tres
iconos están puestos y maquetados, con `aria-label` y `rel="nofollow"`; solo el
`href` está en `#`.

**Para cerrarlo:** sustituir los tres `href="#"` por la URL de cada perfil.

---

### 2. Texto legal: Términos, Privacidad y Cookies

**Qué falta:** el texto de las tres páginas legales.

**Dónde:** `terminos-y-condiciones.php`, `aviso-de-privacidad.php` y
`politica-de-cookies.php`. Las tres existen, están enlazadas desde el pie y en
el sitemap, y muestran un aviso de contenido pendiente.

**No lo redacta quien programa.** Obliga a la empresa frente a organizadores y
asistentes, y un texto copiado de otra web describe un servicio que no es este.

**Lo que sí aporta el código, y está ya escrito en cada página:** el inventario
de lo que el sitio hace de verdad — las reglas que aplica (plazo de 24 horas,
sin revisión previa, OMDARA no gestiona pagos), los seis tipos de dato personal
que guarda, y las cookies que pone. Es la parte que se pierde cuando se copia
una plantilla.

**Para cerrarlo:** sustituir el bloque provisional por el texto definitivo y
borrar el `require` de `includes/aviso-pendiente.php`.

> **Ojo con el orden de prioridad.** En México el Aviso de Privacidad no es
> cortesía: la LFPDPPP lo exige a quien trate datos personales, y este sitio los
> trata desde el primer registro. Ahora mismo el sitio opera sin él.

---

### 3. Traducción al inglés — REQ-00002, fases 2 a 5

**Qué está hecho (fase 1):** los cimientos. El idioma lo decide la dirección,
`rutasSitio()` es el mapa único del que salen enrutado, enlaces, hreflang y
sitemap, el selector conserva la página al cambiar de idioma, y la cabecera y
el pie —que salen en todas las páginas— están traducidos.

**Qué falta, por fases:**

| Fase | Alcance | Depende de |
|---|---|---|
| 2 | Portada, buscador y filtros | Textos EN |
| 3 | Formulario de actividad: 40+ etiquetas y mensajes de validación | Textos EN |
| 4 | Ficha de actividad, contacto, login y correos | Textos EN |
| 5 | Contenido dinámico: título y descripción de cada actividad | Migración de base |

**Las 23 categorías** son caso aparte. Se guardan en `eventos.categoria` como
texto en español, así que traducirlas es añadir la etiqueta inglesa en
`categoriasMenu()` —que ya separa «clave que se guarda» de «etiqueta que se
lee»— sin tocar ni una fila.

**La fase 5 necesita una migración**: columnas para título y descripción en
inglés, más un comportamiento de reserva cuando el organizador no las rellene.
El requerimiento pide expresamente no traducir automáticamente lo que escribió
el organizador.

**Bloqueado por:** los textos finales ES/EN y los textos SEO, que el propio
REQ-00002 declara como dependencia y todavía no se han entregado. En
`includes/idiomas/en.php` está traducida la interfaz funcional —navegación,
botones, etiquetas—; falta el texto editorial: el lema de la marca y las meta
descriptions de cada página.

**Mientras tanto no se promueve a producción.** El requerimiento prohíbe la
traducción parcial, y hoy el inglés cubre el armazón pero no el cuerpo de las
páginas. Vive en pruebas hasta cerrar la fase 4.

---

## Decisiones de diseño abiertas

### 3. El logotipo

**Qué falta:** una marca gráfica para OMDARA.

**Dónde:** `.logo-mark` en `assets/css/app.css` y `assets/css/portada.css`.

**El problema:** es una rueda de cuatro cuartos porque el sitio se llamaba
*Rueda*. Con OMDARA el motivo ya no significa nada. Se ajustaron sus colores
para que se lea sobre la cabecera verde, pero eso es un parche, no una
identidad.

**Para cerrarlo:** una decisión de diseño y sustituir el `conic-gradient` por lo
que salga.

---

### 4. El subtítulo de la marca

**Qué falta:** decidir si "Directorio wellness MX" sigue siendo el claim.

**Dónde:** `includes/layout.php`, el `<small>` dentro de `.logo-text`.

---

## Deuda técnica asumida a propósito

### 5. Los nombres de los tokens de color mienten

**Qué pasa:** `--terracota` vale hoy azul (`#2878D7`) y `--petroleo` vale verde
carbón (`#20332D`). Los nombres son de la paleta anterior.

**Por qué se dejó así:** renombrarlos obliga a tocar unas 400 reglas. Hacerlo a
la vez que el cambio de paleta habría metido un error de color detrás de mil
líneas de renombrado, sin forma de revisar una cosa sin la otra.

**Para cerrarlo:** un commit propio que solo renombre, sin cambiar ni un valor.

---

### 6. Las duraciones de las cookies están declaradas, no comprobadas

**Qué falta:** ver con las tres herramientas encendidas en producción qué
cookies ponen de verdad, y con qué duración.

**Dónde:** `politica-de-cookies.php`, las tres tablas.

**De dónde sale lo que hay hoy:** de la documentación de Google, Microsoft y
Meta. Es lo más honesto que se puede afirmar antes de tener las tres activas con
tráfico real, y cubre el criterio de REQ-00003 —nombre, proveedor, finalidad,
duración y categoría— pero no es todavía «las cookies efectivamente generadas».

**Para cerrarlo:** en producción, con las tres configuradas, aceptar todo y
abrir las herramientas de desarrollo → Application → Cookies. Comparar nombre y
caducidad con la tabla y corregir lo que no coincida. Dos cosas que suelen
diferir: `_gcl_au` solo aparece si la cuenta se enlaza con Google Ads, y el
sufijo real de `_ga_<ID>` no se sabe hasta ver el flujo de datos.

> Ojo con probarlo en pruebas: hoy los IDs de analítica solo están puestos en
> un entorno. Donde no hay IDs no hay banner, y no es un fallo — es lo que
> hace `hayQueConsentir()`.

---

### 8. El acceso a las preferencias de cookies vive solo en la Política

**Qué falta:** decidir si el enlace para reabrir el panel va también en el pie.

**Dónde:** hoy está en `politica-de-cookies.php`, como botón. Ponerlo en el pie
es un `<button data-cookies="configurar">` en la columna «Legal» de
`includes/layout.php`: el script ya escucha ese atributo en toda la página, así
que no hace falta nada más.

**Por qué no se puso ya:** REQ-00001 fija la estructura del pie columna por
columna, y añadir una entrada que no está en ese requerimiento es cambiar algo
que ya se aprobó. Es una línea el día que producto lo pida.

---

### 7. Las direcciones `.php` siguen respondiendo

**Qué pasa:** `/buscar.php` y `/actividades` sirven la misma página. Lo canónico
sería que la primera redirigiera a la segunda.

**Por qué se dejó así:** hay formularios que hacen POST contra su propio `.php`,
y una redirección 301 los convierte en GET, perdiendo lo enviado. Requiere
revisar formulario por formulario.

**Dónde:** el bloque de reescritura en `.htaccess` de la raíz.
