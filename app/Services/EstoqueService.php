<?php

namespace App\Services;

use App\Models\Estoque;
use Filament\Notifications\Notification;

class EstoqueService
{
    /**
     * Adiciona quantidade ao estoque de um item.
     *
     * @param Estoque $item
     * @param float $quantidade
     * @return void
     */
    public static function adicionarEstoque(Estoque $item, float $quantidade): void
    {
        if ($quantidade <= 0) {
            return;
        }

        $item->increment('quantidade', $quantidade);

        Notification::make()
            ->title('Estoque Atualizado')
            ->body("+{$quantidade} {$item->unidade}")
            ->success()
            ->send();
    }

    /**
     * Consome (reduz) quantidade do estoque de um item.
     *
     * @param Estoque $item
     * @param float $quantidade
     * @return void
     */
    public static function consumirEstoque(Estoque $item, float $quantidade): void
    {
        if ($quantidade <= 0) {
            return;
        }

        if ($item->quantidade >= $quantidade) {
            $item->decrement('quantidade', $quantidade);

            Notification::make()
                ->title('Saída Registrada')
                ->body("-{$quantidade} {$item->unidade}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Estoque Insuficiente')
                ->body("Solicitado: {$quantidade} | Disponível: {$item->quantidade}")
                ->danger()
                ->send();

            // Opcional: Lançar exceção se quiser tratar o erro fora
            // throw new \Exception("Estoque insuficiente.");
        }
    }
    /**
     * Baixa o estoque dos produtos utilizados em uma Ordem de Serviço.
     *
     * @param \App\Models\OrdemServico $os
     * @return void
     */
    public static function baixarEstoquePorOS(\App\Models\OrdemServico $os): void
    {
        foreach ($os->produtosUtilizados as $produto) {
            $quantidade = $produto->pivot->quantidade_utilizada;

            if ($quantidade > 0) {
                // Usa o método existente para manter a lógica de notificação/validação
                self::consumirEstoque($produto, $quantidade);
            }
        }
    }
}
