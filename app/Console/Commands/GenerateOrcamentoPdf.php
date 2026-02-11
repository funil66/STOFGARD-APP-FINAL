<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Orcamento;
use App\Services\Pix\PixMasterService;

use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Services\PdfService;

class GenerateOrcamentoPdf extends Command
{
    protected $signature = 'stofgard:generate-orcamento-pdf {orcamentoId?} {--v2 : Use the v2 table-based layout (compact, logo palette)}';
    protected $description = 'Gera um PDF do orçamento especificado (ou o último) e salva em storage/app/public/orcamentos/';

    public function handle(): int
    {
        $id = $this->argument('orcamentoId');

        if ($id) {
            $orc = Orcamento::find($id);
            if (!$orc) {
                $this->error('Orçamento não encontrado: ' . $id);
                return 1;
            }
        } else {
            $orc = Orcamento::latest()->first();
            if (!$orc) {
                $this->error('Nenhum orçamento encontrado no banco.');
                return 1;
            }
        }

        $orc->load(['cliente', 'itens.tabelaPreco', 'loja']);
        $orc->calcularTotal();

        // Usar cache em memória temporariamente
        config(['cache.default' => 'array']);

        $pixService = new \App\Services\Pix\PixMasterService();
        $chavePix = \App\Services\ConfiguracaoService::financeiro('pix_chave');

        $qrCodeBase64 = null;
        if ($chavePix && $orc->valor_total > 0) {
            $pixData = $pixService->gerarQrCode(
                $chavePix,
                \App\Services\ConfiguracaoService::empresa('razao_social', 'Stofgard'),
                \App\Services\ConfiguracaoService::empresa('cidade', 'Ribeirao Preto'),
                $orc->numero ?? 'ORC',
                $orc->valor_total
            );

            $qrCodeBase64 = $pixData['qr_code_img'];
            $payload = $pixData['payload_pix'];
        }

        // Logic for static/legacy code removed as it likely depended on missing service.
        // If needed, PixMasterService handles it.
        $qrCodeBase64 = $qrCodeBase64 ?? $orc->pix_qrcode_base64;

        $config = \App\Models\Setting::all()->pluck('value', 'key')->toArray();
        $jsonFields = ['financeiro_pix_keys', 'financeiro_taxas_cartao', 'financeiro_parcelamento'];
        foreach ($jsonFields as $key) {
            if (isset($config[$key]) && is_string($config[$key])) {
                $decoded = json_decode($config[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $config[$key] = $decoded;
                }
            }
        }

        $viewData = [
            'orcamento' => $orc,
            'qrCodePix' => $qrCodeBase64,
            'chavePix' => $chavePix,
            'copiaCopia' => $payload ?? null,
            'banco' => \App\Services\ConfiguracaoService::financeiro('banco'),
            'agencia' => \App\Services\ConfiguracaoService::financeiro('agencia'),
            'conta' => \App\Services\ConfiguracaoService::financeiro('conta'),
            'config' => $config,
        ];

        if ($this->option('v2')) {
            $view = 'pdf.orcamento_oficial';
        } else {
            $view = 'pdf.orcamento';
        }

        $dir = 'public/orcamentos';
        Storage::makeDirectory($dir);
        $path = $dir . '/orcamento-' . $orc->id . '.pdf';
        $fullPath = storage_path('app/' . $path);

        Pdf::view($view, $viewData)
            ->format('a4')
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                    ->setOption('args', ['--disable-web-security', '--no-sandbox'])
                    ->timeout(60);
            })
            ->save($fullPath);

        $this->info('PDF gerado com sucesso (Spatie): ' . $fullPath);

        return 0;
    }
}
