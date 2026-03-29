<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\Configuracao;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Http\Request;

class OrdemServicoPdfController extends Controller
{
    public function gerarPdf(OrdemServico $record)
    {
        return $this->renderPdf($record);
    }

    private function renderPdf(OrdemServico $record)
    {
        // Garante diretório temporário
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return app(\App\Services\PdfService::class)->generate(
            'pdf.os',
            [
                'record' => $record->load(['produtosUtilizados', 'cliente', 'itens']),
                'config' => Configuracao::first()
            ],
            "OS-{$record->id}.pdf",
            true
        );
    }
}
