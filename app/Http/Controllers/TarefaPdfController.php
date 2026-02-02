<?php

namespace App\Http\Controllers;

use App\Models\Tarefa;
use App\Models\Configuracao;
use Spatie\LaravelPdf\Facades\Pdf;

class TarefaPdfController extends Controller
{
    public function gerarPdf(Tarefa $tarefa)
    {
        return Pdf::view('pdf.tarefa', [
            'tarefa' => $tarefa,
            'config' => Configuracao::first()
        ])
            ->format('a4')
            ->name("Tarefa-{$tarefa->id}.pdf")
            ->withBrowsershot(fn($b) => $b->noSandbox()
                ->setChromePath(config('services.browsershot.chrome_path'))
                ->setNodeBinary(config('services.browsershot.node_path'))
                ->setNpmBinary(config('services.browsershot.npm_path'))
                ->setOption('args', ['--disable-web-security', '--no-sandbox'])
                ->timeout(60))
            ->download();
    }
}
