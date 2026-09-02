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

**El código ya está listo, 2026-09-01** — mismo patrón que el correo público
(punto 2): una llave `'redes'` en `includes/config.local.php` (con su
ejemplo en `config.local.example.php`), fuera de git y distinta por
entorno. `includes/layout.php` valida cada URL con `FILTER_VALIDATE_URL` y
solo pinta el icono correspondiente si hay una válida; si las tres siguen
vacías, `.foot-redes` no se pinta en absoluto. Ya no queda `href="#"` en
ningún caso —un enlace muerto es peor para accesibilidad y SEO que no
mostrar el icono—.

**Para cerrarlo:** poner las tres URLs (o las que existan) en
`includes/config.local.php`, en **los dos entornos**:

```php
'redes' => [
    'instagram' => 'https://instagram.com/...',
    'facebook'  => 'https://facebook.com/...',
    'whatsapp'  => 'https://wa.me/521...',
],
```

No hace falta tocar código: en cuanto tenga valor, el icono aparece solo.

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

**El cliente mandó el requerimiento completo, 2026-09-02** —«Configuración y
uso de correos de Omdara»—, en dos entregas: primero solo el punto 1, después
los tres. Define tres buzones:

| Correo | Visibilidad | Uso |
|---|---|---|
| `hola@omdara...` | PÚBLICO | Dudas generales, comentarios, propuestas, alianzas |
| `soporte@omdara...` | PÚBLICO | Login, cuenta, publicar/editar, errores del sitio |
| `admin@omdara...` | **PRIVADO** | Hosting, dominio, WordPress, herramientas internas — **nunca en el frontend** |

**`admin@` no tiene llave en `config.local.php` a propósito.** No hay ningún
sitio en el código que lo publicaría, así que no hace falta una comprobación
para no filtrarlo: simplemente no existe la variable que alguien podría poner
donde no debe. Es un correo para cuentas de servicios externos (hosting,
dominio, WordPress) y uso interno, no algo que este sitio necesite leer — si
algún día hiciera falta agregarlo aquí para un uso interno de verdad, primero
hay que revisar que ningún `include` lo imprima antes de darlo por seguro.

**`hola@` — dónde se enseña, 2026-09-02:**
- **El pie**, columna «Ayuda», etiquetado «General:» —con dos correos en la
  misma columna hacía falta distinguirlos; con uno solo bastaba la dirección a
  secas—.
- **La página `/contacto`**, arriba del formulario: «Si lo prefieres,
  escríbenos directo a…». El formulario no se toca —pide motivo y actividad,
  que un correo suelto pierde—.
- La cláusula 9 de la Política de Cookies, que ya lo usaba desde antes.

**`soporte@` — dónde se enseña, 2026-09-02** (`correoSoporte()`, mismo patrón
que `correoContacto()` en `includes/correo.php`):
- **El pie**, misma columna, etiquetado «Soporte:».
- **La FAQ** (`preguntas-frecuentes.php`), una pregunta nueva: «¿Tengo un
  problema técnico, qué hago?», con exactamente los casos que el cliente
  listó (entrar, cuenta, publicar/editar, errores). Sin el buzón configurado,
  cae al formulario de `/contacto` en vez de una pregunta sin respuesta.
- **El panel del organizador** (`mis-eventos.php`), una nota bajo la tabla de
  actividades: es donde alguien se topa con un problema para publicar o
  editar, que es justo el caso de uso.

Las tres direcciones siguen el mismo patrón que los iconos de redes: sin
configurar, no se pinta nada, nunca un enlace muerto. Se necesitó CSS nuevo
en dos sitios más (`.contacto-correo a`, `.evergreen-note a`) porque la regla
global `a{color:inherit; text-decoration:none}` deja cualquier dirección
impresa así indistinguible del texto plano.

**El enrutamiento del formulario `/contacto` según el motivo — cerrado,
2026-09-02.** El cliente lo completó en dos mensajes seguidos:

| Motivo | Destino |
|---|---|
| Pregunta general | **Solo** `hola@` |
| Soy organizador | **Solo** `hola@` |
| Problema con una actividad | Administradores **+** `soporte@` |
| Problema con mi cuenta | Administradores **+** `soporte@` |
| Reportar contenido | Administradores **+** `soporte@` |
| Otro | Administradores **+** `soporte@` |

