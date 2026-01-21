<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Services\ConfiguracaoService;
use App\Services\PixService;
use App\Services\StaticPixQrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;use Illuminate\Support\Facades\Auth;
use App\Models\PdfGeneration;use Symfony\Component\Process\Process;

class OrcamentoPdfController extends Controller
{
    public function show(Orcamento $orcamento, PixService $pixService)
    {
        // Carregar relacionamentos necessários
        $orcamento->load(['cliente', 'itens.tabelaPreco', 'parceiro']);

        // Recalcular totais para garantir consistência
        $orcamento->calcularTotal();

        // 1. Busca configurações (Chave Pix)
        $qrCodeBase64 = null;
        // Tenta pegar chave PIX global nas configurações, com fallback para variável de ambiente PIX_CHAVE
        $chavePix = ConfiguracaoService::financeiro('pix_chave') ?: config('services.pix.chave'); 

        // 2. Se tiver chave pix e valor, gera o código
        if ($chavePix && $orcamento->valor_total > 0) {

            // Gera o texto do Payload (Copiar e Colar)
            $payload = $pixService->gerarPayloadPix(
                $chavePix,
                $orcamento->valor_total,
                ConfiguracaoService::empresa('razao_social', 'Stofgard'), // Nome do beneficiário
                ConfiguracaoService::empresa('cidade', 'Ribeirao Preto'), // Cidade
                $orcamento->id // Identificador (TxId)
            );

            // Gera a IMAGEM Base64
            $qrCodeBase64 = $pixService->gerarQrCodeBase64($payload);
        }

        // 2b. Se não há chave global mas o orçamento é PIX e ainda não possui QR, tente gerar via StaticPixQrCodeService
        if (empty($qrCodeBase64) && $orcamento->forma_pagamento === 'pix' && empty($orcamento->pix_qrcode_base64)) {
            app(StaticPixQrCodeService::class)->generate($orcamento);
            // Refresh para garantir que as alterações salvas pelo serviço sejam lidas
            $orcamento->refresh();
        }

        // Se o orçamento já tem QR salvo, preferimos usá-lo
        $qrCodeBase64 = $qrCodeBase64 ?? $orcamento->pix_qrcode_base64;

        // Garantir que copia/cola esteja presente (pode ter sido gerado pelo serviço)
        $payload = $payload ?? $orcamento->pix_copia_cola ?? null;

        // Log debug: verificar presença do QR
        Log::info('Orcamento PDF: QR check', [
            'id' => $orcamento->id,
            'forma_pagamento' => $orcamento->forma_pagamento,
            'qr_present' => ! empty($qrCodeBase64),
            'qr_length' => $qrCodeBase64 ? strlen($qrCodeBase64) : 0,
            'pix_chave_global' => $chavePix ? 'yes' : 'no',
            'pix_chave_orcamento' => $orcamento->pix_chave_tipo,
        ]);

        // 3. Se existir PDF estático, retorna ele (arquivo final aprovado pelo cliente).
        $staticById = public_path("pdfs/orcamento-{$orcamento->id}.pdf");
        $staticFinal = public_path('pdfs/orcamento-final.pdf');
        if (file_exists($staticById)) {
            return response()->file($staticById);
        }
        if (file_exists($staticFinal)) {
            return response()->file($staticFinal);
        }

        // --- Geração dinâmica via Puppeteer (Node) ---
        $viewData = [
            'orcamento' => $orcamento,
            'qrCodePix' => $qrCodeBase64,
            'chavePix' => $chavePix,
            'copiaCopia' => $payload ?? null,
            'banco' => ConfiguracaoService::financeiro('banco'),
            'agencia' => ConfiguracaoService::financeiro('agencia'),
            'conta' => ConfiguracaoService::financeiro('conta'),
            'scale' => 1.0,
        ];

        try {
            $html = view('pdf.orcamento', $viewData)->render();
            $debugDir = storage_path('debug');
            if (!file_exists($debugDir)) {
                @mkdir($debugDir, 0755, true);
            }
            $htmlPath = $debugDir . "/orcamento-{$orcamento->id}.html";
            $pdfPath = $debugDir . "/orcamento-{$orcamento->id}.pdf";
            file_put_contents($htmlPath, $html);

            $node = getenv('NODE_BINARY') ?: config('app.node_binary', 'node');
            // Permite sobrescrever o script via variável de ambiente (útil em testes/CI)
            $script = getenv('PDF_GENERATOR_SCRIPT') ?: config('app.pdf_generator_script', base_path('scripts/generate-pdf.js'));

            $process = new Process([$node, $script, $htmlPath, $pdfPath]);
            $process->setTimeout(120);
            $process->run();

            if ($process->isSuccessful() && file_exists($pdfPath)) {
                $publicPath = public_path("pdfs/orcamento-{$orcamento->id}.pdf");
                if (!is_dir(dirname($publicPath))) {
                    mkdir(dirname($publicPath), 0755, true);
                }
                copy($pdfPath, $publicPath);
                return response()->file($publicPath);
            }

            Log::error('Puppeteer render failed', ['id' => $orcamento->id, 'output' => $process->getOutput(), 'error' => $process->getErrorOutput()]);
        } catch (\Throwable $e) {
            Log::error('Puppeteer exception', ['id' => $orcamento->id, 'message' => $e->getMessage()]);
        }

        // 4. Fallback: tentar Dompdf (se Puppeteer falhar)
        try {
            $pdf = Pdf::loadView('pdf.orcamento', $viewData);
            return $pdf->stream("Orcamento-{$orcamento->id}.pdf");
        } catch (\Throwable $e) {
            Log::error('Dompdf fallback failed', ['id' => $orcamento->id, 'message' => $e->getMessage()]);
            if (file_exists($staticFinal)) {
                return response()->file($staticFinal);
            }
            abort(500, 'Erro ao gerar PDF');
        }
    }

