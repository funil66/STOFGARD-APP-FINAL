<?php

namespace App\Http\Controllers;

use App\Models\NotaFiscal;
use App\Models\Configuracao;

class NotaFiscalPdfController extends Controller
{
    public function gerarPdf(NotaFiscal $notaFiscal)
    {
        return $this->renderPdf($notaFiscal);
    }

    private function renderPdf(NotaFiscal $notaFiscal)
    {
        return app(\App\Services\PdfService::class)->generate(
            'pdf.nota-fiscal',
            [
                'notaFiscal' => $notaFiscal,
                'config' => Configuracao::first(),
            ],
            "NotaFiscal-{$notaFiscal->numero}.pdf",
            true
        );
    }
}
