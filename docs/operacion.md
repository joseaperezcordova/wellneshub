# Operación: servidor, dominio, respaldos y actualización

Todo lo que no es código: dónde vive el sitio, cómo se actualiza en
producción, cómo se respalda y qué hacer si algo se rompe. Complementa
[`integraciones.md`](integraciones.md) (servicios externos) y
[`pase-a-produccion.md`](pase-a-produccion.md) (qué cuenta transferir al
dueño final).

## Servidor

Hosting compartido con cPanel, **sin acceso SSH** — condiciona buena parte de
cómo está construido el proyecto: sin Composer, deploy por FTPS en vez de
`git pull`, y herramientas de diagnóstico como `diagnostico-correo.php`
porque no hay consola donde revisar la configuración de PHP a mano.

- **PHP:** el código está escrito para correr en PHP 7.4 (evita sintaxis
  exclusiva de 8.x), así que funciona igual si el hosting trae 7.4 u 8.x.
- **Base de datos:** MySQL/MariaDB, se administra por phpMyAdmin desde
  cPanel. cPanel antepone el nombre de la cuenta tanto a la base como al
  usuario (algo como `jpcore_wellnes`) — por eso `database/schema.sql` no
  trae `CREATE DATABASE`, ese nombre no se puede adivinar de antemano.
