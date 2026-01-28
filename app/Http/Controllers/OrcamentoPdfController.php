<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
// Importação correta das classes
use App\Services\PixPayload;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrcamentoPdfController extends Controller
{
    public function gerarPdf(Orcamento $orcamento)
    {
        // 1. Carregar dados
        $orcamento->load(['cliente', 'itens', 'vendedor', 'loja']);

        // 2. Carregar configurações
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

        // --- LÓGICA DE NEGÓCIO (PIX & CÁLCULOS) ---

        // A. Cálculos Totais
        $total = $orcamento->itens->sum('subtotal');
        $percDesconto = (float) ($config['financeiro_desconto_avista'] ?? 10);
        $totalAvista = $total * (1 - ($percDesconto / 100));
        $regrasParcelamento = $config['financeiro_parcelamento'] ?? [];

        // B. Seleção da Chave PIX
        $pixKey = trim($orcamento->pix_chave_selecionada);
        if (empty($pixKey)) {
            $rawPixKeys = $config['financeiro_pix_keys'] ?? [];
            if (is_array($rawPixKeys) && !empty($rawPixKeys)) {
                $first = reset($rawPixKeys);
                $pixKey = trim($first['chave'] ?? null);
            }
        }

        // C. Tratamento Inteligente da Chave (EVP vs Telefone)
        $pixKeyForPayload = $pixKey;
        if (!empty($pixKey)) {
            // Se tem letras e traços, é EVP (Aleatória) ou Email -> Não mexe
            $isEVP = preg_match('/[a-zA-Z]/', $pixKey) && strpos($pixKey, '-') !== false;
            $isEmail = strpos($pixKey, '@') !== false;

            if (!$isEVP && !$isEmail) {
                // É numérico (Telefone/CPF/CNPJ)
                $onlyNums = preg_replace('/[^0-9]/', '', $pixKey);
                $isPhone = (strlen($onlyNums) == 10 || strlen($onlyNums) == 11);
                
                // Se for telefone sem +55, adiciona
                if ($isPhone && !str_starts_with($pixKey, '+55')) {
                    $pixKeyForPayload = '+55' . $onlyNums;
                } else {
                    $pixKeyForPayload = $onlyNums;
                }
            }
        }

        // D. Geração do QR Code (Base64)
        $qrCodeBase64 = null;
        $shouldShowPix = ($orcamento->pdf_incluir_pix ?? true) && !empty($pixKey);
        $beneficiario = substr($config['empresa_nome'] ?? 'Stofgard', 0, 25);

        if ($shouldShowPix && class_exists(PixPayload::class)) {
            try {
                // Gera a string de pagamento (Copy & Paste)
                $payload = PixPayload::gerar(
                    (string)$pixKeyForPayload, 
                    $beneficiario, 
                    'Ribeirao Preto', 
                    $orcamento->numero, 
                    $totalAvista
                );
                
                // Gera a imagem do QR Code se a lib existir
                if (class_exists(QrCode::class)) {
                    $pngData = QrCode::format('png')
                        ->size(300) // Tamanho bom para leitura
                        ->margin(1)
                        ->generate($payload);
                    $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($pngData);
                }
            } catch (\Exception $e) {
                Log::error("Erro ao gerar QR Code: " . $e->getMessage());
            }
        }

        // 3. Renderiza a View (Passando APENAS dados prontos)
        $html = view('pdf.orcamento_oficial', [
            'orcamento' => $orcamento,
            'config' => $config,
            'total' => $total,
            'totalAvista' => $totalAvista,
            'percDesconto' => $percDesconto,
            'regras' => $regrasParcelamento,
            'pixKey' => $pixKey, // Chave visual (original)
            'qrCodeImg' => $qrCodeBase64, // Imagem pronta
            'shouldShowPix' => $shouldShowPix,
        ])->render();

        // 4. Salva HTML Temp
        $tempId = $orcamento->id . '_' . time();
        $htmlPath = storage_path("app/public/temp_orc_{$tempId}.html");
        $pdfPath = storage_path("app/public/temp_orc_{$tempId}.pdf");

        if (!File::exists(dirname($htmlPath))) File::makeDirectory(dirname($htmlPath), 0755, true);
        file_put_contents($htmlPath, $html);

        // 5. Gera PDF com Puppeteer
        $scriptPath = base_path('scripts/generate-pdf.js');
        if (!file_exists($scriptPath)) abort(500, "Script Puppeteer não encontrado.");

        $result = Process::run(['node', $scriptPath, $htmlPath, $pdfPath]);

        if ($result->failed()) {
            Log::error("Erro Puppeteer: " . $result->errorOutput());
            @unlink($htmlPath);
            return response()->json(['error' => 'Falha na geração do PDF', 'details' => $result->errorOutput()], 500);
        }

        // 6. Entrega
        if (file_exists($pdfPath)) {
            @unlink($htmlPath);
            return response()->file($pdfPath)->deleteFileAfterSend();
        }
        
        return response()->json(['error' => 'PDF não criado.'], 500);
    }
}
