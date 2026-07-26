# Wellneshub · Directorio de eventos wellness MX

Directorio de retiros, festivales y círculos de bienestar en México. El proyecto
está en **fase de diseño**: lo que vive aquí son prototipos HTML estáticos que se
publican en el hosting para revisarlos en dispositivo real e ir cerrando el
diseño definitivo antes de escribir el producto.

## Los tres prototipos

| Versión | Qué es | Se toca |
| --- | --- | --- |
| `prototipos/v1-rueda-eventbrite/` | Exploración estructural: qué patrones de Eventbrite adoptar, con paleta propia | ❌ Congelado |
| `prototipos/v2-directorio-wellness/` | El producto completo navegable: público + panel organizador + panel admin | ❌ Congelado |
| `prototipos/v3-final/` | La unión de ambos — **aquí se itera el diseño** | ✅ Activo |

v1 y v2 se conservan **tal cual nacieron**, sin un solo cambio. Son la referencia
contra la que se compara: si un ajuste en v3 empeora algo, se abre el original y
se ve cómo estaba. Por eso no se "arreglan" aunque tengan detalles pendientes.

### Qué aporta cada uno a v3

v3 parte de **v2** porque es el que tiene el alcance completo (todas las vistas,
los dos paneles, la rueda del bienestar como elemento distintivo). De **v1** toma
los dos patrones estructurales que v2 no tenía:

1. **Barra de filtros persistente.** En v2 los filtros viven solo dentro de la
   vista de resultados; al hacer scroll se pierden. En v3 la barra queda pegada
   bajo el topbar en todas las vistas públicas, así que se puede reencuadrar la
   búsqueda desde una ficha de evento o desde el blog sin volver al inicio.
2. **Caja de compra fija.** En v2 el precio y el CTA se van con el scroll
   mientras el visitante lee la descripción larga. En v3 la caja es *sticky*: el
   botón de comprar sigue ahí cuando el visitante termina de convencerse.

Se ocultan en las vistas privadas (`admin`, `panel-organizador`): buscar eventos
ahí no significa nada, y sobre el fondo oscuro del admin la barra clara rompe la
lectura.

## La aplicación

La v6 del prototipo quedó como diseño final, así que a partir de ahí empieza la
aplicación real. **Vive en la raíz del dominio**, que es donde tiene que estar:
la portada del sitio es `index.php`, no una subcarpeta. Los prototipos se
apartaron a `prototipos/`, con su propio índice.

**PHP 7.4 + MySQL, sin framework y sin Composer.** El XAMPP de esta máquina trae
PHP 7.4, así que el código evita sintaxis de PHP 8 (`match`, `str_starts_with`,
`never`) y corre igual en 7.4 y en 8.x. Sin Composer porque el hosting no tiene
SSH: no hay forma de ejecutar `composer install` allí, y subir un `vendor/` por
FTP para hacer tres peticiones HTTP no compensa.

```
/
├── index.php              Portada (por ahora, banco de pruebas de la sesión)
├── login.php              Paso 1: Google, o escribir el correo
├── codigo.php             Paso 2: el código de seis cifras que llegó al correo
├── logout.php
├── google-redirect.php    Manda a Google
├── google-callback.php    Vuelta de Google  ← esta es la URI a registrar
├── assets/css/app.css
├── includes/
│   ├── .htaccess              Corta el acceso HTTP a esta carpeta
│   ├── config.php             Sesión, errores, arranque
│   ├── config.local.php       Credenciales · NO está en git · se crea a mano
│   ├── config.local.example.php
│   ├── db.php                 PDO
│   ├── auth.php               Sesión, códigos de acceso, alta por Google, CSRF
│   ├── correo.php             Envío con mail()
│   ├── google.php             OAuth 2.0 a mano
│   └── layout.php             Cabecera con el acceso a la cuenta, y pie
├── evento-nuevo.php       Alta: guarda borrador y manda a la vista previa
├── evento.php             Ficha pública, y vista previa para su dueño
├── evento-editar.php      Edición, con el plazo de 24 h
├── reportar.php           Denuncia de un evento · abierto sin cuenta
├── moderacion.php         Bandeja de avisos · solo administradores
├── database/
│   ├── schema.sql                            Instalación desde cero
│   ├── migracion-01-codigos-por-correo.sql   Para una base que ya existía
│   ├── migracion-02-eventos.sql
│   └── migracion-03-reportes.sql
└── prototipos/            Los prototipos, con su propio índice en index.html
```

### Cómo se modera

Los eventos **se publican solos**. Revisar 99 correctos para encontrar uno malo
no se sostiene con una persona, así que se revisa lo que alguien señala.

