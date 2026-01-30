<?php

namespace App\Services;

use App\Models\Orcamento;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;
use Exception;

class OrdemServicoService
{
    protected FinanceiroService $financeiroService;
    protected EstoqueService $estoqueService;

    // Injeção de Dependência automática do Laravel
    public function __construct(
        FinanceiroService $financeiroService, 
        EstoqueService $estoqueService
    ) {
        $this->financeiroService = $financeiroService;
        $this->estoqueService = $estoqueService;
    }

    /**
     * Transforma um Orçamento Aprovado em uma Ordem de Serviço completa.
     * Transaction Atômica: Ou cria tudo, ou não cria nada.
     */
    public function aprovarOrcamento(Orcamento $orcamento): OrdemServico
    {
        if ($orcamento->status === 'aprovado') {
            throw new Exception('Este orçamento já foi aprovado anteriormente.');
        }

        return DB::transaction(function () use ($orcamento) {
            // 1. Cria a OS
            $os = OrdemServico::create([
                'orcamento_id'  => $orcamento->id,
                'cliente_id'    => $orcamento->cliente_id,
                'status'        => 'aberta',
                'data_abertura' => now(),
                'descricao'     => "Gerado a partir do Orçamento #{$orcamento->id}",
                'valor_total'   => $orcamento->valor_total,
            ]);

            // 2. Aciona o Financeiro (Gera Contas a Receber)
            $this->financeiroService->gerarPreviaReceita($os);

            // 3. Aciona o Estoque (Baixa produtos se houver)
            $this->estoqueService->baixarItensDeOrcamento($orcamento);

            // 4. Atualiza o Orçamento original
            $orcamento->update([
                'status' => 'aprovado',
                'aprovado_em' => now(),
            ]);

            return $os;
        });
    }
}