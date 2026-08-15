<?php
/**
 * La casilla de aceptar los Términos y el Aviso de Privacidad (REQ-00008).
 *
 * Vive en un archivo aparte porque sale en DOS sitios —el formulario de entrada
 * y la pantalla de completar registro— y el requerimiento fija su texto palabra
 * por palabra. Escrita dos veces, un día una de las dos dice otra cosa, y la
 * que se acepta legalmente es la que se leyó.
 *
 * Los enlaces salen de url(), que es el mapa de direcciones del sitio: así
 * apuntan a /terminos-y-condiciones y /aviso-de-privacidad —lo que pide el
 * requerimiento— y siguen apuntando bien si esas páginas cambian de nombre o se
 * traducen.
 *
 * target="_blank" a propósito: leer los documentos no puede costar perder lo que
 * ya se escribió en el formulario.
 *
 * Quien lo incluya puede fijar antes:
 *   $casillaMarcada     = true;    deja la casilla marcada al repintar tras un error
 *   $casillaObligatoria = false;   sin el "required" del navegador
 *
 * Lo segundo es para login.php, que es a la vez entrar y crear cuenta: ahí un
 * "required" bloquearía también a quien solo viene a entrar y ya aceptó en su
 * día. Obligatoria lo es donde la cuenta se crea, y eso lo comprueba el
 * servidor en completar-registro.php.
 */

declare(strict_types=1);

$casillaObligatoria = $casillaObligatoria ?? true;
?>
<label class="casilla-legal">
  <input type="checkbox" name="acepto" value="1"<?= !empty($casillaMarcada) ? ' checked' : '' ?><?= $casillaObligatoria ? ' required' : '' ?>>
  <span>He leído y acepto los
    <a href="<?= e(url('terminos')) ?>" target="_blank" rel="noopener">Términos y Condiciones</a>
    y el
    <a href="<?= e(url('privacidad')) ?>" target="_blank" rel="noopener">Aviso de Privacidad</a>
    de OMDARA.</span>
</label>