- Cualquier visitante puede reportar un evento **sin cuenta**. Quien se topa con
  una estafa no se registra para avisar; pedir cuenta no filtra bots —esos sí se
  registran—, filtra personas.
- **Reportar no oculta nada.** Si un aviso bastara para tumbar una ficha, tumbar
  a la competencia costaría un clic. Ocultar o borrar lo decide un administrador
  en `moderacion.php`.
- Un filtro de palabras revisa el texto al publicar. **No bloquea**: levanta la
  mano y crea un aviso automático en la misma bandeja. La lista es corta a
  propósito — en un directorio de bienestar mexicano, media lista de «términos
  sospechosos» son palabras del oficio.
- Contra el spam: campo trampa, un mínimo de segundos entre cargar y enviar, un
  reporte por IP y evento cada 24 h, motivo obligatorio, y Turnstile o reCAPTCHA
  si se configuran las claves.

### Cómo se entra

No hay contraseñas ni pantalla de registro. Dos caminos, y los dos crean la
cuenta sola la primera vez:

- **Google**, que ya sabe quién eres.
- **Un código de seis cifras al correo**, que demuestra lo mismo que
  demostraría una contraseña —control del buzón— sin obligar a nadie a
  inventarse una ni a nosotros a custodiarla.

El código vale 15 minutos, sirve una vez, admite cinco intentos y se guarda
hasheado. Pedir uno nuevo anula el anterior.

> No puede haber un `index.html` en la raíz junto a `index.php`: el orden de
> `DirectoryIndex` decide cuál gana y no es el mismo en todos los servidores.
> Por eso el índice de prototipos se movió a `prototipos/index.html`.

### Puesta en marcha

**1. Crear las tablas.** El esquema está en `database/schema.sql`.

- *En el hosting:* cPanel → phpMyAdmin → selecciona la base en el panel
  izquierdo → pestaña **Importar** → elige el archivo → Continuar.
- *En local:* phpMyAdmin de XAMPP (`http://localhost/phpmyadmin`), crea la base
  `wellneshub` y haz lo mismo.

El archivo es idempotente (`CREATE TABLE IF NOT EXISTS`): puedes volver a
ejecutarlo sin romper nada.

**2. Crear `config.local.php`** a partir de `config.local.example.php`. Hay que
hacerlo dos veces —una en local y otra subiéndolo por FTP al servidor— porque
no está en git ni lo sincroniza el deploy. Eso es deliberado: si el deploy lo
sincronizara, cada push pisaría las credenciales de producción con las de XAMPP.

