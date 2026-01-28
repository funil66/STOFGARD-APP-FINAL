<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class OrcamentoPdfController extends Controller
{
    public function gerarPdf(Orcamento $orcamento)
    {
        // 1. Carrega dados
        $orcamento->load(['cliente', 'itens', 'vendedor', 'loja']);

        // 2. Prepara configurações
        $config = Setting::all()->pluck('value', 'key')->toArray();
        $jsonFields = ['catalogo_servicos_v2', 'financeiro_pix_keys', 'financeiro_taxas_cartao', 'financeiro_parcelamento'];
        
        foreach ($jsonFields as $key) {
            if (isset($config[$key]) && is_string($config[$key])) {
                $decoded = json_decode($config[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $config[$key] = $decoded;
                }
            }
        }

        // 3. Renderiza o HTML (Blade)
        $html = view('pdf.orcamento_oficial', [
            'orcamento' => $orcamento,
            'config' => $config,
        ])->render();

        // 4. Salva HTML Temporário para o Node ler
        $tempId = $orcamento->id . '_' . time();
        $htmlPath = storage_path("app/public/temp_orc_{$tempId}.html");
        $pdfPath = storage_path("app/public/temp_orc_{$tempId}.pdf");

        // Garante pasta
        if (!File::exists(dirname($htmlPath))) {
            File::makeDirectory(dirname($htmlPath), 0755, true);
        }

        file_put_contents($htmlPath, $html);

        // 5. Define caminho do script Puppeteer
        $scriptPath = base_path('scripts/generate-pdf.js');
        
        if (!file_exists($scriptPath)) {
            abort(500, "Script Puppeteer não encontrado em: $scriptPath");
        }

        // 6. Executa: node scripts/generate-pdf.js input.html output.pdf
        // Isso remove o DomPDF da jogada completamente
        $result = Process::run(['node', $scriptPath, $htmlPath, $pdfPath]);

        // 7. Tratamento de Erro
        if ($result->failed()) {
            Log::error("Erro Puppeteer: " . $result->errorOutput());
            Log::error("Output Puppeteer: " . $result->output());
            
            @unlink($htmlPath);
            return response()->json([
                'message' => 'Erro ao gerar PDF com Puppeteer.',
                'error' => $result->errorOutput()
            ], 500);
        }

        // 8. Entrega o PDF
        if (file_exists($pdfPath)) {
            @unlink($htmlPath); // Limpa HTML
            return response()->file($pdfPath)->deleteFileAfterSend();
        } else {
            return response()->json(['error' => 'Arquivo PDF não foi criado.'], 500);
        }
    }
}
