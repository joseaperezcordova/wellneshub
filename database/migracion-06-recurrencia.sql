-- ============================================================================
--  Wellneshub · migración 06 — actividades recurrentes
--
--  Agrega el "Tipo de actividad" del formulario de alta/edición: de un día
--  (como hasta ahora) o recurrente (se repite con una frecuencia, a una hora
--  fija, entre dos fechas).
--
--  fecha_inicio y fecha_fin NO cambian de tipo ni de significado: siguen
--  siendo el DATETIME real que usan la agenda, el buscador y la ficha. Lo que
--  cambia es quién las llena — el formulario de una actividad recurrente
--  compone esas dos columnas a partir de fecha + hora en vez de pedir un
--  datetime-local de una vez.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  ADD COLUMN tipo_actividad ENUM('unico','recurrente')
                            NOT NULL DEFAULT 'unico' AFTER categoria,
  ADD COLUMN frecuencia ENUM('diaria','semanal','quincenal','mensual')
                            NULL DEFAULT NULL AFTER tipo_actividad,
  ADD COLUMN hora_recurrente TIME NULL DEFAULT NULL AFTER frecuencia;
