-- ============================================================================
--  OMDARA · migración 17 — teléfono de contacto del organizador
--
--  «Mi cuenta → Información de contacto» (REQ-00009) necesita algo que enseñar
--  y algo que editar. Hasta ahora lo único registrado de una persona eran su
--  nombre y su correo, y el correo es con lo que se entra: no se toca desde ahí.
--
--  Campo nuevo en usuarios:
--
--    telefono  · VARCHAR(30), opcional. PRIVADO. Es para que el equipo de
--                OMDARA pueda localizar al organizador —una duda al moderar,
--                un problema con una actividad—, y se ve en el panel de
--                administración. NO se publica en ninguna ficha: el
--                requerimiento dice expresamente que esta sección no crea un
--                perfil público.
--
--                Se guarda tal cual se escribió, con su "+" y sus espacios: es
--                un dato para que una persona lo marque, no para armar un
--                enlace.
--
--  No confundir con contactos.telefono (migración 15), que es el de quien
--  ESCRIBE a un organizador desde una ficha.
--
--  MIENTRAS NO SE EJECUTE, LA PÁGINA FUNCIONA. Enseña y deja editar el nombre;
--  el campo de teléfono no aparece. includes/auth.php lo comprueba antes de
--  leer o escribir, igual que las migraciones 15 y 16.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE usuarios
  ADD COLUMN telefono VARCHAR(30) NULL DEFAULT NULL AFTER email;
