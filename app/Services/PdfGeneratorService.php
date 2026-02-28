<?php

namespace App\Services;

use App\Models\Orcamento;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Serviço responsável pela geração de PDFs.
 *
 * Utiliza Spatie Laravel PDF com backend Browserless (container remoto) ou
 * Chrome/Node local conforme configurado em config/browsershot.php.
 */
class PdfGeneratorService
{
    public function __construct(
        private readonly PdfService $pdfService
    ) {
    }

    /**
     * Gera PDF de um orçamento.
     *
     * @return \Spatie\LaravelPdf\PdfBuilder
     */
    public function gerarOrcamentoPdf(Orcamento $orcamento)
    {
        return Pdf::view('pdf.orcamento', ['orcamento' => $orcamento])
            ->format('a4')
            ->name("Orcamento-{$orcamento->id}.pdf")
            ->withBrowsershot(function ($browsershot) {
                $this->pdfService->configureBrowsershotPublic($browsershot);
            });
    }

    /**
     * Salva o PDF no storage.
     *
     * @param  \Spatie\LaravelPdf\PdfBuilder  $pdf
     */
    public function salvarPdf($pdf, string $path): string
    {
        $content = base64_decode($pdf->base64());
        Storage::put($path, $content);

        return $path;
    }

    /**
     * Gera e salva o PDF de um orçamento.
     */
    public function gerarESalvarOrcamento(Orcamento $orcamento): string
    {
        $pdf = $this->gerarOrcamentoPdf($orcamento);
        $path = "orcamentos/orcamento-{$orcamento->id}-" . now()->format('YmdHis') . '.pdf';

        return $this->salvarPdf($pdf, $path);
    }
}
