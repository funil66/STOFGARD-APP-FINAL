<?php

namespace App\Filament\Resources\FinanceiroResource\Widgets;

use App\Filament\Resources\FinanceiroResource;
use App\Models\Financeiro;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class FinanceiroOverview extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Totais Gerais (Considerando Pagos)
        $entradasPagas = Financeiro::pago()->entrada()->sum('valor_pago');
        $saidasPagas = Financeiro::pago()->saida()->sum('valor_pago');
        $saldo = $entradasPagas - $saidasPagas;

        // Mês Atual
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        $receitasMes = Financeiro::entrada()
            ->whereBetween('data_vencimento', [$inicioMes, $fimMes])
            ->sum('valor');

        $despesasMes = Financeiro::saida()
            ->whereBetween('data_vencimento', [$inicioMes, $fimMes])
            ->sum('valor');

        // Pendentes Gerais
        $pendentesReceber = Financeiro::pendente()->entrada()->sum('valor');
        $pendentesPagar = Financeiro::pendente()->saida()->sum('valor');

        return [
            Stat::make('Saldo em Caixa', Number::currency($saldo, 'BRL'))
                ->description('Total acumulado (Entradas - Saídas)')
                ->descriptionIcon($saldo >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($saldo >= 0 ? 'success' : 'danger')
                ->chart([$saldo - 100, $saldo - 50, $saldo, $saldo + 50, $saldo + 100])
                ->url(FinanceiroResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['value' => 'pago'],
                        'data_range' => [
                            'data_de' => $inicioMes->format('Y-m-d'),
                            'data_ate' => $fimMes->format('Y-m-d'),
                        ],
                    ],
                ])),

            Stat::make('Receitas (Mês)', Number::currency($receitasMes, 'BRL'))
                ->description('Vencimento neste mês')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->color('success')
                ->url(FinanceiroResource::getUrl('index', [
                    'tableFilters' => [
                        'vencimento' => [
                            'vencimento_de' => $inicioMes->format('Y-m-d'),
                            'vencimento_ate' => $fimMes->format('Y-m-d'),
                        ],
                        'tipo' => ['value' => 'entrada'],
                    ],
                ])),

            Stat::make('Despesas (Mês)', Number::currency($despesasMes, 'BRL'))
                ->description('Vencimento neste mês')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('danger')
                ->url(FinanceiroResource::getUrl('index', [
                    'tableFilters' => [
                        'vencimento' => [
                            'vencimento_de' => $inicioMes->format('Y-m-d'),
                            'vencimento_ate' => $fimMes->format('Y-m-d'),
                        ],
                        'tipo' => ['value' => 'saida'],
                    ],
                ])),

            Stat::make('Pendentes a Receber', Number::currency($pendentesReceber, 'BRL'))
                ->description('Total a receber')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(FinanceiroResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['value' => 'pendente'],
                        'tipo' => ['value' => 'entrada'],
                    ],
                ])),
        ];
    }
}
