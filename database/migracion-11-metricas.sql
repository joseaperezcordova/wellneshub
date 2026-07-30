-- ============================================================================
--  Wellneshub · migración 11 — clics de salida para el módulo de métricas
--
--  El panel de métricas (metricas.php) necesita saber cuántas personas dieron
--  clic en "Comprar boletos" o "Reservar lugar" desde la ficha. Esos enlaces
--  van directo a un sitio externo, así que no hay forma de contarlos sin un
--  paso intermedio propio: salida.php registra el clic aquí y de inmediato
--  redirige a la URL real del organizador.
--
--  Los mensajes de "Contactar al organizador" ya se cuentan solos: viven en
--  la tabla contactos desde la migración 10.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Table already exists", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS clics (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  evento_id   INT UNSIGNED    NOT NULL,

  tipo        ENUM('boletos','reservar') NOT NULL,

  ip          VARBINARY(16)   NOT NULL,

  creado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_clics_evento (evento_id, creado_en),
  KEY idx_clics_tipo (tipo, creado_en),

  CONSTRAINT fk_clic_evento
    FOREIGN KEY (evento_id) REFERENCES eventos (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
