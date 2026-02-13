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
        return Pdf::view('pdf.orcamento', ['orcamento' => $orcamento])
            ->format('a4')
            ->name("Orcamento-{$orcamento->id}.pdf")
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                    ->timeout(config('browsershot.timeout', 60))
                    ->setNodeBinary('/usr/bin/node')
                    ->setNpmBinary('/usr/bin/npm')
                    ->setNodeModulePath('/var/www/node_modules')
                    ->setEnvironmentVariables([
                        'NODE_PATH' => '/var/www/node_modules',
                        'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
                    ]);
            });
    }

    /**
     * Salva o PDF no storage
     *
     * @param  \Spatie\LaravelPdf\PdfBuilder  $pdf
     */
    public function salvarPdf($pdf, string $path): string
    {
        // Pega o conteúdo binário do PDF sem salvar em disco primeiro
        // Usando base64() para garantir compatibilidade
        $content = base64_decode($pdf->base64());

        // Salva no disco configurado (local, s3, google)
        Storage::put($path, $content);

        return $path;
    }

    /**
     * Gera e salva o PDF de um orçamento
     */
    public function gerarESalvarOrcamento(Orcamento $orcamento): string
    {
        $pdf = $this->gerarOrcamentoPdf($orcamento);
        $path = "orcamentos/orcamento-{$orcamento->id}-" . now()->format('YmdHis') . '.pdf';

        return $this->salvarPdf($pdf, $path);
    }
}
