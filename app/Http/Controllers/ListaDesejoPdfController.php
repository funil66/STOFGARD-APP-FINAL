<?php

namespace App\Http\Controllers;

use App\Models\ListaDesejo;
use Spatie\LaravelPdf\Facades\Pdf;

class ListaDesejoPdfController extends Controller
{
    public function gerarPdf(ListaDesejo $listadesejo)
    {
        $pdf = Pdf::view('pdfs.listadesejo', ['listadesejo' => $listadesejo])
            ->format('a4')
            ->name('listadesejo-' . $listadesejo->id . '.pdf');

        return $pdf->download();
    }
}
