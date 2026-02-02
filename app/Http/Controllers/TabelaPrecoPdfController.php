<?php

namespace App\Http\Controllers;

use App\Models\TabelaPreco;
use Spatie\LaravelPdf\Facades\Pdf;

class TabelaPrecoPdfController extends Controller
{
    public function gerarPdf(TabelaPreco $tabelapreco)
    {
        $pdf = Pdf::view('pdfs.tabelapreco', ['tabelapreco' => $tabelapreco])
            ->format('a4')
            ->name('tabelapreco-' . $tabelapreco->id . '.pdf');

        return $pdf->download();
    }
}