**3. Credenciales de Google.** En [Google Cloud Console](https://console.cloud.google.com/):

1. Crea un proyecto (o elige uno).
2. **Pantalla de consentimiento de OAuth** → tipo *Externo* → rellena nombre de
   la app, correo de asistencia y correo del desarrollador. Mientras esté en
   modo *Prueba*, solo entran las cuentas que añadas como usuarios de prueba;
   para abrirlo a cualquiera hay que **Publicar la aplicación**.
3. **Credenciales** → *Crear credenciales* → *ID de cliente de OAuth* →
   **Aplicación web**.
4. En **URI de redireccionamiento autorizados**, añade las dos, exactas y sin
   barra final:
   ```
   http://localhost/wellneshub/google-callback.php
   https://wellnesshubmx.jpcorelab.com/google-callback.php
   ```
5. Copia el *ID de cliente* y el *secreto* a `config.local.php`.

> El error más habitual aquí es `redirect_uri_mismatch`: significa que la URL
> registrada no coincide **carácter a carácter** con `url_base` +
> `/google-callback.php`. Vigila `http` frente a `https` y la barra final.

**4. Date permisos de admin.** No hay usuario administrador semilla a propósito:
una contraseña conocida escrita en el repositorio es una puerta abierta desde el
primer día. Regístrate por la interfaz y luego, una sola vez:

```sql
UPDATE usuarios SET rol = 'admin' WHERE email = 'tucorreo@ejemplo.com';
```

### Decisiones de seguridad

Están comentadas en el código, pero las que más condicionan el esquema:

- **Las identidades de Google van en su propia tabla**, no en una columna de
  `usuarios`. Permite tener contraseña *y* Google sobre la misma cuenta, y
  añadir otro proveedor sin tocar el esquema.
- **Se guarda el `sub` de Google, no el correo.** El correo de una cuenta de
  Google puede cambiar; el `sub` no. Emparejar por correo es exactamente lo que
  permite quedarse con la cuenta de otro.
- **Una cuenta con contraseña solo se enlaza a Google si Google confirma que el
  correo está verificado.** Sin esa comprobación, cualquiera que cree una cuenta
  de Google con el correo de otra persona hereda su cuenta de aquí.
- **El login se frena a los 5 fallos en 15 minutos**, contando por correo *y*
  por IP. Solo por correo permitiría atacar muchas cuentas desde un sitio; solo
  por IP dejaría pasar los ataques repartidos.
- **Correo inexistente y contraseña incorrecta dan el mismo mensaje**, o el
  formulario se convierte en un comprobador de qué correos están registrados.

### Pendiente

- Recuperación de contraseña por correo
- Verificación del correo en las altas con contraseña
- Traer el diseño de la v6 a la aplicación, sección por sección

## Histórico de versiones de v3

v3 lleva un selector de versiones abajo a la derecha: un desplegable que salta
entre el estado actual y cada versión congelada. Sirve para comparar decisiones
de diseño sin tener que abrir git.

```
prototipos/v3-final/
├── index.html            La versión viva. Aquí se itera.
├── versiones.json        La lista que alimenta el desplegable
└── versiones/
    ├── v1.html           Instantáneas congeladas, byte a byte
    └── v2.html
```

Las instantáneas **no se reescriben nunca**. El desplegable no lleva la lista
dentro: la pide a `versiones.json` al cargar, así que una instantánea publicada
hace meses muestra igualmente las versiones nuevas. Lo congelado es el diseño,
no el menú.

El selector va deliberadamente en gris pizarra, fuera de la paleta, para que no
se confunda con un control del propio prototipo.

### Crear una versión

```powershell
# 1. Cambias el diseño en prototipos/v3-final/index.html
# 2. Lo commiteas
# 3. Congelas ese estado como versión
.\scripts\nueva-version.ps1 "Título corto de lo que cambió"
# 4. Commiteas la versión nueva
```

El orden importa: el script guarda el hash de `HEAD`, así que ejecutarlo
**después** de commitear es lo que hace que la versión quede etiquetada con el
commit que produjo ese diseño. Al revés apuntaría al commit anterior.

No toda modificación merece versión. Un ajuste de caché o un arreglo de una
errata no son estados de diseño que valga la pena comparar; el histórico pierde
utilidad si se llena de entradas que nadie va a abrir.

## Ver los prototipos

- **En local (XAMPP):** con Apache arrancado, <http://localhost/wellneshub/>.
- **Sin XAMPP:** abrir `index.html` directamente en el navegador funciona igual,
  no hay PHP ni backend todavía.
- **Publicado:** la raíz del hosting, tras el deploy automático.

Los tres prototipos son un solo archivo HTML cada uno, con CSS y JS embebidos y
datos de ejemplo en JS. No hay dependencias, build ni base de datos. La única
carga externa son las tipografías de Google Fonts.

## Despliegue

El hosting es compartido y **no tiene SSH**, así que el deploy va por FTPS desde
GitHub Actions: al hacer `push` a `main`, la acción sincroniza el repo contra el
servidor (ver `.github/workflows/deploy.yml`). Mismo esquema que los proyectos
`mate` y `friotrack`.

Requiere estos *secrets* en el repositorio
(*Settings → Secrets and variables → Actions*):

| Secret | Valor |
| --- | --- |
| `FTP_SERVER` | Host FTP del hosting (ej. `ftp.midominio.com`) |
| `FTP_USERNAME` | Usuario FTP |
| `FTP_PASSWORD` | Contraseña FTP |
| `FTP_SERVER_DIR` | Carpeta destino **con `/` final** (ej. `public_html/`) |

> El `/` final en `FTP_SERVER_DIR` no es opcional: sin él la acción sube los
> archivos un nivel arriba del destino.

La acción guarda `.ftp-deploy-sync-state.json` en el servidor para subir solo lo
que cambió. Si un deploy queda a medias y el estado se desincroniza, se borra ese
archivo del servidor y el siguiente push vuelve a subir todo.

## Estructura

```
wellneshub/
├── index.html                              Índice que enlaza los tres prototipos
├── prototipos/
│   ├── v1-rueda-eventbrite/index.html      Congelado
│   ├── v2-directorio-wellness/index.html   Congelado
│   └── v3-final/index.html                 Iteración activa
└── .github/workflows/deploy.yml            Deploy por FTPS (GitHub Actions)
```

## Siguientes pasos

- Cerrar el diseño en v3 (revisiones sobre el sitio publicado).
- Definir el modelo de datos: eventos, organizadores, categorías, ciudades.
- Elegir stack de backend y convertir v3 en plantillas reales.
