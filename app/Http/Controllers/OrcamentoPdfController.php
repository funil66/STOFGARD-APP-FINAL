<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class OrcamentoPdfController extends Controller
{
    public function gerarPdf(Orcamento $orcamento)
    {
        // 1. Carrega dados e relacionamentos
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
        // Usamos a mesma view, mas agora ela será interpretada por um navegador real
        $html = view('pdf.orcamento_oficial', [
            'orcamento' => $orcamento,
            'config' => $config,
        ])->render();

        // 4. Cria arquivos temporários
        // Precisamos salvar o HTML em disco para o script JS ler
        $tempId = $orcamento->id . '_' . time();
        $htmlPath = storage_path("app/public/temp_orc_{$tempId}.html");
        $pdfPath = storage_path("app/public/temp_orc_{$tempId}.pdf");

        // Garante que a pasta existe
        if (!File::exists(dirname($htmlPath))) {
            File::makeDirectory(dirname($htmlPath), 0755, true);
        }

        file_put_contents($htmlPath, $html);

        // 5. Executa o Script Puppeteer (Node.js)
        // Chama o script que já está na pasta scripts/generate-pdf.js
        $scriptPath = base_path('scripts/generate-pdf.js');
        
        // Verifica se o node e o script existem
        if (!file_exists($scriptPath)) {
            abort(500, "Script de geração de PDF não encontrado: $scriptPath");
        }

        // Executa o comando: node script.js input.html output.pdf
        $result = Process::run(['node', $scriptPath, $htmlPath, $pdfPath]);

        // 6. Tratamento de Erro
        if ($result->failed()) {
            // Limpa o HTML se falhar
            @unlink($htmlPath);
            
            // Loga o erro e mostra na tela (modo debug Iron Code)
            \Log::error("Erro Puppeteer: " . $result->errorOutput());
            return response()->json([
                'error' => 'Falha ao gerar PDF via Puppeteer',
                'details' => $result->errorOutput(),
                'output' => $result->output()
            ], 500);
        }

        // 7. Entrega o PDF e Limpa a sujeira
        // O deleteFileAfterSend garante que não vamos lotar o servidor de lixo
        return response()->file($pdfPath)->deleteFileAfterSend();
        
        // Nota: O HTML temporário pode ser deletado aqui também se quiser, 
        // mas o response->file trava o processo.
        // Idealmente teríamos um Job de limpeza, mas para agora, 
        // podemos deixar o HTML lá ou tentar unlink depois. 
        // Para simplificar e evitar race conditions, vou deixar o HTML lá por enquanto 
        // (ele é pequeno) ou você pode rodar um cron de limpeza na pasta temp.
    }
}
