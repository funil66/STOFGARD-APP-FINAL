<?php

namespace App\Http\Controllers;

use App\Models\Equipamento;
use Spatie\LaravelPdf\Facades\Pdf;

class EquipamentoPdfController extends Controller
{
    public function gerarPdf(Equipamento $equipamento)
    {
        $pdf = Pdf::view('pdfs.equipamento', ['equipamento' => $equipamento])
            ->format('a4')
            ->name('equipamento-' . $equipamento->id . '.pdf');

        return $pdf->download();
    }
}
