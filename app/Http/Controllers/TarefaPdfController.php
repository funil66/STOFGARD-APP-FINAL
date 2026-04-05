<?php

namespace App\Http\Controllers;

use App\Models\Tarefa;
use App\Models\Configuracao;

class TarefaPdfController extends Controller
{
    public function gerarPdf(Tarefa $tarefa)
    {
        return app(\App\Services\PdfService::class)->generate(
            'pdf.tarefa',
            [
                'tarefa' => $tarefa,
                'config' => Configuracao::first(),
            ],
            "Tarefa-{$tarefa->id}.pdf",
            true
        );
    }
}
