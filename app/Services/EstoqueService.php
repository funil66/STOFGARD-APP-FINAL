<?php

namespace App\Services;

use App\Models\Estoque;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço responsável pela gestão de estoque
 * Centraliza todas as operações de movimentação de estoque
 */
class EstoqueService
{
    /**
     * Verifica e baixa o estoque baseado nos itens do orçamento.
     */
    public function baixarItensDeOrcamento(Orcamento $orcamento): void
    {
        // Implementação futura: Iterar sobre $orcamento->itens e reduzir do Estoque
        // Por enquanto, apenas loga a intenção.
        Log::info("Estoque: Baixa solicitada para Orçamento #{$orcamento->id}");
        
        // Exemplo futuro:
        // foreach ($orcamento->itens as $item) {
        //     $item->produto->decrement('estoque_atual', $item->quantidade);
        // }
    }

    /**
     * Registra saída de produtos ao finalizar uma OS
     * 
     * @param OrdemServico $os
     * @return void
     */
    public function baixarEstoquePorOS(OrdemServico $os): void
    {
        DB::transaction(function () use ($os) {
            foreach ($os->itens as $item) {
                if ($item->produto_id) {
                    $this->registrarSaida(
                        produtoId: $item->produto_id,
                        quantidade: $item->quantidade,
                        motivo: "OS #{$os->numero_os} Concluída",
                        ordemServicoId: $os->id
                    );
                }
            }
        });
    }

    /**
     * Registra uma entrada de estoque
     * 
     * @param int $produtoId
     * @param float $quantidade
     * @param string $motivo
     * @param int|null $ordemServicoId
     * @return Estoque
     */
    public function registrarEntrada(
        int $produtoId,
        float $quantidade,
        string $motivo,
        ?int $ordemServicoId = null
    ): Estoque {
        return $this->registrarMovimento(
            tipo: 'entrada',
            produtoId: $produtoId,
            quantidade: $quantidade,
            motivo: $motivo,
            ordemServicoId: $ordemServicoId
        );
    }

    /**
     * Registra uma saída de estoque
     * 
     * @param int $produtoId
     * @param float $quantidade
     * @param string $motivo
     * @param int|null $ordemServicoId
     * @return Estoque
     */
    public function registrarSaida(
        int $produtoId,
        float $quantidade,
        string $motivo,
        ?int $ordemServicoId = null
    ): Estoque {
        return $this->registrarMovimento(
            tipo: 'saida',
            produtoId: $produtoId,
            quantidade: $quantidade,
            motivo: $motivo,
            ordemServicoId: $ordemServicoId
        );
    }

    /**
     * Registra um movimento de estoque (entrada ou saída)
     * 
     * @param string $tipo
     * @param int $produtoId
     * @param float $quantidade
     * @param string $motivo
     * @param int|null $ordemServicoId
     * @return Estoque
     */
    private function registrarMovimento(
        string $tipo,
        int $produtoId,
        float $quantidade,
        string $motivo,
        ?int $ordemServicoId = null
    ): Estoque {
        return Estoque::create([
            'produto_id' => $produtoId,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
            'motivo' => $motivo,
            'ordem_servico_id' => $ordemServicoId,
            'data_movimento' => now(),
        ]);
    }

    /**
     * Verifica se há estoque disponível para um produto
     * 
     * @param int $produtoId
     * @param float $quantidadeRequerida
     * @return bool
     */
    public function temEstoqueDisponivel(int $produtoId, float $quantidadeRequerida): bool
    {
        $saldoAtual = $this->calcularSaldoAtual($produtoId);
        return $saldoAtual >= $quantidadeRequerida;
    }

    /**
     * Calcula o saldo atual de um produto
     * 
     * @param int $produtoId
     * @return float
     */
    public function calcularSaldoAtual(int $produtoId): float
    {
        $entradas = Estoque::where('produto_id', $produtoId)
            ->where('tipo', 'entrada')
            ->sum('quantidade');

        $saidas = Estoque::where('produto_id', $produtoId)
            ->where('tipo', 'saida')
            ->sum('quantidade');

        return $entradas - $saidas;
    }
}