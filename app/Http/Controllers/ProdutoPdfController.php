<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Configuracao;

class ProdutoPdfController extends Controller
{
    public function gerarPdf(Produto $produto)
    {
        return $this->renderPdf($produto);
    }

    private function renderPdf(Produto $produto)
    {
        return app(\App\Services\PdfService::class)->generate(
            'pdf.produto',
            [
                'produto' => $produto,
                'config' => Configuracao::first(),
            ],
            "Produto-{$produto->id}.pdf",
            true
        );
    }
}
