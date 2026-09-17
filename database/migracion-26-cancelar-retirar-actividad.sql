-- ============================================================================
--  OMDARA · migración 26 — cancelar actividad, historial de cambio de fecha,
--  y retiro sin borrado permanente
--
--  Origen: documento del cliente "Req. 17092026" (requerimientos 2 y 8), con
--  3 decisiones que confirmó por escrito el 2026-09-17 (ver memoria de
--  proyecto req_17092026_decisiones_cliente):
--
--    1. CANCELADA es un estado PÚBLICO nuevo y DISTINTO de OCULTO. Cuando el
--       organizador cancela su propia actividad, la ficha sigue siendo
--       visible, con el aviso "CANCELADA". 'oculto' (moderación del admin)
--       no cambia de significado: sigue siendo invisible al público —ver
--       puedeVerEvento() en includes/eventos.php—. Por eso este archivo
--       agrega 'cancelado' como valor nuevo del ENUM en vez de reutilizar
--       'oculto'.
--
--    2. El organizador NUNCA vuelve a borrar una actividad de verdad. El
--       botón "Eliminar" (evento.php, dentro de las 24 h) deja de hacer
--       DELETE y pasa a ocultarla.
--
--    3. El administrador TAMPOCO borra de verdad, ni siquiera desde
--       moderación (moderacion.php). Incluido ahí.
--
--  Los puntos 2 y 3 son cambio de LÓGICA (includes/eventos.php, evento.php,
--  moderacion.php) y no tocan el esquema —'oculto' ya existe—, así que no
--  están en este archivo; esta migración es solo la parte de base de datos:
--  el estado nuevo, y las dos tablas de auditoría que pide el requerimiento
--  para poder demostrar qué cambió, cuándo y quién lo pidió.
--
--  Nota para quien lea contactos.evento_id (migración 23, ON DELETE SET
--  NULL): esa columna se hizo NULL-able para que el historial de contactos
--  sobreviviera un DELETE real de la actividad. Con esta migración ese DELETE
--  deja de ocurrir en el flujo normal, así que evento_id en NULL a partir de
--  ahora solo debería verse en contactos previos a este cambio —no hace falta
--  tocar esa tabla, solo se documenta aquí para que no despiste a futuro—.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega entero
--    → Continuar.
--
--  NO es completamente idempotente: el ALTER del ENUM se puede repetir sin
--  problema (deja la misma definición), pero las dos tablas nuevas sí lo son
--  (CREATE TABLE IF NOT EXISTS) — en conjunto, ejecutarlo dos veces no hace
--  daño.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
--  eventos.situacion — nuevo valor 'cancelado'
--
--  MODIFY y no un ALTER...ADD: en MySQL/MariaDB un ENUM se redefine entero.
--  Se repiten aquí los 3 valores existentes tal cual estaban para no
--  reordenar ni perder ninguno —el orden de un ENUM importa para ORDER BY
--  implícito, aunque este proyecto no depende de eso, mejor no arriesgar—.
-- ----------------------------------------------------------------------------
ALTER TABLE eventos
  MODIFY COLUMN situacion ENUM('borrador','publicado','oculto','cancelado')
                           NOT NULL DEFAULT 'borrador';

