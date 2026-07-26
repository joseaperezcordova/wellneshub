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
-- ============================================================================


-- ----------------------------------------------------------------------------
--  usuarios
--
--  password_hash admite NULL a propósito: quien entra con Google nunca elige
--  contraseña, y guardar una cadena vacía o inventada obligaría a distinguir
--  "sin contraseña" de "contraseña rara" en cada consulta.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  nombre              VARCHAR(120)     NOT NULL,

  -- 190 y no 255: con utf8mb4 un índice UNIQUE sobre VARCHAR(255) supera el
  -- límite de 767 bytes de InnoDB en MySQL 5.6 y versiones viejas de MariaDB,
  -- que es lo que suele haber en hosting compartido.
  email               VARCHAR(190)     NOT NULL,
  email_verificado_en DATETIME         NULL DEFAULT NULL,

  password_hash       VARCHAR(255)     NULL DEFAULT NULL,
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
--    · Una persona puede tener contraseña Y Google sobre la misma cuenta. Con
--      una columna suelta eso se puede modelar, pero añadir Apple o Facebook
--      más adelante obliga a otra columna, y otra, y otra.
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
--  intentos_login
--
--  Un formulario de login sin freno se prueba a miles de contraseñas por
--  minuto. Se registra cada intento fallido por correo y por IP, y el login se
--  bloquea temporalmente al pasar del umbral.
--
--  Se guarda también la IP porque frenar solo por correo permite atacar muchas
--  cuentas distintas desde un mismo sitio, y frenar solo por IP deja pasar los
--  ataques repartidos entre varias.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS intentos_login (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email      VARCHAR(190)    NOT NULL,
  ip         VARBINARY(16)   NOT NULL COMMENT 'inet6_pton / inet_pton: vale para IPv4 e IPv6',
  exito      TINYINT(1)      NOT NULL DEFAULT 0,
  creado_en  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_intentos_email_fecha (email, creado_en),
  KEY idx_intentos_ip_fecha (ip, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
--  DESPUÉS DE REGISTRARTE: date permisos de administrador
--
--  No hay usuario administrador semilla en este archivo. Un usuario con
--  contraseña conocida y escrita en el repositorio es una puerta abierta desde
--  el minuto uno, y en un hosting compartido nadie se acuerda de cambiarla.
--
--  El camino correcto: regístrate por la interfaz y luego ejecuta esto una vez,
--  con tu correo:
--
--      UPDATE usuarios SET rol = 'admin' WHERE email = 'tucorreo@ejemplo.com';
-- ============================================================================
