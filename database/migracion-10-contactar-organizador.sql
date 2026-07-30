-- ============================================================================
--  Wellneshub · migración 10 — contactar al organizador
--
--  "Acción principal" gana su tercera modalidad de verdad: además de comprar
--  boletos o reservar lugar, ahora se puede contactar al organizador desde
--  un formulario público en la ficha, sin necesidad de cuenta.
--
--  Campos nuevos en eventos:
--
--    url_reserva        · Enlace de reserva, independiente de url_boletos
--                          (antes se compartía un solo campo para las dos
--                          acciones; ahora cada una tiene el suyo).
--    whatsapp_contacto   · Número de WhatsApp opcional del organizador para
--                          ESTE evento. Se guarda solo con dígitos (sin
--                          espacios, guiones ni +) para poder armar el
--                          enlace wa.me directamente.
--
--  Tabla nueva:
--
--    contactos  · Un renglón por cada mensaje enviado desde el formulario
--                 de "Contactar al organizador". Sirve para el límite de
--                 envíos repetidos por IP, igual que ya existe para
--                 reportes.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name" o "Table already exists", que solo quiere decir
--  que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  ADD COLUMN url_reserva VARCHAR(500) NULL DEFAULT NULL AFTER url_boletos,
  ADD COLUMN whatsapp_contacto VARCHAR(15) NULL DEFAULT NULL AFTER accion_principal;

CREATE TABLE IF NOT EXISTS contactos (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  evento_id   INT UNSIGNED    NOT NULL,

  nombre      VARCHAR(120)    NOT NULL,
  email       VARCHAR(190)    NOT NULL,
  mensaje     VARCHAR(1000)   NULL DEFAULT NULL,

  ip          VARBINARY(16)   NOT NULL,

  creado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_contactos_evento (evento_id, creado_en),
  KEY idx_contactos_ip (ip, evento_id, creado_en),

  CONSTRAINT fk_contacto_evento
    FOREIGN KEY (evento_id) REFERENCES eventos (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
