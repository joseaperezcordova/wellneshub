-- ============================================================================
--  Wellneshub · migración 21 — título y descripción en inglés
--
--  REQ-00002, fase 5: la versión en inglés de cada actividad la escribe su
--  organizador, no una traducción automática de lo que puso en español —el
--  requerimiento lo prohíbe expresamente. Los dos campos son opcionales; si
--  se dejan vacíos, quien vea la ficha en inglés sigue leyendo el título y la
--  descripción en español (ver evento.php).
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  ADD COLUMN titulo_en      VARCHAR(160) NULL DEFAULT NULL AFTER titulo,
  ADD COLUMN descripcion_en TEXT         NULL DEFAULT NULL AFTER descripcion;