`motivosAHola()` (`includes/contacto.php`) es la única fuente de la primera
fila: los dos motivos que van solo a `hola@`, sin avisar a nadie más. Todo lo
demás cae en `avisarContactoSitio()` (antes `avisarAdminsContactoSitio()`,
renombrada porque ya no siempre avisa solo a administradores): a los
administradores de siempre y, si `soporte@` ya está configurado, **también**
a `soporte@` —el cliente pidió «además de», no «en vez de»—.

Mientras ninguno de los dos buzones institucionales esté configurado, el
comportamiento es exactamente el de antes de este cambio: todo llega a los
administradores y nada se pierde. En cuanto se configure `hola@`, los dos
primeros motivos dejan de avisar a los administradores —es la única
excepción a «nunca se pierde», y es la que el cliente pidió explícitamente—.

**Dos preguntas del cliente siguen sin responder**:
1. Qué son los «botones de contacto general» y la «sección de información
   general» donde debería ir `hola@` (item 1 original). No hay un botón ni
   una sección con ese nombre hoy; hace falta que digan qué pantalla es.
2. **«Mensajes de ayuda relacionados con problemas técnicos, cuando
   corresponda»** (item 2) se dejó fuera a propósito: el propio requerimiento
   lo redacta como discrecional, y el candidato más claro —el mensaje
   genérico «Algo salió mal» tras un fallo de login— es una cadena de
   catálogo (`login.error.generico`) compartida entre ES/EN sin mecanismo
   para insertar una dirección dentro. Si quieren que ahí también salga
   `soporte@`, es un cambio concreto y pequeño, pero hay que decirlo.

**Para cerrarlo:** crear los dos buzones PÚBLICOS en cPanel → Email Accounts
y ponerlos en `includes/config.local.php`, en **los dos entornos**:

```php
'correo' => [
    'contacto' => 'hola@…',
    'soporte'  => 'soporte@…',
],
```

No hace falta tocar código: en cuanto tengan valor aparecen solos en todos
los sitios de arriba, y los enlaces de respaldo al formulario desaparecen
solos.

> **Ojo:** tiene que ser un buzón del dominio principal. El subdominio de
> pruebas no tiene MX, así que ninguna dirección suya recibe correo — es el
> mismo problema que ya se documentó para el remitente. Y tiene que existir
> ANTES de configurarlo: publicar en el pie de todas las páginas una dirección
> que rebota es peor que no publicar ninguna.

> **Lo que este cambio NO cubre:** la cláusula 5 del Aviso de Privacidad, que
> manda ejercer los derechos ARCO «al correo electrónico de contacto de
> omdara» sin decir cuál. Ese texto es literal y no se toca —el criterio de
> aceptación de REQ-00015 dice «sin modificaciones»—, así que la dirección no
> se imprime ahí aunque esté configurada. Si el cliente quiere que el correo
> aparezca también en el Aviso, es una modificación del texto legal y entra
> por el punto 2m, no por aquí.

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

### 2b/2d/2f/2h/2j. Rodeos de las migraciones 15, 16, 17, 18 y 19 — cerrado

**Migraciones ejecutadas y código de compatibilidad quitado**, 2026-09-01.
`crearContacto()` y `crearContactoSitio()` (`includes/contacto.php`),
`registrarAceptacionLegal()` (`includes/auth.php`) y la función
`camposContactoDisponibles()` —que filtraba qué campos enseñar en «Mi
cuenta» y en el formulario de publicar— ya no comprueban `columnaExiste()`:
escriben directo, y los tres sitios que llamaban a
`camposContactoDisponibles()` (`guardarContactoOrganizador()` en
`includes/auth.php`, `mi-cuenta.php`, `includes/form-evento.php`) pasan a
usar `camposContactoOrganizador()` sin filtrar. `columnaExiste()`
(`includes/db.php`) se queda sin llamadas por ahora —no estorba, y la
próxima migración a mano lo puede volver a usar—.

