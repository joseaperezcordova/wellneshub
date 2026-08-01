-- ============================================================================
--  Wellneshub · migración 14 — formulario de contacto del sitio
--
--  Hasta ahora "contactar" solo existía atado a una actividad —contactar al
--  organizador—. Esto es para cuando quien escribe no tiene ninguna actividad
--  en mente: una duda general, una alianza, un problema con el sitio. Tabla
--  aparte de "contactos" (que exige evento_id) por eso mismo.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: CREATE TABLE IF NOT EXISTS.
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS mensajes_contacto (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  nombre      VARCHAR(120)    NOT NULL,
  email       VARCHAR(190)    NOT NULL,
  mensaje     VARCHAR(1000)   NOT NULL,

  ip          VARBINARY(16)   NOT NULL,

  creado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_mensajes_contacto_ip (ip, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
