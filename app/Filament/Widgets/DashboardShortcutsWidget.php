<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Configuracao;
use App\Filament\Pages\BuscaAvancada;
use App\Filament\Resources\CadastroResource;
use App\Filament\Resources\OrdemServicoResource;
use App\Filament\Resources\OrcamentoResource;
use App\Filament\Pages\Agenda;
use App\Filament\Resources\FinanceiroResource;
use App\Filament\Resources\ProdutoResource;
use App\Filament\Pages\Configuracoes;

class DashboardShortcutsWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-shortcuts-widget';
    protected int | string | array $columnSpan = 'full'; 
    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        return [
            // Agora busca do banco. Se quiser mudar a cidade, muda no banco, não no código.
            'weatherUrl' => Configuracao::where('chave', 'url_clima')->value('valor') 
                            ?? 'https://wttr.in/Ribeirao+Preto?0QT&lang=pt',
            'shortcuts' => $this->getShortcuts(),
        ];
    }

    public function getShortcuts(): array
    {
        // Uso de ::getUrl() garante que se você mudar a rota admin, o botão não quebra.
        return [
            ['label' => 'Busca Avançada', 'icon' => 'heroicon-o-magnifying-glass', 'url' => BuscaAvancada::getUrl(), 'color' => '#0891B2'],
            ['label' => 'Cadastro', 'icon' => 'heroicon-o-user-group', 'url' => CadastroResource::getUrl('index'), 'color' => '#2563EB'],
            ['label' => 'Ordens de Serviço', 'icon' => 'heroicon-o-wrench-screwdriver', 'url' => OrdemServicoResource::getUrl('index'), 'color' => '#DC2626'],
            ['label' => 'Orçamentos', 'icon' => 'heroicon-o-document-plus', 'url' => OrcamentoResource::getUrl('index'), 'color' => '#16A34A'],
            ['label' => 'Agenda', 'icon' => 'heroicon-o-calendar', 'url' => Agenda::getUrl(), 'color' => '#F59E0B'],
            ['label' => 'Financeiro', 'icon' => 'heroicon-o-banknotes', 'url' => FinanceiroResource::getUrl('index'), 'color' => '#059669'],
            ['label' => 'Almoxarifado', 'icon' => 'heroicon-o-cube', 'url' => ProdutoResource::getUrl('index'), 'color' => '#EA580C'],
            ['label' => 'Configurações', 'icon' => 'heroicon-o-cog-6-tooth', 'url' => Configuracoes::getUrl(), 'color' => '#475569'],
        ];
    }
}

