-- ============================================================================
--  OMDARA · migración 20 — más de una categoría por actividad
--
--  Hasta ahora eventos.categoria guardaba una sola categoría por fila. El
--  organizador podía tener una actividad que fuera Yoga Y Meditación a la vez
--  y solo podía elegir una de las dos, así que se quedaba fuera de la mitad de
--  los filtros donde de verdad encajaba.
--
--  eventos_categorias es la tabla nueva: un renglón por cada categoría de cada
--  evento. eventos.categoria NO desaparece —se queda como la "categoría
--  principal", la primera que eligió el organizador— porque de ahí siguen
--  leyendo la tarjeta de la portada, el aviso de actividad duplicada y las
--  estadísticas de un vistazo del panel de administración, y no hay ninguna
--  razón para que un carril de tarjetas enseñe tres etiquetas donde antes
--  enseñaba una.
--
--  La fuente de verdad del CONJUNTO completo —lo que ve el organizador al
--  volver a editar, lo que se enseña en la ficha, lo que usa el buscador para
--  no dejar fuera una actividad por su segunda categoría— es esta tabla.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Es idempotente: CREATE TABLE IF NOT EXISTS no rompe si ya existe, e INSERT
--  IGNORE no duplica filas si el respaldo ya se hizo antes —la clave primaria
--  es (evento_id, categoria)—.
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS eventos_categorias (
  evento_id  INT UNSIGNED NOT NULL,
  categoria  VARCHAR(60)  NOT NULL,

  PRIMARY KEY (evento_id, categoria),
  KEY idx_eventos_categorias_categoria (categoria),

  CONSTRAINT fk_eventos_categorias_evento
    FOREIGN KEY (evento_id) REFERENCES eventos (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Respaldo: cada actividad ya publicada entra con su categoría de siempre
-- como único renglón. A partir de aquí, quien la edite y marque una segunda
-- categoría suma un renglón más sin tocar este primero.
INSERT IGNORE INTO eventos_categorias (evento_id, categoria)
SELECT id, categoria FROM eventos;