    // Alias para compatibilidade de rota
    public function gerarPdf(Orcamento $orcamento, PixService $pixService)
    {
        return $this->show($orcamento, $pixService);
    }

    /**
     * Gera o PDF do orçamento e salva em public/pdfs/orcamento-{id}.pdf
     * Aceita parâmetro POST `include_pix` (boolean) para forçar incluir ou não o QR.
     * Se `persist` for true, salva a preferência em `orcamentos.pdf_incluir_pix`.
     */
    public function generateAndSave(\Illuminate\Http\Request $request, Orcamento $orcamento, PixService $pixService)
    {
        // Carregar relacionamentos necessários e recalcular
        $orcamento->load(['cliente', 'itens.tabelaPreco', 'parceiro']);
        $orcamento->calcularTotal();

        $includePix = $request->boolean('include_pix', $orcamento->pdf_incluir_pix ?? true);

        if ($request->boolean('persist', false)) {
            $orcamento->pdf_incluir_pix = $includePix;
            $orcamento->save();
        }

        // Gerar QR somente se solicitado e se houver valor
        $qrCodeBase64 = null;
        $chavePix = ConfiguracaoService::financeiro('pix_chave') ?: config('services.pix.chave');
        if ($includePix && $chavePix && $orcamento->valor_total > 0) {
            $payload = $pixService->gerarPayloadPix(
                $chavePix,
                $orcamento->valor_total,
                ConfiguracaoService::empresa('razao_social', 'Stofgard'),
                ConfiguracaoService::empresa('cidade', 'Ribeirao Preto'),
                $orcamento->id
            );
            $qrCodeBase64 = $pixService->gerarQrCodeBase64($payload);
        }

        // Renderiza o HTML e chama Puppeteer via script (reaproveita lógica existente)
        $viewData = [
            'orcamento' => $orcamento,
            'qrCodePix' => $qrCodeBase64,
            'chavePix' => $chavePix,
            'copiaCopia' => $payload ?? null,
            'banco' => ConfiguracaoService::financeiro('banco'),
            'agencia' => ConfiguracaoService::financeiro('agencia'),
            'conta' => ConfiguracaoService::financeiro('conta'),
            'scale' => 1.0,
        ];

        try {
            $html = view('pdf.orcamento', $viewData)->render();
            $debugDir = storage_path('debug');
            if (!file_exists($debugDir)) {
                @mkdir($debugDir, 0755, true);
            }
            $htmlPath = $debugDir . "/orcamento-{$orcamento->id}.html";
            $pdfPath = $debugDir . "/orcamento-{$orcamento->id}.pdf";
            file_put_contents($htmlPath, $html);

            $node = getenv('NODE_BINARY') ?: config('app.node_binary', 'node');
            // Permite sobrescrever o script via variável de ambiente (útil em testes/CI)
            $script = getenv('PDF_GENERATOR_SCRIPT') ?: config('app.pdf_generator_script', base_path('scripts/generate-pdf.js'));

            $process = new Process([$node, $script, $htmlPath, $pdfPath]);
            $process->setTimeout(120);
            $process->run();

            if ($process->isSuccessful() && file_exists($pdfPath)) {
                $publicPath = public_path("pdfs/orcamento-{$orcamento->id}.pdf");
                if (!is_dir(dirname($publicPath))) {
                    mkdir(dirname($publicPath), 0755, true);
                }

                $finalPublicPath = $publicPath;
                try {
                    copy($pdfPath, $publicPath);
                } catch (\Throwable $e) {
                    // Se falhar ao copiar para public, tenta salvar em storage/app/public/pdfs
                    Log::warning('Failed to copy PDF to public, trying storage public', ['error' => $e->getMessage(), 'orcamento' => $orcamento->id]);
                    $storagePublic = storage_path("app/public/pdfs/orcamento-{$orcamento->id}.pdf");
                    if (!is_dir(dirname($storagePublic))) {
                        mkdir(dirname($storagePublic), 0755, true);
                    }
                    copy($pdfPath, $storagePublic);
                    $finalPublicPath = $storagePublic;
                }

                // Determina URL pública preferencial
                $publicUrl = ($finalPublicPath === $publicPath && file_exists($publicPath)) ? url("/pdfs/orcamento-{$orcamento->id}.pdf") : url("/storage/pdfs/orcamento-{$orcamento->id}.pdf");

                // Registro de auditoria: quem gerou o PDF
                try {
                    PdfGeneration::create([
                        'orcamento_id' => $orcamento->id,
                        'user_id' => Auth::id(),
                        'include_pix' => $includePix,
                        'url' => $publicUrl,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to record PdfGeneration', ['error' => $e->getMessage(), 'orcamento' => $orcamento->id]);
                }

                return response()->json([ 'url' => $publicUrl ], 200);
            }

            Log::error('Puppeteer render failed (generateAndSave)', ['id' => $orcamento->id, 'output' => $process->getOutput(), 'error' => $process->getErrorOutput()]);
        } catch (\Throwable $e) {
            Log::error('Puppeteer exception (generateAndSave)', ['id' => $orcamento->id, 'message' => $e->getMessage()]);
        }

        // Fallback para Dompdf
        try {
            $pdf = Pdf::loadView('pdf.orcamento', $viewData);
            $pdfPath = storage_path("debug/orcamento-{$orcamento->id}-dompdf.pdf");
            file_put_contents($pdfPath, $pdf->output());
            $publicPath = public_path("pdfs/orcamento-{$orcamento->id}.pdf");
            if (!is_dir(dirname($publicPath))) {
                mkdir(dirname($publicPath), 0755, true);
            }

            $finalPublicPath = $publicPath;
            try {
                copy($pdfPath, $publicPath);
            } catch (\Throwable $e) {
                Log::warning('Failed to copy PDF to public (dompdf), trying storage public', ['error' => $e->getMessage(), 'orcamento' => $orcamento->id]);
                $storagePublic = storage_path("app/public/pdfs/orcamento-{$orcamento->id}.pdf");
                if (!is_dir(dirname($storagePublic))) {
                    mkdir(dirname($storagePublic), 0755, true);
                }
                copy($pdfPath, $storagePublic);
                $finalPublicPath = $storagePublic;
            }

            $publicUrl = ($finalPublicPath === $publicPath && file_exists($publicPath)) ? url("/pdfs/orcamento-{$orcamento->id}.pdf") : url("/storage/pdfs/orcamento-{$orcamento->id}.pdf");

            // Registro de auditoria: quem gerou o PDF (fallback Dompdf)
            try {
                PdfGeneration::create([
                    'orcamento_id' => $orcamento->id,
                    'user_id' => Auth::id(),
                    'include_pix' => $includePix,
                    'url' => $publicUrl,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to record PdfGeneration (dompdf)', ['error' => $e->getMessage(), 'orcamento' => $orcamento->id]);
            }
            return response()->json([ 'url' => $publicUrl ], 200);
        } catch (\Throwable $e) {
            Log::error('Dompdf fallback failed (generateAndSave)', ['id' => $orcamento->id, 'message' => $e->getMessage()]);
            return response()->json([ 'error' => 'Erro ao gerar PDF' ], 500);
        }
    }
}
