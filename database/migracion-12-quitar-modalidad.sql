-- ============================================================================
--  Wellneshub · migración 12 — quitar "modalidad"
--
--  Se quita la sección "Presencial / En línea / Híbrida" del formulario: toda
--  actividad se trata ya como presencial, con lugar, ciudad, estado y mapa
--  siempre visibles y siempre obligatorios. La columna modalidad, que ya no
--  escribe ni lee ningún código, se elimina para no dejarla de adorno.
--
--  OJO — esto borra datos, no se puede deshacer solo con volver a correr el
--  script. Si hay actividades ya guardadas como 'en_linea' o 'hibrida', se
--  pierde ese dato: quedan como cualquier otra actividad, sin nada que diga
--  que eran en línea. Antes de correr esto en producción conviene revisar
--  si existe alguna con:
--
--    SELECT id, titulo, modalidad FROM eventos WHERE modalidad <> 'presencial';
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "check that column/key exists", que solo quiere decir que ya estaba
--  aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  DROP COLUMN modalidad;