- **Archivos:** se suben por FTPS, ver [Cómo actualizar el sitio](#cómo-actualizar-el-sitio-en-producción)
  más abajo. `includes/config.local.php` es la única excepción: se sube a
  mano una sola vez y el deploy nunca lo toca (está en la lista de
  exclusión de `.github/workflows/deploy.yml`).
- **Proxy delante de Apache:** hay un nginx delante que cachea HTML y
  termina el TLS — por eso `config.php` no confía en `$_SERVER['HTTPS']` a
  secas y en su lugar mira `X-Forwarded-Proto` primero (ver
  `detectarUrlBase()` en `includes/config.php`, y la misma lógica repetida
  en `.htaccess` para la redirección a HTTPS).
- **Permisos de archivos y carpetas:** sin verificar contra el hosting
  final todavía — pendiente de acceso.

## Dominio

El sitio vive hoy en un subdominio de desarrollo
(`wellnesshubmx.jpcorelab.com`), a la espera de que se decida el dominio
final. Cuando eso pase, hay una lista concreta de cosas que tocar — no es
solo apuntar el DNS:

1. **DNS**: apuntar el dominio (o subdominio) final al hosting — normalmente
   un registro `A` o `CNAME` según lo que pida el proveedor de hosting.
2. **SSL/HTTPS**: confirmar que el certificado cubre el dominio nuevo. La
   mayoría de los hostings de cPanel lo emiten solos (AutoSSL) en cuanto el
   DNS resuelve, pero hay que confirmarlo, no darlo por hecho.
3. **`config.local.php` en el servidor**: revisar `url_base` — debería
   seguir vacío (se autodetecta), así que en teoría no hay nada que tocar
   aquí, pero vale la pena confirmarlo después del cambio.
4. **Google Cloud Console**: agregar la nueva URI de redireccionamiento
   (`https://tudominio.com/google-callback.php`) — el login con Google se
   rompe si no se hace este paso.
5. **Search Console**: es por dominio, así que hay que volver a verificar la
   propiedad nueva — la verificación del subdominio de desarrollo no se
   traslada sola.
6. **Meta Pixel**, si se llega a usar Conversions API: su verificación de
   dominio tampoco se traslada sola.
7. **Canónico `www.` vs. sin `www.`**: decidir cuál es el host "de verdad" y
   redirigir el otro — el subdominio de desarrollo responde igual con y sin
   `www.`, que es justamente lo que causó un `redirect_uri_mismatch` con
   Google durante el desarrollo. No conviene resolver esto en el subdominio
   que de todas formas va a desaparecer; se resuelve directo en el dominio
   final.

GA4, Clarity y Meta Pixel (si se usa) **no** dependen del dominio — sus IDs
siguen funcionando igual tras el cambio, sin tocar `config.local.php`.

## Correo

Configuración y diagnóstico completos en
[`integraciones.md` → Correo saliente](integraciones.md#correo-saliente).

## Copias de seguridad

No hay backups automáticos configurados todavía — pendiente de decidir
frecuencia y de si el hosting los ofrece de forma nativa (muchos cPanel
traen una sección *Backup* o *JetBackup* que programa esto sin escribir
nada). Mientras tanto, el procedimiento manual:

### Base de datos

cPanel → phpMyAdmin → selecciona la base → pestaña **Exportar** → método
*Rápido* → formato *SQL* → **Exportar**. Descarga un `.sql` con toda la
base tal como está en ese momento.

Guardar esa copia fuera del propio hosting (en el equipo local, o en algún
almacenamiento en la nube) — un respaldo que vive en el mismo servidor que
respalda no protege contra que el servidor entero falle.

### Archivos

Lo único que no está en git y que hace falta respaldar aparte son las
imágenes subidas (`assets/subidas/` o la carpeta que use `includes/subidas.php`
en el servidor) y `includes/config.local.php`. El resto del código ya vive
en el repositorio de GitHub, que es en sí mismo una copia — ver
[Repositorio del código](#repositorio-del-código).

Por FTP: conectar con las mismas credenciales del deploy (ver los *secrets*
en el README) y descargar la carpeta de imágenes completa.

### Restaurar

**Base de datos:** cPanel → phpMyAdmin → selecciona la base → pestaña
**Importar** → elige el `.sql` exportado → Continuar. Como
`database/schema.sql` usa `CREATE TABLE IF NOT EXISTS`, importar un respaldo
sobre una base que ya tiene las tablas no falla — pero **si las tablas ya
tienen datos, importar no los reemplaza ni los borra**: para una restauración
real (volver exactamente al estado del respaldo) hay que vaciar las tablas
primero o importar sobre una base nueva.

**Archivos:** subir por FTP la carpeta de imágenes al mismo lugar de donde
se descargó, y `includes/config.local.php` al lugar que espera
`includes/config.php` (`includes/config.local.php`, junto a los demás
`includes/`).

**Sin probar todavía end-to-end** — queda pendiente hacer un simulacro real
de restauración una vez que haya acceso continuo al hosting final, para
confirmar que estos pasos alcanzan de verdad.

## Cómo actualizar el sitio en producción

Automático: cualquier `push` a `main` en GitHub dispara
`.github/workflows/deploy.yml`, que:

1. Minifica CSS y JS (`clean-css-cli`, `terser`) — solo en la copia que se
   va a subir, el repositorio se queda con el código fuente legible.
2. Sincroniza por FTPS contra el hosting, subiendo solo lo que cambió desde
   el último deploy.

No hace falta ningún paso manual. Si un cambio incluye una migración de base
de datos nueva (`database/migracion-NN-*.sql`), **esa sí hay que correrla a
mano**: cPanel → phpMyAdmin → selecciona la base → pestaña **SQL** → pega el
contenido del archivo → Continuar. El deploy no ejecuta SQL por su cuenta.

**Si un deploy se queda a medias** (por ejemplo, se corta la conexión FTP a
medio subir): el estado de sincronización vive en
`.ftp-deploy-sync-state.json`, en el propio servidor. Si el siguiente deploy
se comporta raro (sube de menos o de más), borrar ese archivo del servidor
por FTP y el próximo push vuelve a sincronizar todo desde cero.

**Revertir un cambio:** no hay botón de "deshacer" en el deploy — revertir
significa hacer `git revert` (o corregir el error) y volver a hacer push;
eso dispara un deploy nuevo que sube el estado corregido.

## Repositorio del código

El código fuente vive en GitHub: `github.com/joseaperezcordova/wellneshub`
(privado). Dar acceso a alguien más:

1. GitHub → el repositorio → *Settings* → *Collaborators and teams* →
   *Add people* → su usuario o correo de GitHub.
2. Para que pueda desplegar (no solo ver el código), también necesita que
   alguien con acceso a *Settings → Secrets and variables → Actions* del
   repositorio comparta o rote las credenciales FTP si va a operar el
   hosting directamente — los *secrets* de GitHub Actions no se pueden ver
   una vez guardados, ni por quien los creó.
3. Para clonar y correr el proyecto en local, ver
   [Puesta en marcha en local, en el README](../README.md#puesta-en-marcha-en-local)
   — no requiere ningún permiso especial más allá de lectura del
   repositorio.

Ver también [`pase-a-produccion.md`](pase-a-produccion.md) para la lista
completa de qué otras cuentas (hosting, dominio, Google Cloud, analítica)
necesita el dueño final para no depender de quien programó el sitio — el
acceso al repositorio es solo una pieza de esa lista.
