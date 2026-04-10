<?php

namespace App\Http\Controllers;

use App\Models\Estoque;

class EstoquePdfController extends BasePdfQueueController
{
    public function gerarPdf(Estoque $estoque)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdfs.estoque',
            [
                'estoque' => $estoque,
                'config' => $config,
            ],
            'estoque',
            $estoque,
            []
        );
    }
}
