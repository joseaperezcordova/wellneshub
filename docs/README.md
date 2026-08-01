# docs/

Material interno del proyecto. **No se despliega**: el workflow de FTP excluye
esta carpeta entera (ver `.github/workflows/deploy.yml`). Está aquí para que
quede versionada junto al código que describe, no para publicarse.

## pase-a-produccion.md y pase-a-produccion.html

Dos vistas de lo mismo. El `.md` es la guía completa: qué cuenta es cada
cosa, por qué importa, y cómo transferirla o recrearla, paso a paso por
consola. El `.html` es la referencia rápida: el propio `config.local.php`
tal como quedaría en producción, con cada línea marcada en rojo cuando es
un token creado con una cuenta de quien programó el sitio (y qué no lo es).

El `.html` se abre con doble clic, no necesita servidor. También está
publicado como Artifact:

<https://claude.ai/code/artifact/2cd5266a-0ec9-4a4f-8776-c037750d1ed7>

Igual que con `pruebas.html`: si cambia una llave de `config.local.php`
—se agrega una integración, se quita otra—, hay que actualizar el `.html`
(y su Artifact) para que no quede desactualizado.

## pruebas.html

La guía de pruebas del sitio: 100 comprobaciones repartidas en once secciones,
escritas para alguien que no conoce el proyecto. Cada punto dice qué hacer y qué
debería pasar.

Se abre con doble clic, no necesita servidor. También está publicada como
Artifact, que es la forma cómoda de pasársela a alguien:

<https://claude.ai/code/artifact/97e04c93-05ec-4903-a845-9e04eb504757>

Las dos copias hay que actualizarlas a la vez. Si solo se toca una, la otra
empieza a mentir.

### Cómo recuerda lo marcado

Cada prueba se identifica por una **huella de su propio texto**, no por su
posición. Eso es lo que permite añadir secciones nuevas por el medio sin que las
marcas de quien esté probando caigan sobre puntos que no son — que fue
exactamente lo que pasó la primera vez que se amplió.

Si se reescribe el enunciado de una prueba, esa pierde su marca. Es lo correcto:
ya no es la misma prueba y hay que volver a comprobarla.

El guardado tiene tres redes —almacenamiento del navegador, de la pestaña, o
memoria— porque dentro de un marco Safari lo bloquea entero. La página **dice en
pantalla cuál está usando**: antes fallaba en silencio y el problema aparecía con
cuarenta casillas ya marcadas. Cuando no puede guardar, ofrece un código para
copiar y pegar, que además sirve para seguir en otro dispositivo.
