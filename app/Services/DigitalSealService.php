<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use SimpleSoftwareIO\QrCode\Generator;

class DigitalSealService
{
    public static function buildSealData(string $tipo, string $modeloId): array
    {
        $nomeFantasia = 'Autonomia';
        try {
            $settings = new SettingsHelper();
            $nomeFantasia = $settings->get('empresa_nome', 'Autonomia') ?: 'Autonomia';
        } catch (\Throwable) {
            $nomeFantasia = 'Autonomia';
        }

        $data = now()->format('d/m/Y H:i:s');

        $hashInput = $tipo . $modeloId . $nomeFantasia . $data;
        $docHash = hash('sha256', $hashInput);
        $validationUrl = rtrim((string) env('APP_URL', 'https://autonomia.com'), '/') . '/validar/' . $docHash;

        $qrSvg = null;

        try {
            $qrSvg = QrCode::format('svg')->size(100)->generate($validationUrl);
        } catch (\Throwable) {
            try {
                if (class_exists(Generator::class)) {
                    $qrSvg = (new Generator())->format('svg')->size(100)->generate($validationUrl);
                }
            } catch (\Throwable) {
                $qrSvg = null;
            }
        }

        if (blank($qrSvg)) {
            throw new \RuntimeException('Não foi possível gerar QR Code do selo digital.');
        }

        $qrBase64 = base64_encode($qrSvg);

        try {
            Cache::put('digital_seal:' . $docHash, [
                'tipo' => $tipo,
                'modelo_id' => $modeloId,
                'company_name' => $nomeFantasia,
                'generated_at' => $data,
                'hash' => $docHash,
                'validation_url' => $validationUrl,
                'validated_at' => now()->toDateTimeString(),
            ], now()->addYears(2));
        } catch (\Throwable) {
            // Falha de cache não pode impedir a geração do PDF/QR.
        }

        return [
            'company_name' => $nomeFantasia,
            'generated_at' => $data,
            'hash' => $docHash,
            'validation_url' => $validationUrl,
            'qr_base64' => $qrBase64,
        ];
    }

    public static function appendSeal($html, $tipo, $modeloId)
    {
        if (strpos($html, 'data-digital-seal="embedded"') !== false || strpos($html, 'sealed-footer-embedded') !== false) {
            return $html;
        }

        $sealData = self::buildSealData((string) $tipo, (string) $modeloId);
        $imgSrc = 'data:image/svg+xml;base64,' . $sealData['qr_base64'];
        $nomeFantasia = $sealData['company_name'];
        $data = $sealData['generated_at'];
        $docHash = $sealData['hash'];

                $sealHtml = <<<HTML
        <div class="digital-seal" style="border-top: 1px solid #e5e7eb; margin-top: 15px; padding-top: 10px; width: 100%; clear: both; page-break-inside: avoid;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td width="70" align="left" valign="middle">
                        <img src="{$imgSrc}" width="60" height="60" />
                    </td>
                    <td valign="middle" style="padding-left: 10px; font-family: sans-serif; font-size: 9px; color: #555; text-align: left; line-height: 1.2;">
                        <strong style="color: #333; font-size: 11px;">🔒 Selo de Autenticidade Digital</strong><br>
                        Documento assinado digitalmente por: <strong>{$nomeFantasia}</strong> - {$data}<br>
                        <span style="font-family: monospace; font-size: 8px; background: #f3f4f6; padding: 2px 4px; border-radius: 3px;">Hash: {$docHash}</span><br>
                        <span style="font-size: 8px;">Para verificar a autenticidade, leia o QR Code ou acesse a URL de validação fornecida.</span>
                    </td>
                </tr>
            </table>
        </div>
        
        // If there is a designated slot for the seal, use it
        if (strpos($html, '<!-- DIGITAL_SEAL_SLOT -->') !== false) {
            return str_replace('<!-- DIGITAL_SEAL_SLOT -->', $sealHtml, $html);
        }

        // Append the seal before </body> closing tag if exists, preserving valid HTML
        if (strpos($html, '</body>') !== false) {
            return str_replace('</body>', $sealHtml . '</body>', $html);
        }

        // If there is only </html>, inject seal before it
        if (strpos($html, '</html>') !== false) {
            return str_replace('</html>', $sealHtml . '</html>', $html);
        }

        return $html . $sealHtml;
    }
}
