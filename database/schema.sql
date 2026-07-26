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
