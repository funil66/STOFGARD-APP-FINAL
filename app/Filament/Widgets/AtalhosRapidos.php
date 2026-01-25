<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AtalhosRapidos extends Widget
{
    protected static string $view = 'filament.widgets.atalhos-rapidos';

    public function getViewData(): array
    {
        return [
            'links' => [
                [
                    'label' => 'Cadastros',
                    'url' => \App\Filament\Resources\CadastroResource::getUrl(),
                    'icon' => 'heroicon-o-user-group',
                    'color' => 'bg-blue-500',
                ],
                [
                    'label' => 'Orçamentos',
                    'url' => \App\Filament\Resources\OrcamentoResource::getUrl(),
                    'icon' => 'heroicon-o-banknotes',
                    'color' => 'bg-green-500',
                ],
                [
                    'label' => 'Ordens de Serviço',
                    'url' => \App\Filament\Resources\OrdemServicoResource::getUrl(),
                    'icon' => 'heroicon-o-clipboard-document-list',
                    'color' => 'bg-orange-500',
                ],
            ],
        ];
    }
}
