-- ============================================================================
--  Wellneshub · migración 04 — el punto en el mapa
--
--  Añade a los eventos el enlace de Google Maps que pega el organizador y las
--  coordenadas que sacamos de él. El enlace se guarda solo para poder volver a
--  enseñarlo en el formulario; quien manda son la latitud y la longitud.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  OJO: phpMyAdmin se PARA en el primer error y no ejecuta lo que venga
--  después, sin decirlo muy alto. Si alguna línea falla porque ya la habías
--  ejecutado antes, quita esa línea y vuelve a lanzar el resto.
--
--  Se puede ejecutar sobre una base con eventos dentro: las tres columnas
--  nacen vacías y un evento sin coordenadas simplemente no enseña mapa.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  --  El enlace tal cual lo pegó el organizador. 500 caracteres porque las
  --  direcciones largas de Google Maps —las que llevan el bloque !3d!4d— pasan
  --  con facilidad de 300.
  ADD COLUMN mapa_url VARCHAR(500) NULL DEFAULT NULL
      COMMENT 'Enlace de Google Maps tal como lo pegó el organizador'
      AFTER lugar,

  --  DECIMAL y no FLOAT: aquí una coordenada es un dato, no una medida, y con
  --  FLOAT el valor que se guarda no es exactamente el que se leyó.
  --  (10,7) da siete decimales, que son once milímetros. De sobra.
  ADD COLUMN latitud  DECIMAL(10,7) NULL DEFAULT NULL AFTER mapa_url,
  ADD COLUMN longitud DECIMAL(10,7) NULL DEFAULT NULL AFTER latitud;

--  Para el día que haya un "eventos cerca de mí". Todavía no lo usa nadie, pero
--  crearlo ahora cuesta cero y crearlo con la tabla llena cuesta un rato.
ALTER TABLE eventos
  ADD INDEX idx_eventos_punto (latitud, longitud);
