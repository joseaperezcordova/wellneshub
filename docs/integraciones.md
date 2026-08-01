# Integraciones y servicios de terceros

Todas las claves de las integraciones de abajo viven en
`includes/config.local.php` — ver la tabla de
[Variables de entorno en el README](../README.md#variables-de-entorno) y el
archivo de ejemplo comentado, `includes/config.local.example.php`, que es la
fuente de verdad más al día sobre dónde consigue cada clave.

Qué cuenta transferir o recrear para que el dueño final no dependa de quien
programó el sitio: [`pase-a-produccion.md`](pase-a-produccion.md).

## Login con Google

**Para qué:** una de las dos formas de entrar (la otra es el código de un
solo uso por correo). OAuth 2.0 implementado a mano en `includes/google.php`
— sin SDK de Google, solo `peticionHttp()` contra sus endpoints.

**Dónde se configura:** Google Cloud Console → *APIs y servicios* →
*Credenciales* → *ID de cliente OAuth 2.0* → tipo *Aplicación web*. Las URIs
de redireccionamiento autorizadas tienen que coincidir **carácter a
carácter** con `URL_BASE . '/google-callback.php'` — con o sin `www.`, con
`http` o `https` según el entorno. Si el sitio responde en más de un host
(por ejemplo con y sin `www.`), hay que registrar las dos por separado o el
login falla con `redirect_uri_mismatch` para quien entre por la que falte.

**Qué pasa si falla:** `googleCanjearCodigo()` y `googlePerfil()`
(`includes/google.php`) comprueban el código HTTP y el JSON de vuelta antes
de confiar en nada; si algo no cuadra, `google-callback.php` manda de vuelta
a `login.php?error=google` sin tumbar el sitio. El código por correo sigue
funcionando igual, sea lo que sea que le pase a Google.

**Detalle no obvio:** `pedir_perfil` en la config queda en `false` mientras
el hosting mantenga una regla de `mod_security` que devuelve 403 ante
cualquier URL con la cadena `.profile` — Google la incluye en el callback si
se pide ese permiso. Cuando soporte del hosting la desactive, cambiarlo a
`true` recupera el nombre y la foto de la cuenta de Google en vez de
completarlos a mano.

## Analítica: GA4, Microsoft Clarity, Meta Pixel, Search Console

**Para qué:** medir tráfico y comportamiento real del sitio. Las cuatro son
independientes entre sí — cada una se enciende sola si su clave tiene algo
puesto, sin que las demás tengan que estar configuradas.

**Dónde se configuran:** `config.local.php` → `analytics.*`. El propio
archivo de ejemplo explica, clave por clave, dónde sacar cada ID
(`analytics.google.com`, `clarity.microsoft.com`,
`business.facebook.com`, `search.google.com/search-console`).

**Nunca se activan en local:** `includes/layout.php` no imprime ningún
script de analítica cuando la petición viene de `localhost`/`127.0.0.1`,
tengan o no las claves puestas — así probar el sitio en la máquina de quien
programa nunca ensucia los datos reales.

**Eventos propios:** `window.whTrack(nombre, params)` (definido en
`layout.php`) es el único punto de entrada para todo evento que dispara el
resto del sitio — `evento.php`, `buscar.js`, `contacto.php`, `auth.php`,
etc. Reparte a las tres plataformas a la vez, cada una detrás de su propio
`typeof`: si solo GA4 está activo, las líneas de `fbq`/`clarity` simplemente
no hacen nada.

**Estado real (ver el checklist de seguimiento del alcance para el detalle
completo):** GA4, Search Console y Clarity están en vivo desde julio de
2026. Meta Pixel tiene el código listo pero la cuenta no se llegó a crear —
no es indispensable salvo que se vaya a anunciar en Facebook/Instagram.

## OpenStreetMap / Nominatim / Leaflet

**Para qué:** el mapa interactivo del formulario de alta/edición (arrastrar
un pin para fijar la ubicación) y el mapa incrustado en cada ficha pública.
Decisión deliberada, no una limitación: OpenStreetMap no pide clave ni
facturación, a diferencia de la API de Google Maps. El botón «Cómo llegar»
de la ficha sí abre Google Maps — es lo que la gente ya trae instalado en el
teléfono para navegar, aunque el mapa que se ve en la página sea de OSM.

**Cómo se usa:**
- **Leaflet** (`unpkg.com/leaflet@1.9.4`, por CDN) dibuja el mapa
  arrastrable. Es la única librería de JavaScript de terceros del proyecto,
  y solo se carga en las dos páginas que la usan
  (`$mapaInteractivo = true;` antes de pedir `layout.php`).
- **Nominatim** (`nominatim.openstreetmap.org`) resuelve nombre de
  ciudad → coordenadas (para centrar el mapa) y coordenadas → dirección
  aproximada (geocoding inverso, para autocompletar ciudad/estado/dirección
  al mover el pin). Las dos llamadas son del navegador directo
  (`fetch()` en `includes/form-evento.php`), sin pasar por el servidor, y
  fallan en silencio (`.catch(function(){})`) — es una ayuda para rellenar
  el formulario más rápido, nunca la fuente de verdad: lo que de verdad se
  guarda pasa otra vez por `coordenadasValidas()` en el servidor.
- **`resolverEnlaceMaps()`** (`includes/mapa.php`) es aparte: convierte un
  enlace de Google Maps pegado a mano en lat/lng, siguiendo redirecciones
  del lado del servidor pero solo dentro de dominios de Google
  (`esDominioDeGoogleMaps()`) — nunca se convierte en un proxy abierto hacia
  cualquier URL.

## Turnstile / reCAPTCHA

**Para qué:** una capa extra de defensa contra bots en los formularios que
escriben sin cuenta (reportar, contactar, contacto general). **Opcional**: sin
claves configuradas, esos formularios siguen defendidos por un campo trampa y
un mínimo de segundos entre cargar y enviar (`includes/captcha.php`), que no
dependen de ningún servicio externo.

**Dónde se configura:** `config.local.php` → `captcha.turnstile.*` /
`captcha.recaptcha.*`. Turnstile es la opción recomendada en el propio
archivo de ejemplo — gratis, no rastrea a quien lo usa, y no obliga a un
aviso de cookies de Google en un formulario donde alguien solo quiere
denunciar algo. Si las dos tienen claves puestas, gana Turnstile.

**Qué pasa si falla:** si el proveedor no contesta (`$http !== 200`), el
envío **se deja pasar** — es una decisión a propósito, documentada en el
propio código: bloquear un formulario de denuncias porque el proveedor de
captcha tiene un mal día es peor que colar algún bot ocasional. Si el
proveedor sí contesta pero dice que la verificación falló, ahí sí se
rechaza.

## Correo saliente

**Para qué:** los códigos de acceso de un solo uso, y los avisos a
administradores/organizadores (nuevo reporte, nuevo mensaje de contacto).

**Cómo:** `mail()` nativo de PHP (`includes/correo.php`), sin proveedor
transaccional (SendGrid, SES, Postmark…) — **decisión deliberada, no una
limitación pendiente**: el volumen de correo del sitio no justifica hoy la
cuenta y el costo extra de un proveedor dedicado.

**Configuración obligatoria:** `config.local.php` → `correo.remitente` /
`correo.nombre`. El remitente tiene que ser una dirección real del propio
dominio, con buzón existente — los filtros de salida de Gmail/Outlook hacen
*callout verification* (se conectan al servidor de correo del remitente
antes de aceptar el mensaje) y rechazan cualquier dirección que no supere esa
comprobación. El archivo de ejemplo trae el detalle completo, incluido el
error exacto que da un dominio sin registro MX.

**Diagnóstico:** `diagnostico-correo.php` (solo administradores) muestra la
versión de PHP, si `mail()` existe, la configuración de `sendmail_path`, y
comprueba los registros DNS (SPF, MX) del dominio de envío — existe porque el
hosting no tiene SSH y sin eso "no llega el correo" se convierte en adivinar
a ciegas. Borrable una vez que el correo esté confirmado funcionando en el
servidor final.

## Google Fonts

**Para qué:** tipografía (Fraunces, Inter, IBM Plex Mono). Carga no
bloqueante — `<link rel="stylesheet" media="print" onload="this.media='all'">`
con `<noscript>` de respaldo — para que la hoja de estilos externa no
retrase el primer pintado de la página. Sin clave, sin cuenta.
