<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\PixPayload;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrcamentoPdfController extends Controller
{
    public function gerarPdf(Orcamento $orcamento)
    {
        $orcamento->load(['cliente', 'itens', 'vendedor', 'loja']);

        $config = Setting::all()->pluck('value', 'key')->toArray();
        $jsonFields = ['catalogo_servicos_v2', 'financeiro_pix_keys', 'financeiro_taxas_cartao', 'financeiro_parcelamento'];
        
        foreach ($jsonFields as $key) {
            if (isset($config[$key]) && is_string($config[$key])) {
                $decoded = json_decode($config[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) $config[$key] = $decoded;
            }
        }

        // --- CÁLCULOS ---
        $total = $orcamento->itens->sum('subtotal');
        $percDesconto = (float) ($config['financeiro_desconto_avista'] ?? 10);
        $totalAvista = $total * (1 - ($percDesconto / 100));
        $regrasParcelamento = $config['financeiro_parcelamento'] ?? [];

        // --- TRATAMENTO DE CHAVE ---
        $pixKey = trim($orcamento->pix_chave_selecionada);
        if (empty($pixKey)) {
            $rawPixKeys = $config['financeiro_pix_keys'] ?? [];
            if (is_array($rawPixKeys) && !empty($rawPixKeys)) {
                $first = reset($rawPixKeys);
                $pixKey = trim($first['chave'] ?? null);
            }
        }

        $pixKeyForPayload = $pixKey;
        if (!empty($pixKey)) {
            $isEVP = preg_match('/[a-zA-Z]/', $pixKey) && strpos($pixKey, '@') === false;
            $isEmail = strpos($pixKey, '@') !== false;

            if (!$isEVP && !$isEmail) {
                $onlyNums = preg_replace('/[^0-9]/', '', $pixKey);
                $isPhone = (strlen($onlyNums) == 10 || strlen($onlyNums) == 11);
                if ($isPhone && !str_starts_with($pixKey, '+55')) {
                    $pixKeyForPayload = '+55' . $onlyNums;
                } else {
                    $pixKeyForPayload = $onlyNums;
                }
            }
        }

        // --- GERAÇÃO E AUTOVERIFICAÇÃO ---
        $qrCodeBase64 = null;
        $shouldShowPix = ($orcamento->pdf_incluir_pix ?? true) && !empty($pixKey);
        
        // Dados para exibição no PDF (Inicialmente vazios, serão preenchidos pelo scan)
        $pixDadosLidos = [
            'beneficiario' => $config['empresa_nome'] ?? 'Stofgard',
            'chave' => $pixKey,
            'valor' => $totalAvista
        ];

        if ($shouldShowPix && class_exists(PixPayload::class)) {
            try {
                // 1. GERA
                $payload = PixPayload::gerar(
                    (string)$pixKeyForPayload, 
                    $pixDadosLidos['beneficiario'], 
                    'Ribeirao Preto', 
                    'ORC' . $orcamento->id, 
                    (float)$totalAvista
                );

                // 2. AUTOVERIFICAÇÃO (Lê o payload gerado)
                $dadosVerificados = PixPayload::lerPayload($payload);

                if ($dadosVerificados['valido']) {
                    // Atualiza os dados de exibição com o que foi lido REALMENTE do código
                    $pixDadosLidos['beneficiario'] = $dadosVerificados['beneficiario'];
                    $pixDadosLidos['chave'] = $dadosVerificados['chave']; // A chave real formatada
                    // $pixDadosLidos['valor'] = $dadosVerificados['valor'];
                }

                // 3. GERA IMAGEM
                if (class_exists(QrCode::class)) {
                    $pngData = QrCode::format('png')
                        ->size(300)
                        ->margin(1)
                        ->generate($payload);
                    $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($pngData);
                }
            } catch (\Exception $e) {
                Log::error("Erro QR Code: " . $e->getMessage());
            }
        }

        // --- RENDERIZAR VIEW ---
        $html = view('pdf.orcamento_oficial', [
            'orcamento' => $orcamento,
            'config' => $config,
            'total' => $total,
            'totalAvista' => $totalAvista,
            'percDesconto' => $percDesconto,
            'regras' => $regrasParcelamento,
            
            // Passamos os dados VERIFICADOS
            'pixKey' => $pixDadosLidos['chave'], 
            'pixBeneficiario' => $pixDadosLidos['beneficiario'],
            
            'qrCodeImg' => $qrCodeBase64,
            'shouldShowPix' => $shouldShowPix,
        ])->render();

        $tempId = $orcamento->id . '_' . time();
        $htmlPath = storage_path("app/public/temp_orc_{$tempId}.html");
        $pdfPath = storage_path("app/public/temp_orc_{$tempId}.pdf");

        if (!File::exists(dirname($htmlPath))) File::makeDirectory(dirname($htmlPath), 0755, true);
        file_put_contents($htmlPath, $html);

        $scriptPath = base_path('scripts/generate-pdf.js');
        if (!file_exists($scriptPath)) abort(500, "Script Puppeteer ausente.");

        $result = Process::run(['node', $scriptPath, $htmlPath, $pdfPath]);

        if ($result->failed()) {
            @unlink($htmlPath);
            return response()->json(['error' => 'Falha PDF', 'details' => $result->errorOutput()], 500);
        }

        if (file_exists($pdfPath)) {
            @unlink($htmlPath);
            return response()->file($pdfPath)->deleteFileAfterSend();
        }
        
        return response()->json(['error' => 'PDF não criado.'], 500);
    }
}
