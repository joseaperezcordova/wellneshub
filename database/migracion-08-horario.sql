-- ============================================================================
--  Wellneshub · migración 08 — hora de fin
--
--  El formulario de fecha y horario pasó a pedir hora de inicio Y de fin,
--  tanto para una actividad de un día como para cada sesión de una
--  recurrente. hora_recurrente (que ya existía) pasa a significar "hora de
--  inicio"; esta migración solo agrega la de fin.
--
--  Para "Actividad de un día" no hace falta columna nueva: su hora de fin ya
--  vive dentro de fecha_fin, igual que siempre.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  ADD COLUMN hora_fin_recurrente TIME NULL DEFAULT NULL AFTER hora_recurrente;
