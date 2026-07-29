-- ============================================================================
--  Wellneshub · Esquema de autenticación
--  MySQL / MariaDB · InnoDB · utf8mb4
--
--  CÓMO EJECUTARLO (hosting sin SSH):
--    1. Entra a cPanel → phpMyAdmin
--    2. Selecciona tu base de datos en el panel izquierdo (NO uses "SQL" sin
--       haberla seleccionado antes, o creará las tablas donde no toca)
--    3. Pestaña "Importar" → elige este archivo → Continuar
--       (o pestaña "SQL" → pega el contenido → Continuar)
--
--  Este archivo es idempotente: puedes volver a ejecutarlo sin romper nada.
--  No incluye CREATE DATABASE a propósito — en hosting compartido la base la
--  crea el panel con un prefijo de cuenta y el nombre no se puede adivinar.
--
--  ¿YA TENÍAS LA VERSIÓN ANTERIOR, la de contraseñas? Entonces no es este el
--  archivo que buscas: ejecuta migracion-01-codigos-por-correo.sql.
-- ============================================================================


-- ----------------------------------------------------------------------------
--  usuarios
--
--  No hay columna de contraseña, y no es un olvido. Aquí se entra de dos
--  maneras y ninguna la necesita: con Google, o pidiendo un código de un solo
--  uso al correo. Una contraseña que nadie usa es solo una cosa más que se
--  puede filtrar, reutilizar entre sitios y perder.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  nombre              VARCHAR(120)     NOT NULL,

  -- 190 y no 255: con utf8mb4 un índice UNIQUE sobre VARCHAR(255) supera el
  -- límite de 767 bytes de InnoDB en MySQL 5.6 y versiones viejas de MariaDB,
  -- que es lo que suele haber en hosting compartido.
  email               VARCHAR(190)     NOT NULL,
  email_verificado_en DATETIME         NULL DEFAULT NULL,

  avatar_url          VARCHAR(500)     NULL DEFAULT NULL,

  rol                 ENUM('visitante','organizador','admin')
                                       NOT NULL DEFAULT 'visitante',
  estado              ENUM('activo','suspendido')
                                       NOT NULL DEFAULT 'activo',

  ultimo_acceso_en    DATETIME         NULL DEFAULT NULL,
  creado_en           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                       ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
