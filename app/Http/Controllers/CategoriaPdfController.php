<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Configuracao;
use Spatie\LaravelPdf\Facades\Pdf;

class CategoriaPdfController extends Controller
{
    public function gerarPdf(Categoria $categoria)
    {
        return $this->renderPdf($categoria);
    }

    private function renderPdf(Categoria $categoria)
    {
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return Pdf::view('pdf.categoria', [
            'categoria' => $categoria,
            'config' => Configuracao::first()
        ])
            ->format('a4')
            ->name("Categoria-{$categoria->slug}.pdf")
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
