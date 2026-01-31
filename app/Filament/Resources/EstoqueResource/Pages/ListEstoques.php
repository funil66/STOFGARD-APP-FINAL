<?php

namespace App\Filament\Resources\EstoqueResource\Pages;

use App\Filament\Resources\EstoqueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEstoques extends ListRecords
{
    protected static string $resource = EstoqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('check_stock')
                ->label('Verificar Níveis')
                ->icon('heroicon-o-scale')
                ->color('info')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('estoque:alert-low');

                    \Filament\Notifications\Notification::make()
                        ->title('Verificação Concluída')
                        ->body('Alertas de estoque baixo foram enviados se necessário.')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make()
                ->label('Novo Item')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\ProdutoResource\Widgets\EstoqueVisualWidget::class,
            // ConsumoEstoqueChart temporariamente removido - precisa de registro Livewire
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
