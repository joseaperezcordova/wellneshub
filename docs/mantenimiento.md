# Mantenimiento mensual: qué entra y qué se cotiza

El sitio tiene un plan de mantenimiento de **$750 MXN al mes**. El documento
formal que se le entregó al cliente está en
https://claude.ai/code/artifact/412bf066-2c66-4139-a9c2-9d2e1168991a
(redactado el 2026-09-24).

Este archivo sirve para decidir, ante cada solicitud del cliente, si entra en
el mantenimiento o se cotiza aparte.

## Entra en el mantenimiento

| Área | Qué cubre |
| --- | --- |
| Funcionamiento | Que el sitio, el formulario de publicación, el buscador y el panel admin funcionen; caídas causadas por el sitio. |
| Corrección de errores | Fallas en funciones que **ya existen**, sin límite de horas. |
| Seguridad | Certificado SSL, actualizaciones de seguridad del código, revisión de cuentas admin. |
| Respaldos | Uno al mes, de la base de datos y las imágenes, guardado fuera del hosting; restauración si hace falta. |
| Correos del sistema | Que lleguen los códigos de acceso, las solicitudes de información y los avisos a administradores. |
| Cambios menores | **Hasta 2 horas al mes**, no acumulables, sobre lo que ya existe: agregar o renombrar categorías, cambiar textos, FAQ o textos legales (con el texto que dé el cliente), correos de contacto, imágenes. |
| Soporte al administrador | Dudas sobre cómo usar el panel (moderación, reportes, solicitudes). |
| Reporte mensual | Resumen de los trabajos del mes. |

## Se cotiza aparte

- **Funciones nuevas o cambios en cómo funciona algo:** nuevos tipos de
  actividad, pagos en línea, secciones nuevas, cambiar el comportamiento del
  formulario, etc.
- **Rediseño:** diseño, colores, tipografía, estructura de las páginas.
- **Cambios menores que pasen de las 2 horas del mes.**
- **Costos de terceros:** hosting, dominio, cuentas de correo. Los paga el cliente directamente.
- **Contenido y moderación:** redactar textos, revisar o publicar actividades,
  atender reportes. Eso lo hace el administrador del sitio.
- **Fallas de terceros:** hosting, Google u otros servicios externos. Se les da
  seguimiento, pero no se garantiza la solución.

## Cómo clasificar una solicitud

1. ¿Es un error en algo que ya existía y dejó de funcionar o funciona mal?
   → **Mantenimiento** (corrección de errores).
2. ¿Es un ajuste de contenido o configuración sobre algo que ya existe, sin
   cambiar cómo funciona (texto, categoría, correo, imagen)? → **Mantenimiento**
   (cambio menor), mientras quepa en las 2 horas del mes.
3. ¿Agrega algo que el sitio no hacía, o cambia cómo funciona? → **Se cotiza**.
4. ¿Es diseño? → **Se cotiza**.

Al responder, decir en qué categoría cae, por qué (citando la fila de arriba)
y, si se cotiza, una estimación de horas.

Ejemplos ya clasificados:

| Solicitud | Resultado |
| --- | --- |
| Agregar una categoría nueva | Mantenimiento (cambio menor) |
| Cambiar el texto de una pregunta frecuente | Mantenimiento (cambio menor) |
| Recordatorio por correo antes de que venza una actividad recurrente | Se cotiza (función nueva) |

## Pendiente de confirmar con el cliente

El documento formal tiene datos entre corchetes que todavía no se llenan:
nombres, fecha de inicio, IVA, canal y horario de soporte, día de pago y
tarifa de la hora extra. Las 2 horas al mes y que el hosting y el dominio los
paga el cliente son supuestos del borrador. Si cambian, actualizar este archivo.
