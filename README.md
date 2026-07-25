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