> **Ojo con el entorno local de desarrollo:** al hacer esta limpieza se
> descubrió que las migraciones 15 a 19 nunca se habían aplicado en la base
> local de XAMPP —José las confirmó en pruebas y producción, pero esas dos
> son entornos remotos, distintos del local—. El código limpio, sin el
> rodeo, rompía ahí con «Unknown column». Se aplicaron las cinco migraciones
> directo en la base local (mismo mecanismo que ya se usó para la 21) antes
> de dar la limpieza por probada. **Vale la pena recordar esto la próxima
> vez que se declare una migración de producción como excusa para quitar
> una comprobación**: hay que confirmar también el entorno local antes de
> asumirlo.
>
> **Además se encontró que `columnaExiste()` estaba rota de verdad en este
> entorno** —no por la migración que faltaba, sino aparte—: su
> `SHOW COLUMNS FROM \`tabla\` LIKE ?` fallaba con «Syntax error… near '?'»
> en cada llamada y siempre devolvía `false`, sin importar si la columna
> existía. Como ahora no queda ninguna llamada a esa función, no bloqueó
> nada de esta limpieza, pero si alguna migración futura vuelve a usarla,
> primero hay que arreglarla —probablemente cambiando a interpolar el
> nombre de la columna, ya validado contra `[A-Za-z0-9_]+`, en vez de
> pasarlo como parámetro del `LIKE`—.

Probado de punta a punta contra Apache+MySQL reales: envío de
`/contactar/{id}` con teléfono, envío de `/contacto` con motivo, y un alta
nueva completa por correo confirmando que `usuarios.acepto_legal_en` queda
con fecha. Las tres pruebas insertaron y borraron sus propias filas.

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
| 3 | Formulario de actividad — **hecho, 2026-09-01** (`evento-editar.php` con ruta `/edit-activity/{id}` propia desde el mismo día, ver nota) | Textos EN |
| 4 | Ficha, contacto/reportar y login/registro — **hecho, 2026-09-01**. Correo de código de acceso también; los de organizador/admin se quedan en español a propósito, ver nota | Textos EN |
| 5 | Contenido dinámico: título y descripción de cada actividad — **hecho, 2026-09-01** | Migración de base |

**Las 23 categorías — hecho, 2026-09-01.** `categoriasMenu()` acepta idioma
y devuelve la etiqueta inglesa sin tocar `eventos.categoria` ni el `?cat=` de
las direcciones, que se quedan en español siempre. Se traduce sola en
cualquier página que la use (portada, `buscar.php`, el formulario de alta).

**La fase 5** agregó `titulo_en`/`descripcion_en` (migración 21, opcionales)
y **una ruta nueva, `/activity/{slug}`**, paralela a `/actividad/{slug}`
(`router.php`) — sin ella, el contenido en inglés no tenía forma de
mostrarse a nadie. `urlEvento()` genera el prefijo según el idioma;
`urlEquivalente()` ahora deja que una página fuera de `rutasSitio()`
declare su propia equivalencia entre idiomas (`evento.php` la usa, así que
el selector de idioma se queda en la misma actividad en vez de mandar al
inicio). `tituloEvento()`/`descripcionEvento()` centralizan la reserva al
español cuando el organizador no escribió versión en inglés —nunca se
traduce nada solo—.

**Dos fugas de idioma más, encontradas y evitadas al implementar la fase
5**: los correos `avisarAdminsNuevaActividad()` y `avisarOrganizador()`
ahora fijan español explícito en su enlace a la ficha (mismo patrón que
`motivosContacto()`, abajo); y `buscar-datos.php` —un `fetch()` directo que
no pasa por el enrutador— nunca sabía en qué idioma estaba la página que lo
llamaba, así que las tarjetas de resultados se quedaban en español pese a
`tituloEvento()`. `buscar.js` le manda ahora el idioma explícito por
parámetro. De paso se encontró un texto de la fase 2 que se había quedado
suelto: "Gratis"/"Ver actividad →" en `assets/js/tarjetas.js` (compartido
por portada y buscador, JS global y no de una página) — resuelto con un
objeto `TARJETA_T` impreso en `includes/layout.php` para todo el sitio.

