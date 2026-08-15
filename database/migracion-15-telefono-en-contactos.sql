-- ============================================================================
--  OMDARA · migración 15 — teléfono en los mensajes al organizador
--
--  El formulario de "Contactar al organizador" (REQ-00007) añade un campo
--  opcional de teléfono / WhatsApp. Es de quien ESCRIBE, no del organizador:
--  sirve para que el organizador pueda responder por WhatsApp en vez de por
--  correo, que es como se habla en la práctica de estas actividades.
--
--  No confundir con eventos.whatsapp_contacto, que era el número DEL
--  organizador y se quitó de la ficha por decisión de producto. Este es el del
--  visitante, y solo lo ve el organizador de esa actividad en el correo que
--  recibe.
--
--  Campo nuevo en contactos:
--
--    telefono  · VARCHAR(30), opcional. Se guarda tal cual lo escribió quien
--                contacta —con su "+", sus espacios y su prefijo—: es un dato
--                para que una persona lo marque, no para armar un enlace, así
--                que normalizarlo solo serviría para estropear formatos raros
--                que sí funcionan.
--
--  MIENTRAS NO SE EJECUTE, EL FORMULARIO SIGUE FUNCIONANDO. El teléfono llega
--  igual al correo del organizador; lo único que no ocurre es que se guarde en
--  la base. includes/contacto.php lo comprueba antes de escribir. Ese rodeo se
--  puede quitar una vez aplicada esta migración en los dos entornos.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE contactos
  ADD COLUMN telefono VARCHAR(30) NULL DEFAULT NULL AFTER email;
