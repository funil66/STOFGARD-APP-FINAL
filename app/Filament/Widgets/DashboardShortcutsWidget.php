<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Configuracao;
use App\Filament\Pages\BuscaAvancada;
use App\Filament\Resources\CadastroResource;
use App\Filament\Resources\OrdemServicoResource;
use App\Filament\Resources\OrcamentoResource;
use App\Filament\Resources\AgendaResource;
use App\Filament\Resources\FinanceiroResource;
use App\Filament\Pages\Almoxarifado;
use App\Filament\Pages\Configuracoes;

class DashboardShortcutsWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-shortcuts-widget';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            // Widget de Clima
            'weatherCity' => settings('dashboard_weather_city', 'São Paulo'),
            'mostrarClima' => settings('dashboard_mostrar_clima', true),
            
            // Textos personalizáveis do banner
            'saudacaoTexto' => settings('dashboard_saudacao', 'Tenha um dia de trabalho produtivo.'),
            'fraseMotivacional' => settings('dashboard_frase', 'BORA TRABALHAR!'),
            
            // Cores do gradiente do banner
            'bannerColorStart' => settings('dashboard_banner_color_start', '#1e3a8a'),
            'bannerColorEnd' => settings('dashboard_banner_color_end', '#3b82f6'),
            
            // Grid customizável
            'gridColunasDesktop' => settings('dashboard_grid_colunas', '4'),
            'gridColunasMobile' => settings('dashboard_grid_colunas_mobile', '2'),
            'gridGap' => settings('dashboard_grid_gap', '2rem'),
            
            // Atalhos
            'shortcuts' => $this->getShortcuts(),
        ]);
    }

    public function getShortcuts(): array
    {
        // Uso de ::getUrl() garante que se você mudar a rota admin, o botão não quebra.
        return [
            ['label' => 'Busca Avançada', 'icon' => 'heroicon-o-magnifying-glass', 'url' => BuscaAvancada::getUrl(), 'color' => '#0891B2'],
            ['label' => 'Cadastro', 'icon' => 'heroicon-o-user-group', 'url' => CadastroResource::getUrl('index'), 'color' => '#2563EB'],
            ['label' => 'Ordens de Serviço', 'icon' => 'heroicon-o-wrench-screwdriver', 'url' => OrdemServicoResource::getUrl('index'), 'color' => '#DC2626'],
            ['label' => 'Orçamentos', 'icon' => 'heroicon-o-document-plus', 'url' => OrcamentoResource::getUrl('index'), 'color' => '#16A34A'],
            ['label' => 'Agenda', 'icon' => 'heroicon-o-calendar', 'url' => AgendaResource::getUrl('index'), 'color' => '#F59E0B'],
            ['label' => 'Financeiro', 'icon' => 'heroicon-o-banknotes', 'url' => FinanceiroResource::getUrl('index'), 'color' => '#059669'],
            ['label' => 'Almoxarifado', 'icon' => 'heroicon-o-archive-box', 'url' => Almoxarifado::getUrl(), 'color' => '#EA580C'],
            ['label' => 'Configurações', 'icon' => 'heroicon-o-cog-6-tooth', 'url' => Configuracoes::getUrl(), 'color' => '#475569'],
        ];
    }
}