**Las seis páginas sin ruta limpia en `/en` ya la tienen, 2026-09-01.**
`evento-editar.php`, `contactar.php` y `reportar.php` ganaron rutas por id
—`/editar-actividad/{id}` y `/edit-activity/{id}`, `/contactar/{id}` y
`/contact-organizer/{id}`, `/reportar/{id}` y `/report/{id}` (`router.php`,
un solo bloque combinado con el mismo patrón que `/actividad/{slug}`; sin
slug porque no son direcciones para compartir ni indexar). `login.php`,
`codigo.php` y `completar-registro.php` entraron a `rutasSitio()` como
páginas fijas —`/iniciar-sesion`/`/sign-in`, `/codigo-de-acceso`/
`/access-code`, `/completar-registro`/`/complete-registration`—, y por eso
**no** se sumaron a `sitemap.php`: ese archivo solo publica lo que trae
entrada en su propio `$prioridades`, así que las tres se quedan fuera del
sitemap sin necesidad de marcarlas aparte.

`urlContactar()`, `urlReportar()` y `urlEditarEvento()` (`includes/config.php`)
generan estas direcciones según el idioma, igual que `urlEvento()`; los
enlaces que antes escribían `evento-editar.php?id=`, `contactar.php?id=` y
`reportar.php?id=` a mano —en `evento.php`, `admin.php`, `moderacion.php` y
`mis-eventos.php`— pasan todos por ahí ahora. Las tres páginas de acción
declaran su propia `$GLOBALS['urlEquivalente']` para que el selector de
idioma se quede en el mismo formulario.

Todos los `redirigir('/login.php')`, `redirigir('/codigo.php')` y
`redirigir('/completar-registro.php')` sueltos por el flujo de entrada
(`login.php`, `codigo.php`, `completar-registro.php`, `includes/auth.php`)
pasaron a `redirigir(url('login'))` etc., para que alguien que entra desde
`/sign-in` no acabe a mitad de camino en español. El único hueco que quedaba
era **Google**: `google-callback.php` tiene dirección fija por Google
Console y no puede pasar por el enrutador, así que no tenía de dónde sacar
el idioma de quien volvía. Se resolvió pasándolo como `?idioma=` al salir
hacia Google (`login.php` → `google-redirect.php`) y guardándolo en sesión
de un solo uso para que `google-callback.php` lo recupere a la vuelta —y con
`$GLOBALS['idioma']` fijado ahí mismo, hasta los mensajes de error que arma
`resolverGoogle()` (`includes/auth.php`) salen en el idioma correcto en vez
de en español fijo.

**Los correos a organizadores y administradores se quedan en español fijo, a
propósito** (`includes/correo.php`, `includes/contacto.php`,
`includes/eventos.php`, `includes/moderacion.php`): van siempre a la misma
gente del negocio, así que traducirlos según el idioma de quien disparó el
aviso —el visitante que escribió el formulario— produciría un correo mitad
inglés mitad español para quien siempre lee en español. No es una traducción
que falte: es una decisión ya tomada.

**El código de acceso sí se tradujo, 2026-09-01.** Era la única plantilla que
sí depende del idioma de quien la recibe —va a cualquier visitante que
intente entrar, no a un organizador o admin fijo—, y el motivo por el que
seguía bloqueada ya no aplicaba: hacía falta saber el idioma de la página que
disparó el correo, y desde que `login.php` y `codigo.php` tienen ruta `/en`
propia (ver arriba), `idiomaActual()` en `enviarCodigoAcceso()`
(`includes/correo.php`) ya refleja el idioma real de quien lo pidió. Las
llaves nuevas son `correo.codigo.asunto`/`correo.codigo.cuerpo`, con
`{codigo}`/`{minutos}`/`{marca}` como marcadores —`strtr()` y no `sprintf()`,
porque `{marca}` se repite dentro del cuerpo y el orden posicional se
volvía frágil—. Probado de punta a punta con un POST real a `/sign-in`: en
entorno local el correo no se manda de verdad, se escribe en el log de
Apache («=== CORREO (no enviado, entorno local) ===»), y ahí se confirmó el
asunto y cuerpo completos en inglés.

Se encontró y evitó un caso real de fuga de idioma en el camino:
`motivosContacto()`, que alimenta tanto el formulario como el correo de
aviso a los admins, ahora fija español explícito para el correo
(`t($clave, 'es')`) en vez de heredar el idioma de quien escribió.

