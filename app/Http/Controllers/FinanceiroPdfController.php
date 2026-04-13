<?php

namespace App\Http\Controllers;

use App\Models\Financeiro;
use Illuminate\Http\Request;

class FinanceiroPdfController extends BasePdfQueueController
{
    public function gerarPdf(Financeiro $financeiro)
    {
        $config = $this->loadConfig();

        return $this->enqueuePdf(
            'pdf.financeiro',
            [
                'financeiro' => $financeiro,
                'config' => $config,
            ],
            'financeiro',
            $financeiro,
            ['categoria', 'cadastro', 'ordemServico', 'orcamento']
        );
    }

    public function gerarRecibo(Financeiro $financeiro)
    {
        $config = $this->loadConfig();

        if ($financeiro->status !== 'pago' || $financeiro->tipo !== 'entrada') {
            \Filament\Notifications\Notification::make()
                ->title('Apenas receitas pagas')
                ->body('O recibo só pode ser gerado para pagamentos confirmados (status: pago).')
                ->warning()
                ->send();
            return back();
        }

        $financeiro->load(['cadastro', 'categoria', 'ordemServico.cliente', 'ordemServico.itens']);

        if (!$financeiro->recibo_selo) {
            $financeiro->update(['recibo_selo' => strtoupper(uniqid('REC-') . '-' . substr(hash('sha256', microtime()), 0, 8))]);
        }

        return $this->enqueuePdf(
            'pdf.recibo',
            [
                'record' => $financeiro,
                'financeiro' => $financeiro,
                'config' => $config,
            ],
            'recibo_pagamento',
            $financeiro,
            []
        );
    }

    public function gerarRelatorioMensal(Request $request)
    {
        $mes = $request->query('mes', now()->month);
        $ano = $request->query('ano', now()->year);

        $inicio = \Carbon\Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
        $fim = \Carbon\Carbon::createFromDate($ano, $mes, 1)->endOfMonth();

        $entradas = Financeiro::whereBetween('data', [$inicio, $fim])
            ->where('tipo', 'entrada')
            ->sum('valor');

        $saidas = Financeiro::whereBetween('data', [$inicio, $fim])
            ->where('tipo', 'saida')
            ->sum('valor');

        $saldo = $entradas - $saidas;

        $porCategoria = Financeiro::whereBetween('data', [$inicio, $fim])
            ->where('tipo', 'saida')
            ->selectRaw('categoria_id, sum(valor) as total')
            ->groupBy('categoria_id')
            ->with('categoria')
            ->orderByDesc('total')
            ->get();

        $transacoes = Financeiro::whereBetween('data', [$inicio, $fim])
            ->with(['categoria', 'cadastro'])
            ->orderBy('data')
            ->get();

        $config = $this->loadConfig();
        
        // Cria modelo fake para enfileirar relatorio
        $fakeModel = new Financeiro();
        $fakeModel->id = "relatorio-{$mes}-{$ano}";

        $htmlContent = view('pdf.financeiro_mensal', [
            'mes' => $mes,
            'ano' => $ano,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $saldo,
            'porCategoria' => $porCategoria,
            'transacoes' => $transacoes,
            'config' => $config
        ])->render();

        try {
            \App\Services\PdfQueueService::enqueue(
                $fakeModel->id,
                'relatorio_financeiro',
                auth()->id(),
                $htmlContent
            );

            \Filament\Notifications\Notification::make()
                ->title('🚀 Relatório em Processamento')
                ->body('Seu relatório financeiro foi enfileirado.')
                ->success()
                ->send();

            return redirect()->route('filament.admin.resources.pdf-geracoes.index');
        } catch (\Exception $e) {
            \Log::error('Erro ao enfileirar relatório financeiro', ['erro' => $e->getMessage()]);
            \Filament\Notifications\Notification::make()
                ->title('❌ Erro ao Processar')
                ->danger()
                ->send();
            return back();
        }
    }
}
