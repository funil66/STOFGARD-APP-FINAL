<?php

namespace App\Http\Controllers;

use App\Models\Agenda;

class AgendaPdfController extends BasePdfQueueController
{
    public function gerarPdf(Agenda $agenda)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdf.agenda',
            [
                'agenda' => $agenda,
                'config' => $config,
            ],
            'agenda',
            $agenda,
            []
        );
    }
}
