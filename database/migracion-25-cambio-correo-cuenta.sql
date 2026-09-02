-- ============================================================================
--  OMDARA · migración 25 — cambiar el correo de la cuenta desde "Mi cuenta"
--
--  Punto 2g / punto 18 de docs/pendientes.md. Aquí no hay contraseñas: el
--  correo ES la credencial, y el código de acceso va justo a ese buzón.
--  Cambiarlo sin verificar antes el buzón nuevo dejaría a esa persona fuera
--  de su cuenta para siempre —basta un dedazo—, así que hace falta el mismo
--  tipo de verificación por código que ya usan el login
--  (codigos_acceso, includes/auth.php) y el correo de contacto por
--  actividad (codigos_correo_contacto, migración 24).
--
--  TABLA APARTE, otra vez por el mismo motivo que la 24: un código de aquí
--  confirma un cambio de correo de UNA cuenta, no dobla como código de
--  acceso. Mezclarlas dejaría que pedir uno invalidara sin querer el otro
--  —"un código vivo por cuenta", la misma regla que ya usan las otras dos—.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega entero
--    → Continuar.
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Table already exists", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS codigos_cambio_correo (
  id           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  usuario_id   INT UNSIGNED     NOT NULL,
  email_nuevo  VARCHAR(190)     NOT NULL,
  codigo_hash  VARCHAR(255)     NOT NULL,

  intentos     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expira_en    DATETIME         NOT NULL,
  usado_en     DATETIME         NULL DEFAULT NULL,

  ip           VARBINARY(16)    NOT NULL COMMENT 'inet_pton: vale para IPv4 e IPv6',
  creado_en    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_cce_usuario (usuario_id, creado_en),
  KEY idx_cce_expira (expira_en),

  CONSTRAINT fk_cce_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
