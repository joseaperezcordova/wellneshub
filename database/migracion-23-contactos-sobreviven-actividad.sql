-- ============================================================================
--  OMDARA · migración 23 — el historial de contactos sobrevive a que se
--  borre la actividad
--
--  Sigue a la migración 22: ahí "contactos" empezó a guardar organizador_id,
--  ciudad y categoria —una foto de cada solicitud, para medir la interacción
--  real entre usuarios y actividades— pero la tabla seguía con
--  ON DELETE CASCADE contra eventos. Si un organizador eliminaba su
--  actividad —solo puede, dentro de las primeras
--  EVENTO_MARGEN_ELIMINACION_H horas—, todas sus solicitudes de contacto
--  desaparecían con ella, foto incluida.
--
--  El cliente confirmó (2026-09-02): quiere ese historial, así que
--  "eliminarEvento()" (includes/eventos.php) ya no debe borrar las filas de
--  "contactos" que apunten a la actividad que se va —solo la actividad—.
--
--  CÓMO
--  evento_id pasa de obligatorio a opcional, y su llave foránea cambia de
--  ON DELETE CASCADE a ON DELETE SET NULL: al borrar la actividad, la fila de
--  contactos SE QUEDA, con evento_id en NULL y el resto tal como quedó
--  escrito el día del contacto —nombre, organizador_id, ciudad, categoria,
--  tipo_cta, fecha—. No hace falta tocar eliminarEvento(): sigue siendo un
--  DELETE FROM eventos a secas, y es la base la que decide qué le pasa a
--  cada tabla relacionada según su propia llave foránea.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega entero
--    → Continuar.
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez la
--  primera sentencia falla porque la llave ya no existe con ese nombre —o ya
--  no es CASCADE—, que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE contactos
  DROP FOREIGN KEY fk_contacto_evento;

ALTER TABLE contactos
  MODIFY COLUMN evento_id INT UNSIGNED NULL DEFAULT NULL;

ALTER TABLE contactos
  ADD CONSTRAINT fk_contacto_evento
    FOREIGN KEY (evento_id) REFERENCES eventos (id)
    ON DELETE SET NULL ON UPDATE CASCADE;