**Bloqueado por:** los textos finales ES/EN y los textos SEO, que el propio
REQ-00002 declara como dependencia y todavía no se han entregado. En
`includes/idiomas/en.php` está traducida la interfaz funcional —navegación,
botones, etiquetas—; falta el texto editorial: el lema de la marca y las meta
descriptions de cada página. La portada es la primera excepción: su copy
editorial (título del hero, subtítulo, textos del carrusel) se tradujo sin
esperar ese entregable —decisión explícita del 2026-09-01, no un cambio de
la regla para el resto de páginas.

**Actualización 2026-09-02 — el cliente mandó el copy del carrusel y de «Cómo
funciona».** Dos entregas de texto editorial:

- **Carrusel de la portada:** 4 titulares+subtítulos nuevos, pensados como EL
  mensaje de cada diapositiva —el cuarto dirigido a quien organiza, no a quien
  busca—. Antes el h1/subtítulo eran fijos y solo rotaba una etiqueta pequeña
  de esquina (ambientación, sin mensaje); implementarlo tal cual mandó el
  cliente significaba que el h1 rotara con el carrusel, así que se preguntó y
  se confirmó ese cambio de diseño. Hecho: `index.php` guarda cada par en
  `data-titulo`/`data-sub` de su `.slide`, `assets/js/inicio.js` los copia a
  `#heroTitulo`/`#heroSub` al girar, y `includes/idiomas/{es,en}.php` traen
  las 8 llaves nuevas (`inicio.hero.slideN_titulo`/`slideN_sub`) en los dos
  idiomas. La etiqueta de esquina (`.slide-chip`) se quitó entera —ya no tenía
  contenido que mostrar— junto con su CSS.
- **«Cómo funciona» (`como-funciona.php`):** los pasos y motivos que mandó el
  cliente ya coincidían casi palabra por palabra con lo que estaba escrito —no
  se tocaron—. Lo que sí faltaba era el «CTA final»: la franja de cierre decía
  «Descubre. Conecta. Vive nuevas experiencias.» y ahora dice «Da visibilidad
  a tu experiencia de bienestar. / Publica tu actividad y conecta con
  personas que buscan nuevas experiencias.», tal como lo mandó. Esta página
  sigue sin `t()`/`et()` —el mismo hueco de `preguntas-frecuentes.php`—, así
  que el cambio quedó solo en español, igual que el resto de su texto.

Sigue bloqueado el resto: el lema de marca y las meta descriptions de las
demás páginas, que es lo que falta para poder decir que el inglés cubre
también el copy editorial completo y no solo la portada.

**Sobre promover a producción:** el requerimiento prohíbe la traducción
parcial. Hoy el inglés cubre el armazón, la portada, el buscador, el
formulario de actividad, la ficha, contacto/reportar, login/registro, el
contenido bilingüe por actividad y el correo del código de acceso —fases 2 a
5 completas—, salvo los correos a organizadores y administradores, que se
quedan en español a propósito (ver nota arriba). El sitio ya vive en el
dominio final
(`omdara.com.mx`, ver `docs/operacion.md`); esta nota queda para que quede
claro que esa promoción se hizo sin cerrar esta parte del requerimiento, no
para sugerir que ya se cumplió.

**Migración 21 ejecutada** (`titulo_en`/`descripcion_en` en `eventos`) —
confirmado por José el 2026-09-01, en pruebas y producción.

---

### 2j. Quitar el rodeo de la migración 19 (motivo y estado en los mensajes de contacto)

**Migración ejecutada** — confirmado por José el 2026-09-01, en pruebas y
producción.

**Qué queda:** `crearContactoSitio()` (`includes/contacto.php`) sigue
comprobando `columnaExiste()` antes de escribir `motivo`, `actividad_nombre`
y `estado`. Se puede quitar esa comprobación.

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

### 2o. Ejecutar las migraciones 22 y 23 (métricas de "Contactar al organizador")

**Qué falta:** correr `database/migracion-22-metricas-contacto.sql` y
`database/migracion-23-contactos-sobreviven-actividad.sql` en phpMyAdmin, en
pruebas y en producción, **en ese orden** —la 23 le cambia la llave foránea a
una columna que crea la 22—.

