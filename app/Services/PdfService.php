<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class PdfService
{
    /**
     * Gera o PDF de uma view e retorna o conteúdo binário.
     * Iron Code Standard: Robustez para produção (Linux/Contabo).
     */
    public function generatePdfFromView(string $view, array $data): string
    {
        try {
            // Renderiza o HTML do Blade primeiro
            $html = view($view, $data)->render();

            $browsershot = Browsershot::html($html)
                ->setNodeBinary(config('app.node_binary'))
                ->setNpmBinary(config('app.npm_binary'))
                ->noSandbox()
                ->margins(10, 10, 10, 10)
                ->format('A4')
                ->showBackground()
                ->waitUntilNetworkIdle();

            // Configuração específica para ambiente Local (Windows/WSL) vs Produção
            if (app()->environment('local')) {
                // No Windows/WSL, às vezes o Puppeteer não acha o Chrome sozinho
                // Se der erro localmente, verifique o caminho do seu Chrome
                // $browsershot->setChromePath('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe');
            }

            return $browsershot->pdf();

        } catch (\Exception $e) {
            Log::error('Erro crítico na geração de PDF: '.$e->getMessage());
            throw $e;
        }
    }
}
