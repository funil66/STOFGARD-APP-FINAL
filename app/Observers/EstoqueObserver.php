<?php

namespace App\Observers;

use App\Models\Estoque;
use App\Models\Produto;

class EstoqueObserver
{
    /**
     * Handle the Estoque "created" event.
     */
    public function created(Estoque $estoque): void
    {
        // Se foi criado um estoque que tem um Produto correspondente (pelo nome),
        // atualizamos o produto.
        // CUIDADO: Se o produto criou o estoque, isso pode gerar loop infinito.
        // Mas o produto cria o estoque e define valores iguais, então isDirty deve prevenir loop.
        $this->syncToProduto($estoque);
    }

    /**
     * Handle the Estoque "updated" event.
     */
    public function updated(Estoque $estoque): void
    {
        $this->syncToProduto($estoque);
    }

    protected function syncToProduto(Estoque $estoque): void
    {
        // Tenta achar produto pelo nome
        // Como não temos FK, usamos o nome
        $produto = Produto::where('nome', $estoque->item)->first();

        if ($produto) {
            // Sincroniza APENAS a quantidade (Estoque é a fonte da verdade da quantidade)
            // Não sincronizamos preço, pois Produto é a fonte da verdade do preço.

            // Mas se o usuário editou 'quantidade' no estoque, devemos refletir no produto
            // para que a listagem de produtos mostre o estoque real.

            if ($produto->estoque_atual != $estoque->quantidade) {
                // Use saveQuietly se não quisermos disparar o ProdutoObserver de volta
                // Mas o ProdutoObserver só atualiza estoque se 'nome' ou 'estoque_atual' mudarem.
                // Se atualizarmos aqui, o ProdutoObserver vai rodar.
                // ProdutoObserver: if isDirty('estoque_atual') -> update Estoque.
                // Isso geraria loop infinito: Estoque -> Produto -> Estoque.

                // Solução: saveQuietly() no Produto, OU checar quem disparou.
                // Vamos usar updateQuietly (Laravel 10+) ou saveQuietly.

                $produto->estoque_atual = $estoque->quantidade;
                $produto->saveQuietly();
            }
        }
    }
}