-- Texto opcional del organizador al cancelar (Req 2A: "Información para los
-- asistentes"). Se muestra junto al aviso público de cancelación cuando no
-- está vacío; ver evento.php cuando se construya esa parte.
ALTER TABLE eventos
  ADD COLUMN info_cancelacion VARCHAR(500) NULL DEFAULT NULL AFTER situacion;


-- ----------------------------------------------------------------------------
--  eventos_historial_fecha
--
--  Req 2B: cambiar la fecha/hora de una actividad ya publicada debe dejar
--  rastro de qué decía antes, qué dice ahora, cuándo y quién lo cambió — no
--  basta con actualizado_en (genérico, no dice QUÉ cambió ni quién).
--
--  Guarda las 4 columnas de fecha/hora juntas (fecha_inicio, fecha_fin,
--  hora_recurrente, hora_fin_recurrente) en vez de solo fecha_inicio: una
--  actividad recurrente cambia de hora en hora_recurrente/hora_fin_recurrente,
--  no en fecha_inicio/fecha_fin —ver el comentario de esas columnas en
--  schema.sql—, y separar "fecha" de "hora" según el tipo de actividad habría
--  significado dos tablas o una con columnas condicionales. Una fila por
--  cambio, con las 4 columnas anteriores y las 4 nuevas, cubre los dos casos
--  sin ramificar el esquema.
--
--  TABLA APARTE y no columnas *_anterior en eventos: puede haber más de un
--  cambio de fecha en la vida de una actividad, y el requerimiento pide
--  "el historial" (plural), no solo el último salto.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS eventos_historial_fecha (
  id                            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  evento_id                     INT UNSIGNED    NOT NULL,

  fecha_inicio_anterior         DATETIME NOT NULL,
  fecha_fin_anterior            DATETIME NULL DEFAULT NULL,
  hora_recurrente_anterior      TIME     NULL DEFAULT NULL,
  hora_fin_recurrente_anterior  TIME     NULL DEFAULT NULL,

  fecha_inicio_nueva            DATETIME NOT NULL,
  fecha_fin_nueva               DATETIME NULL DEFAULT NULL,
  hora_recurrente_nueva         TIME     NULL DEFAULT NULL,
  hora_fin_recurrente_nueva     TIME     NULL DEFAULT NULL,

  -- Quién lo cambió. SET NULL y no CASCADE: si la cuenta de quien hizo el
  -- cambio se borra más adelante, el historial de LA ACTIVIDAD se queda —lo
  -- que importa conservar es que cambió y cuándo, igual que reportes.revisado_por—.
  cambiado_por                  INT UNSIGNED NULL DEFAULT NULL,
  cambiado_en                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_ehf_evento (evento_id, cambiado_en),

  CONSTRAINT fk_ehf_evento
    FOREIGN KEY (evento_id) REFERENCES eventos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_ehf_usuario
    FOREIGN KEY (cambiado_por) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
--  eventos_retiros
--
--  Req 8: el organizador puede pedir que su actividad deje de estar visible.
--  Dentro de las primeras 24 horas desde publicada lo hace directo (el mismo
--  plazo que ya usa EVENTO_MARGEN_ELIMINACION_H en includes/eventos.php, que
--  hasta hoy gobernaba borrar y ahora gobierna esto). Pasado ese plazo,
--  queda pendiente hasta que administración la revise y la oculte.
--
--  Las dos situaciones caben en la misma tabla con el mismo par de columnas
--  solicitado_en/retirado_en: cuando el organizador lo hace directo (≤24h),
--  el código llena las dos al mismo tiempo —se solicita y se retira en el
--  mismo instante—; cuando queda pendiente de administración (>24h),
--  retirado_en se queda en NULL hasta que alguien lo procese. La bandeja de
--  pendientes del panel admin es, entonces, "WHERE retirado_en IS NULL"
--  —mismo patrón que reportes.situacion, sin necesitar otra columna de
--  estado aparte—.
--
--  motivo es opcional a propósito: el requerimiento dice "motivo, cuando
--  corresponda", no que sea obligatorio.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS eventos_retiros (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  evento_id       INT UNSIGNED    NOT NULL,

  motivo          VARCHAR(500) NULL DEFAULT NULL,

  solicitado_por  INT UNSIGNED NULL DEFAULT NULL,
  solicitado_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  retirado_por    INT UNSIGNED NULL DEFAULT NULL,
  retirado_en     DATETIME     NULL DEFAULT NULL,

  PRIMARY KEY (id),
  KEY idx_er_evento (evento_id, solicitado_en),
  -- Para la bandeja de pendientes del admin: quién sigue esperando que se
  -- procese su solicitud de retiro (>24h).
  KEY idx_er_pendientes (retirado_en, solicitado_en),

  CONSTRAINT fk_er_evento
    FOREIGN KEY (evento_id) REFERENCES eventos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_er_solicitante
    FOREIGN KEY (solicitado_por) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT fk_er_ejecutor
    FOREIGN KEY (retirado_por) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
