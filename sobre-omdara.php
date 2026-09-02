<?php
/**
 * Sobre Omdara: quiénes somos, misión, visión y en qué creemos.
 *
 * Requerimiento del cliente, 2026-09-02: una página propia enlazada solo
 * desde el pie —no del menú principal—, con el texto tal como lo mandó. Mismo
 * criterio que terminos-y-condiciones.php: es texto que compromete a la
 * empresa y lo redacta quien responde de él, así que aquí no se corrige ni se
 * completa, a diferencia de lo que se hizo en «¿Cómo funciona?» o en las
 * preguntas frecuentes.
 *
 * Sin traducir a propósito, igual que el resto de páginas de contenido
 * (¿Cómo funciona?, preguntas frecuentes, las tres legales): el armazón del
 * sitio —cabecera, pie, el enlace mismo— sí tiene su versión en inglés, pero
 * el cuerpo del texto queda pendiente de que llegue esa traducción. Ver
 * docs/pendientes.md, sección 3.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

$titulo      = t('pagina.sobre_omdara.titulo');
$descripcion = t('pagina.sobre_omdara.meta');
$anchoLibre  = true;

/** Título + una frase. En array por lo mismo que $motivos en como-funciona.php:
 *  se leen seguidos al revisarlos y no hace falta repetir el marcado por cada uno. */
$valores = [
    ['Comunidad',    'Creemos en el poder de conectar personas.'],
    ['Bienestar',    'Promovemos una vida saludable e integral.'],
    ['Confianza',    'Presentamos información clara y verificable.'],
    ['Inclusión',    'Buscamos que el bienestar sea accesible para todos.'],
    ['Autenticidad', 'Damos visibilidad a proyectos y experiencias reales.'],
];

require __DIR__ . '/includes/layout.php';
?>

<section class="wrap block">
  <div class="block-head">
    <div>
      <h1 style="margin-top:6px;">Sobre Omdara</h1>
    </div>
  </div>

  <p class="guia-intro">Omdara reúne actividades, eventos, talleres, retiros y experiencias de
     bienestar en un solo lugar. Facilitamos el descubrimiento de nuevas opciones y conectamos a
     las personas con quienes crean y organizan estas experiencias.</p>

  <div class="sobre-bloque">
    <h2>Nuestra misión</h2>
    <p>Ayudar a las personas a encontrar las experiencias y herramientas que necesitan para vivir
       mejor.</p>
  </div>

  <div class="sobre-bloque">
    <h2>Nuestra visión</h2>
    <p>Lograr que el bienestar sea una posibilidad accesible para cualquier persona, en cualquier
       lugar.</p>
  </div>

  <div class="sobre-bloque">
    <h2>Lo que creemos</h2>
    <ul class="valores-grid">
      <?php foreach ($valores as [$nombre, $texto]): ?>
        <li class="valor">
          <h3><?= e($nombre) ?></h3>
          <p><?= e($texto) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php pie(); ?>
