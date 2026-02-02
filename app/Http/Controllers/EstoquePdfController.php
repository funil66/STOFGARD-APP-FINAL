<?php

namespace App\Http\Controllers;

use App\Models\Estoque;
use Spatie\LaravelPdf\Facades\Pdf;

class EstoquePdfController extends Controller
{
    public function gerarPdf(Estoque $estoque)
    {
        $pdf = Pdf::view('pdfs.estoque', ['estoque' => $estoque])
            ->format('a4')
            ->name('estoque-' . $estoque->id . '.pdf');

        return $pdf->download();
    }
}