**Qué piden y por qué:** requerimiento del cliente, 2026-09-02: medir la
interacción real entre usuarios y actividades, para poder ofrecer más
adelante estadísticas y reportes de rendimiento por organizador. `contactos`
—la tabla de "Contactar al organizador", que ya guardaba usuario/contacto,
actividad y fecha/hora desde REQ-00007— gana `organizador_id`, `tipo_cta`
(hoy siempre `'informacion'`, el único CTA que llega a este formulario),
`ciudad` y `categoria` —una foto de la actividad al momento del contacto, no
un JOIN en vivo, para que un reporte no cambie con retroactividad si el
organizador edita la actividad después— y `estado` (vacía a propósito, para
"si posteriormente lo implementas", sin inventar valores). Separado a
propósito de una eventual base de marketing/newsletter, que necesitaría su
propio consentimiento.

La 23 resuelve algo que la 22 dejaba abierto y el cliente confirmó el mismo
día: quiere el historial de solicitudes aunque se borre la actividad. Antes,
`contactos` tenía `ON DELETE CASCADE` contra `eventos` —eliminar una
actividad borraba también sus contactos, foto incluida—. Ahora `evento_id` es
opcional y su llave pasa a `ON DELETE SET NULL`: al borrar la actividad la
fila de contacto se queda, con `evento_id` en NULL y todo lo demás —nombre,
organizador, ciudad, categoría, fecha— tal como quedó escrito el día del
contacto. No hace falta tocar `eliminarEvento()` (`includes/eventos.php`):
sigue siendo un `DELETE FROM eventos` a secas: es la base la que decide.

**Qué pasa mientras tanto:** el formulario de "Contactar al organizador"
sigue funcionando igual —nada de esto lo toca—. Antes de correr la 22, la
tabla sigue como estaba: sin las columnas nuevas y con `ON DELETE CASCADE`
todavía activo.

**Para cerrarlo:** ejecutar los dos `.sql`, en orden, en los dos entornos.
Ninguna requiere quitar código después.

---

### 2p. Página «Sobre Omdara» — implementada

**Requerimiento del cliente, 2026-09-02:** una sección nueva en el pie
—«Sobre Omdara», debajo de «Explora», nunca en el menú principal— que lleva a
una página propia con misión, visión y los cinco valores, con el texto tal
como lo mandó.

**Hecho:** `sobre-omdara.php` (rutas `/sobre-omdara` y `/about-omdara`,
`includes/idioma.php`), enlazada como columna propia del pie
(`includes/layout.php`, entre «Explora» y «Para organizadores»; el grid del
pie pasó de 5 a 6 columnas, `assets/css/portada.css`) y dada de alta en
`sitemap.php`. Probado en los dos idiomas y contra el pie de la portada real.

**Igual que «¿Cómo funciona?» y las tres páginas legales:** el texto no se
corrige —es texto que compromete a la empresa—, y el cuerpo se queda en
español a propósito; lo que sí tiene versión en inglés es el armazón —el
título de pestaña y el enlace del pie («About Omdara»)—. Se suma a la lista
de contenido pendiente de traducir de la sección 3.

---

### 2r. Ejecutar la migración 24 (correo de contacto por actividad)

**Qué falta:** correr `database/migracion-24-correo-contacto-evento.sql` en
phpMyAdmin, en pruebas y en producción.

**Qué piden y por qué:** requerimiento del cliente, 2026-09-02: separar el
correo de la cuenta (con el que se inicia sesión) del correo donde cada
actividad recibe "Contactar al organizador". Antes había un solo correo por
organizador —el de su cuenta— y todas sus actividades lo compartían; ahora
cada actividad puede tener el suyo propio —por ejemplo, la cuenta de un
colaborador que gestiona esa actividad en particular—, con el de la cuenta
como valor por defecto.

