<?php

namespace App\Http\Controllers;

use App\Models\Tarefa;

class TarefaPdfController extends BasePdfQueueController
{
    public function gerarPdf(Tarefa $tarefa)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdf.tarefa',
            [
                'tarefa' => $tarefa,
                'config' => $config,
            ],
            'tarefa',
            $tarefa,
            []
        );
    }
}
