<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Orcamento;
use App\Services\Pix\PixMasterService;

class RenderOrcamentoHtml extends Command
{
    protected $signature = 'autonomia:render-orcamento-html {orcamentoId?}';
    protected $description = 'Renderiza a view do orcamento para HTML em storage/debug/orcamento-{id}.html para testes (Puppeteer)';

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

        $orc->load(['cliente', 'itens.tabelaPreco', 'parceiro']);
        app(\App\Actions\Financeiro\CalculateOrcamentoTotalsAction::class)->execute($orc);

        $pixService = app(PixMasterService::class);
        $chavePix = \App\Services\ConfiguracaoService::financeiro('pix_chave') ?: env('PIX_CHAVE');

        $qrCodeBase64 = null;
        $payload = null;
        if ($chavePix && $orc->valor_total > 0) {
            $resultado = $pixService->gerarQrCode(
                $chavePix,
                \App\Services\ConfiguracaoService::empresa('razao_social', 'Autonomia Ilimitada'),
                \App\Services\ConfiguracaoService::empresa('cidade', 'Ribeirao Preto'),
                (string) $orc->id,
                $orc->valor_total
            );
            $payload = $resultado['payload_pix'];
            $qrCodeBase64 = $resultado['qr_code_img'];
        }

        if (empty($qrCodeBase64) && $orc->forma_pagamento === 'pix' && empty($orc->pix_qrcode_base64)) {
            app(\App\Services\StaticPixQrCodeService::class)->generate($orc);
            $orc->refresh();
        }

        $qrCodeBase64 = $qrCodeBase64 ?? $orc->pix_qrcode_base64;

        $viewData = [
            'orcamento' => $orc,
            'qrCodePix' => $qrCodeBase64,
            'chavePix' => $chavePix,
            'copiaCopia' => $payload ?? null,
            'banco' => \App\Services\ConfiguracaoService::financeiro('banco'),
            'agencia' => \App\Services\ConfiguracaoService::financeiro('agencia'),
            'conta' => \App\Services\ConfiguracaoService::financeiro('conta'),
            'scale' => 1.0,
        ];

        $html = view('pdf.orcamento', $viewData)->render();

        $debugDir = storage_path('debug');
        if (!file_exists($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        $htmlPath = $debugDir . "/orcamento-{$orc->id}.html";
        file_put_contents($htmlPath, $html);

        $this->info('HTML renderizado: ' . $htmlPath);

        return 0;
    }
}
