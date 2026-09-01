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

### 2. El correo público de OMDARA

**Qué falta:** una dirección de correo que lea alguien, para publicarla en las
páginas legales.

**Los tres textos legales ya están escritos**, y con eso cae el bloqueo que
encabezaba esta lista desde REQ-00001. Términos y Condiciones (REQ-00014, once
cláusulas), Aviso de Privacidad (REQ-00015, ocho cláusulas — con la salvedad de
2m) y Política de Cookies (REQ-00016, nueve cláusulas). Lo que queda es un dato
suelto que **dos de los tres documentos aprobados piden y ninguno da**:

- El **Aviso de Privacidad**, cláusula 5, manda ejercer los derechos ARCO
  «enviando una solicitud al correo electrónico de contacto de omdara».
- La **Política de Cookies**, cláusula 9, deja un hueco literal donde va:
  `[correo de omdara]`.

**Qué hace el sitio mientras tanto:** `correoContacto()` nace vacía y, mientras
lo esté, la Política ofrece el formulario de `/contacto`, que sí llega a los
administradores. Lo que **no** hace es publicar el `no-responder@`: un buzón que
nadie lee, impreso en un documento que promete atender consultas, es peor que no
poner ninguno.

**Para cerrarlo:** crear el buzón en cPanel → Email Accounts y ponerlo en
`includes/config.local.php`, en **los dos entornos**:

```php
'correo' => ['contacto' => 'hola@…'],
```

No hace falta tocar código: en cuanto tenga valor, la cláusula 9 lo imprime y el
enlace al formulario desaparece solo.

> **Ojo:** tiene que ser un buzón del dominio principal. El subdominio de
> pruebas no tiene MX, así que ninguna dirección suya recibe correo — es el
> mismo problema que ya se documentó para el remitente.

---

### 2n. La Política de Cookies ya no lleva la tabla dentro

**Qué cambió:** hasta REQ-00016 la página publicaba catorce cookies con nombre,
proveedor y duración, y afirmaba que ese inventario era definitivo. **No lo
era**, y ahora ya no lo dice.

**Por qué se movió:** el propio texto aprobado lo resuelve por su cuenta. La
cláusula 7 remite el detalle «al mecanismo de gestión de cookies implementado en
el sitio», así que la tabla no tiene por qué vivir dentro del documento legal.
Bajó a un **anexo informativo**, separado y rotulado como tal, partido en dos:

- **Las que pone OMDARA** (`wh_sesion`, `omdara_cookies`): seguras. Salen del
  código de la plataforma.
- **Las que pueden poner Google, Microsoft y Meta**: de la documentación de cada
  proveedor, con la salvedad delante. Sigue abierto en el punto 6.

**Para cerrarlo:** ver el punto 6. Cuando esa lista esté comprobada contra la
instalación real, decidir si se confirma en el anexo o si se lleva al panel de
preferencias, que es donde la cláusula 7 dice que está.

---

### 2ñ. Dos cosas que los Términos no dicen y el sitio sí hace

**Qué pasa:** no es un error del documento —puede ser deliberado—, pero conviene
que lo mire quien asesora.

- El plazo de **24 horas** para corregir una actividad publicada, pasado el cual
  se congela.
- Que la moderación es **posterior**, no previa.

Las dos son reglas que obligan a los organizadores y que hoy solo constan en las
preguntas frecuentes y en «¿Cómo funciona?». Ver también 2l.

---

### 2m. El Aviso de Privacidad cubre menos de lo que el sitio hace

**Qué pasa:** el Aviso publicado (REQ-00015) describe el tratamiento de los
datos del formulario **«Contactar Organizador»**. El sitio trata más datos que
esos, y en más momentos.

**Por qué está tal cual:** el criterio de aceptación del requerimiento dice
«el contenido se muestra completo y sin modificaciones». No se toca ni una coma,
igual que en los Términos. Esto no es una corrección: es la lista para que la
mire quien asesora y decida si falta o si es deliberado.

**Lo que el sitio guarda hoy, y el Aviso no menciona:**

- **Nombre y correo** de quien se registra, para poder entrar y para avisar al
  organizador de sus mensajes.
- **Identificador de Google** de quien entra con esa cuenta. Se guarda el `sub`,
  no el correo, porque el `sub` no cambia.
- **Códigos de acceso** enviados por correo. Se guardan cifrados y caducan solos.
- **Dirección IP** de quien reporta una actividad, escribe a un organizador o
  usa el formulario de `/contacto`, para limitar envíos repetidos.
