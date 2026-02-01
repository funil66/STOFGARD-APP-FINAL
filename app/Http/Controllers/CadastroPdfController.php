<?php

namespace App\Http\Controllers;

use App\Models\Cadastro;
use App\Models\Setting;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Http\Request;

class CadastroPdfController extends Controller
{
    public function gerarPdf(Cadastro $cadastro)
    {
        return $this->renderPdf($cadastro);
    }

    /**
     * Lógica central de geração do PDF (mesmo padrão do OrcamentoPdfController).
     */
    private function renderPdf(Cadastro $cadastro)
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

        // Carrega relacionamentos
        $cadastro->load('loja');

        // Nome de arquivo seguro
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '-', $cadastro->nome);

        return Pdf::view('pdf.cadastro_ficha', [
            'cadastro' => $cadastro,
            'config' => $config,
        ])
            ->format('a4')
            ->name("Ficha-Cadastral-{$safeName}.pdf")
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                    ->setChromePath(config('browsershot.chrome_path'))
                    ->setNodeBinary(config('browsershot.node_path'))
                    ->setNpmBinary(config('browsershot.npm_path'))
                    ->setOption('args', config('browsershot.chrome_args'))
                    ->timeout(config('browsershot.timeout'));
            })
            ->inline();
    }
}
