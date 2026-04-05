<?php

namespace App\Http\Controllers;

use App\Models\Equipamento;

class EquipamentoPdfController extends Controller
{
    public function gerarPdf(Equipamento $equipamento)
    {
        return app(\App\Services\PdfService::class)->generate(
            'pdfs.equipamento',
            ['equipamento' => $equipamento],
            'equipamento-' . $equipamento->id . '.pdf',
            true
        );
    }
}
