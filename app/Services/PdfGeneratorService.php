<?php

namespace App\Services;

use App\Models\Orcamento;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Serviço responsável pela geração de PDFs
 * Utiliza Spatie Laravel PDF (Browsershot/Chromium) para renderização perfeita
 */
class PdfGeneratorService
{
    /**
     * Gera PDF de um orçamento
     *
     * @return \Spatie\LaravelPdf\PdfBuilder
     */
    public function gerarOrcamentoPdf(Orcamento $orcamento)
    {
        // Garante diretório temporário
        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return Pdf::view('pdf.orcamento', ['orcamento' => $orcamento])
            ->format('a4')
            ->name("Orcamento-{$orcamento->id}.pdf")
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                    ->setNodeBinary(config('services.browsershot.node_path', '/usr/bin/node'))
                    ->setNpmBinary(config('services.browsershot.npm_path', '/usr/bin/npm'))
                    ->setOption('args', [
                        '--disable-web-security',
                        '--no-sandbox',
                        '--disable-setuid-sandbox',
                    ])
                    ->timeout(config('services.browsershot.timeout', 60));
            });
    }

    /**
     * Salva o PDF no storage
     *
     * @param  \Spatie\LaravelPdf\PdfBuilder  $pdf
     */
    public function salvarPdf($pdf, string $path): string
    {
        Storage::put($path, $pdf->save());

        return $path;
    }

    /**
     * Gera e salva o PDF de um orçamento
     */
    public function gerarESalvarOrcamento(Orcamento $orcamento): string
    {
        $pdf = $this->gerarOrcamentoPdf($orcamento);
        $path = "orcamentos/orcamento-{$orcamento->id}-".now()->format('YmdHis').'.pdf';

        return $this->salvarPdf($pdf, $path);
    }
}
