<?php
/**
 * La documentación técnica, vista desde el panel de administración.
 *
 * docs/ y el README no se sirven nunca por HTTP directo —hay un .htaccess
 * que lo corta, igual que con includes/— porque algunos de estos documentos
 * explican cómo darse permisos de administrador o qué cuentas hay que
 * transferir. Este archivo es la única puerta: valida sesión de admin y lee
 * los archivos del disco, nunca a partir de una ruta que llegue del visitante
 * —por eso CATALOGO_DOCUMENTOS es una lista cerrada y documentoValido() solo
 * acepta una clave que ya esté en ella—.
 */

declare(strict_types=1);

/**
 * @return array<string, array{titulo:string, descripcion:string, archivo:string, tipo:string}>
 */
function catalogoDocumentos(): array
{
    $raiz = dirname(__DIR__);

    return [
        'readme' => [
            'titulo'      => 'README',
            'descripcion' => 'Visión general: stack, estructura de carpetas, puesta en marcha en local, variables de entorno y despliegue.',
            'archivo'     => $raiz . '/README.md',
            'tipo'        => 'md',
        ],
        'base-de-datos' => [
            'titulo'      => 'Base de datos',
            'descripcion' => 'El modelo de datos: las ocho tablas, cómo se relacionan, y el histórico completo de migraciones.',
            'archivo'     => $raiz . '/docs/base-de-datos.md',
            'tipo'        => 'md',
        ],
        'integraciones' => [
            'titulo'      => 'Integraciones',
            'descripcion' => 'Cada servicio externo —Google, analítica, mapas, captcha, correo—: para qué se usa y qué pasa si falla.',
            'archivo'     => $raiz . '/docs/integraciones.md',
            'tipo'        => 'md',
        ],
        'operacion' => [
            'titulo'      => 'Operación',
            'descripcion' => 'Servidor, dominio, copias de seguridad, cómo se actualiza el sitio en producción, y acceso al repositorio.',
            'archivo'     => $raiz . '/docs/operacion.md',
            'tipo'        => 'md',
        ],
        'pase-a-produccion' => [
            'titulo'      => 'Pase a producción',
            'descripcion' => 'Qué cuenta de cada servicio hay que transferir o recrear para no depender de quien programó el sitio.',
            'archivo'     => $raiz . '/docs/pase-a-produccion.md',
            'tipo'        => 'md',
        ],
        'pase-a-produccion-html' => [
            'titulo'      => 'Pase a producción — referencia rápida',
            'descripcion' => 'El config.local.php de producción, anotado línea por línea: qué token es de quién.',
            'archivo'     => $raiz . '/docs/pase-a-produccion.html',
            'tipo'        => 'html',
        ],
        'pruebas' => [
            'titulo'      => 'Guía de pruebas',
            'descripcion' => '100 comprobaciones manuales del sitio, repartidas en once secciones.',
            'archivo'     => $raiz . '/docs/pruebas.html',
            'tipo'        => 'html',
        ],
    ];
}

/** Nunca acepta una ruta que venga del visitante — solo una clave del catálogo. */
function documentoValido(string $clave): ?array
{
    $doc = catalogoDocumentos()[$clave] ?? null;

    return ($doc !== null && is_file($doc['archivo'])) ? $doc : null;
}

/**
 * Convierte el markdown de estos documentos a HTML.
 *
 * No es un conversor de markdown genérico: cubre justo lo que se usa en
 * README.md y docs/*.md —encabezados, párrafos, negritas, código en línea y
 * en bloque, enlaces, listas, tablas, citas y líneas horizontales— porque son
 * documentos propios y se sabe de antemano qué sintaxis traen. Si algún día
 * se escribe un documento con algo que esto no cubre, se ve tal cual en vez
 * de romper la página: cada línea que no reconoce la deja como párrafo.
 */
