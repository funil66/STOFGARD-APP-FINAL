<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Resources\CadastroResource;
use App\Filament\Resources\OrdemServicoResource;
use App\Filament\Resources\AgendaResource;
use App\Filament\Resources\OrcamentoResource;
use App\Filament\Resources\FinanceiroResource;
use App\Filament\Resources\EstoqueResource;
use App\Filament\Resources\ConfiguracaoResource;
use App\Filament\Pages\BuscaAvancada;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Painel de Controle Stofgard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\AtalhosRapidos::class,
        ];
    }
}
