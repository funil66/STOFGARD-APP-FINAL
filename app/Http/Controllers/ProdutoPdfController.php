<?php

namespace App\Http\Controllers;

use App\Models\Produto;

class ProdutoPdfController extends BasePdfQueueController
{
    public function gerarPdf(Produto $produto)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdf.produto',
            [
                'produto' => $produto,
                'config' => $config,
            ],
            'produto',
            $produto,
            []
        );
    }
}
