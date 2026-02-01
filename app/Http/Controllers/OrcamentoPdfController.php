<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Http\Request;

class OrcamentoPdfController extends Controller
{
    /**
     * Gera o PDF para download/visualização dentro do Painel (Autenticado).
     */
    public function gerarPdf(Orcamento $orcamento)
    {
        return $this->renderPdf($orcamento);
    }

    /**
     * Gera o PDF para visualização pública via Link Assinado (WhatsApp).
     */
    public function stream(Orcamento $orcamento)
    {
        // Se a rota for assinada, o Laravel já validou no middleware 'signed'.
        // Se quiser validar expiração extra, faça aqui.
        return $this->renderPdf($orcamento);
    }

    /**
     * Lógica central de geração do PDF.
     */
    private function renderPdf(Orcamento $orcamento)
    {
        // Garante que o diretório de arquivos temporários exista e tenha permissão
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        // --- LÓGICA DE PIX ---
        if ($orcamento->pdf_incluir_pix && $orcamento->pix_chave_selecionada) {
            try {
                // 1. Calcular Valor Final (Com descontos)
                $valorFinal = $orcamento->valor_final_editado ?? $orcamento->valor_total;

                // Desconto do Prestador
                if (($orcamento->desconto_prestador ?? 0) > 0) {
                    if (!$orcamento->valor_final_editado) { // Evita descontar duas vezes se já estiver no valor final
                        $valorFinal -= $orcamento->desconto_prestador;
                    }
                }

                // Desconto PIX (Se configurado e habilitado)
                $config = \App\Models\Configuracao::first(); // Ou buscar via Settings se usar json
                $descontoPix = 0;

                // Busca configurações via Helper ou Model Setting diretamente para garantir
                $settingsArray = \App\Models\Setting::all()->pluck('value', 'key')->toArray();

                // Decodifica JSONs conhecidos
                $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
                foreach ($jsonFields as $k) {
                    if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                        $settingsArray[$k] = json_decode($settingsArray[$k], true);
                    }
                }

                // Cria objeto Config Fake para a View
                $config = (object) $settingsArray;

                $percentualPix = $settingsArray['financeiro_desconto_avista'] ?? 10;

                if ($orcamento->aplicar_desconto_pix && $percentualPix > 0) {
                    $descontoPix = ($valorFinal * $percentualPix) / 100;
                    $valorFinal -= $descontoPix;
                }

                // 2. Encontrar Titular da Chave
                $chavesPix = $config->financeiro_pix_keys ?? []; // Já decodificado acima
                $titular = $settingsArray['nome_sistema'] ?? 'Stofgard'; // Fallback
                $cidade = 'Ribeirao Preto'; // Fallback

                if (is_array($chavesPix)) {
                    foreach ($chavesPix as $keyItem) {
                        if ($keyItem['chave'] === $orcamento->pix_chave_selecionada) {
                            $titular = $keyItem['titular'] ?? $titular;
                            break;
                        }
                    }
                }

                // 3. Gerar QR Code
                $pixService = new \App\Services\Pix\PixMasterService();
                $pixData = $pixService->gerarQrCode(
                    $orcamento->pix_chave_selecionada,
                    $titular,
                    $cidade,
                    $orcamento->numero ?? 'ORC',
                    $valorFinal
                );

                // 4. Injetar no Objeto (Temporário)
                $orcamento->pix_qrcode_base64 = $pixData['qr_code_img'];
                $orcamento->pix_copia_cola = $pixData['payload_pix'];

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erro gerar PIX PDF: " . $e->getMessage());
            }
        }

        // Se Config não foi criado no bloco if acima (caso PIX desligado)
        if (!isset($config)) {
            $settingsArray = \App\Models\Setting::all()->pluck('value', 'key')->toArray();
            $jsonFields = ['financeiro_pix_keys', 'pdf_layout'];
            foreach ($jsonFields as $k) {
                if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                    $settingsArray[$k] = json_decode($settingsArray[$k], true);
                }
            }
            $config = (object) $settingsArray;
        }

        return Pdf::view('pdf.orcamento', [
            'orcamento' => $orcamento,
            'config' => $config
        ])
            ->format('a4')
            ->name("Orcamento-{$orcamento->id}.pdf")
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                    ->setChromePath(config('services.browsershot.chrome_path', '/usr/bin/google-chrome'))
                    ->setNodeBinary(config('services.browsershot.node_path', '/usr/bin/node'))
                    ->setNpmBinary(config('services.browsershot.npm_path', '/usr/bin/npm'))
                    ->setOption('args', ['--disable-web-security', '--no-sandbox', '--disable-setuid-sandbox'])
                    ->timeout(60);
            })
            ->inline();
    }
}
