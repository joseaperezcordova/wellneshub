-- ============================================================================
--  OMDARA · migración 16 — aceptación de Términos y Aviso de Privacidad
--
--  REQ-00008 exige que nadie tenga cuenta sin haber aceptado los Términos y
--  Condiciones y el Aviso de Privacidad, y que esa aceptación "se registre como
--  parte del proceso de alta". Registrarla es justamente lo que hace falta el
--  día que alguien pregunte: una casilla marcada que no se guarda en ninguna
--  parte no prueba nada.
--
--  Campo nuevo en usuarios:
--
--    acepto_legal_en · DATETIME, cuándo aceptó. NULL en las cuentas anteriores
--                      a esto, que es información y no un hueco: dice
--                      exactamente cuáles se dieron de alta sin pasar por la
--                      casilla. A ninguna se le pone una fecha inventada.
--
--  NO SE GUARDA QUÉ VERSIÓN ACEPTÓ, y es a propósito: los dos documentos
--  todavía no están escritos —muestran un aviso de "contenido pendiente"—, así
--  que una columna de versión solo podría guardar un número inventado. Cuando
--  haya texto de verdad y empiece a cambiar, hará falta una migración más para
--  la versión. Está anotado en docs/pendientes.md.
--
--  MIENTRAS NO SE EJECUTE, EL ALTA SIGUE FUNCIONANDO. La casilla se pide y se
--  exige igual; lo único que no ocurre es que quede el registro en la base.
--  includes/auth.php lo comprueba antes de escribir, igual que hace
--  includes/contacto.php con el teléfono de la migración 15.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE usuarios
  ADD COLUMN acepto_legal_en DATETIME NULL DEFAULT NULL AFTER email_verificado_en;
