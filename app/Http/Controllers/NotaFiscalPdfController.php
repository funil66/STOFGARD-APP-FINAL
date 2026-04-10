<?php

namespace App\Http\Controllers;

use App\Models\NotaFiscal;

class NotaFiscalPdfController extends BasePdfQueueController
{
    public function gerarPdf(NotaFiscal $notaFiscal)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdf.nota-fiscal',
            [
                'notaFiscal' => $notaFiscal,
                'config' => $config,
            ],
            'nota_fiscal',
            $notaFiscal,
            []
        );
    }
}
