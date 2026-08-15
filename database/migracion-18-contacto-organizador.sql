-- ============================================================================
--  OMDARA · migración 18 — Instagram y sitio web del organizador
--
--  REQ-00012 pide que el organizador pueda dar su información de contacto al
--  publicar y que quede guardada en su cuenta para no volver a escribirla en
--  cada actividad. Los cuatro campos que pide son nombre, Instagram, WhatsApp y
--  sitio web:
--
--    nombre    · ya existe (usuarios.nombre).
--    WhatsApp  · ya existe (usuarios.telefono, migración 17). NO se añade otra
--                columna: dos números de teléfono de la misma persona, en la
--                misma tabla, acaban diciendo cosas distintas y nadie sabe cuál
--                vale. Es el mismo dato con dos nombres según dónde se pida.
--    Instagram · nuevo.
--    sitio web · nuevo.
--
--  Campos nuevos en usuarios:
--
--    instagram  · VARCHAR(120). Se guarda como @cuenta. Si alguien pega la URL
--                 completa, PHP se queda con el nombre de usuario.
--    sitio_web  · VARCHAR(500). No confundir con eventos.sitio_web, que es el
--                 de UNA actividad; este es el del organizador.
--
--  MIENTRAS NO SE EJECUTE, PUBLICAR SIGUE FUNCIONANDO. La sección de
--  información de contacto enseña solo los campos que existen, y guarda los que
--  puede. includes/auth.php lo comprueba antes de escribir, igual que las
--  migraciones 15, 16 y 17.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE usuarios
  ADD COLUMN instagram VARCHAR(120) NULL DEFAULT NULL AFTER telefono,
  ADD COLUMN sitio_web VARCHAR(500) NULL DEFAULT NULL AFTER instagram;