- **Nombre, correo, teléfono y mensaje** de quien escribe por `/contacto`, que es
  un formulario distinto del que el Aviso describe y va a OMDARA, no a un
  organizador.
- **Datos de la actividad** que publica un organizador, públicos por definición,
  y desde REQ-00012 también su **teléfono, Instagram y sitio web** guardados en
  la cuenta.
- **Datos de navegación** que reciben terceros cuando el visitante los acepta:
  Google Analytics 4, Microsoft Clarity y Meta Pixel, más OpenStreetMap para los
  mapas. Están documentados en la Política de Cookies, pero el Aviso no los
  nombra.

**Tres desajustes concretos, además del inventario:**

1. **La cláusula 8 ata el consentimiento al formulario de contacto.** Pero desde
   REQ-00008 **nadie crea cuenta sin aceptar este Aviso**, y ese momento no
   aparece en el documento. La casilla del alta enlaza aquí igualmente.
2. **La cláusula 5 no da a dónde escribir.** Dice «al correo electrónico de
   contacto de omdara», sin dirección. Los derechos ARCO se ejercen por una vía
   concreta; hoy la más cercana es `/contacto`, que el Aviso tampoco nombra.
3. **No hay responsable identificado.** La LFPDPPP pide nombre y domicilio de
   quien trata los datos. El documento no los trae.

**Para cerrarlo:** decidir si el Aviso se amplía. Si se amplía, el texto nuevo
entra en `$clausulas` dentro de `aviso-de-privacidad.php` y **hay que subir la
fecha de `$legalActualizado`** — es lo que permite saber qué versión aceptó cada
persona (ver 2e).

---

### 2b. Ejecutar la migración 15 (teléfono en los mensajes al organizador)

**Qué falta:** correr `database/migracion-15-telefono-en-contactos.sql` en
phpMyAdmin, en pruebas y en producción.

**Qué pasa mientras tanto:** nada visible. El formulario funciona, el teléfono
llega al correo del organizador —que es para lo que se pide— y solo se pierde en
la base. `crearContacto()` comprueba si la columna existe antes de escribir, para
que publicar el código antes de aplicar la migración no tire el formulario
entero, que es lo que pasó con las tablas la primera vez.

**Para cerrarlo:** ejecutar el `.sql` en los dos entornos y luego quitar el rodeo
de `crearContacto()` en `includes/contacto.php` —el `columnaExiste()` y la rama
sin teléfono—, que vuelve a ser un único INSERT. `columnaExiste()` en
`includes/db.php` se queda: no estorba y la próxima migración a mano lo
agradecerá.

---

### 2d. Ejecutar la migración 16 (aceptación de Términos y Aviso)

**Qué falta:** correr `database/migracion-16-aceptacion-legal.sql` en phpMyAdmin,
en pruebas y en producción.

**Qué pasa mientras tanto:** la casilla se pide y se exige igual —nadie crea
cuenta sin marcarla—, pero la fecha no queda registrada y en el log aparece un
aviso por cada alta. `registrarAceptacionLegal()` lo comprueba antes de escribir,
por el mismo motivo que la 15: publicar el código antes de aplicar la migración
no puede significar «nadie puede crear cuenta».

**Cuanto antes mejor**, más que la 15: aquí lo que se pierde no es un dato de
contacto, es la prueba de que alguien aceptó.

**Para cerrarlo:** ejecutar el `.sql` y quitar el `columnaExiste()` de
`registrarAceptacionLegal()` en `includes/auth.php`.

---

### 2f. Ejecutar la migración 17 (teléfono del organizador)

**Qué falta:** correr `database/migracion-17-telefono-organizador.sql` en
phpMyAdmin, en pruebas y en producción.

**Qué pasa mientras tanto:** «Mi cuenta → Información de contacto» funciona y
deja editar el nombre; el campo de teléfono sencillamente no aparece, ni en la
página ni en el panel de administración.

**Para cerrarlo:** ejecutar el `.sql`. La comprobación vive en
`camposContactoDisponibles()` (`includes/auth.php`) y cubre también la migración
18, así que se quita cuando estén aplicadas las dos.

---

### 2h. Ejecutar la migración 18 (Instagram y sitio web del organizador)

**Qué falta:** correr `database/migracion-18-contacto-organizador.sql` en
phpMyAdmin, en pruebas y en producción. Va con la 17: son la misma sección.

**Qué pasa mientras tanto:** la sección «Información de contacto» del formulario
de publicar aparece igual, pero solo con los campos cuya columna exista. Sin
ninguna de las dos migraciones, solo se ve el nombre.

