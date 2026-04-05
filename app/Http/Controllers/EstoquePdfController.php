<?php

namespace App\Http\Controllers;

use App\Models\Estoque;

class EstoquePdfController extends Controller
{
    public function gerarPdf(Estoque $estoque)
    {
        return app(\App\Services\PdfService::class)->generate(
            'pdfs.estoque',
            ['estoque' => $estoque],
            'estoque-' . $estoque->id . '.pdf',
            true
        );
    }
}
