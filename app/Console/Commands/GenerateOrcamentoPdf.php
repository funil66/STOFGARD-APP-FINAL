<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Orcamento;
use App\Services\PixService;
use App\Services\StaticPixQrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GenerateOrcamentoPdf extends Command
{
    protected $signature = 'stofgard:generate-orcamento-pdf {orcamentoId?} {--minimal : Use the minimal PDF layout} {--clean : Use the cleaned table-based PDF layout} {--v2 : Use the v2 table-based layout (compact, logo palette)}';
    protected $description = 'Gera um PDF do orçamento especificado (ou o último) e salva em storage/app/public/orcamentos/';

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

        // Usar cache em memória temporariamente para evitar problemas de permissão em cache de arquivos durante execução CLI
        config(['cache.default' => 'array']);

        $pixService = app(PixService::class);
        $chavePix = \App\Services\ConfiguracaoService::financeiro('pix_chave');

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
            app(StaticPixQrCodeService::class)->generate($orc);
        }

        $qrCodeBase64 = $qrCodeBase64 ?? $orc->pix_qrcode_base64;

        $config = \App\Models\Setting::all()->pluck('value', 'key')->toArray();
        $jsonFields = ['catalogo_servicos_v2', 'financeiro_pix_keys', 'financeiro_taxas_cartao', 'financeiro_parcelamento'];
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

        if ($this->option('minimal')) {
            $view = 'pdf.orcamento_minimal';
        } elseif ($this->option('clean')) {
            $view = 'pdf.orcamento_clean';
        } elseif ($this->option('v2')) {
            $view = 'pdf.orcamento_oficial';
        } else {
            $view = 'pdf.orcamento';
        }

        $pdf = Pdf::loadView($view, $viewData);

        $dir = 'public/orcamentos';
        Storage::makeDirectory($dir);
        $path = $dir.'/orcamento-'.$orc->id.'.pdf';
        $fullPath = storage_path('app/'.$path);

        $pdf->save($fullPath);

        $this->info('PDF gerado: '.$fullPath);

        return 0;
    }
}
