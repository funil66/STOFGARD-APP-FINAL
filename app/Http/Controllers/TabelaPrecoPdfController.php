<?php

namespace App\Http\Controllers;

use App\Models\TabelaPreco;

class TabelaPrecoPdfController extends Controller
{
    public function gerarPdf(TabelaPreco $tabelapreco)
    {
        return app(\App\Services\PdfService::class)->generate(
            'pdfs.tabelapreco',
            ['tabelapreco' => $tabelapreco],
            'tabelapreco-' . $tabelapreco->id . '.pdf',
            true
        );
    }
}
