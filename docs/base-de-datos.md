# Base de datos

MySQL/MariaDB, InnoDB, `utf8mb4`. Todo el esquema vive en
[`database/schema.sql`](../database/schema.sql) (instalación desde cero,
idempotente) más catorce `migracion-NN-*.sql` para llevar una base que ya
existía al estado actual, una por cada cambio. Cómo correrlas —en local y en
producción— está en [`operacion.md`](operacion.md).

## Las ocho tablas

```
usuarios ──┬── identidades_oauth   (login con Google — el "sub", no el correo)
           │
           └── eventos ──┬── reportes    (denuncias · situacion pendiente/revisado)
                          ├── contactos  ("Contactar al organizador" de ESTA actividad)
                          └── clics      (clics en "Comprar boletos"/"Reservar lugar")

codigos_acceso        (códigos de un solo uso al correo · sin FK, vive sola)
mensajes_contacto     (contacto general del sitio · sin FK, no está atado a un evento)
```

### `usuarios`

Sin columna de contraseña — no es un olvido, aquí no existen: se entra con
Google o con un código de un solo uso. `rol` (`visitante` / `organizador` /
`admin`) decide qué puede ver y hacer cada quien; no hay tabla de permisos
aparte, el rol se comprueba directo en cada página sensible.

No hay fila semilla con rol `admin`: ver
[«Por qué no hay seeders» en el README](../README.md#por-qué-no-hay-seeders).

### `identidades_oauth`

Tabla aparte de `usuarios` y no una columna `google_id`, por dos razones que
importan si algún día se agrega otro proveedor (Apple, Facebook): permite que
la misma cuenta tenga varias identidades, y guarda el `sub` de Google —estable
de por vida— en vez del correo, que puede cambiar y que sí serviría para
secuestrar una cuenta si se usara como llave.

### `codigos_acceso`

No cuelga de `usuarios` con FK: una fila puede existir para un correo que
todavía no tiene cuenta (la cuenta se crea en el momento de canjear el
código). Guarda el **hash** del código, no el código — una copia de la base
filtrada no sirve para entrar con los códigos que estuvieran vivos en ese
momento. `intentos` se cuenta por código y no por cuenta, así nadie puede
bloquear el acceso de otra persona pidiendo su correo y fallando aposta.

### `eventos`

La tabla central. Dos aclaraciones que evitan releer el código cada vez:

- **`entidad` vs. `situacion`** — "estado" significa dos cosas en español
  (la entidad federativa y la situación de la publicación), así que aquí son
  dos columnas con nombres que no chocan.
- **`publicado_en` vs. `creado_en`** — el organizador tiene 24 horas para
  *eliminar* su actividad *desde que la publica*, no desde que empezó a
  escribirla (editarla no tiene plazo, REQ-000-XX). Un borrador que estuvo
  tres días a medias no gasta ese margen.

`situacion` es `borrador` → `publicado` → (`oculto` si un administrador lo
retira). `tipo_actividad` distingue única de recurrente; si es recurrente,
`frecuencia` y las horas vienen llenas, pero `fecha_inicio`/`fecha_fin` siguen
siendo el rango real (primera y última ocurrencia), no un dato aparte.

`categoria` es texto libre (`VARCHAR(60)`), sin FK a una tabla de categorías
— el catálogo de las 20 categorías válidas vive en código
(`categoriasMenu()` en `includes/eventos.php`), no en la base. Ver
[«Por qué no hay una capa de modelos» en el checklist](https://claude.ai/code/artifact/450f2518-3399-4b32-a856-8ce5a4408364)
para el razonamiento completo detrás de esta y otras decisiones de
arquitectura parecidas.

### `reportes`

Una bandeja, no una acción automática: reportar **no** oculta el evento por sí
solo — lo decide un administrador desde `moderacion.php`. `origen` distingue
un reporte de un visitante del que crea solo el filtro automático de
palabras; van a la misma tabla a propósito, para tener una sola bandeja que
vaciar.

### `contactos` vs. `mensajes_contacto`

Dos tablas para dos formularios distintos, no una: `contactos` siempre está
atado a una actividad (`evento_id NOT NULL`) — es "contactar al organizador
de ESTA actividad", desde `contactar.php`. `mensajes_contacto` no tiene
`evento_id`: es el formulario de contacto general del sitio
(`contacto.php`), para quien escribe sin tener una actividad en mente.

### `clics`

Un enlace externo (el sitio del organizador) no se puede contar directo.
`salida.php` registra el clic aquí y de inmediato redirige a la URL real —
es la única forma de saber cuántas veces se pulsó "Comprar boletos" sin
retrasar ni una fracción de segundo a quien lo pulsa.

## Índices que importan

Todos los índices compuestos existen porque una consulta real los necesita
—no hay ninguno especulativo—:

- `idx_eventos_agenda (situacion, fecha_inicio)` — el listado público siempre
  filtra por `situacion = 'publicado'` y ordena/filtra por fecha.
- `idx_eventos_categoria (categoria, fecha_inicio)` — el filtro por categoría
  del buscador.
- `idx_eventos_usuario (usuario_id, creado_en)` — "Mis actividades" del panel
  del organizador.
- `idx_eventos_punto (latitud, longitud)` — sin usar todavía; queda listo
  para el día que haya búsqueda por cercanía.
- Los `idx_*_ip` de `codigos_acceso`, `reportes`, `contactos` y
  `mensajes_contacto` son los que hacen posible el límite de envíos por IP
  sin escanear la tabla entera en cada intento.

## Migraciones

Cada una se puede volver a ejecutar sin romper nada (`CREATE TABLE IF NOT
EXISTS`, `ADD COLUMN IF NOT EXISTS` donde el motor lo soporta, o comprobación
previa donde no). Orden y qué agrega cada una:

| # | Archivo | Qué agrega |
| - | --- | --- |
| 01 | `migracion-01-codigos-por-correo.sql` | Pasa de contraseñas a códigos de acceso por correo — el cambio de arquitectura de login más grande del proyecto. |
| 02 | `migracion-02-eventos.sql` | La tabla `eventos`, desde cero. |
| 03 | `migracion-03-reportes.sql` | La tabla `reportes` y el flujo de moderación. |
| 04 | `migracion-04-mapa.sql` | `mapa_url`, `latitud`, `longitud`. |
| 05 | `migracion-05-categorias.sql` | Pasa de categoría libre a catálogo cerrado. |
| 06 | `migracion-06-recurrencia.sql` | `tipo_actividad`, `frecuencia`, horas recurrentes. |
| 07 | `migracion-07-modalidad.sql` | Modalidad de la actividad (presencial/en línea) — **revertido en la 12**. |
| 08 | `migracion-08-horario.sql` | `hora_fin_recurrente`. |
| 09 | `migracion-09-precio-cupo-info.sql` | `forma_pago`, `cupo_maximo`, `sitio_web`, `accion_principal`. |
| 10 | `migracion-10-contactar-organizador.sql` | La tabla `contactos` y "Contactar al organizador". |
| 11 | `migracion-11-metricas.sql` | La tabla `clics`, para el panel de métricas. |
| 12 | `migracion-12-quitar-modalidad.sql` | Quita la columna de la 07 — decisión de producto revertida. |
| 13 | `migracion-13-direccion.sql` | `direccion`, aparte de `lugar`. |
| 14 | `migracion-14-contacto-sitio.sql` | La tabla `mensajes_contacto`, para el formulario de contacto general. |

`schema.sql` siempre refleja el estado **final** — ya incluye todo lo de
arriba. Las migraciones son solo para una base que ya estaba en un estado
anterior y no se puede reimportar desde cero sin perder datos.

`database/datos-de-prueba.sql` es aparte de todo esto: opcional, solo para
sembrar algunas actividades de ejemplo en un entorno local.