**Para cerrarlo:** ejecutar el `.sql` y quitar `camposContactoDisponibles()`,
dejando que todo use `camposContactoOrganizador()` directamente.

---

### 2i. ¿El contacto del organizador se enseña en la ficha?

**Qué falta:** una decisión de producto.

**Qué hay hoy:** Instagram, WhatsApp y sitio web del organizador se guardan en su
cuenta (REQ-00012) y **no se publican en ninguna parte**. Se ven en «Mi cuenta» y
en el panel de administración, nada más.

**Por qué se dejó así:** REQ-00012 solo pide guardarlos y reutilizarlos en las
siguientes publicaciones; no dice que se muestren. Y REQ-00009 dice lo contrario
de forma expresa —que esa sección no crea un perfil público—. Además el botón de
WhatsApp de la ficha se quitó a propósito hace unas semanas.

**Si la intención era volver a enseñarlos**, es otro requerimiento: hay que
decidir cuáles se publican, con qué aviso a quien los escribe, y qué pasa con
las actividades ya publicadas de quien nunca aceptó que su número saliera.

---

### 2g. Cambiar el correo desde «Mi cuenta»

**Qué falta:** el flujo para que alguien cambie su propio correo.

**Dónde:** `mi-cuenta.php`. Hoy el correo se enseña pero no se edita, y la
página explica por qué en pantalla.

**Por qué no se hizo ya:** aquí no hay contraseñas — el correo *es* la
credencial, y el código de acceso va justo a ese buzón. Un cambio sin verificar
antes el buzón nuevo deja a esa persona fuera de su cuenta **para siempre**, sin
ninguna forma de recuperarla: basta un dedazo. No es una validación de formato
lo que falta, es un flujo entero.

**Lo que hace falta para cerrarlo:**

1. Comprobar que el correo nuevo no tiene ya cuenta.
2. Mandar un código **al correo nuevo** y guardar el cambio como pendiente.
3. Cambiarlo solo al confirmar ese código.
4. Avisar al correo viejo de que se cambió, por si no fue su dueño quien lo
   pidió. Es el paso que suele faltar y el único que detecta un secuestro.

Mientras tanto, la página dice que se escriba para cambiarlo. Es honesto, pero
no aguanta muchos organizadores.

---

### 2e. Qué versión de los documentos aceptó cada persona

**Qué falta:** decidir si hace falta guardarlo, y con qué numeración.

**Qué hay hoy:** solo `usuarios.acepto_legal_en`, la fecha. No se guarda versión
**a propósito**: los dos documentos no están escritos, así que una columna de
versión solo podría guardar un número inventado.

**Cuándo empieza a hacer falta:** en cuanto los textos existan y cambien. A
partir de ahí, «aceptó el 14 de agosto» deja de decir qué aceptó, y toca una
migración más —columna de versión— y decidir si un cambio de documento obliga a
volver a pedir la aceptación.

**Y una decisión aparte:** las cuentas creadas antes de REQ-00008 tienen
`acepto_legal_en` en NULL, que es información correcta y no un hueco. Hay que
decidir si se les pide aceptar al entrar, o si se da por bueno lo que ya estaba.
El código NO las obliga: obligar a las cuentas existentes habría dejado fuera a
todo el mundo el día del despliegue.

---

### 3. Traducción al inglés — REQ-00002, fases 2 a 5

**Qué está hecho (fase 1):** los cimientos. El idioma lo decide la dirección,
`rutasSitio()` es el mapa único del que salen enrutado, enlaces, hreflang y
sitemap, el selector conserva la página al cambiar de idioma, y la cabecera y
el pie —que salen en todas las páginas— están traducidos.

**Qué falta, por fases:**

| Fase | Alcance | Depende de |
|---|---|---|
| 2 | Portada y buscador — **hecho, 2026-09-01** | Textos EN |
| 3 | Formulario de actividad — **hecho, 2026-09-01** (`evento-editar.php` traducido pero sin ruta `/en` propia, ver nota) | Textos EN |
| 4 | Ficha de actividad, contacto, login y correos | Textos EN |
| 5 | Contenido dinámico: título y descripción de cada actividad | Migración de base |

**Las 23 categorías — hecho, 2026-09-01.** `categoriasMenu()` acepta idioma
y devuelve la etiqueta inglesa sin tocar `eventos.categoria` ni el `?cat=` de
las direcciones, que se quedan en español siempre. Se traduce sola en
cualquier página que la use (portada, `buscar.php`, el formulario de alta).

