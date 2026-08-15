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

### 6. Analítica sin consentimiento

**Qué pasa:** los scripts de GA4, Clarity y Meta Pixel se cargan sin pedir
permiso.

**Hoy es defendible** porque el sitio mira a México. Deja de serlo en cuanto
haya visitantes de la UE: ahí hace falta un banner que los bloquee hasta que
alguien acepte.

**Dónde:** `includes/layout.php`, donde se imprimen los scripts de analítica.

---

### 7. Las direcciones `.php` siguen respondiendo

**Qué pasa:** `/buscar.php` y `/actividades` sirven la misma página. Lo canónico
sería que la primera redirigiera a la segunda.

**Por qué se dejó así:** hay formularios que hacen POST contra su propio `.php`,
y una redirección 301 los convierte en GET, perdiendo lo enviado. Requiere
revisar formulario por formulario.

**Dónde:** el bloque de reescritura en `.htaccess` de la raíz.