**El cliente pidió explícitamente verificación** ("idealmente mediante un
enlace de verificación"): un correo nuevo no se activa hasta que se confirma
con un código de un solo uso, igual que el código de acceso para entrar al
sitio, pero en una tabla aparte (`codigos_correo_contacto`) para que pedir uno
no interfiera con el otro. Antes de confirmarlo, la actividad sigue usando el
correo de la cuenta —nunca se activa un correo sin confirmar—.

**Dónde:** `/editar-actividad/{id}` (`evento-editar.php`), sección «Correo de
contacto de esta actividad», fuera del formulario grande porque un `<form>`
no puede ir dentro de otro —el archivo nuevo es
`includes/correo-contacto-evento.php`—. Las funciones viven en
`includes/eventos.php` (`correoContactoEvento()`,
`solicitarCodigoCorreoContacto()`, `confirmarCodigoCorreoContacto()`,
`cancelarCodigoCorreoContacto()`, `quitarCorreoContactoEvento()`), y
`includes/contacto.php` (`avisarOrganizador()`) ya usa
`correoContactoEvento($ev)` en vez de `$ev['organizador_email']` a secas.

**Qué NO cambia:** el correo con el que se recibe un reporte de moderación
(`moderacion.php`) sigue mostrando el de la cuenta del organizador, a
propósito —para eso, administración necesita el correo real de quien es
dueño de la cuenta, no uno que puso para enrutar mensajes de contacto—.

**Alcance de esta primera entrega:** solo se puede fijar un correo de
contacto DESPUÉS de crear la actividad (en «Editar»), no en el momento de
publicarla por primera vez —`evento-nuevo.php` no la toca—. La actividad
recién creada necesita su propio id antes de poder pedirle un código a un
correo, así que hacerlo en el mismo formulario de alta habría significado
meter esa verificación dentro de una página que también sube una imagen
(`multipart/form-data`), una complicación aparte que no se pidió resolver
ahora. Mientras tanto, toda actividad nueva usa el correo de la cuenta hasta
que su organizador entra a editarla.

**Qué pasa mientras tanto:** "Contactar al organizador" sigue funcionando
igual —al correo de la cuenta, como siempre—. Antes de correr la migración,
la sección de "Correo de contacto de esta actividad" no aparece —depende de
que exista la columna `eventos.correo_contacto`—, y donde sí aparece intentar
guardar algo falla con un error de columna desconocida.

**Para cerrarlo:** ejecutar el `.sql`, en los dos entornos.

---

### 2q. Preguntas frecuentes — texto del cliente (2026-09-02)

**Hecho:** en el bloque «Para usuarios» de `preguntas-frecuentes.php`, la
pregunta 1 («¿Qué es Omdara?») y la pregunta 4 («¿Omdara organiza las
actividades?») se reemplazaron con el texto que mandó el cliente, y se
agregaron sus dos preguntas nuevas justo después de la 4 («¿Puedo reservar o
comprar una actividad en Omdara?» y «¿Omdara garantiza las actividades
publicadas?»).

**Pendiente de decidir — «Omdara» en vez de «OMDARA» en todo el sitio.** El
cliente pidió explícitamente que la marca se escriba «Omdara» —solo la
primera con mayúscula— en cualquier texto normal, y que «OMDARA» completo en
mayúsculas quede reservado a los textos legales. Se aplicó dentro de
`preguntas-frecuentes.php` (las dos preguntas de arriba y la respuesta de
«¿Puedo cancelar una reserva?», que también decía «OMDARA»), pero el mismo
«OMDARA» en mayúsculas aparece en más sitios que esta tarea no tocó:
`como-funciona.php` (el cuerpo entero), los correos que arma el código
(`includes/contacto.php`, `includes/eventos.php`, `includes/correo.php`),
las meta descriptions (`includes/idiomas/es.php`/`en.php`, claves
`pagina.*.meta`), `mi-cuenta.php`, `metricas.php` y `diagnostico-correo.php`.
Aparte quedan, a propósito, sitios donde «OMDARA» no es texto de marca sino
un identificador técnico que no se debe tocar —el user-agent de
`includes/mapa.php`, la constante `OMDARA_ARRANCADO`
(`includes/config.php`), la variable de JavaScript `OMDARA_COOKIES`
(`includes/layout.php`)— y la duda mayor: `'marca.nombre' => 'OMDARA'`
(`includes/idiomas/{es,en}.php`), el logotipo del sitio, que sale en la
cabecera, el pie y el `<title>` de cada página. Cambiarlo sería una decisión
de diseño —¿el logotipo también baja a «Omdara», o es la excepción porque es
un wordmark y no un texto corrido?—, no solo un cambio de texto, así que se
deja para que el cliente lo confirme antes de tocarlo.

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
