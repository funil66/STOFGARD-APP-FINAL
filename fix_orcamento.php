<?php

$path = 'resources/views/pdf/orcamento.blade.php';
$content = file_get_contents($path);

// CSS Replacement
$oldHeader = '/\.header \{[^}]+\}\s*\}/s'; // Since it contains another nested {} maybe? No it doesn\'t.
// Actually let's just do str_replace for the exact CSS rules.
$oldCSS = '        /* HEADER FIXO - Topo absoluto da página */
        .header {
            position: fixed; 
            top: 0;
            left: 1cm;
            right: 1cm; 
            height: 3.8cm;  /* Reduzido levemente de 4.5cm para 3.8cm para subir a régua */
            padding-top: 0.5cm;
            border-bottom: 3px solid {{ $primary }};
            display: flex;
            background: white;
            z-index: 1000;
        }

        /* FOOTER FIXO - Rodapé absoluto da página */
        .footer {
            position: fixed; 
            bottom: 0;
            left: 1cm; 
            right: 1cm; 
            height: 2.5cm;  /* Aumentado de 2cm para 2.5cm */
            padding-bottom: 0.5cm;
            background: white;
            padding-top: 5px;
            border-top: 1px solid #e5e7eb;
            z-index: 1000;
        }';

$newCSS = '        /* HEADER MOVIDO PARA THEAD */
        .header {
            padding-top: 0.5cm;
            padding-bottom: 0.2cm;
            margin-bottom: 0.3cm;
            border-bottom: 3px solid {{ $primary }};
            display: flex;
            background: white;
        }

        /* FOOTER MOVIDO PARA TFOOT */
        .footer {
            padding-bottom: 0.5cm;
            background: white;
            padding-top: 5px;
            margin-top: 0.3cm;
            border-top: 1px solid #e5e7eb;
        }';

$content = str_replace($oldCSS, $newCSS, $content);

// Remove Spacers CSS
$content = preg_replace('/\.header-spacer \{[^}]+\}/s', '', $content);
$content = preg_replace('/\.footer-spacer \{[^}]+\}/s', '', $content);

// Move HTML
$headerHtmlRegex = '/<!-- FIXED HEADER -->.*?@endif/s';
preg_match($headerHtmlRegex, $content, $headerMatch);
$headerHtml = $headerMatch[0] ?? '';
$content = preg_replace($headerHtmlRegex, '', $content, 1);

$footerHtmlRegex = '/<!-- FIXED FOOTER -->.*?@endif/s';
preg_match($footerHtmlRegex, $content, $footerMatch);
$footerHtml = $footerMatch[0] ?? '';
$content = preg_replace($footerHtmlRegex, '', $content, 1);

$content = str_replace('<td><div class="header-spacer"></div></td>', '<td>' . $headerHtml . '</td>', $content);
$content = str_replace('<td><div class="footer-spacer"></div></td>', '<td>' . $footerHtml . '</td>', $content);

file_put_contents($path, $content);
