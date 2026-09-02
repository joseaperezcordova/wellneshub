-- ============================================================================
--  OMDARA · migración 24 — correo de contacto por actividad
--
--  Requerimiento del cliente (2026-09-02): "Contactar al organizador" hoy
--  manda siempre al correo de la cuenta (usuarios.email, el mismo con el que
--  se inicia sesión). El cliente pidió separar las dos cosas: el correo de
--  cuenta sigue siendo el de login, pero cada actividad puede recibir sus
--  solicitudes en un correo distinto —por ejemplo, la cuenta de un
--  colaborador que gestiona esa actividad en particular—, con el de cuenta
--  como valor por defecto.
--
--  DOS PIEZAS
--
--  1. eventos.correo_contacto — el correo EFECTIVO de esa actividad, o NULL
--     para "usa el de mi cuenta" (el comportamiento de siempre). Nunca se
--     escribe a mano: solo lo pone confirmarCodigoCorreoContacto()
--     (includes/eventos.php) cuando el código llega correcto. Así un correo
--     ajeno nunca puede quedar activo sin que su dueño de verdad lo haya
--     confirmado —el cliente lo pidió explícitamente ("idealmente mediante
--     un enlace de verificación")—.
--
--  2. codigos_correo_contacto — el mismo patrón que codigos_acceso
--     (includes/auth.php), pero aparte: un código ahí vale para entrar a
--     CUALQUIER cuenta con ese correo, y mezclarlo con esto habría dejado que
--     pedir "confirmar mi correo de contacto" invalidara sin querer el
--     código de acceso de otra pestaña, o al revés. Aquí cada código es de
--     una actividad concreta (evento_id), no de una cuenta.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega entero
--    → Continuar.
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez la
--  primera sentencia falla con "Duplicate column name" y la segunda con
--  "Table already exists", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  ADD COLUMN correo_contacto VARCHAR(190) NULL DEFAULT NULL AFTER url_reserva;

CREATE TABLE IF NOT EXISTS codigos_correo_contacto (
  id           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  evento_id    INT UNSIGNED     NOT NULL,
  email        VARCHAR(190)     NOT NULL,
  codigo_hash  VARCHAR(255)     NOT NULL,

  intentos     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expira_en    DATETIME         NOT NULL,
  usado_en     DATETIME         NULL DEFAULT NULL,

  ip           VARBINARY(16)    NOT NULL COMMENT 'inet_pton: vale para IPv4 e IPv6',
  creado_en    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_ccc_evento (evento_id, creado_en),
  KEY idx_ccc_expira (expira_en),

  CONSTRAINT fk_ccc_evento
    FOREIGN KEY (evento_id) REFERENCES eventos (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
