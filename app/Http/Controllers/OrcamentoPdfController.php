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
            // 1. Detecta EVP (Aleatória): Letras, Números e Hífens
            $isEVP = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $pixKey);
            
            // 2. Detecta Email
            $isEmail = filter_var($pixKey, FILTER_VALIDATE_EMAIL);

            if ($isEVP || $isEmail) {
                // Se for EVP ou Email, NÃO MEXE. Manda como está.
                $pixKeyForPayload = $pixKey;
            } else {
                // Assume que é numérico (Telefone, CPF, CNPJ)
                $onlyNums = preg_replace('/[^0-9]/', '', $pixKey);
                
                // Se tiver 10 ou 11 dígitos, tratamos como CELULAR
                if (strlen($onlyNums) == 10 || strlen($onlyNums) == 11) {
                    // Regra de Ouro: Telefone PRECISA do +55
                    $pixKeyForPayload = '+55' . $onlyNums;
                } else {
                    // CPF ou CNPJ (apenas números)
                    $pixKeyForPayload = $onlyNums;
                }
            }
        }

        // D. Gera o QR Code e as Strings para View
        $qrCodeBase64 = null;
        $shouldShowPix = ($orcamento->pdf_incluir_pix ?? true) && !empty($pixKey);
        $beneficiario = $config['empresa_nome'] ?? 'Stofgard';

        if ($shouldShowPix && class_exists(PixPayload::class)) {
            try {
                $payload = PixPayload::gerar(
                    (string)$pixKeyForPayload,
                    $beneficiario,
                    'Ribeirao Preto',
                    $orcamento->numero,
                    (float)$totalAvista
                );

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
            
            // Passamos os dados (sanitizados)
            'pixKey' => $pixKeyForPayload,
            'pixBeneficiario' => $config['empresa_nome'] ?? 'Stofgard',
            
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
