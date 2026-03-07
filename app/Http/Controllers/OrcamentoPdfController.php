<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Setting;
use Illuminate\Http\Request;

class OrcamentoPdfController extends Controller
{
    public function gerarPdf(Orcamento $orcamento)
    {
        return $this->renderPdf($orcamento);
    }

    private function renderPdf(Orcamento $orcamento)
    {
        // Garante que o diretório de arquivos temporários exista
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        // Carrega configurações do sistema
        $settingsArray = Setting::all()->pluck('value', 'key')->toArray();

        // Decodifica JSONs conhecidos
        $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
        foreach ($jsonFields as $k) {
            if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                $settingsArray[$k] = json_decode($settingsArray[$k], true);
            }
        }

        // Cria objeto Config para a View
        $config = (object) $settingsArray;

        // Carrega relacionamentos para popular o PDF
        $orcamento->load(['cliente', 'itens']);

        return app(\App\Services\PdfService::class)->generate(
            'pdf.orcamento',
            [
                'orcamento' => $orcamento,
                'config' => $config,
            ],
            "Orcamento-{$orcamento->id}.pdf",
            true
        );
    }
}
