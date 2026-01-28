<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
// Importação das Classes de Serviço
use App\Services\PixPayload;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrcamentoPdfController extends Controller
{
    public function gerarPdf(Orcamento $orcamento)
    {
        // 1. Carrega dados
        $orcamento->load(['cliente', 'itens', 'vendedor', 'loja']);

        // 2. Prepara configurações
        $config = Setting::all()->pluck('value', 'key')->toArray();
        $jsonFields = ['catalogo_servicos_v2', 'financeiro_pix_keys', 'financeiro_taxas_cartao', 'financeiro_parcelamento'];
        
        foreach ($jsonFields as $key) {
            if (isset($config[$key]) && is_string($config[$key])) {
                $decoded = json_decode($config[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $config[$key] = $decoded;
                }
            }
        }

        // --- LÓGICA FINANCEIRA E PIX (Movida da View para cá) ---
        
        // A. Cálculos
        $total = $orcamento->itens->sum('subtotal');
        $percDesconto = (float) ($config['financeiro_desconto_avista'] ?? 10);
        $totalAvista = $total * (1 - ($percDesconto / 100));
        $regrasParcelamento = $config['financeiro_parcelamento'] ?? [];

        // B. Determinar Chave PIX
        $pixKey = trim($orcamento->pix_chave_selecionada);
        if (empty($pixKey)) {
            $rawPixKeys = $config['financeiro_pix_keys'] ?? [];
            if (is_array($rawPixKeys) && !empty($rawPixKeys)) {
                $first = reset($rawPixKeys);
                $pixKey = trim($first['chave'] ?? null);
            }
        }

        // C. Tratamento da Chave (EVP/Email vs Telefone)
        $pixKeyForPayload = $pixKey;
        if (!empty($pixKey)) {
            // Regex para Chave Aleatória (UUID)
            $isEVP = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $pixKey);
            $isEmail = filter_var($pixKey, FILTER_VALIDATE_EMAIL);

            if (!$isEVP && !$isEmail) {
                // Limpa se for telefone/CPF
                $onlyNums = preg_replace('/[^0-9]/', '', $pixKey);
                $isPhone = (strlen($onlyNums) == 10 || strlen($onlyNums) == 11);
                
                // Adiciona +55 se for telefone sem código
                if ($isPhone && !str_starts_with($pixKey, '+55')) {
                    $pixKeyForPayload = '+55' . $onlyNums;
                } else {
                    $pixKeyForPayload = $onlyNums;
                }
            }
        }

        // D. Geração do QR Code
        $qrCodeImg = null;
        $shouldShowPix = ($orcamento->pdf_incluir_pix ?? true) && !empty($pixKey);
        $beneficiario = substr($config['empresa_nome'] ?? 'Stofgard', 0, 25);

        if ($shouldShowPix) {
            if (class_exists(PixPayload::class) && class_exists(QrCode::class)) {
                try {
                    $payload = PixPayload::gerar((string)$pixKeyForPayload, $beneficiario, 'Ribeirao Preto', $orcamento->numero, $totalAvista);
                    
                    $pngData = QrCode::format('png')
                        ->size(200)
                        ->margin(0)
                        ->generate($payload);
                        
                    $qrCodeImg = 'data:image/png;base64,' . base64_encode($pngData);
                } catch (\Exception $e) {
                    Log::error("Erro ao gerar QR Code no Controller: " . $e->getMessage());
                }
            }
        }

        // 3. Renderiza o HTML (Agora a View é limpa)
        $html = view('pdf.orcamento_oficial', [
            'orcamento' => $orcamento,
            'config' => $config,
            // Variáveis calculadas
            'total' => $total,
            'totalAvista' => $totalAvista,
            'percDesconto' => $percDesconto,
            'regras' => $regrasParcelamento,
            'pixKey' => $pixKey,
            'qrCodeImg' => $qrCodeImg,
            'shouldShowPix' => $shouldShowPix,
        ])->render();

        // 4. Salva e Gera PDF (Puppeteer)
        $tempId = $orcamento->id . '_' . time();
        $htmlPath = storage_path("app/public/temp_orc_{$tempId}.html");
        $pdfPath = storage_path("app/public/temp_orc_{$tempId}.pdf");

        if (!File::exists(dirname($htmlPath))) {
            File::makeDirectory(dirname($htmlPath), 0755, true);
        }

        file_put_contents($htmlPath, $html);

        $scriptPath = base_path('scripts/generate-pdf.js');
        
        if (!file_exists($scriptPath)) {
            abort(500, "Script Puppeteer ausente.");
        }

        $result = Process::run(['node', $scriptPath, $htmlPath, $pdfPath]);

        if ($result->failed()) {
            Log::error("Erro Puppeteer: " . $result->errorOutput());
            @unlink($htmlPath);
            return response()->json(['error' => 'Falha na geração do PDF', 'details' => $result->errorOutput()], 500);
        }

        if (file_exists($pdfPath)) {
            @unlink($htmlPath);
            return response()->file($pdfPath)->deleteFileAfterSend();
        } else {
            return response()->json(['error' => 'PDF não criado.'], 500);
        }
    }
}