function markdownAHtml(string $md): string
{
    $lineas = explode("\n", str_replace("\r\n", "\n", $md));
    $html = '';
    $enCodigo = false;
    $enLista = false;
    $enCita = [];
    $filaTabla = 0;

    $cerrarLista = function () use (&$html, &$enLista) {
        if ($enLista) { $html .= "</ul>\n"; $enLista = false; }
    };
    $cerrarCita = function () use (&$html, &$enCita) {
        if ($enCita) { $html .= '<blockquote>' . inlineMd(implode(' ', $enCita)) . "</blockquote>\n"; $enCita = []; }
    };

    $totalLineas = count($lineas);
    for ($i = 0; $i < $totalLineas; $i++) {
        $linea = $lineas[$i];

        // --- bloque de código ---
        if (preg_match('/^```/', $linea)) {
            if (!$enCodigo) {
                $cerrarLista(); $cerrarCita();
                $html .= "<pre><code>";
                $enCodigo = true;
            } else {
                $html .= "</code></pre>\n";
                $enCodigo = false;
            }
            continue;
        }
        if ($enCodigo) {
            $html .= e($linea) . "\n";
            continue;
        }

        // --- tabla: fila de cabecera seguida de |---|---| ---
        if (preg_match('/^\s*\|(.+)\|\s*$/', $linea, $m)
            && isset($lineas[$i + 1])
            && preg_match('/^\s*\|[\s:|-]+\|\s*$/', $lineas[$i + 1])
        ) {
            $cerrarLista(); $cerrarCita();
            $cabeceras = array_map('trim', explode('|', trim($m[1], '|')));
            $html .= "<div class=\"doc-tabla\"><table><thead><tr>";
            foreach ($cabeceras as $c) $html .= '<th>' . inlineMd($c) . '</th>';
            $html .= "</tr></thead><tbody>\n";
            $i += 2; // salta la fila de cabecera y la de guiones
            while ($i < $totalLineas && preg_match('/^\s*\|(.+)\|\s*$/', $lineas[$i], $mf)) {
                $celdas = array_map('trim', explode('|', trim($mf[1], '|')));
                $html .= '<tr>';
                foreach ($celdas as $c) $html .= '<td>' . inlineMd($c) . '</td>';
                $html .= "</tr>\n";
                $i++;
            }
            $html .= "</tbody></table></div>\n";
            $i--; // el for principal vuelve a sumar 1
            continue;
        }

        // --- encabezados ---
        if (preg_match('/^(#{1,4})\s+(.*)$/', $linea, $m)) {
            $cerrarLista(); $cerrarCita();
            $nivel = strlen($m[1]);
            $html .= "<h$nivel>" . inlineMd($m[2]) . "</h$nivel>\n";
            continue;
        }

        // --- línea horizontal ---
        if (preg_match('/^-{3,}\s*$/', $linea)) {
            $cerrarLista(); $cerrarCita();
            $html .= "<hr>\n";
            continue;
        }

        // --- cita ---
        if (preg_match('/^>\s?(.*)$/', $linea, $m)) {
            $cerrarLista();
            $enCita[] = $m[1];
            continue;
        }
        $cerrarCita();

        // --- lista ---
        if (preg_match('/^[-*]\s+(.*)$/', $linea, $m)) {
            if (!$enLista) { $html .= "<ul>\n"; $enLista = true; }
            $html .= '<li>' . inlineMd($m[1]) . "</li>\n";
            continue;
        }
        $cerrarLista();

        // --- línea en blanco ---
        if (trim($linea) === '') {
            continue;
        }

        // --- párrafo (junta líneas seguidas hasta la próxima en blanco) ---
        $parrafo = [$linea];
        while ($i + 1 < $totalLineas && trim($lineas[$i + 1]) !== ''
            && !preg_match('/^(#{1,4}\s|[-*]\s|>|\s*\|.+\|\s*$|```)/', $lineas[$i + 1])
        ) {
            $i++;
            $parrafo[] = $lineas[$i];
        }
        $html .= '<p>' . inlineMd(implode(' ', $parrafo)) . "</p>\n";
    }
    $cerrarLista();
    $cerrarCita();
    if ($enCodigo) $html .= "</code></pre>\n"; // bloque sin cerrar, por si acaso

    return $html;
}

/** Negrita, código en línea y enlaces, dentro de una línea ya sin HTML propio. */
function inlineMd(string $texto): string
{
    $texto = e($texto);
    $texto = preg_replace('/`([^`]+)`/', '<code>$1</code>', $texto);
    $texto = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $texto);
    $texto = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $texto);

    return $texto;
}
