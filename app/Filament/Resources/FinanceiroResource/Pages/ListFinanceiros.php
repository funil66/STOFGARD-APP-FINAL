<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Pages\Relatorios;
use App\Filament\Resources\FinanceiroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\FinanceiroResource\Pages\FinanceiroDashboard;
use App\Filament\Resources\FinanceiroResource\Pages\FinanceiroAnalise;
use Filament\Support\Enums\ActionSize;

class ListFinanceiros extends ListRecords
{
    protected static string $resource = FinanceiroResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return parent::canAccess($parameters) && FinanceiroResource::hasTableAvailable();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Transação')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('notas_fiscais')
                ->label('Notas Fiscais')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('info')
                ->url(url('/admin/notas-fiscais')),

            // Gráficos (Overlay Interativo)
            Actions\Action::make('graficos')
                ->label('📊 Gráficos')
                ->color('primary')
                ->size(ActionSize::Large)
                ->modalHeading('Análise Financeira')
                ->modalContent(view('filament.pages.partials.financeiro-charts-overlay-livewire'))
                ->modalSubmitAction(false)
                ->modalCancelAction(fn($action) => $action->label('Fechar')),

            // Grupo de Análises
            Actions\ActionGroup::make([
                Actions\Action::make('analise_vendedores')
                    ->label('👔 Por Vendedor')
                    ->icon('heroicon-o-user-group')
                    ->url(FinanceiroResource::getUrl('analise-vendedores')),

                Actions\Action::make('analise_lojas')
                    ->label('🏪 Por Loja')
                    ->icon('heroicon-o-building-storefront')
                    ->url(FinanceiroResource::getUrl('analise-lojas')),

                Actions\Action::make('analise_categorias')
                    ->label('🏷️ Por Categoria')
                    ->icon('heroicon-o-tag')
                    ->url(FinanceiroResource::getUrl('analise-categorias')),

                Actions\Action::make('comissoes')
                    ->label('💼 Comissões')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(FinanceiroResource::getUrl('comissoes')),

                Actions\Action::make('extratos')
                    ->label('📄 Extratos')
                    ->icon('heroicon-o-document-text')
                    ->url(FinanceiroResource::getUrl('extratos')),
            ])
                ->label('📈 Análises')
                ->icon('heroicon-m-chart-bar')
                ->color('warning')
                ->button(),

            // Visualizações por Status
            Actions\ActionGroup::make([
                Actions\Action::make('receitas')
                    ->label('💰 Receitas')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->url(FinanceiroResource::getUrl('receitas')),

                Actions\Action::make('despesas')
                    ->label('💸 Despesas')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->url(FinanceiroResource::getUrl('despesas')),

                Actions\Action::make('pendentes')
                    ->label('⏳ Pendentes')
                    ->icon('heroicon-o-clock')
                    ->url(FinanceiroResource::getUrl('pendentes')),

                Actions\Action::make('atrasadas')
                    ->label('🔴 Atrasadas')
                    ->icon('heroicon-o-exclamation-circle')
                    ->url(FinanceiroResource::getUrl('atrasadas')),
            ])
                ->label('📋 Visualizar')
                ->icon('heroicon-m-eye')
                ->color('info')
                ->button(),

            Actions\ActionGroup::make([
                Actions\Action::make('relatorioSimples')
                    ->label('Simples / Resumido')
                    ->icon('heroicon-o-chart-bar')
                    ->url(Relatorios::getUrl()),

                // NOVO: Relatório Mensal PDF
                Actions\Action::make('relatorioMensalPdf')
                    ->label('Relatório Mensal (PDF)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->form([
                        \Filament\Forms\Components\Select::make('mes')
                            ->label('Mês')
                            ->options([
                                1 => 'Janeiro',
                                2 => 'Fevereiro',
                                3 => 'Março',
                                4 => 'Abril',
                                5 => 'Maio',
                                6 => 'Junho',
                                7 => 'Julho',
                                8 => 'Agosto',
                                9 => 'Setembro',
                                10 => 'Outubro',
                                11 => 'Novembro',
                                12 => 'Dezembro',
                            ])
                            ->default(now()->month)
                            ->required(),
                        \Filament\Forms\Components\Select::make('ano')
                            ->label('Ano')
                            ->options(
                                collect(range(now()->year - 2, now()->year + 1))
                                    ->mapWithKeys(fn($year) => [$year => $year])
                            )
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        return redirect()->route('financeiro.relatorio_mensal', [
                            'mes' => $data['mes'],
                            'ano' => $data['ano'],
                        ]);
                    }),
            ])
                ->label('Relatórios')
                ->icon('heroicon-m-document-chart-bar')
                ->color('success')
                ->button(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceiroResource\Widgets\FinanceiroOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
