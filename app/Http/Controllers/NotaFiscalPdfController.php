<?php

namespace App\Http\Controllers;

use App\Models\NotaFiscal;
use App\Models\Configuracao;
use Spatie\LaravelPdf\Facades\Pdf;

class NotaFiscalPdfController extends Controller
{
    public function gerarPdf(NotaFiscal $notaFiscal)
    {
        return $this->renderPdf($notaFiscal);
    }

    private function renderPdf(NotaFiscal $notaFiscal)
    {
        // Garante diretório temporário
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return Pdf::view('pdf.nota-fiscal', [
            'notaFiscal' => $notaFiscal,
            'config' => Configuracao::first()
        ])
            ->format('a4')
            ->name("NotaFiscal-{$notaFiscal->numero}.pdf")
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                    ->setChromePath(config('services.browsershot.chrome_path', '/usr/bin/google-chrome'))
                    ->setNodeBinary(config('services.browsershot.node_path', '/usr/bin/node'))
                    ->setNpmBinary(config('services.browsershot.npm_path', '/usr/bin/npm'))
                    ->setOption('args', ['--disable-web-security', '--no-sandbox', '--disable-setuid-sandbox'])
                    ->timeout(60);
            })
            ->download();
    }
}
