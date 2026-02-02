<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Configuracao;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Http\Request;

class AgendaPdfController extends Controller
{
    public function gerarPdf(Agenda $agenda)
    {
        return $this->renderPdf($agenda);
    }

    private function renderPdf(Agenda $agenda)
    {
        // Garante diretório temporário
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return Pdf::view('pdf.agenda', [
            'agenda' => $agenda,
            'config' => Configuracao::first()
        ])
            ->format('a4')
            ->name("Agenda-{$agenda->id}.pdf")
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
