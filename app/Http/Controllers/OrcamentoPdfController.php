<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;

class OrcamentoPdfController extends BasePdfQueueController
{
    public function gerarPdf(Orcamento $orcamento)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdf.orcamento',
            [
                'orcamento' => $orcamento,
                'config' => $config,
            ],
            'orcamento',
            $orcamento,
            ['cliente', 'itens']
        );
    }
}
