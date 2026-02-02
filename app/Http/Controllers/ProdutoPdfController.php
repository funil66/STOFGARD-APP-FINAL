<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Configuracao;
use Spatie\LaravelPdf\Facades\Pdf;

class ProdutoPdfController extends Controller
{
    public function gerarPdf(Produto $produto)
    {
        return $this->renderPdf($produto);
    }

    private function renderPdf(Produto $produto)
    {
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return Pdf::view('pdf.produto', [
            'produto' => $produto,
            'config' => Configuracao::first()
        ])
            ->format('a4')
            ->name("Produto-{$produto->id}.pdf")
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