**La fase 5 necesita una migración**: columnas para título y descripción en
inglés, más un comportamiento de reserva cuando el organizador no las rellene.
El requerimiento pide expresamente no traducir automáticamente lo que escribió
el organizador.

**`evento-editar.php` no tiene ruta limpia en `/en`**: no está en
`rutasSitio()` —es `evento-editar.php?id=N`, no una dirección fija—, así que
`idiomaActual()` no tiene de dónde sacar el idioma y siempre cae al español,
aunque su texto ya está traducido en `includes/idiomas/en.php` y listo para
cuando exista esa ruta. `evento-nuevo.php` sí tiene ruta propia
(`/publish-an-activity`) y sí se ve en inglés.

**Bloqueado por:** los textos finales ES/EN y los textos SEO, que el propio
REQ-00002 declara como dependencia y todavía no se han entregado. En
`includes/idiomas/en.php` está traducida la interfaz funcional —navegación,
botones, etiquetas—; falta el texto editorial: el lema de la marca y las meta
descriptions de cada página. La portada es la primera excepción: su copy
editorial (título del hero, subtítulo, textos del carrusel) se tradujo sin
esperar ese entregable —decisión explícita del 2026-09-01, no un cambio de
la regla para el resto de páginas.

**Sobre promover a producción:** el requerimiento prohíbe la traducción
parcial, y hoy el inglés cubre el armazón, la portada, el buscador y el
formulario de actividad (fases 2 y 3 completas) pero no la ficha, el
contacto, el login ni los correos —fases 4 y 5 siguen sin empezar—. El sitio
ya vive en el dominio final (`omdara.com.mx`, ver
`docs/operacion.md`); esta nota queda para que quede claro que esa promoción
se hizo sin cerrar esta parte del requerimiento, no para sugerir que ya se
cumplió.

---

### 2j. Ejecutar la migración 19 (motivo y estado en los mensajes de contacto)

**Qué falta:** correr `database/migracion-19-contacto-motivo.sql` en phpMyAdmin,
en pruebas y en producción.

**Qué pasa mientras tanto:** el formulario de `/contacto` funciona entero —pide
el motivo, pide la actividad cuando toca, y los dos van en el correo al
administrador, que es lo que hace que alguien actúe—. Lo único que se pierde es
guardarlos en la base, y en el panel esas columnas salen con un guion.

**Para cerrarlo:** ejecutar el `.sql` y quitar el `columnaExiste()` de
`crearContactoSitio()` en `includes/contacto.php`.

---

### 2k. Los mensajes de contacto se quedan en «Nuevo» para siempre

**Qué falta:** una pantalla para cambiar el estado de un mensaje.

**Qué hay hoy:** la columna `estado` con sus cuatro valores, y una pestaña
«Mensajes» en el panel de administración que los **lee**. Nadie puede pasar uno
a «En revisión», «Respondido» ni «Cerrado».

**Por qué se dejó así:** el propio requerimiento lo acota — «no es necesario
crear un sistema completo de tickets para el MVP; un registro básico es
suficiente». Se guarda el campo para no tener que migrar otra vez el día que se
haga, pero conviene saber que hoy no significa nada.

**Para cerrarlo:** un desplegable por fila en esa pestaña y un POST que lo
guarde. Media hora, cuando haya suficientes mensajes para que haga falta.

---

### 2l. Dos páginas dan por hecha una revisión previa que no existe

**Dónde:** las preguntas frecuentes y «¿Cómo funciona?». Los dos requerimientos
llegaron con el mismo supuesto, así que es una sola decisión.

**Qué pasa:** las FAQ traían cuatro respuestas construidas sobre una cola de
aprobación —«envía tu actividad para revisión», «revisamos cada publicación»,
«normalmente entre 24 y 72 horas hábiles», «te indicaremos el motivo por el que
no fue aprobado»— y «¿Cómo funciona?» traía dos de sus cuatro pasos —«Envía tu
actividad a revisión» y «Una vez aprobada»—. **Eso no es lo que hace el sitio.**
`publicarEvento()` pone la actividad en línea en el momento en que su dueño le
da a publicar, y la moderación es posterior: alguien reporta, un administrador
mira y, si toca, la oculta.

> **En «¿Cómo funciona?» es peor que en las FAQ**, y por eso se corrigió igual:
> esa página se lee ANTES de publicar. Quien la creyera esperaría un correo de
> aprobación, no revisaría que su actividad ya está pública, y descubriría el
> error cuando le escribiera la primera persona interesada. El paso 2 pasa a
> describir el que sí existe —la vista previa—, que además es donde de verdad se
> decide publicar.

