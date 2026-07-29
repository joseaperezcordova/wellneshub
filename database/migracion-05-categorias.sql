-- ============================================================================
--  Wellneshub · migración 05 — categorías nuevas
--
--  La lista de categorías pasó de 13 a 23. Casi todo el cambio es añadir, y
--  añadir no toca la base: las categorías viven en categoriasMenu(), dentro de
--  includes/eventos.php, y la tabla eventos solo guarda el nombre en texto.
--
--  Lo único que sí hay que arreglar aquí son dos que cambiaron de nombre. Los
--  eventos que las tuvieran seguirían guardando el nombre viejo, que ya no
--  existe en la lista: no saldrían en ningún filtro, la ficha se quedaría sin
--  icono y su organizador no podría guardar una edición sin elegir otra
--  categoría a mano.
--
--    Cacao     → Ceremonia de Cacao
--    Ice Bath  → Cold Plunge
--
--  Retiro, Festival y Ceremonia se conservan tal cual. No venían en la lista
--  que pidió el cliente, pero hay eventos publicados usándolas y son formatos
--  reales; borrarlas era romper fichas que hoy funcionan.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  OJO: phpMyAdmin se PARA en el primer error y no ejecuta lo que venga
--  después, sin decirlo muy alto. Si alguna línea falla porque ya la habías
--  ejecutado antes, quita esa línea y vuelve a lanzar el resto.
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez no encuentra
--  ninguna fila que cambiar y actualiza cero.
-- ============================================================================

SET NAMES utf8mb4;

--  Antes de tocar nada, mira qué hay. Si esto devuelve cero filas, la
--  migración no tiene trabajo que hacer y te la puedes saltar entera.
SELECT categoria, COUNT(*) AS eventos
  FROM eventos
 WHERE categoria IN ('Cacao', 'Ice Bath')
 GROUP BY categoria;

UPDATE eventos SET categoria = 'Ceremonia de Cacao' WHERE categoria = 'Cacao';
UPDATE eventos SET categoria = 'Cold Plunge'        WHERE categoria = 'Ice Bath';

--  Comprobación: esta consulta lista las categorías guardadas que ya no están
--  en la lista de la aplicación. Lo normal es que no devuelva nada. Si devuelve
--  algo, son eventos huérfanos —de alguna categoría anterior que se quitó— y
--  hay que reasignarlos a mano antes de darlo por bueno.
SELECT categoria, COUNT(*) AS eventos
  FROM eventos
 WHERE categoria NOT IN (
         'Yoga', 'Meditación', 'Pilates', 'Breathwork', 'Sound Healing',
         'Tai Chi', 'Qi Gong', 'Temazcal', 'Ceremonia de Cacao', 'Ceremonia',
         'Ecstatic Dance', 'Senderismo', 'Carreras', 'Ciclismo', 'Surf',
         'Nutrición', 'Ayurveda', 'Spa', 'Cold Plunge', 'Biohacking',
         'Longevidad', 'Retiro', 'Festival'
       )
 GROUP BY categoria;
