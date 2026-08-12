# OMDARA · Directorio de eventos wellness MX

Directorio de retiros, festivales y círculos de bienestar en México: quien
organiza publica su actividad, quien busca filtra por ciudad, fecha o
categoría, y contacta al organizador sin pasar por ningún intermediario ni
comisión.

## Estado del proyecto

La aplicación real está en producción, en un subdominio de desarrollo
(`wellnesshubmx.jpcorelab.com`) a la espera del dominio final. No es un
prototipo: hay organizadores publicando actividades de verdad, con moderación,
analítica y un panel de administración funcionando sobre datos reales.

Sigue habiendo alcance deliberadamente fuera del MVP —pagos en línea, chat
organizador-usuario, reseñas, app móvil— documentado en el propio código
(buscar `Fuera de alcance del MVP` en `admin.php`, `mis-eventos.php` y
`metricas.php`) y en el checklist de seguimiento del alcance.

> **`prototipos/`** es anterior a la aplicación real: tres maquetas HTML
> estáticas (sin PHP, sin base de datos) de cuando el diseño todavía se estaba
> cerrando. Quedan en el repositorio como referencia histórica de las
> decisiones de diseño, pero nadie los edita ni el sitio público enlaza a
> ellos. No confundir con la aplicación, que vive en la raíz del dominio
> (`index.php`).

## Stack técnico

- **PHP 7.4**, procedural, sin framework. El código evita sintaxis exclusiva
  de PHP 8 (`match`, `str_starts_with`, `never`, `readonly`) para correr igual
  en el XAMPP local (7.4) y en el hosting.
- **MySQL/MariaDB** vía PDO, con consultas preparadas en todo el código —nunca
  SQL armado con concatenación—.
- **Sin Composer.** El hosting no tiene SSH, así que no hay forma de correr
  `composer install` ahí, y subir un `vendor/` completo por FTP para evitar
  tres funciones no compensa. Cero dependencias de PHP de terceros.