--  identidades_oauth
--
--  Tabla aparte en vez de una columna google_id en usuarios. Dos razones:
--
--    · Una persona puede entrar hoy con Google y mañana con un código al mismo
--      correo, sobre la misma cuenta. Con una columna suelta eso se modela,
--      pero añadir Apple o Facebook más adelante obliga a otra columna, y otra.
--    · El identificador de Google que se guarda es "sub", no el correo. El
--      correo de una cuenta de Google puede cambiar; "sub" no cambia nunca.
--      Emparejar por correo es lo que permite que alguien secuestre una cuenta
--      registrando ese correo en otro proveedor.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS identidades_oauth (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id       INT UNSIGNED NOT NULL,

  proveedor        VARCHAR(30)  NOT NULL DEFAULT 'google',
  proveedor_uid    VARCHAR(191) NOT NULL COMMENT 'El "sub" de Google: estable de por vida',
  email_proveedor  VARCHAR(190) NULL DEFAULT NULL,

  creado_en        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_identidad_proveedor (proveedor, proveedor_uid),
  KEY idx_identidad_usuario (usuario_id),
  CONSTRAINT fk_identidad_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
--  codigos_acceso
--
--  Los códigos de un solo uso que se mandan por correo.
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
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS eventos (
  id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  usuario_id    INT UNSIGNED  NOT NULL,

  titulo        VARCHAR(160)  NOT NULL,
  slug          VARCHAR(190)  NOT NULL,

  descripcion   TEXT          NOT NULL,
  categoria     VARCHAR(60)   NOT NULL,

  -- De un día (de siempre) o recurrente. Si es recurrente, frecuencia y
  -- hora_recurrente vienen llenas; fecha_inicio/fecha_fin siguen siendo el
  -- rango real —primera y última ocurrencia—, no un dato aparte.
  tipo_actividad  ENUM('unico','recurrente')
                                NOT NULL DEFAULT 'unico',
  frecuencia      ENUM('diaria','semanal','quincenal','mensual')
                                NULL DEFAULT NULL,
  hora_recurrente TIME          NULL DEFAULT NULL,

  -- Presencial, en línea o híbrida. "En línea" es la única que no lleva
  -- ciudad/entidad/lugar/mapa —validarEvento() los deja vacíos aunque
  -- lleguen del formulario—, así que una ficha en internet no dice que
  -- ocurre en un sitio físico que no existe.
  modalidad     ENUM('presencial','en_linea','hibrida')
                              NOT NULL DEFAULT 'presencial',

  ciudad        VARCHAR(90)   NOT NULL,
  entidad       VARCHAR(90)   NOT NULL COMMENT 'Entidad federativa: Jalisco, Oaxaca…',
  lugar         VARCHAR(160)  NULL DEFAULT NULL,

  -- El punto en el mapa. El enlace se guarda solo para poder volver a
  -- enseñarlo en el formulario; quien manda son latitud y longitud, que es lo
  -- que pinta la ficha y lo que serviría para buscar por cercanía.
  -- DECIMAL y no FLOAT: una coordenada aquí es un dato, no una medida.
  mapa_url      VARCHAR(500)  NULL DEFAULT NULL COMMENT 'Enlace de Google Maps tal como lo pegó el organizador',
  latitud       DECIMAL(10,7) NULL DEFAULT NULL,
  longitud      DECIMAL(10,7) NULL DEFAULT NULL,

  -- Aparte de url_boletos: ese es el enlace de compra/registro externo, este
  -- es un acceso —videollamada, transmisión, grupo— que puede acompañar a
  -- cualquier modalidad, incluida una actividad presencial.
  enlace_acceso VARCHAR(500)  NULL DEFAULT NULL,

  fecha_inicio  DATETIME      NOT NULL,
  fecha_fin     DATETIME      NULL DEFAULT NULL,

  -- Gratuito aparte del precio en vez de deducirlo de precio = 0: "gratis" y
  -- "todavía no sé el precio" son cosas distintas y con un solo campo se
  -- confunden.
  gratuito      TINYINT(1)    NOT NULL DEFAULT 0,
  precio        DECIMAL(10,2) NULL DEFAULT NULL,

  url_boletos   VARCHAR(500)  NULL DEFAULT NULL,
  imagen_url    VARCHAR(500)  NULL DEFAULT NULL,
  color         CHAR(7)       NOT NULL DEFAULT '#89A67D',

  situacion     ENUM('borrador','publicado','oculto')
                              NOT NULL DEFAULT 'borrador',
  publicado_en  DATETIME      NULL DEFAULT NULL,

  creado_en     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_eventos_slug (slug),
  KEY idx_eventos_agenda (situacion, fecha_inicio),
  KEY idx_eventos_usuario (usuario_id, creado_en),
  KEY idx_eventos_categoria (categoria, fecha_inicio),
  -- Para el día que haya un «eventos cerca de mí». Todavía no lo usa nadie.
  KEY idx_eventos_punto (latitud, longitud),

  CONSTRAINT fk_evento_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
--  reportes
--
--  Los eventos se publican solos: revisar 99 correctos para encontrar uno malo
--  es trabajo que no se sostiene. Lo que se revisa es lo que alguien señala.
--
--  Reportar no esconde el evento. Si bastara un reporte para tumbar una ficha,
--  tumbar la competencia costaría un clic. El reporte avisa; ocultar o borrar
--  lo decide una persona.
--
--  'origen' distingue el reporte de un visitante del que crea solo el filtro de
--  palabras. Van a la misma tabla a propósito: una sola bandeja que vaciar.
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

  CONSTRAINT fk_reporte_evento
    FOREIGN KEY (evento_id) REFERENCES eventos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_reporte_revisor
    FOREIGN KEY (revisado_por) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
--  DESPUÉS DE ENTRAR LA PRIMERA VEZ: date permisos de administrador
--
--  No hay usuario administrador semilla en este archivo. Un administrador
--  creado a ciegas desde el repositorio es una puerta abierta desde el minuto
--  uno, y en un hosting compartido nadie se acuerda de cerrarla.
--
--  El camino correcto: entra por la interfaz —con Google o con un código— y
--  luego ejecuta esto una vez, con tu correo:
--
--      UPDATE usuarios SET rol = 'admin' WHERE email = 'tucorreo@ejemplo.com';
-- ============================================================================
