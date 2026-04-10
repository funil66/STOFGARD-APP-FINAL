<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;

class OrdemServicoPdfController extends BasePdfQueueController
{
    public function gerarPdf(OrdemServico $record)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdf.os',
            [
                'record' => $record,
                'config' => $config,
            ],
            'os',
            $record,
            ['produtosUtilizados', 'cliente', 'itens']
        );
    }
}
