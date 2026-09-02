-- ============================================================================
--  OMDARA · migración 22 — métricas de "Contactar al organizador"
--
--  Requerimiento del cliente (2026-09-02): medir la interacción real entre
--  usuarios y actividades, para poder generar más adelante estadísticas y
--  reportes de rendimiento por organizador. Aparte de una eventual base de
--  marketing/newsletter, que necesitaría su propio consentimiento — esto es
--  solo el registro operativo de cada solicitud de información.
--
--  "contactos" ya guardaba usuario/contacto (nombre, email, teléfono),
--  actividad (evento_id) y fecha/hora (creado_en) desde REQ-00007. Lo que
--  faltaba para el reporte que pide el cliente:
--
--    organizador_id · a quién le llegó la solicitud. Se podría sacar siempre
--                      con un JOIN a eventos, pero un reporte "por
--                      organizador" no debería depender de que la actividad
--                      siga existiendo tal cual.
--    tipo_cta        · qué botón la disparó. Hoy solo hay un valor posible
--                      —"informacion", el único que llega a este formulario—,
--                      pero queda explícito para cuando el reporte compare
--                      contra clics de "boletos"/"reservar" (tabla clics).
--    ciudad,
--    categoria       · una FOTO de la actividad en el momento del contacto,
--                      no un JOIN en vivo: si el organizador edita la
--                      actividad después, el reporte de "cuántas solicitudes
--                      llegaron a Guadalajara en agosto" no debe cambiar con
--                      retroactividad.
--    estado          · vacía a propósito. El cliente la menciona como algo a
--                      futuro ("si posteriormente lo implementas"); no hay
--                      todavía una lista de estados que definir, así que no
--                      se inventa un ENUM. Se deja la columna para no migrar
--                      otra vez el día que sí haga falta — mismo criterio que
--                      mensajes_contacto.estado (migración 19).
--
--  OJO — LO QUE ESTA MIGRACIÓN NO CAMBIA: hoy "contactos" sigue con
--  ON DELETE CASCADE contra eventos. Si una actividad se elimina —dentro de
--  las primeras EVENTO_MARGEN_ELIMINACION_H horas, la única ventana en que se
--  puede— sus solicitudes de contacto, con la ciudad y categoría que acabamos
--  de guardar, se borran con ella. Si se quiere que el historial de
--  solicitudes sobreviva a que se borre la actividad, hace falta otra
--  migración aparte (evento_id opcional + ON DELETE SET NULL): no se hizo
--  aquí porque no se pidió, y es un cambio de comportamiento, no solo de
--  columnas nuevas.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega entero
--    → Continuar. Son tres sentencias: la primera crea las columnas, la
--    segunda rellena organizador_id/ciudad/categoria de lo que ya había
--    (INSERT/UPDATE seguro de repetir: la segunda vez no encuentra filas
--    pendientes y no hace nada), la tercera cierra organizador_id como
--    NOT NULL y le pone su llave foránea.
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez la
--  primera y la tercera sentencia fallan con "Duplicate column name" /
--  "Duplicate key name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE contactos
  ADD COLUMN organizador_id INT UNSIGNED NULL DEFAULT NULL AFTER evento_id,
  ADD COLUMN tipo_cta       VARCHAR(20)  NOT NULL DEFAULT 'informacion' AFTER organizador_id,
  ADD COLUMN ciudad         VARCHAR(90)  NULL DEFAULT NULL AFTER tipo_cta,
  ADD COLUMN categoria      VARCHAR(60)  NULL DEFAULT NULL AFTER ciudad,
  ADD COLUMN estado         VARCHAR(20)  NULL DEFAULT NULL AFTER categoria;

UPDATE contactos c
  JOIN eventos e ON e.id = c.evento_id
   SET c.organizador_id = e.usuario_id,
       c.ciudad          = e.ciudad,
       c.categoria       = e.categoria
 WHERE c.organizador_id IS NULL;

ALTER TABLE contactos
  MODIFY COLUMN organizador_id INT UNSIGNED NOT NULL,
  ADD CONSTRAINT fk_contacto_organizador
    FOREIGN KEY (organizador_id) REFERENCES usuarios (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD KEY idx_contactos_organizador (organizador_id, creado_en);
