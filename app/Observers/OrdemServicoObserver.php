<?php

namespace App\Observers;

use App\Models\Agenda;
use App\Models\Estoque;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdemServicoObserver
{
    /**
     * Handle the OrdemServico "created" event.
     */
    public function created(OrdemServico $os): void
    {
        // Verifica se já existe uma agenda para esta OS
        // (evita duplicação quando criada via aprovação de orçamento)
        $agendaExistente = Agenda::where('ordem_servico_id', $os->id)->exists();

        if ($agendaExistente) {
            return; // Já foi criada manualmente, não duplicar
        }

        // Cria agendamento automático ao criar a OS
        Agenda::create([
            'titulo' => 'Serviço - ' . ($os->cliente->nome ?? 'Cliente'),
            'descricao' => "Ordem de Serviço {$os->numero_os}",
            'cliente_id' => $os->cliente_id ?? null,
            'cadastro_id' => $os->cadastro_id,
            'ordem_servico_id' => $os->id,
            'tipo' => 'servico',
            'tipo_servico' => 'servico',
            'data_hora_inicio' => $os->data_prevista ?? now(),
            'data_hora_fim' => $os->data_prevista ? \Carbon\Carbon::parse($os->data_prevista)->addHours(2) : now()->addHours(2),
            'status' => 'agendado',
            'local' => $os->cliente->endereco ?? 'Endereço não informado',
            'endereco_completo' => $os->cliente->endereco_completo ?? null,
            'criado_por' => Auth::id() ?? 1,
        ]);
    }

    /**
     * Handle the OrdemServico "updated" event.
     * Cria garantia automaticamente ao concluir a OS.
     */
    public function updated(OrdemServico $os): void
    {
        // Só cria garantia na primeira conclusão
        if ($os->status === 'concluida' && $os->wasChanged('status')) {
            $this->criarGarantiaAutomatica($os);
        }
    }

    /**
     * Cria garantia automaticamente baseada nas configurações do sistema.
     */
    protected function criarGarantiaAutomatica(OrdemServico $os): void
    {
        // Verifica se já existe garantia para esta OS
        if ($os->garantias()->exists()) {
            Log::info("Garantia já existe para OS {$os->numero_os}, pulando criação");
            return;
        }

        try {
            // Determina tipo(s) de serviço da OS
            $tipoServico = $os->tipo_servico ?? 'servico';

            // Obtém dias de garantia via ServiceTypeManager (Centralizado)
            $dias = \App\Services\ServiceTypeManager::getDiasGarantia($tipoServico);

            // Obtém descrição (label do serviço)
            $labelServico = \App\Services\ServiceTypeManager::getLabel($tipoServico);
            $descricao = "Garantia padrão para {$labelServico}";

            // Cria a garantia
            \App\Models\Garantia::create([
                'ordem_servico_id' => $os->id,
                'tipo_servico' => $tipoServico,
                'data_inicio' => now(),
                // Garante que é inteiro
                'dias_garantia' => (int) $dias,
                // Data fim calculada com base nos dias
                'data_fim' => now()->addDays((int) $dias),
                'status' => 'ativa',
                'observacoes' => $descricao,
            ]);

            Log::info("Garantia de {$dias} dias criada automaticamente para OS {$os->numero_os} ({$tipoServico})");

        } catch (\Exception $e) {
            Log::error("Erro ao criar garantia automática para OS {$os->numero_os}: " . $e->getMessage());
        }
    }

    /**
     * Handle the OrdemServico "saved" event (after create or update).
     * Gerencia baixas e estornos de produtos do estoque.
     */
    public function saved(OrdemServico $ordemServico): void
    {
        // Verifica se há dados de produtos no request
        if (!request()->has('produtosUtilizados') && !request()->has('data.produtosUtilizados')) {
            return;
        }

        try {
            DB::transaction(function () use ($ordemServico) {
                // Recarrega produtos atuais do banco
                $produtosAtuais = $ordemServico->produtosUtilizados()
                    ->withPivot('quantidade_utilizada')
                    ->get()
                    ->keyBy('id');

                // Obtém produtos do request (suporta ambos formatos: direto ou dentro de 'data')
                $produtosRequest = request('produtosUtilizados', request('data.produtosUtilizados', []));
                $produtosNovos = collect($produtosRequest)->keyBy('estoque_id');

                // ESTORNAR produtos removidos
                foreach ($produtosAtuais as $produtoAtual) {
                    if (!$produtosNovos->has($produtoAtual->id)) {
                        Log::info("Estornando produto {$produtoAtual->item}: {$produtoAtual->pivot->quantidade_utilizada}");
                        $produtoAtual->estornar($produtoAtual->pivot->quantidade_utilizada);
                    }
                }

                // PROCESSAR produtos novos ou alterados
                foreach ($produtosNovos as $estoqueId => $dados) {
                    $estoque = Estoque::findOrFail($estoqueId);
                    $quantidadeNova = (float) $dados['quantidade_utilizada'];

                    if ($produtosAtuais->has($estoqueId)) {
                        // Produto já existia - ajustar diferença
                        $quantidadeAntiga = $produtosAtuais[$estoqueId]->pivot->quantidade_utilizada;
                        $diferenca = $quantidadeNova - $quantidadeAntiga;

                        if ($diferenca > 0) {
                            Log::info("Aumentando baixa do produto {$estoque->item}: diferença de {$diferenca}");
                            $estoque->darBaixa($diferenca);
                        } elseif ($diferenca < 0) {
                            Log::info("Estornando parcialmente produto {$estoque->item}: {" . abs($diferenca) . '}');
                            $estoque->estornar(abs($diferenca));
                        }
                    } else {
                        // Produto novo - dar baixa total
                        Log::info("Dando baixa em novo produto {$estoque->item}: {$quantidadeNova}");
                        $estoque->darBaixa($quantidadeNova);
                    }
                }
            });
        } catch (\Exception $e) {
            // Log do erro mas não interrompe o salvamento da OS
            Log::error("Erro ao processar estoque da OS {$ordemServico->numero_os}: " . $e->getMessage());

            // Re-lança a exception para que o usuário saiba do problema
            throw $e;
        }
    }

    /**
     * Handle the OrdemServico "deleting" event.
     * Estorna todos os produtos antes de deletar a OS.
     */
    public function deleting(OrdemServico $ordemServico): void
    {
        try {
            DB::transaction(function () use ($ordemServico) {
                // Carrega produtos antes de deletar
                $produtos = $ordemServico->produtosUtilizados()
                    ->withPivot('quantidade_utilizada')
                    ->get();

                foreach ($produtos as $produto) {
                    Log::info("Estornando produto {$produto->item} da OS deletada: {$produto->pivot->quantidade_utilizada}");
                    $produto->estornar($produto->pivot->quantidade_utilizada);
                }
            });
        } catch (\Exception $e) {
            Log::error("Erro ao estornar produtos da OS {$ordemServico->numero_os} ao deletar: " . $e->getMessage());
            throw $e;
        }
    }
}
