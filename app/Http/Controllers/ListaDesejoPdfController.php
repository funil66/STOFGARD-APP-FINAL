<?php

namespace App\Http\Controllers;

use App\Models\ListaDesejo;

class ListaDesejoPdfController extends Controller
{
    public function gerarPdf(ListaDesejo $listadesejo)
    {
        return app(\App\Services\PdfService::class)->generate(
            'pdfs.listadesejo',
            ['listadesejo' => $listadesejo],
            'listadesejo-' . $listadesejo->id . '.pdf',
            true
        );
    }
}
