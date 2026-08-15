-- ============================================================================
--  OMDARA · migración 19 — motivo, actividad y estado en los mensajes de contacto
--
--  El formulario de /contacto (REQ-00012 del bloque 08) pasa de tres campos a
--  cinco: se añade el motivo por el que alguien escribe y, cuando ese motivo lo
--  pide, el nombre de la actividad de la que habla.
--
--  Campos nuevos en mensajes_contacto:
--
--    motivo            · VARCHAR(40). Una de las seis claves de
--                        motivosContacto(), en includes/contacto.php. VARCHAR y
--                        no ENUM a propósito: añadir un motivo no debería
--                        costar otra migración, y la lista válida ya se
--                        comprueba en PHP antes de escribir.
--
--    actividad_nombre  · VARCHAR(200) NULL. El texto que escribió quien manda
--                        el mensaje, tal cual. NO es una clave ajena a eventos:
--                        el requerimiento dice expresamente que en el MVP no
--                        hace falta relacionarlo con la base, y forzarlo
--                        obligaría a acertar el nombre exacto de una actividad
--                        para poder quejarse de ella.
--
--    estado            · ENUM. Aquí sí ENUM: son cuatro estados de un flujo
--                        cerrado, no una lista que crezca, y un valor fuera de
--                        esos cuatro no significaría nada.
--
--  MIENTRAS NO SE EJECUTE, EL FORMULARIO FUNCIONA. Se piden el motivo y la
--  actividad, viajan al correo del administrador —que es lo que hace que
--  alguien actúe— y solo se pierden en la base. includes/contacto.php lo
--  comprueba antes de escribir, igual que las migraciones 15 a 18.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE mensajes_contacto
  ADD COLUMN motivo           VARCHAR(40)  NOT NULL DEFAULT 'general' AFTER email,
  ADD COLUMN actividad_nombre VARCHAR(200) NULL DEFAULT NULL AFTER motivo,
  ADD COLUMN estado ENUM('nuevo','revision','respondido','cerrado')
                                 NOT NULL DEFAULT 'nuevo' AFTER mensaje;
