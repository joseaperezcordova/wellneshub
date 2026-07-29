-- ============================================================================
--  Wellneshub · migración 07 — modalidad de la actividad
--
--  Agrega "Presencial / En línea / Híbrida" al formulario de alta/edición, y
--  el campo "Enlace de acceso" que va junto a Lugar (sirve también para una
--  actividad presencial que además transmite o manda un grupo de WhatsApp).
--
--  Todas las actividades que ya existen quedan como 'presencial' —el valor
--  por defecto—, que es lo que eran antes de que existiera esta columna.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  ADD COLUMN modalidad ENUM('presencial','en_linea','hibrida')
                        NOT NULL DEFAULT 'presencial' AFTER hora_recurrente,
  ADD COLUMN enlace_acceso VARCHAR(500) NULL DEFAULT NULL AFTER mapa_url;
