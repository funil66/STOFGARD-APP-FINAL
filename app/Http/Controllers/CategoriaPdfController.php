<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Configuracao;

class CategoriaPdfController extends Controller
{
    public function gerarPdf(Categoria $categoria)
    {
        return $this->renderPdf($categoria);
    }

    private function renderPdf(Categoria $categoria)
    {
        return app(\App\Services\PdfService::class)->generate(
            'pdf.categoria',
            [
                'categoria' => $categoria,
                'config' => Configuracao::first(),
            ],
            "Categoria-{$categoria->slug}.pdf",
            true
        );
    }
}
