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
            // A. Busca dinâmica da Categoria (Fim do Magic Number "1")
            // Se não encontrar "venda-servico", busca a primeira de receita ou cria uma fallback
            $categoriaVenda = Categoria::where('slug', 'venda-servico')->first();
            
            if (! $categoriaVenda) {
                // Tenta achar pelo ID 1 apenas como último recurso de compatibilidade, 
                // mas o ideal é ter o slug correto no banco.
                $categoriaVenda = Categoria::find(1) ?? Categoria::firstOrCreate(
                    ['slug' => 'venda-padrao'],
                    ['nome' => 'Venda de Serviço', 'tipo' => 'receita', 'ativa' => true]
                );
            }

            // B. Criação da OS (Sem calcular ID manualmente para evitar colisão)
            $os = OrdemServico::create([
                'orcamento_id'  => $orcamento->id,
                'cliente_id'    => $orcamento->cliente_id,
                'status'        => 'aberta', // Ideal: Usar Enum OrdemServicoStatus::ABERTA
                'data_abertura' => now(),
                'descricao'     => "Gerado a partir do Orçamento #{$orcamento->id}",
                'valor_total'   => $orcamento->valor_total,
                // Adicione aqui outros campos obrigatórios da sua tabela OS
            ]);

            // C. Criação do Financeiro (Receita Prevista)
            Financeiro::create([
                'descricao'       => "Receita ref. OS #{$os->id} - {$orcamento->cliente->nome}",
                'categoria_id'    => $categoriaVenda->id, 
                'ordem_servico_id'=> $os->id,
                'cliente_id'      => $orcamento->cliente_id,
                'valor'           => $orcamento->valor_total,
                'data_vencimento' => now()->addDays(30), // Pode virar configuração no banco: 'dias_padrao_vencimento'
                'status'          => 'pendente',
                'tipo'            => 'receita',
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
        if ($lancamento->status === 'pago') return;

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
