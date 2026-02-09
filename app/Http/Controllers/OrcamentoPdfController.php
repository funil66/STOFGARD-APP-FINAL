<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Services\PdfService;
use Illuminate\Http\Request;

class OrcamentoPdfController extends Controller
{
    public function __construct(protected PdfService $pdfService)
    {
    }

    /**
     * Gera o PDF para download/visualização dentro do Painel (Autenticado).
     */
    public function gerarPdf(Orcamento $orcamento)
    {
        return $this->renderPdf($orcamento, true); // true = download
    }

    /**
     * Gera o PDF para visualização pública via Link Assinado (WhatsApp).
     */
    public function stream(Orcamento $orcamento)
    {
        // Se a rota for assinada, o Laravel já validou no middleware 'signed'.
        return $this->renderPdf($orcamento, false); // false = inline (visualização)
    }

    /**
     * Lógica central de geração do PDF.
     */
    private function renderPdf(Orcamento $orcamento, bool $download = true)
    {
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
                    // NEW RULE: If value was manually edited, do NOT apply PIX discount again
                    if (!$orcamento->valor_final_editado) {
                        $descontoPix = ($valorFinal * $percentualPix) / 100;
                        $valorFinal -= $descontoPix;
                    }
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

        return $this->pdfService->generate(
            'pdf.orcamento',
            ['orcamento' => $orcamento, 'config' => $config],
            "Orcamento-{$orcamento->id}.pdf",
            $download
        );
    }
}
