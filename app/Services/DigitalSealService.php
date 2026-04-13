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
