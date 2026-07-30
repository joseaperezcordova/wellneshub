-- ============================================================================
--  Wellneshub · migración 13 — campo Dirección
--
--  El pin del mapa y el geocoding inverso (Nominatim) rellenan ciudad y
--  estado solos, pero no siempre aciertan la calle exacta. Este campo es
--  para que el organizador la escriba o la corrija a mano; opcional, y
--  aparte de "Lugar" —que es el nombre del sitio, no su dirección postal—.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  ADD COLUMN direccion VARCHAR(255) NULL DEFAULT NULL AFTER lugar;
