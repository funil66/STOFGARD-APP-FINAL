<?php

namespace App\Http\Controllers;

use App\Models\Categoria;

class CategoriaPdfController extends BasePdfQueueController
{
    public function gerarPdf(Categoria $categoria)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdf.categoria',
            [
                'categoria' => $categoria,
                'config' => $config,
            ],
            'categoria',
            $categoria,
            []
        );
    }
}
