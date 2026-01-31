<?php

namespace App\Filament\Resources\ProdutoResource\Widgets;

use App\Models\Estoque;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;

class EstoqueVisualWidget extends Widget
{
    protected static string $view = 'filament.widgets.estoque-visual-widget';

    protected int|string|array $columnSpan = 'full';

    public array $produtos = [];

    public function mount(): void
    {
        $this->loadProdutos();
    }

    public function loadProdutos(): void
    {
        // Usar tabela estoques diretamente (item, quantidade, minimo_alerta)
        $this->produtos = Estoque::all()->map(function ($item) {
            $estoque = (float) $item->quantidade;
            $galoes = floor($estoque / 20);
            $resto = $estoque % 20;
            $minimo = (float) ($item->minimo_alerta ?? 20);

            // Determinar cor baseado no nível
            $cor = match (true) {
                $estoque <= $minimo => 'danger',   // Crítico
                $estoque <= $minimo * 3 => 'warning',  // Atenção
                default => 'success',               // OK
            };

            // Verificar escassez e notificar
            if ($estoque <= $minimo && $estoque > 0) {
                $this->notificarEscassez($item->item, $estoque);
            }

            return [
                'id' => $item->id,
                'nome' => $item->item,
                'estoque' => $estoque,
                'galoes' => $galoes,
                'resto' => $resto,
                'cor' => $cor,
                'unidade' => $item->unidade ?? 'litros',
            ];
        })->toArray();
    }

    protected function notificarEscassez(string $nomeProduto, float $volume): void
    {
        // Evita múltiplas notificações (cache por 1 hora)
        $cacheKey = "estoque_alerta_" . str_replace(' ', '_', $nomeProduto);
        if (!cache()->has($cacheKey)) {
            Notification::make()
                ->title('⚠️ ESTOQUE BAIXO!')
                ->body("Apenas {$volume}L de {$nomeProduto} restantes!")
                ->danger()
                ->persistent()
                ->send();

            cache()->put($cacheKey, true, now()->addHours(1));
        }
    }

    public function getViewData(): array
    {
        return [
            'produtos' => $this->produtos,
        ];
    }
}
