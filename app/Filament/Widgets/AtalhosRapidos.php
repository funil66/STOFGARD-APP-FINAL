<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AtalhosRapidos extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Gestão de Clientes', 'Acessar')
                ->description('Lista filtrada de Clientes')
                ->descriptionIcon('heroicon-m-user')
                ->color('info')
                ->url('/admin/cadastros?activeTab=clientes'),

            Stat::make('Parceiros e Lojas', 'Acessar')
                ->description('Comissões e Indicações')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success')
                ->url('/admin/cadastros?activeTab=parceiros'),

            Stat::make('Novo Orçamento', 'Criar')
                ->description('Gerar proposta para cliente')
                ->descriptionIcon('heroicon-m-document-plus')
                ->color('warning')
                ->url('/admin/orcamentos/create'),
        ];
    }
}
