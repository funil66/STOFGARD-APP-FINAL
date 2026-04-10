<?php

namespace App\Http\Controllers;

use App\Models\Equipamento;

class EquipamentoPdfController extends BasePdfQueueController
{
    public function gerarPdf(Equipamento $equipamento)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdfs.equipamento',
            [
                'equipamento' => $equipamento,
                'config' => $config,
            ],
            'equipamento',
            $equipamento,
            []
        );
    }
}
