-- ============================================================================
--  Wellneshub · migración 09 — forma de pago, cupo, sitio web y acción principal
--
--  Cuatro campos nuevos para el formulario de alta/edición:
--
--    forma_pago        · Si el precio es por toda la actividad o por sesión.
--    cupo_maximo       · Cuántas personas caben (opcional).
--    sitio_web         · Enlace informativo aparte de url_boletos —sitio
--                         propio, redes—, sin acción de compra o registro.
--    accion_principal  · Qué se espera que haga quien ve la ficha: solicitar
--                         información, comprar boletos o reservar lugar.
--                         Decide el texto del botón en la ficha.
--
--  Los eventos que ya existen quedan con accion_principal = 'informacion'
--  —el valor por defecto, el más neutro— y el resto en NULL: no hay forma de
--  saber cuál de las opciones nuevas les habría tocado.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona la base → pestaña "SQL" → pega → Continuar
--
--  Se puede ejecutar más de una vez sin hacer daño: la segunda vez falla con
--  "Duplicate column name", que solo quiere decir que ya estaba aplicada.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE eventos
  ADD COLUMN forma_pago ENUM('completa','sesion') NULL DEFAULT NULL AFTER precio,
  ADD COLUMN cupo_maximo INT UNSIGNED NULL DEFAULT NULL AFTER forma_pago,
  ADD COLUMN sitio_web VARCHAR(500) NULL DEFAULT NULL AFTER url_boletos,
  ADD COLUMN accion_principal ENUM('informacion','boletos','reservar')
                               NOT NULL DEFAULT 'informacion' AFTER sitio_web;
