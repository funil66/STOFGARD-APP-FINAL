<?php

namespace App\Http\Controllers;

use App\Models\Cadastro;

class CadastroPdfController extends BasePdfQueueController
{
    public function gerarPdf(Cadastro $cadastro)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdf.cadastro_ficha',
            [
                'cadastro' => $cadastro,
                'config' => $config,
            ],
            'cadastro',
            $cadastro,
            ['loja']
        );
    }
}
