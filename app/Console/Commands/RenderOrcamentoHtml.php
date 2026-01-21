<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Orcamento;
use App\Services\PixService;

class RenderOrcamentoHtml extends Command
{
    protected $signature = 'stofgard:render-orcamento-html {orcamentoId?}';
    protected $description = 'Renderiza a view do orcamento para HTML em storage/debug/orcamento-{id}.html para testes (Puppeteer)';

    public function handle(): int
    {
        $id = $this->argument('orcamentoId');

        if ($id) {
            $orc = Orcamento::find($id);
            if (! $orc) {
                $this->error('Orçamento não encontrado: '.$id);
                return 1;
            }
        } else {
            $orc = Orcamento::latest()->first();
            if (! $orc) {
                $this->error('Nenhum orçamento encontrado no banco.');
                return 1;
            }
        }

        $orc->load(['cliente', 'itens.tabelaPreco', 'parceiro']);
        $orc->calcularTotal();

        $pixService = app(PixService::class);
        $chavePix = \App\Services\ConfiguracaoService::financeiro('pix_chave') ?: env('PIX_CHAVE');

        $qrCodeBase64 = null;
        if ($chavePix && $orc->valor_total > 0) {
            $payload = $pixService->gerarPayloadPix(
                $chavePix,
                $orc->valor_total,
                \App\Services\ConfiguracaoService::empresa('razao_social', 'Stofgard'),
                \App\Services\ConfiguracaoService::empresa('cidade', 'Ribeirao Preto'),
                $orc->id
            );
            $qrCodeBase64 = $pixService->gerarQrCodeBase64($payload);
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

        $this->info('HTML renderizado: '.$htmlPath);

        return 0;
    }
}
