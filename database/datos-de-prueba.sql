-- ============================================================================
--  Wellneshub · Cuatro eventos de prueba
--
--  Para ver la portada con contenido sin tener que escribir cuatro fichas a
--  mano. NO es parte del esquema: esto se ejecuta solo si lo quieres, y al
--  final del archivo está el borrado para dejarlo como estaba.
--
--  ANTES DE EJECUTARLO
--
--  Cambia el correo de la línea de abajo por el tuyo, el mismo con el que
--  entras al sitio. Los eventos necesitan un dueño que exista: si el correo no
--  coincide con ninguna cuenta, @yo queda en NULL y los INSERT fallan con un
--  error de clave foránea. Ese error es la señal de que te equivocaste de
--  correo, no de que el archivo esté mal.
--
--  CÓMO EJECUTARLO
--    cPanel → phpMyAdmin → selecciona tu base → pestaña "SQL" → pega → Continuar
--
--  Ojo con la codificación al copiar y pegar: los acentos y la ñ tienen que
--  llegar bien o los títulos saldrán con caracteres raros.
-- ============================================================================

SET NAMES utf8mb4;

SET @yo = (SELECT id FROM usuarios WHERE email = 'tucorreo@ejemplo.com' LIMIT 1);


-- ----------------------------------------------------------------------------
--  Las fechas van relativas a hoy y no escritas a pelo.
--
--  La portada solo enseña lo que no ha terminado todavía. Con fechas fijas,
--  estos cuatro eventos dejarían de aparecer en cuanto pasaran, y dentro de un
--  mes el archivo parecería roto sin estarlo.
-- ----------------------------------------------------------------------------

INSERT INTO eventos
  (usuario_id, titulo, slug, descripcion, categoria, ciudad, entidad, lugar,
   fecha_inicio, fecha_fin, gratuito, precio, url_boletos, color,
   situacion, publicado_en)
VALUES

-- 1. De pago, con enlace de reserva. El más cercano en el tiempo.
(@yo,
 'Amanecer en el Cenote — Yoga y Sonido',
 'prueba-amanecer-en-el-cenote',
 'Practicamos hatha suave sobre la plataforma de madera mientras entra la primera luz por la boca del cenote. Al terminar, un baño de sonido con cuencos de cuarzo y, para quien quiera, un nado en agua dulce.\n\nIncluye tapete, toalla y desayuno ligero. Llega quince minutos antes: la entrada al cenote se cierra al empezar.\n\nPara todos los niveles, también si nunca has hecho yoga.',
 'Yoga', 'Tulum', 'Quintana Roo', 'Cenote Zacil-Ha',
 DATE_ADD(CURDATE(), INTERVAL 6 DAY) + INTERVAL 6 HOUR, NULL,
 0, 1450.00, 'https://example.com/boletos', '#89A67D',
 'publicado', NOW()),

-- 2. Gratuito. Sirve para ver la etiqueta verde de "Gratis" en la tarjeta.
(@yo,
 'Círculo de Cacao y Luna Llena',
 'prueba-circulo-de-cacao',
 'Ceremonia abierta de cacao ceremonial de Tabasco, con círculo de palabra y meditación guiada bajo la luna llena. Sin costo: quien quiera puede dejar una aportación voluntaria para el cacao y el espacio.\n\nTrae algo cómodo para sentarte y una manta. Somos treinta personas como máximo, así que conviene llegar temprano.',
 'Ceremonia de Cacao', 'San Miguel de Allende', 'Guanajuato', 'Jardín de la Casa Naranja',
 DATE_ADD(CURDATE(), INTERVAL 13 DAY) + INTERVAL 19 HOUR, NULL,
 1, NULL, NULL, '#C76E43',
 'publicado', NOW()),

-- 3. De varios días. Comprueba que un retiro sigue en cartel mientras dura y
--    no desaparece el mismo día que empieza.
(@yo,
 'Retiro de Silencio — Tres días en la sierra',
 'prueba-retiro-de-silencio',
 'Tres días sin teléfono y sin conversación, con meditación sentada y caminando, comida vegetariana y dos entrevistas personales con la facilitadora.\n\nEl silencio empieza el viernes por la noche y se rompe el domingo después de comer. No hace falta experiencia previa, pero sí venir con la idea clara de lo que es: no es un fin de semana de descanso, es trabajo interior.\n\nAlojamiento en cabañas compartidas. Cupo de doce personas.',
 'Retiro', 'Oaxaca de Juárez', 'Oaxaca', 'Sierra Norte',
 DATE_ADD(CURDATE(), INTERVAL 21 DAY) + INTERVAL 17 HOUR,
 DATE_ADD(CURDATE(), INTERVAL 23 DAY) + INTERVAL 15 HOUR,
 0, 4200.00, NULL, '#2F4E5D',
 'publicado', NOW()),

-- 4. El más lejano, para ver el orden por fecha en el carril.
(@yo,
 'Temazcal de Cierre de Ciclo',
 'prueba-temazcal-cierre-de-ciclo',
 'Temazcal tradicional guiado por un temazcalero de la comunidad, con cuatro puertas y canto en náhuatl. Antes hay un círculo breve para nombrar lo que cada quien viene a soltar.\n\nSe entra con ropa ligera de algodón. No es recomendable si estás embarazada o tienes problemas de corazón o de presión; escríbenos si tienes dudas y lo vemos.\n\nIncluye agua de frutas y fruta fresca al salir.',
 'Temazcal', 'Guadalajara', 'Jalisco', NULL,
 DATE_ADD(CURDATE(), INTERVAL 34 DAY) + INTERVAL 18 HOUR, NULL,
 0, 850.00, NULL, '#496B52',
 'publicado', NOW());


-- ============================================================================
--  PARA BORRARLOS DESPUÉS
--
--  Todos los slugs empiezan por "prueba-", así que se van de una vez y sin
--  tocar nada que hayas publicado tú:
--
--      DELETE FROM eventos WHERE slug LIKE 'prueba-%';
--
--  Sus reportes, si llegaste a probar el botón de denunciar, se van con ellos
--  por la clave foránea en cascada.
-- ============================================================================


-- ============================================================================
--  DOS COSAS QUE PUEDES PROBAR ENCIMA
--
--  Ver uno oculto en el panel de administración, sin borrarlo:
--      UPDATE eventos SET situacion = 'oculto' WHERE slug = 'prueba-temazcal-cierre-de-ciclo';
--
--  Ver cómo se comporta un evento al que ya se le pasó el plazo de edición
--  —el aviso de "pídeselo al administrador"— sin esperar 24 horas:
--      UPDATE eventos SET publicado_en = DATE_SUB(NOW(), INTERVAL 25 HOUR)
--       WHERE slug = 'prueba-circulo-de-cacao';
-- ============================================================================