- **JavaScript sin build ni framework**: archivos sueltos en `assets/js/`,
  cargados directos con `<script src>`. Sin npm, sin bundler — ver
  [Despliegue](#despliegue) para cómo se minifican solo al desplegar.
- **Leaflet + OpenStreetMap** para el mapa interactivo del formulario de alta
  (sin clave ni facturación, a diferencia de la API de Google Maps). Es la
  única librería de terceros del proyecto, cargada por CDN solo en las dos
  páginas que la usan.

Ver [`docs/integraciones.md`](docs/integraciones.md) para el resto de
servicios externos (Google OAuth, analítica, captcha, correo).

## Estructura del proyecto

```
wellneshub/
├── index.php                   Portada pública
├── buscar.php, buscar-datos.php  Buscador (la segunda es el JSON que consume el JS)
├── evento.php                  Ficha pública de una actividad
├── evento-nuevo.php            Alta: guarda como borrador y manda a vista previa
├── evento-editar.php           Edición, con la ventana de 24h tras publicar
├── mis-eventos.php             Panel del organizador
├── admin.php                   Panel de administración (5 pestañas)
├── moderacion.php              Bandeja de reportes · solo administradores
├── metricas.php                Panel de métricas del propio sitio (no GA4)
├── contactar.php, contacto.php   "Contactar al organizador" / contacto general del sitio
├── reportar.php                 Denunciar una actividad · sin cuenta
├── login.php, codigo.php, google-redirect.php, google-callback.php
│                                Acceso: código de un solo uso por correo, o Google
├── logout.php
├── salida.php                  Registra el clic antes de salir a un enlace externo
├── resolver-mapa.php           Endpoint AJAX: enlace de Google Maps → lat/lng
├── sitemap.php                 sitemap.xml dinámico para buscadores
├── robots.txt
├── blog.php                    Maqueta — no hay tabla de artículos todavía
├── diagnostico-correo.php      Herramienta de diagnóstico de correo · solo admin
│                                (borrable una vez que el correo funcione en el hosting final)
├── .htaccess                   Redirección a HTTPS, cache de estáticos
├── .github/workflows/deploy.yml  Deploy por FTPS a cada push a main
├── assets/
│   ├── css/app.css             Estilos de formularios, auth, paneles internos
│   ├── css/portada.css         Paleta base, topbar, y el resto del sitio público
│   └── js/                     Un archivo por función: buscar, tarjetas, admin…
├── includes/
│   ├── config.php              Arranque: sesión, config, requires
│   ├── config.local.php        Credenciales · NO está en git · se crea a mano
│   ├── config.local.example.php  Plantilla comentada de config.local.php
│   ├── db.php                  Conexión PDO
│   ├── auth.php                Sesión, código por correo, alta por Google, CSRF
│   ├── eventos.php             Categorías, validación y consultas de actividades
│   ├── busqueda.php            Los filtros del buscador
│   ├── moderacion.php          Reportes y avisos a administradores
│   ├── contacto.php            Los dos formularios de contacto
│   ├── metricas.php            Consultas detrás del panel de métricas
│   ├── mapa.php                Geocoding, URLs de mapa, resolución de enlaces
│   ├── captcha.php             Campo trampa + reloj + Turnstile/reCAPTCHA opcional
│   ├── correo.php              Envío con mail() nativo
│   ├── google.php              OAuth 2.0 a mano, sin SDK
│   ├── http.php                Peticiones HTTP salientes compartidas (cURL)
│   ├── subidas.php             Redimensionado y compresión de imágenes (GD)
│   ├── form-evento.php         El formulario compartido de alta/edición
│   ├── layout.php              Cabecera, pie, analítica, y whTrack()
│   └── aviso-errores.php, guia-accion.php   Piezas de plantilla reutilizadas
├── database/
│   ├── schema.sql              Instalación desde cero (idempotente)
│   ├── migracion-01…14-*.sql   Para una base que ya existía, una por cambio
│   └── datos-de-prueba.sql     Opcional, solo para sembrar datos en local
└── docs/                       Documentación interna — no se despliega
```

## Puesta en marcha en local

Con **XAMPP** (Apache + MySQL + PHP 7.4 o compatible):

**1. Clona el repositorio dentro de `htdocs`** para que quede en
`http://localhost/wellneshub/`.

**2. Crea las tablas.** Arranca MySQL, abre phpMyAdmin
(`http://localhost/phpmyadmin`), crea una base llamada `wellneshub` y
**Importa** `database/schema.sql` ahí. El archivo usa
`CREATE TABLE IF NOT EXISTS`: se puede volver a ejecutar sin romper nada.

Opcionalmente, importa después `database/datos-de-prueba.sql` para tener
algunas actividades con las que probar sin publicar nada a mano.

**3. Crea `includes/config.local.php`** a partir de
`includes/config.local.example.php` (cópialo y rellena los valores). No está
en git —estas credenciales nunca se suben—, así que hay que crearlo a mano en
cada sitio donde vaya a correr la aplicación. En XAMPP, los valores de base de
datos por defecto son host `127.0.0.1`, usuario `root`, contraseña vacía.

Deja `url_base` vacío: la aplicación la calcula sola a partir de la petición
(ver el comentario en el propio archivo de ejemplo).

**4. Credenciales de Google** (opcional para probar el resto del sitio, pero
necesario para el login con Google). En
[Google Cloud Console](https://console.cloud.google.com/):

1. Crea un proyecto (o elige uno existente).
2. **Pantalla de consentimiento de OAuth** → tipo *Externo* → nombre de la
   app, correo de asistencia, correo del desarrollador.
3. **Credenciales** → *Crear credenciales* → *ID de cliente de OAuth* →
   **Aplicación web**.
4. En **URI de redireccionamiento autorizados**, añade, exactas y sin barra
   final:
   ```
   http://localhost/wellneshub/google-callback.php
   ```
   (y la del dominio de producción cuando corresponda — ver
   [`docs/integraciones.md`](docs/integraciones.md)).
5. Copia el *ID de cliente* y el *secreto* a `config.local.php` → `google`.

> El error más habitual aquí es `redirect_uri_mismatch`: la URL registrada no
> coincide **carácter a carácter** con la que arma la aplicación. Vigila
> `http` frente a `https`, `www.` frente a sin él, y la barra final.

**5. Sirve el sitio.** Lo más simple es el Apache de XAMPP, que ya sirve todo
`htdocs/` — con Apache arrancado, abre `http://localhost/wellneshub/`.

Como alternativa, el servidor embebido de PHP, arrancado desde `htdocs/` (no
desde dentro de `wellneshub/`, para que la URL con `/wellneshub/` funcione
igual que con Apache):

```powershell
cd C:\xampp\htdocs
php -S localhost:8080 -t .
```

y abre `http://localhost:8080/wellneshub/`.

**6. Date permisos de administrador.** No hay usuario administrador semilla —
es a propósito, ver [«Por qué no hay seeders»](#por-qué-no-hay-seeders) más
abajo—. Regístrate por la interfaz (con Google o con tu correo) y luego, una
sola vez, en phpMyAdmin:

```sql
UPDATE usuarios SET rol = 'admin' WHERE email = 'tucorreo@ejemplo.com';
```

### Por qué no hay seeders

Un usuario administrador con contraseña conocida escrito en el repositorio es
una puerta de entrada abierta desde el primer día para cualquiera que vea el
código. El primer administrador se da de alta a mano, una sola vez, tras
registrarse por la interfaz — es el paso 6 de arriba. Las categorías y demás
catálogos tampoco necesitan seed: viven como listas en el propio código
(`categoriasMenu()` en `includes/eventos.php`), no en tablas.

## Variables de entorno

Todo vive en `includes/config.local.php` (nunca en variables de entorno del
sistema — el hosting compartido no ofrece forma cómoda de configurarlas por
FTP). El archivo de ejemplo (`config.local.example.php`) documenta cada clave
en el propio código; resumen:

| Clave | Para qué | Obligatoria |
| --- | --- | --- |
| `db.*` | Host, nombre, usuario y contraseña de MySQL | Sí |
| `url_base` | Casi siempre vacío — se autodetecta | No |
| `correo.remitente` / `correo.nombre` | De dónde salen los códigos de acceso y los avisos | Sí (el valor por defecto no sirve en producción) |
| `captcha.turnstile.*` / `captcha.recaptcha.*` | Capa extra sobre el campo trampa y el reloj de los formularios | No |
| `google.client_id` / `google.client_secret` | Login con Google | No (sin ellas, solo queda el código por correo) |
| `analytics.*` | GA4, Clarity, Meta Pixel, verificación de Search Console | No — cada una enciende su propia herramienta si tiene algo puesto |

Ninguna de estas se activa en local: `layout.php` no imprime ningún script de
analítica cuando la petición viene de `localhost`/`127.0.0.1`, tenga o no IDs
puestos, para que probar el sitio en la máquina de quien programa no ensucie
los datos reales.

Detalle de cada servicio, cómo conseguir sus claves y qué cuenta transferirle
al dueño final: [`docs/integraciones.md`](docs/integraciones.md) y
[`docs/pase-a-produccion.md`](docs/pase-a-produccion.md).

## Cómo se modera

Los eventos **se publican solos**. Revisar cien correctos para encontrar uno
malo no se sostiene con una persona, así que se revisa lo que alguien señala.

- Cualquier visitante puede reportar una actividad **sin cuenta**.
- **Reportar no oculta nada.** Ocultar o borrar lo decide un administrador en
  `moderacion.php`.
- Contra el spam: campo trampa, un mínimo de segundos entre cargar y enviar,
  un límite por IP, motivo obligatorio, y Turnstile o reCAPTCHA si se
  configuran las claves.

## Cómo se entra

No hay contraseñas. Dos caminos, y los dos crean la cuenta sola la primera
vez: **Google**, o **un código de seis cifras al correo** (vale 15 minutos,
sirve una vez, admite cinco intentos, y se guarda hasheado).

## Decisiones de seguridad

- **Las identidades de Google van en su propia tabla**, no en una columna de
  `usuarios`: permite tener código-por-correo *y* Google sobre la misma
  cuenta.
- **Se guarda el `sub` de Google, no el correo** — el correo de una cuenta de
  Google puede cambiar; el `sub` no.
- **El acceso se frena a los 5 fallos en 15 minutos**, contando por correo *y*
  por IP.
- **PDO preparado en todo el código**, sin excepciones ni SQL armado a mano.
- **CSRF con token por sesión** en todos los formularios que escriben.

## Despliegue

El hosting es compartido y **no tiene SSH**, así que el deploy va por FTPS
desde GitHub Actions: al hacer `push` a `main`, la acción sincroniza el repo
contra el servidor (`.github/workflows/deploy.yml`).

Antes de subir, el propio workflow **minifica CSS y JS** (con `clean-css-cli`
y `terser`) — el código fuente del repositorio se queda comentado y legible
tal cual se edita; solo la copia que sale por FTP pesa menos.

Requiere estos *secrets* en el repositorio
(*Settings → Secrets and variables → Actions*):

| Secret | Valor |
| --- | --- |
| `FTP_SERVER` | Host FTP del hosting |
| `FTP_USERNAME` | Usuario FTP |
| `FTP_PASSWORD` | Contraseña FTP |
| `FTP_SERVER_DIR` | Carpeta destino **con `/` final** (ej. `public_html/`) |

`config.local.php` **no** lo sincroniza el deploy —está en la lista de
exclusiones—: se sube a mano por FTP una sola vez y se queda ahí. Si el deploy
lo sincronizara, cada push pisaría las credenciales de producción con las de
quien programa.

La acción guarda `.ftp-deploy-sync-state.json` en el servidor para subir solo
lo que cambió. Si un deploy queda a medias y el estado se desincroniza, se
borra ese archivo del servidor y el siguiente push vuelve a subir todo.

Detalle paso a paso (incluida la primera vez, con servidor nuevo) en
[`docs/operacion.md`](docs/operacion.md).

## Documentación adicional

Todo lo de abajo vive en `docs/` — no se despliega, es material interno.

- [`docs/base-de-datos.md`](docs/base-de-datos.md) — el modelo de datos: qué
  tabla es cada cosa, cómo se relacionan, y el histórico de migraciones.
- [`docs/integraciones.md`](docs/integraciones.md) — cada servicio externo
  (Google OAuth, GA4/Clarity/Meta Pixel, OpenStreetMap, Turnstile/reCAPTCHA,
  correo): para qué se usa, dónde se configura, qué pasa si falla.
- [`docs/operacion.md`](docs/operacion.md) — servidor, dominio, correo,
  copias de seguridad, cómo actualizar la aplicación en producción, y cómo dar
  acceso al repositorio.
- [`docs/pase-a-produccion.md`](docs/pase-a-produccion.md) y
  [`pase-a-produccion.html`](docs/pase-a-produccion.html) — qué cuenta hay que
  transferir o recrear para que el dueño final no dependa de quien programó el
  sitio.
- [`docs/pruebas.html`](docs/pruebas.html) — guía de pruebas manuales, 100
  puntos repartidos en once secciones.

## Pendiente

- Recuperación de contraseña / verificación de correo: no aplica — no hay
  contraseñas en este sistema (ver [Cómo se entra](#cómo-se-entra)).
- Dominio final, DNS y confirmación de SSL/HTTPS ahí.
- Blog real (tabla de artículos y forma de escribirlos) — hoy es una maqueta.
- Meta Pixel: el código ya está listo, falta crear la cuenta.
