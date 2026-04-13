<?php
$serviceFile = 'app/Services/DigitalSealService.php';
$content = file_get_contents($serviceFile);

// Change the layout to be more concise and suitable for a footer
$newSealHtml = <<<HTML
        \$sealHtml = <<<HTML
        <div class="digital-seal" style="border-top: 1px solid #e5e7eb; margin-top: 15px; padding-top: 10px; width: 100%; clear: both; page-break-inside: avoid;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td width="70" align="left" valign="middle">
                        <img src="{\$imgSrc}" width="60" height="60" />
                    </td>
                    <td valign="middle" style="padding-left: 10px; font-family: sans-serif; font-size: 9px; color: #555; text-align: left; line-height: 1.2;">
                        <strong style="color: #333; font-size: 11px;">🔒 Selo de Autenticidade Digital</strong><br>
                        Documento assinado digitalmente por: <strong>{\$nomeFantasia}</strong> - {\$data}<br>
                        <span style="font-family: monospace; font-size: 8px; background: #f3f4f6; padding: 2px 4px; border-radius: 3px;">Hash: {\$docHash}</span><br>
                        <span style="font-size: 8px;">Para verificar a autenticidade, leia o QR Code ou acesse a URL de validação fornecida.</span>
                    </td>
                </tr>
            </table>
        </div>
HTML;

$content = preg_replace('/\$sealHtml = <<<HTML.*?HTML;/s', $newSealHtml, $content);
file_put_contents($serviceFile, $content);
echo "Service patched.\n";

$dir = 'resources/views/pdf/';
$files = glob($dir . '*.blade.php');

foreach ($files as $file) {
    if (basename($file) == 'partials' || basename($file) == 'pdf_master.blade.php') continue;
    $html = file_get_contents($file);
    
    // Remove existing seal slots globally
    $html = str_replace('<!-- DIGITAL_SEAL_SLOT -->', '', $html);
    
    // Inject at the end of the footer div if found
    if (strpos($html, '<div class="footer">') !== false) {
        // If there's a footer, put it right before the footer's closing tag.
        // It's a bit tricky with nested divs, but let's just put it right after the opening tag for safety.
        // Or if we know the structure... let's just put it after <div class="footer">
        $html = preg_replace('/<div class="footer">/i', '<div class="footer">' . "\n" . '            <!-- DIGITAL_SEAL_SLOT -->', $html);
    } else if (strpos($html, '<div class="footer-legal">') !== false) {
        $html = preg_replace('/<div class="footer-legal">/i', '<div class="footer-legal">' . "\n" . '            <!-- DIGITAL_SEAL_SLOT -->', $html);
    } else if (strpos($html, '</body>') !== false) {
        // No footer found, append before body
        $html = str_replace('</body>', '<!-- DIGITAL_SEAL_SLOT -->' . "\n" . '</body>', $html);
    }
    
    file_put_contents($file, $html);
}
echo "Templates patched.\n";
