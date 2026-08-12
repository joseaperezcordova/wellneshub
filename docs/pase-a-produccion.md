# Pase a producción: cuentas que deben quedar en manos del dueño final

Todo lo que se ha ido dando de alta para OMDARA —Google, GA4, y lo que
falta de Clarity, Meta y Search Console— se creó con cuentas personales de
quien programa, porque es lo natural mientras el proyecto se está armando.
El problema aparece después: si el dueño final necesita cambiar algo —un
correo de recuperación, quién tiene acceso, apagar una integración— y el
token vive en una cuenta que no es la suya, depende de pedírselo a alguien
para siempre. Esta guía existe para que eso no pase: es la lista de todo lo
que tiene que acabar bajo la identidad del dueño final antes de dar el
proyecto por entregado.

No hay ninguna credencial escrita aquí a propósito —ni contraseñas ni
tokens—, solo dónde se crea cada cosa y quién tiene que ser el dueño.

## Cómo usar esta guía

Es una lista, no un orden estricto, salvo el primer punto: dominio y
hosting van primero porque todo lo demás —despliegue, correo, cookies de
sesión— depende de en qué dirección vive el sitio. Ve marcando cada
apartado conforme se resuelva, y para lo que ya existe bajo la cuenta de
quien programa, hay dos caminos posibles según el servicio:

- **Transferir** la propiedad de lo que ya existe (cuando el servicio lo
  permite): conserva el historial —los meses de datos de GA4, por
  ejemplo— y es menos trabajo.
- **Crear de cero** bajo la cuenta del dueño final: obligatorio cuando el
  servicio no permite transferir, y de todos modos es lo que toca para
  Search Console (ver el punto 9: sus datos no se trasladan ni transfiriendo
  nada, porque están atados al dominio, no a la cuenta).

## 1. Dominio

El dominio final —el que sea, no el subdominio de pruebas
`wellnesshubmx.jpcorelab.com`— tiene que estar registrado con la cuenta del
dueño final en su propio registrador (GoDaddy, Namecheap, el que use). Si
hoy no existe todavía, es lo primero que hay que resolver: hosting, correo,
SSL y las integraciones de analítica dependen de saber cuál es.

## 2. Hosting (cPanel)

El sitio vive hoy en hosting de quien programa. Para producción, el dueño
final necesita su propia cuenta de hosting —cPanel u otro—, con su propio
dominio apuntando ahí. La migración es: copiar los archivos, exportar e
importar la base de datos, y subir un `config.local.php` nuevo con las
credenciales del hosting nuevo (nunca el mismo archivo que en desarrollo:
ver `includes/config.local.example.php`).

## 3. Repositorio en GitHub

Hoy vive en una cuenta personal de GitHub. Para que el dueño final no
dependa de nadie para ver el código, hacer un fork, o cambiar de
programador en el futuro, el repositorio tiene que quedar bajo una cuenta u
organización de GitHub que él controle —ya sea transfiriendo este mismo
repositorio (Settings → Transfer ownership) o teniéndolo como colaborador
con permisos de administrador, con la propiedad real a su nombre.

## 4. Despliegue automático (secretos de GitHub Actions)

El push a `main` despliega solo, vía FTPS (`.github/workflows/deploy.yml`).
Las credenciales del FTP viven como secretos del repositorio
(`FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_SERVER_DIR`) — apuntan
al hosting de quien programa. En cuanto el sitio se mude al hosting del
dueño final (punto 2), estos cuatro secretos hay que actualizarlos con las
credenciales del FTP nuevo. Quien tenga acceso de administrador al
repositorio (punto 3) es quien puede verlos y cambiarlos.

## 5. Base de datos

No es una cuenta aparte — viene con el hosting (punto 2). Al migrar, se
exporta la base de datos actual y se importa en el hosting nuevo; las
credenciales (host, usuario, contraseña) se actualizan en el
`config.local.php` de producción.

## 6. Login con Google (OAuth)

El botón «Continuar con Google» usa un ID de cliente OAuth creado en Google
Cloud Console, bajo una cuenta de Google de quien programa. Dos caminos:

- **Transferir el proyecto de Google Cloud** al dueño final (Google Cloud
  Console → IAM y administración → agregarlo como propietario, y quitarse a
  uno mismo después). Conserva el mismo `client_id`, no hay que tocar nada
  más en el código.
- O **crear un proyecto nuevo** bajo la cuenta de Google del dueño final y
  reemplazar `client_id`/`client_secret` en el `config.local.php` de
  producción. Hay que volver a registrar los URIs de redirección
  autorizados (`/google-callback.php`, con y sin `www.` — ver el incidente
  de `redirect_uri_mismatch` que ya tuvimos).

## 7. Correo saliente

El correo de códigos de acceso sale de una dirección real
(`config.local.php → correo.remitente`). Tiene que ser un buzón que exista
de verdad en el dominio final, creado en el cPanel del dueño final (cPanel
→ Email Accounts) — no un Gmail personal, o el correo se va a spam (ver la
explicación completa en `includes/config.local.example.php`).

## 8. Google Analytics 4

La propiedad de GA4 se crea dentro de una cuenta de Google Analytics. Si ya
existe bajo una cuenta de quien programa, se puede **agregar al dueño final
como Administrador** de esa cuenta (Administrar → Gestión de accesos a la
cuenta → +) y luego quitar el acceso de quien programa — así se conserva
todo el historial de datos. El ID de medición (`G-XXXXXXXXXX`) no cambia
al hacer esto.

## 9. Google Search Console

A diferencia de todo lo anterior, **aquí no hay transferencia que valga la
pena**: los datos de Search Console están atados al dominio verificado, no
a la cuenta que lo verificó, y no se trasladan aunque cambie el dueño. Por
eso quedó pendiente hasta tener el dominio final (ver conversación previa):
se verifica una sola vez, directamente con la cuenta de Google del dueño
final, sobre el dominio definitivo.

## 10. Microsoft Clarity

El proyecto se crea con una cuenta de Microsoft, Google o Facebook. Se
puede **agregar al dueño final como colaborador del proyecto**
(Configuración → Colaboradores) y luego quitar el acceso de quien programa,
conservando las grabaciones y mapas de calor ya acumulados. El
`clarity_id` no cambia.

## 11. Meta Pixel / Business Manager

El píxel vive dentro de un Business Manager de Meta. Si el Business Manager
lo creó quien programa, hay que **agregar al dueño final con un rol de
administrador** (business.facebook.com → Configuración del negocio →
Personas → Agregar) y después quitar el acceso de quien programa. Si el
dueño final ya tiene su propio Business Manager, es más limpio crear el
píxel directamente ahí desde el principio. El `meta_pixel_id` no cambia
mientras el píxel no se recree.

## 12. Turnstile / reCAPTCHA (si algún día se activan)

Hoy no están configurados (`config.local.php → captcha`, ambos vacíos). El
día que se activen, la cuenta de Cloudflare (Turnstile) o Google
(reCAPTCHA) donde se den de alta las claves tiene que ser del dueño final
desde el principio — son gratuitos y tardan minutos en crearse, así que no
vale la pena crearlos primero bajo otra cuenta para transferirlos después.
