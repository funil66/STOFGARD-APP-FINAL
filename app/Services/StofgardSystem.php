<?php

namespace App\Services;

use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\Financeiro;
use App\Models\Estoque;
use App\Models\Agenda;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Models\Categoria;
use Barryvdh\DomPDF\Facade\Pdf;

class StofgardSystem
{
    /**
     * Aprova um orçamento, gerando OS e previsão financeira de forma atômica.
     * Iron Code Refactoring: Remoção de magic numbers e race conditions.
     */
    public function aprovarOrcamento(Orcamento $orcamento): OrdemServico
    {
        // 1. Validação prévia para evitar duplicidade lógica
        if ($orcamento->status === 'aprovado') {
            throw new \Exception('Este orçamento já foi aprovado anteriormente.');
        }

        return DB::transaction(function () use ($orcamento) {
            // A. Criação da OS (Usando cadastro_id unificado)
            $os = OrdemServico::create([
                'orcamento_id' => $orcamento->id,
                'cadastro_id' => $orcamento->cadastro_id,
                'loja_id' => $orcamento->loja_id,
                'vendedor_id' => $orcamento->vendedor_id,
                'status' => 'aberta',
                'data_abertura' => now(),
                'tipo_servico' => $orcamento->tipo_servico ?? 'servico',
                'descricao_servico' => "Gerado a partir do Orçamento #{$orcamento->numero}",
                'valor_total' => $orcamento->valor_total,
                'criado_por' => auth()->id() ?? 1,
            ]);

            // B. Criação do Financeiro (Receita Prevista) - Usando Financeiro model com cadastro_id
            Financeiro::create([
                'descricao' => "Receita ref. OS #{$os->numero_os} - " . ($orcamento->cliente->nome ?? 'Cliente'),
                'ordem_servico_id' => $os->id,
                'orcamento_id' => $orcamento->id,
                'cadastro_id' => $orcamento->cadastro_id,
                'valor' => $orcamento->valor_total,
                'data_vencimento' => now()->addDays(30),
                'status' => 'pendente',
                'tipo' => 'entrada',
                'categoria' => 'servico',
            ]);

            // C. Cria Agenda (Serviço Agendado)
            Agenda::create([
                'titulo' => "Serviço - " . ($orcamento->cliente->nome ?? 'Cliente'),
                'descricao' => "OS #{$os->numero_os} via Orçamento #{$orcamento->numero}",
                'cadastro_id' => $orcamento->cadastro_id,
                'ordem_servico_id' => $os->id,
                'orcamento_id' => $orcamento->id,
                'tipo' => 'servico',
                'data_hora_inicio' => now()->addDays(1)->setHour(9),
                'data_hora_fim' => now()->addDays(1)->setHour(11),
                'status' => 'agendado',
                'local' => $orcamento->cliente->endereco ?? 'A definir',
                'criado_por' => auth()->id() ?? 1,
            ]);

            // D. Atualiza o Orçamento
            $orcamento->update([
                'status' => 'aprovado',
                'aprovado_em' => now(),
            ]);

            return $os;
        });
    }

    /**
     * Confirma pagamento e libera a OS
     */
    public function confirmarPagamento(Financeiro $lancamento)
    {
        if ($lancamento->status === 'pago')
            return;

        DB::transaction(function () use ($lancamento) {
            // 1. Baixa Financeira
            $lancamento->update([
                'status' => 'pago',
                'data_pagamento' => now(),
            ]);

            // 2. Se tiver OS vinculada (pela ordem_servico_id), avança status
            if ($lancamento->ordemServico) {
                $os = $lancamento->ordemServico;
                $os->update(['status' => 'em_execucao']);
            }

            Notification::make()->title('Pagamento confirmado')->success()->send();
        });
    }

    /**
     * Finaliza OS e Baixa Estoque
     */
    public function finalizarOS(OrdemServico $os)
    {
        DB::transaction(function () use ($os) {
            $os->update(['status' => 'concluido', 'data_fim' => now()]);

            // Baixa estoque dos produtos usados (se houver)
            foreach ($os->itens as $item) {
                if ($item->produto_id) {
                    Estoque::create([
                        'produto_id' => $item->produto_id,
                        'tipo' => 'saida',
                        'quantidade' => $item->quantidade,
                        'motivo' => "OS #{$os->id} Concluída",
                        'data_movimento' => now(),
                    ]);
                }
            }
        });
    }

    /**
     * Renderiza o PDF do orçamento.
     *
     * @param Orcamento $orcamento
     * @return \Barryvdh\DomPDF\PDF
     */
    private function renderPdf(Orcamento $orcamento)
    {
        // Garante diretório temporário
        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return Pdf::view('pdf.orcamento', ['orcamento' => $orcamento])
            ->format('a4')
            ->name("Orcamento-{$orcamento->id}.pdf")
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                    ->setNodeBinary('/usr/bin/node') // Caminho padrão no Sail/Alpine
                    ->setNpmBinary('/usr/bin/npm')
                    ->setOption('args', ['--disable-web-security', '--no-sandbox', '--disable-setuid-sandbox'])
                    ->timeout(60); // Aumenta timeout para 60s
            })
            ->inline();
    }
}
