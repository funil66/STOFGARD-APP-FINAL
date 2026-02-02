<?php

namespace App\Http\Controllers;

use App\Models\Garantia;
use Spatie\LaravelPdf\Facades\Pdf;

class GarantiaPdfController extends Controller
{
    public function gerarPdf(Garantia $garantia)
    {
        $pdf = Pdf::view('pdfs.garantia', ['garantia' => $garantia])
            ->format('a4')
            ->name('garantia-' . $garantia->numero_garantia . '.pdf');

        return $pdf->download();
    }
}
