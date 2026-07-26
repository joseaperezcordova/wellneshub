-- ============================================================================
--  Wellneshub · Migración 02 — eventos
--
--  PARA QUIÉN ES ESTE ARCHIVO
--  Para una base que ya tiene usuarios, identidades_oauth y codigos_acceso.
--  Si instalas de cero, schema.sql ya incluye todo esto.
--
--  CÓMO EJECUTARLO (hosting sin SSH):
--    1. cPanel → phpMyAdmin
--    2. Selecciona tu base de datos en el panel izquierdo
--    3. Pestaña "SQL" → pega esto → Continuar
--
--  Es idempotente: puedes volver a ejecutarlo sin romper nada.
-- ============================================================================


-- ----------------------------------------------------------------------------
--  eventos
--
--  DOS NOMBRES QUE CHOCAN, Y CÓMO SE RESUELVEN
--
--  "Estado" significa dos cosas distintas en este proyecto: la entidad
--  federativa (Jalisco, Oaxaca) y la situación de la publicación (borrador,
--  publicado). Llamar "estado" a las dos columnas garantiza que alguien lea
--  mal una consulta algún día. Aquí son 'entidad' y 'situacion'.
--
--  EL RELOJ DE LAS 24 HORAS
--
--  publicado_en no es lo mismo que creado_en, y la diferencia es justo la regla
--  del producto: el organizador puede corregir su evento durante 24 horas
--  DESDE QUE LO PUBLICA, no desde que empezó a escribirlo. Un borrador que pasó
--  tres días a medias no gasta ese margen.
--
--  Queda NULL mientras es borrador, que además es lo que distingue un evento
--  sin publicar de uno publicado sin tener que mirar dos columnas.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS eventos (
  id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  usuario_id    INT UNSIGNED  NOT NULL,

  titulo        VARCHAR(160)  NOT NULL,

  -- Para URLs legibles: /evento.php?e=amanecer-en-el-cenote-12
  slug          VARCHAR(190)  NOT NULL,

  descripcion   TEXT          NOT NULL,
  categoria     VARCHAR(60)   NOT NULL,

  ciudad        VARCHAR(90)   NOT NULL,
  entidad       VARCHAR(90)   NOT NULL COMMENT 'Entidad federativa: Jalisco, Oaxaca…',
  lugar         VARCHAR(160)  NULL DEFAULT NULL COMMENT 'Nombre del sitio, si lo hay',

  fecha_inicio  DATETIME      NOT NULL,
  fecha_fin     DATETIME      NULL DEFAULT NULL,

  -- Gratuito se guarda aparte del precio en vez de deducirlo de precio = 0.
  -- "Gratis" y "todavía no sé el precio" son cosas distintas, y con un solo
  -- campo se confunden: un evento sin precio decidido aparecería como gratuito.
  gratuito      TINYINT(1)    NOT NULL DEFAULT 0,
  precio        DECIMAL(10,2) NULL DEFAULT NULL,

  url_boletos   VARCHAR(500)  NULL DEFAULT NULL,
  imagen_url    VARCHAR(500)  NULL DEFAULT NULL,

  -- El color del marcador de posición cuando no hay imagen, que es como el
  -- diseño pinta las tarjetas desde el prototipo.
  color         CHAR(7)       NOT NULL DEFAULT '#89A67D',

  situacion     ENUM('borrador','publicado','oculto')
                              NOT NULL DEFAULT 'borrador',
  publicado_en  DATETIME      NULL DEFAULT NULL,

  creado_en     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_eventos_slug (slug),

  -- La consulta de la portada es "publicados, de hoy en adelante, por fecha".
  -- Este índice la resuelve entera sin ordenar en memoria.
  KEY idx_eventos_agenda (situacion, fecha_inicio),
  KEY idx_eventos_usuario (usuario_id, creado_en),
  KEY idx_eventos_categoria (categoria, fecha_inicio),

  -- Si se borra la cuenta, se van sus eventos. La alternativa —dejarlos
  -- huérfanos— llena la portada de fichas sin nadie a quien preguntar.
  CONSTRAINT fk_evento_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
