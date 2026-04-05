<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DigitalSealService
{
    public static function appendSeal($html, $tipo, $modeloId)
    {
        $settings = new SettingsHelper();
        $nomeFantasia = $settings->get('empresa_nome', 'Stofgard');
        $data = now()->format('d/m/Y H:i:s');
        
        // Let's create a unique hash (Document validation hash)
        $hashInput = $tipo . $modeloId . $nomeFantasia . $data;
        $docHash = hash('sha256', $hashInput);
        
        // This QR Code points to a generic validation page or just contains the text
        // Usually, point to a route on your app (e.g. env('APP_URL')/validar/$docHash)
        $validationUrl = env('APP_URL', 'https://stofgard.com') . "/validar/" . $docHash;
        
        $qrBase64 = base64_encode(QrCode::format('svg')->size(100)->generate($validationUrl));
        $imgSrc = 'data:image/svg+xml;base64,' . $qrBase64;

        $sealHtml = <<<HTML
        <div style="border-top: 2px dashed #ccc; margin-top: 30px; padding-top: 20px; page-break-inside: avoid;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td width="120" align="center" valign="middle">
                        <img src="{$imgSrc}" width="100" height="100" />
                    </td>
                    <td valign="middle" style="padding-left: 20px; font-family: sans-serif; font-size: 11px; color: #555;">
                        <h4 style="margin: 0 0 5px 0; color: #333; font-size: 14px;">🔒 Selo de Autenticidade Digital</h4>
                        <p style="margin: 0 0 4px 0;"><strong>{$nomeFantasia}</strong> certifica a integridade deste documento.</p>
                        <p style="margin: 0 0 4px 0;">Documento assinado digitalmente por: <strong>{$nomeFantasia}</strong></p>
                        <p style="margin: 0 0 4px 0;">Data e Hora da Geração: <strong>{$data}</strong></p>
                        <p style="margin: 0 0 4px 0; font-family: monospace; font-size: 10px; background: #eee; padding: 4px; display: inline-block; border-radius: 4px;">Hash: {$docHash}</p>
                        <p style="margin: 6px 0 0 0; font-size: 10px;">Para verificar a autenticidade, leia o QR Code ou acesse a URL de validação fornecida por nossa instituição.</p>
                    </td>
                </tr>
            </table>
        </div>
HTML;
        
        // Append the seal before </body> closing tag if exists, otherwise at the end
        if (strpos($html, '</body>') !== false) {
            return str_replace('</body>', $sealHtml, $html);
        }
        
        return $html . $sealHtml;
    }
}
