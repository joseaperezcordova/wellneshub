-- ============================================================================
--  Wellneshub · Migración 01 — de contraseñas a códigos por correo
--
--  PARA QUIÉN ES ESTE ARCHIVO
--  Para una base de datos que YA tiene el esquema anterior (el de contraseñas).
--  Si vas a instalar desde cero, no lo uses: schema.sql ya viene actualizado.
--
--  CÓMO EJECUTARLO (hosting sin SSH):
--    1. cPanel → phpMyAdmin
--    2. Selecciona tu base de datos en el panel izquierdo
--    3. Pestaña "SQL" → pega esto → Continuar
--
--  QUÉ CAMBIA Y POR QUÉ
--  Ya no hay contraseñas. Se entra con Google o pidiendo un código de un solo
--  uso al correo. Eso deja sin sentido dos cosas del esquema viejo:
--
--    · usuarios.password_hash — no habrá contraseñas que guardar.
--    · intentos_login — existía para frenar la fuerza bruta contra el
--      formulario de contraseña. Sin contraseña no hay nada que adivinar por
--      ahí, y el freno del código nuevo va contado dentro de la propia fila del
--      código, que es más preciso: cinco intentos por código, no por cuenta.
-- ============================================================================


-- ----------------------------------------------------------------------------
--  1. La tabla nueva
--
--  El código NO se guarda en claro, se guarda su hash. Quien lea la base —una
--  copia de seguridad mal guardada, un backup en el escritorio de alguien— no
--  puede usar los códigos que estén vivos en ese momento.
--
--  "intentos" se cuenta por código y no por cuenta a propósito. Contar por
--  cuenta deja que cualquiera bloquee el acceso de otra persona pidiendo su
--  correo y fallando cinco veces. Contando por código, el atacante solo quema
--  el código que él mismo pidió.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS codigos_acceso (
  id           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  email        VARCHAR(190)     NOT NULL,
  codigo_hash  VARCHAR(255)     NOT NULL,

  intentos     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expira_en    DATETIME         NOT NULL,
  usado_en     DATETIME         NULL DEFAULT NULL,

  ip           VARBINARY(16)    NOT NULL COMMENT 'inet_pton: vale para IPv4 e IPv6',
  creado_en    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_codigos_email (email, creado_en),
  KEY idx_codigos_ip (ip, creado_en),
  KEY idx_codigos_expira (expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
--  2. Fuera lo que ya no se usa
--
--  Estas dos líneas NO son idempotentes: si las ejecutas dos veces, la segunda
--  se queja de que la columna o la tabla no existen. Ese error es inofensivo y
--  significa justo que ya estaba hecho.
--
--  Antes de soltar el gatillo, comprueba que no pierdes nada real:
--
--      SELECT COUNT(*) FROM usuarios WHERE password_hash IS NOT NULL;
--
--  Si eso da 0 —lo esperable, porque hasta ahora solo se ha entrado con
--  Google— no hay ninguna contraseña que tirar a la basura.
-- ----------------------------------------------------------------------------
ALTER TABLE usuarios DROP COLUMN password_hash;

DROP TABLE IF EXISTS intentos_login;
