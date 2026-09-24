-- ============================================================================
--  OMDARA · migración 27 — programación en tres tipos: fecha específica,
--  recurrente y por reserva / disponibilidad
--
--  Origen: requerimiento del cliente "Sustituir campo actual de
--  programación", con respuestas confirmadas por escrito el 2026-09-23:
--
--    1. FECHA ESPECÍFICA es el "de un día" de siempre, sin cambios: fecha,
--       hora de inicio, hora de fin y "termina otro día". Sigue en
--       fecha_inicio/fecha_fin, tipo_actividad = 'unico'.
--
--    2. RECURRENTE deja de pedir fechas, frecuencia y horas: ahora es un solo
--       texto libre ("Todos los martes a las 6:00 p.m."), en
--       programacion_texto. frecuencia/hora_recurrente/hora_fin_recurrente
--       se quedan en la tabla —no se borran columnas— pero ya no se llenan.
--       El cliente confirmó que no había recurrentes publicadas, así que no
--       hay datos que convertir.
--
--    3. POR RESERVA / DISPONIBILIDAD es nuevo: tampoco tiene fecha, solo el
--       texto de disponibilidad, también en programacion_texto.
--
--    4. Recurrente y por reserva tienen VIGENCIA MENSUAL: se ven un mes y
--       luego hay que renovarlas. Eso es vigente_hasta. Para fecha
--       específica se queda en NULL —ahí el corte sigue siendo la propia
--       fecha de la actividad—.
--
--  Como recurrente y por reserva ya no tienen fecha, fecha_inicio pasa a
--  aceptar NULL.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega entero
--    → Continuar.
--
--  NO es idempotente: los ADD COLUMN fallan la segunda vez ("Duplicate
--  column"). Si eso pasa es que ya estaba aplicada; no hace falta nada más.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  MODIFY COLUMN tipo_actividad ENUM('unico','recurrente','reserva')
                                NOT NULL DEFAULT 'unico',
  MODIFY COLUMN fecha_inicio   DATETIME NULL DEFAULT NULL,
  ADD COLUMN programacion_texto VARCHAR(500) NULL DEFAULT NULL
      COMMENT 'Recurrente: frecuencia/horario. Por reserva: disponibilidad/indicaciones'
      AFTER hora_fin_recurrente,
  ADD COLUMN vigente_hasta DATETIME NULL DEFAULT NULL
      COMMENT 'Recurrente y por reserva: hasta cuándo se ve sin renovar'
      AFTER fecha_fin;
