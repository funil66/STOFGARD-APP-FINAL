<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Configuracao;

class AgendaPdfController extends Controller
{
    public function gerarPdf(Agenda $agenda)
    {
        return $this->renderPdf($agenda);
    }

    private function renderPdf(Agenda $agenda)
    {
        return app(\App\Services\PdfService::class)->generate(
            'pdf.agenda',
            [
                'agenda' => $agenda,
                'config' => Configuracao::first(),
            ],
            "Agenda-{$agenda->id}.pdf",
            true
        );
    }
}
