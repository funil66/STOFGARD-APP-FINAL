<?php

namespace App\Http\Controllers;

use App\Models\TabelaPreco;

class TabelaPrecoPdfController extends BasePdfQueueController
{
    public function gerarPdf(TabelaPreco $tabelapreco)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdfs.tabelapreco',
            [
                'tabelapreco' => $tabelapreco,
                'config' => $config,
            ],
            'tabelapreco',
            $tabelapreco,
            []
        );
    }
}
