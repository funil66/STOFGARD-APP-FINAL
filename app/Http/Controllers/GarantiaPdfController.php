<?php

namespace App\Http\Controllers;

use App\Models\Garantia;
use App\Models\Setting;
use Illuminate\Http\Request;

class GarantiaPdfController extends Controller
{
    public function gerarPdf(Garantia $garantia)
    {
        $garantia->load(['ordemServico.cliente']);

        // Carrega configurações do sistema (mesmo padrão)
        $settingsArray = Setting::all()->pluck('value', 'key')->toArray();
        $config = (object) $settingsArray;

        $safeName = "Certificado-Garantia-OS-" . ($garantia->ordemServico->numero_os ?? 'ND');

        return app(\App\Services\PdfService::class)->generate(
            'pdf.certificado_garantia',
            [
                'garantia' => $garantia,
                'os' => $garantia->ordemServico,
                'config' => $config,
            ],
            "{$safeName}.pdf",
            true
        );
    }
}
