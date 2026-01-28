<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrcamentoPdfController extends Controller
{
    public function gerarPdf(Orcamento $orcamento)
    {
        // Carrega relacionamentos para não dar erro na view
        $orcamento->load(['cliente', 'itens']);

        // Carrega configurações da empresa (Logo, Nome, etc)
        $config = Setting::all()->pluck('value', 'key')->toArray();

        // Decodifica campos JSON usados pela view (Repeaters/settings salvos como JSON)
        $jsonFields = ['catalogo_servicos_v2', 'financeiro_pix_keys', 'financeiro_taxas_cartao', 'financeiro_parcelamento'];
        foreach ($jsonFields as $key) {
            if (isset($config[$key]) && is_string($config[$key])) {
                $decoded = json_decode($config[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $config[$key] = $decoded;
                }
            }
        }

        // Renderiza o PDF
        $pdf = Pdf::loadView('pdf.orcamento_oficial', [
            'orcamento' => $orcamento,
            'config' => $config,
        ]);

        // Configura papel A4
        $pdf->setPaper('a4', 'portrait');

        // Exibe no navegador (stream)
        return $pdf->stream("Orcamento-{$orcamento->numero}.pdf");
    }
}
