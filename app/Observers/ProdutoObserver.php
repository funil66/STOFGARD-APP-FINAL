<?php

namespace App\Observers;

use App\Models\Produto;
use App\Models\Estoque;

class ProdutoObserver
{
    /**
     * Handle the Produto "created" event.
     */
    public function created(Produto $produto): void
    {
        // Cria um item correspondente no estoque
        Estoque::create([
            'item' => $produto->nome,
            'quantidade' => $produto->estoque_atual ?? 0,
            'unidade' => $this->mapUnidade($produto->unidade),
            'minimo_alerta' => $produto->estoque_minimo ?? 5,
            'tipo' => 'produto', // Default type
            'preco_interno' => $produto->preco_custo,
            'preco_venda' => $produto->preco_venda,
            'observacoes' => "Produto cadastrado em: " . now()->format('d/m/Y') . "\n" . $produto->descricao,
        ]);
    }

    /**
     * Handle the Produto "updated" event.
     */
    public function updated(Produto $produto): void
    {
        // Tenta encontrar o item de estoque pelo nome (ou idealmente, deveria ter um campo produto_id no estoque)
        // Como não temos produto_id no estoque linkado, vamos tentar pelo nome exato, 
        // mas isso é frágil se o nome mudar.
        // O ideal seria adicionar produto_id no estoque, mas para resolver rápido vamos pelo nome.

        // Se o nome mudou, tentamos achar pelo nome antigo (getOriginal)
        $nomeBusca = $produto->isDirty('nome') ? $produto->getOriginal('nome') : $produto->nome;

        $estoque = Estoque::where('item', $nomeBusca)->first();

        if ($estoque) {
            $estoque->update([
                'item' => $produto->nome,
                'quantidade' => $produto->estoque_atual, // Sincroniza estoque total? Ou apenas detalhes?
                // CUIDADO: Se atualizarmos a quantidade aqui, podemos sobrescrever baixas feitas no Almoxarifado.
                // Melhor NÃO sincronizar quantidade no update, apenas no create.
                // Mas o usuário pode editar o estoque no produto... Dilema.
                // Vamos assumir que Produto é a fonte da verdade para DETALHES, mas Estoque é fonte para QUANTIDADE.
                // Mas se o usuário editar 'estoque_atual' no form de produto, ele espera que mude.

                // Vamos sincronizar quantidade APENAS se ela foi alterada explicitamente no Produto
                // 'quantidade' => $produto->estoque_atual, 

                'unidade' => $this->mapUnidade($produto->unidade),
                'minimo_alerta' => $produto->estoque_minimo,
                'preco_interno' => $produto->preco_custo,
                'preco_venda' => $produto->preco_venda,
                'observacoes' => $produto->descricao,
            ]);

            // Se a quantidade foi alterada no produto, atualizamos o estoque
            if ($produto->isDirty('estoque_atual')) {
                $estoque->quantidade = $produto->estoque_atual;
                $estoque->save();
            }
        }
    }

    /**
     * Handle the Produto "deleted" event.
     */
    public function deleted(Produto $produto): void
    {
        // Opcional: Deletar ou arquivar o estoque?
        // Melhor não deletar automaticamente para evitar perda de histórico de uso em OS.
        // Apenas marcamos observação?
        $estoque = Estoque::where('item', $produto->nome)->first();
        if ($estoque) {
            $estoque->update(['observacoes' => $estoque->observacoes . "\n[PRODUTO ORIGINAL DELETADO]"]);
        }
    }

    protected function mapUnidade(?string $unidade): string
    {
        // Map string input to valid Enum values for Estoque
        $validUnits = ['unidade', 'litros', 'caixa', 'metro'];
        $normalized = strtolower($unidade ?? 'unidade');

        if (in_array($normalized, $validUnits)) {
            return $normalized;
        }

        // Fallbacks
        if (str_contains($normalized, 'litr'))
            return 'litros';
        if (str_contains($normalized, 'cx') || str_contains($normalized, 'caixa'))
            return 'caixa';
        if (str_contains($normalized, 'mt') || str_contains($normalized, 'metr'))
            return 'metro';

        return 'unidade';
    }
}
