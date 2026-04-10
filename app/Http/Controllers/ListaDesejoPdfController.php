<?php

namespace App\Http\Controllers;

use App\Models\ListaDesejo;

class ListaDesejoPdfController extends BasePdfQueueController
{
    public function gerarPdf(ListaDesejo $listadesejo)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdfs.listadesejo',
            [
                'listadesejo' => $listadesejo,
                'config' => $config,
            ],
            'listadesejo',
            $listadesejo,
            []
        );
    }
}
