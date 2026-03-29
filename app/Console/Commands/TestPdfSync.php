<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Orcamento;
use App\Models\Tenant;

class TestPdfSync extends Command
{
    protected $signature = 'test:pdf_sync';
    protected $description = 'Testa a geracao sincrona do PDF de um Orçamento localmente';

    public function handle()
    {
        $this->info("Iniciando teste fisico de PDF...");

        $tenant = Tenant::first();
        if (!$tenant) {
            $this->error("FALHA: Nenhum tenant encontrado!");
            return 1;
        }
        tenancy()->initialize($tenant);
        $this->info("OK - Tenant " . $tenant->id . " carregado.");

        $orcamento = Orcamento::with(['cliente', 'itens'])->orderBy('id', 'desc')->first();
        if (!$orcamento) {
            $this->warn("Nenhum orçamento para testar.");
            return 0;
        }
        $this->info("OK - Orçamento #" . $orcamento->id . " carregado.");

        $settingsArray = \App\Models\Setting::all()->pluck('value', 'key')->toArray();
        $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
        foreach ($jsonFields as $k) {
            if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                $settingsArray[$k] = json_decode($settingsArray[$k], true);
            }
        }
        $config = (object) $settingsArray;

        $pdfService = app(\App\Services\PdfService::class);
        
        $startTime = microtime(true);
        try {
            // Emulando exatamente a chamada síncrona real feita pelo navegador
            $pdfResponse = $pdfService->generate(
                'pdf.orcamento',
                [
                    'orcamento' => $orcamento,
                    'config' => $config,
                ],
                "Orcamento-{$orcamento->id}.pdf",
                true // Stream/Inline
            );
            
            $timeTook = round(microtime(true) - $startTime, 2);

            // Se é o PdfBuilder (Responsable), forçamos a renderização invocando base64()
            $output = '';
            if (method_exists($pdfResponse, 'base64')) {
                $output = $pdfResponse->base64();
            } elseif (method_exists($pdfResponse, 'getContent')) {
                $output = $pdfResponse->getContent();
            }

            if (strlen($output) > 100) {
                $this->info("SUCESSO ABSOLUTO! PDF Local processado. Motor Chromium respondeu com integridade. Tamanho base64: " . strlen($output) . " bytes em {$timeTook}s.");
                return 0;
            } else {
                $this->error("FALHA - O PdfService não conseguiu renderizar: Tamanho retornado = " . strlen($output) . " bytes.");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("FALHA CRÍTICA: " . $e->getMessage());
            return 1;
        }
    }
}
