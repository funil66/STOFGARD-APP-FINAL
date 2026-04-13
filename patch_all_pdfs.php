<?php
$dir = 'resources/views/pdf/';
$files = glob($dir . '*.blade.php');

foreach ($files as $file) {
    if (basename($file) == 'partials' || basename($file) == 'pdf_master.blade.php') continue;
    $content = file_get_contents($file);
    
    // Remover DIGITAL_SEAL_SLOT existente
    $content = str_replace('<!-- DIGITAL_SEAL_SLOT -->', '', $content);
    
    // Inserir antes de fechar a div .footer se existir
    $footerPos = strrpos($content, '<div class="footer">');
    if ($footerPos !== false) {
        // Encontrar o fechamento dessa div .footer
        // Simplified: Insert right after the literal '<div class="footer">' or right before '</body>'
    }
}
