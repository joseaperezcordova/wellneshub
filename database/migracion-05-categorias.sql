-- ============================================================================
--  Wellneshub · migración 05 — catálogo de categorías
--
--  El catálogo pasó de 13 a 20 categorías. Añadir no toca la base: las
--  categorías viven en categoriasMenu(), dentro de includes/eventos.php, y la
--  tabla eventos solo guarda el nombre en texto.
--
--  Lo que sí hay que arreglar aquí son las cinco que dejaron de existir con ese
--  nombre. Un evento cuya categoría ya no está en la lista se rompe de tres
--  maneras a la vez, y ninguna avisa:
--
--    · no sale en ningún filtro del buscador,
--    · su ficha se queda sin icono,
--    · y su organizador no puede guardar NINGUNA edición sin elegir antes otra
--      categoría —validarEvento() rechaza cualquier valor fuera del catálogo—,
--      aunque lo que quisiera cambiar fuese la hora.
--
--  DOS SE RENOMBRARON. Es el mismo formato con otro nombre, así que se cambian
--  solas, sin que nadie tenga que decidir nada:
--
--    Cacao     → Ceremonia de Cacao
--    Ice Bath  → Cold Plunge
--
--  TRES SE QUITARON del catálogo (commit c601590):
--
--    Retiro · Festival · Ceremonia
--
--  Estas NO se reasignan solas, y es a propósito. Son formatos, no prácticas: un
--  retiro puede ser de yoga, de meditación o de temazcal, y elegir por el
--  organizador significa publicar su ficha bajo una práctica que no es la suya.
--  Más abajo hay una consulta que las lista con su título para decidirlas una a
--  una, y la plantilla del UPDATE lista para copiar.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  OJO: phpMyAdmin se PARA en el primer error y no ejecuta lo que venga después,
--  sin decirlo muy alto. Si alguna línea falla porque ya la habías ejecutado
--  antes, quita esa línea y vuelve a lanzar el resto.
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez no encuentra
--  ninguna fila que cambiar y actualiza cero.
-- ============================================================================

SET NAMES utf8mb4;


-- ----------------------------------------------------------------------------
--  1. Qué hay antes de tocar nada
--
--  Si esto devuelve cero filas, la migración no tiene trabajo y te la puedes
--  saltar entera.
-- ----------------------------------------------------------------------------
SELECT categoria, COUNT(*) AS eventos
  FROM eventos
 WHERE categoria IN ('Cacao', 'Ice Bath', 'Retiro', 'Festival', 'Ceremonia')
 GROUP BY categoria;


-- ----------------------------------------------------------------------------
--  2. Las dos que solo cambiaron de nombre
-- ----------------------------------------------------------------------------
UPDATE eventos SET categoria = 'Ceremonia de Cacao' WHERE categoria = 'Cacao';
UPDATE eventos SET categoria = 'Cold Plunge'        WHERE categoria = 'Ice Bath';


-- ----------------------------------------------------------------------------
--  3. Las tres que se quitaron: hay que mirarlas una a una
--
--  Esta consulta te da lo que necesitas para decidir sin abrir el sitio: el
--  título, quién organiza y en qué situación está. Un borrador de hace meses no
--  merece el mismo cuidado que una ficha publicada con gente apuntada.
-- ----------------------------------------------------------------------------
SELECT e.id,
       e.categoria     AS categoria_vieja,
       e.titulo,
       e.situacion,
       u.nombre        AS organiza,
       e.fecha_inicio
  FROM eventos e
  JOIN usuarios u ON u.id = e.usuario_id
 WHERE e.categoria IN ('Retiro', 'Festival', 'Ceremonia')
 ORDER BY e.situacion, e.fecha_inicio;

--  Y aquí la plantilla. Copia una línea por evento, pon el id que salió arriba y
--  la categoría que le corresponda de las 20 de la lista del final.
--
--  Va por id y no por categoría a propósito: "todos los retiros a Meditación" es
--  la forma rápida de dejar mal etiquetados a los que eran de yoga.
--
--    UPDATE eventos SET categoria = 'Meditación' WHERE id = 12;
--    UPDATE eventos SET categoria = 'Yoga'       WHERE id = 27;
--
--  Si alguno ya no vale la pena —una prueba, algo que ya pasó—, lo honesto es
--  ocultarlo en vez de colgarle una categoría cualquiera:
--
--    UPDATE eventos SET situacion = 'oculto' WHERE id = 31;


-- ----------------------------------------------------------------------------
--  4. Comprobación final
--
--  Lista las categorías guardadas que ya no están en el catálogo. Lo normal es
--  que no devuelva nada; si devuelve algo, quedan huérfanos por reasignar en el
--  paso 3 y la migración todavía no está terminada.
--
--  ESTA LISTA TIENE QUE CUADRAR CON categoriasMenu() en includes/eventos.php.
--  Si se toca el catálogo y no se toca esto, la comprobación empieza a dar por
--  buenos los huérfanos, que es justo lo que vino a evitar. Para verla al día:
--
--    php -r "require 'includes/eventos.php'; print_r(array_keys(categoriasMenu()));"
-- ----------------------------------------------------------------------------
SELECT categoria, COUNT(*) AS eventos
  FROM eventos
 WHERE categoria NOT IN (
         'Yoga', 'Meditación', 'Pilates', 'Breathwork',
         'Sound Healing', 'Tai Chi', 'Qi Gong', 'Temazcal',
         'Ceremonia de Cacao', 'Ecstatic Dance', 'Senderismo', 'Carreras',
         'Ciclismo', 'Surf', 'Nutrición', 'Ayurveda',
         'Spa', 'Cold Plunge', 'Biohacking', 'Longevidad'
       )
 GROUP BY categoria;
