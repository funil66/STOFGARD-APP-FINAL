<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\Configuracao;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Http\Request;

class OrdemServicoPdfController extends Controller
{
    public function gerarPdf(OrdemServico $record)
    {
        return $this->renderPdf($record);
    }

    private function renderPdf(OrdemServico $record)
    {
        // Garante diretório temporário
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return Pdf::view('pdf.os', [
            'record' => $record->load(['produtosUtilizados', 'cliente', 'itens']),
            'config' => Configuracao::first()
        ])
            ->format('a4')
            ->name("OS-{$record->id}.pdf")
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
