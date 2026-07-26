-- ============================================================================
--  Wellneshub · Migración 03 — reportes de eventos
--
--  PARA QUIÉN ES ESTE ARCHIVO
--  Para una base que ya tiene la tabla eventos. Si instalas de cero, schema.sql
--  ya lo incluye.
--
--  CÓMO EJECUTARLO (hosting sin SSH):
--    1. cPanel → phpMyAdmin
--    2. Selecciona tu base de datos en el panel izquierdo
--    3. Pestaña "SQL" → pega esto → Continuar
--
--  Es idempotente.
-- ============================================================================


-- ----------------------------------------------------------------------------
--  reportes
--
--  EL MODELO DE MODERACIÓN
--
--  Los eventos se publican solos. Nadie revisa nada por adelantado, porque
--  revisar 99 eventos correctos para encontrar uno malo es trabajo que no se
--  sostiene. Lo que se revisa es lo que alguien señala.
--
--  Reportar no esconde el evento. Si bastara un reporte para tumbar una ficha,
--  tumbar la competencia costaría un clic. El reporte solo avisa; ocultar o
--  borrar lo decide una persona.
--
--  ORIGEN: quién levantó la mano
--
--  'visitante' es alguien que pulsó el botón. 'automatico' lo pone el filtro de
--  palabras al publicar. Van a la misma tabla a propósito: un solo sitio donde
--  mirar, una sola bandeja que vaciar. Si el filtro tuviera su propia lista,
--  habría dos pantallas que revisar y una se acabaría olvidando.
--
--  LA IP
--
--  Guardarla es lo que permite el límite de un reporte por evento y día. Se
--  guarda la del visitante de verdad, no la del proxy —ver ipCliente() en
--  config.php—, porque detrás de nginx todas las peticiones parecen venir de
--  127.0.0.1 y el límite valdría para el sitio entero.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reportes (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  evento_id    INT UNSIGNED    NOT NULL,

  motivo       ENUM('spam','enganoso','inapropiado','no_es_wellness','duplicado','otro')
                               NOT NULL,
  comentario   VARCHAR(1000)   NULL DEFAULT NULL,

  origen       ENUM('visitante','automatico')
                               NOT NULL DEFAULT 'visitante',

  ip           VARBINARY(16)   NOT NULL,

  situacion    ENUM('pendiente','revisado')
                               NOT NULL DEFAULT 'pendiente',
  revisado_por INT UNSIGNED    NULL DEFAULT NULL,
  revisado_en  DATETIME        NULL DEFAULT NULL,

  creado_en    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),

  KEY idx_reportes_evento (evento_id, creado_en),
  KEY idx_reportes_ip (ip, evento_id, creado_en),
  KEY idx_reportes_bandeja (situacion, creado_en),

  -- Si el evento se borra, sus reportes ya no significan nada.
  CONSTRAINT fk_reporte_evento
    FOREIGN KEY (evento_id) REFERENCES eventos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  -- Si se borra la cuenta del administrador que revisó, el reporte se queda:
  -- lo que importa es que se revisó, no quién. SET NULL y no CASCADE.
  CONSTRAINT fk_reporte_revisor
    FOREIGN KEY (revisado_por) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