**Qué se hizo:** las respuestas se reescribieron para decir lo que ocurre de
verdad. Publicarlas tal cual habría dejado a los organizadores esperando un
correo de aprobación que no llega, y a los visitantes creyendo que alguien
comprobó lo que están leyendo.

**Otras tres, por lo mismo:**

| Decía | Por qué no |
|---|---|
| «Puedes actualizar la información antes o después de su publicación» | Cierto a medias: después de publicar hay 24 horas y luego se congela |
| «Encontrarás sus datos de contacto o el botón para comunicarte» | Los datos del organizador no se publican (REQ-00009) |
| «Puedes ocultarlo desde tu panel» | Ocultar es una acción de administración; su dueño puede eliminarla dentro del plazo |

**Para cerrarlo, una de dos:**

1. **Producto confirma** que no habrá revisión previa → no hay nada que hacer:
   las respuestas de hoy ya son correctas.
2. **Se implementa la revisión previa** → es un requerimiento propio (cola de
   moderación, estado «en revisión», aviso al organizador cuando se aprueba o se
   rechaza con el motivo), y entonces se restituye la redacción original, que
   está en el requerimiento tal como llegó.

---

## Decisiones de diseño abiertas

### 2c. ¿El contacto tiene que flotar sobre la ficha?

**Qué falta:** decidir si «Contactar al organizador» abre una ventana encima de
la actividad —con la ficha detrás, difuminada— o sigue siendo una página propia.

**Qué hay hoy:** una página, `/contactar.php?id=`, con la tarjeta dibujada tal
como la enseña REQ-00007. En una captura no se distingue; la diferencia es que
hay una dirección y una recarga.

**Por qué se hizo así:** el envío se valida en el servidor como todo lo demás
—CSRF, captcha y límite por IP—. Una ventana que envía sin recargar necesita una
capa de JavaScript por encima de esos tres, y esa capa es justo donde se cuelan
los envíos sin validar. Además así funciona sin JavaScript, y un error de
validación tiene dónde volver con lo escrito dentro.

**Para cerrarlo si se quiere la ventana:** no hay que tirar nada. El mismo
formulario se carga dentro de un contenedor y se envía por `fetch`, con la página
actual como respaldo cuando falle. `.modal-overlay` ya existe en
`assets/css/portada.css`, sin usar, desde el prototipo.

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

**Dónde:** `politica-de-cookies.php`, la segunda tabla del anexo — «Las que
pueden poner Google, Microsoft y Meta». La primera, la de las dos cookies de
OMDARA, sí es segura: sale del código.

**De dónde sale lo que hay hoy:** de la documentación de Google, Microsoft y
Meta. Es lo más honesto que se puede afirmar antes de tener las tres activas con
tráfico real, y cubre el criterio de REQ-00003 —nombre, proveedor, finalidad,
duración y categoría— pero no es todavía «las cookies efectivamente generadas».

**Desde REQ-00016 ya no compromete al documento legal.** La tabla vive en un
anexo separado, con la salvedad escrita encima, y el texto de la Política remite
el detalle al panel de preferencias (ver 2n). Lo que antes era una afirmación
del documento ahora es información de apoyo etiquetada como provisional.

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

> **Si se lleva al pie, hay que copiar la condición.** Desde REQ-00016 el botón
> de la Política solo se pinta cuando `hayQueConsentir()`: sin herramientas
> configuradas no se pinta el diálogo, y el botón no abriría nada. Un botón
> muerto en el pie de todas las páginas es peor que uno en una sola.

---

### 7. Las direcciones `.php` siguen respondiendo

**Qué pasa:** `/buscar.php` y `/actividades` sirven la misma página. Lo canónico
sería que la primera redirigiera a la segunda.

**Ya no es un problema de posicionamiento.** Desde REQ-00006 las dos declaran
`<link rel="canonical">` a `/actividades`, así que Google indexa una sola. Y la
ficha sí redirige de verdad: `/evento.php?id=7` manda un 301 a
`/actividad/{slug}`, porque ahí el POST se pudo separar del GET —los
formularios de la ficha postean contra ella misma y una redirección los
convertiría en GET, perdiendo lo enviado—.

**Lo que falta:** hacer lo mismo con el resto de páginas. Requiere revisar
formulario por formulario cuáles postean contra su propio `.php`.

**Dónde:** el bloque de reescritura en `.htaccess` de la raíz, o el mismo patrón
de `evento.php` (redirigir solo en GET).
