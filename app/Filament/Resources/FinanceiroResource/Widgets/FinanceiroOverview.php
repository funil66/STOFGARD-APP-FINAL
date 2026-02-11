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
        // Totais Gerais (Considerando Pagos)
        $entradasPagas = Financeiro::pago()->entrada()->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(valor_pago, valor)'));
        $saidasPagas = Financeiro::pago()->saida()->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(valor_pago, valor)'));
        $saldo = $entradasPagas - $saidasPagas;

        // Mês Atual
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        // #8: Receitas/Despesas do Mês — SOMENTE PAGOS (não pendentes)
        $receitasMes = Financeiro::entrada()->pago()
            ->whereBetween('data_pagamento', [$inicioMes, $fimMes])
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(valor_pago, valor)'));

        $despesasMes = Financeiro::saida()->pago()
            ->whereBetween('data_pagamento', [$inicioMes, $fimMes])
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(valor_pago, valor)'));

        // Pendentes Gerais (Inclui Atrasados)
        $pendentesReceber = Financeiro::whereIn('status', ['pendente', 'atrasado'])->entrada()->sum('valor');
        $pendentesPagar = Financeiro::whereIn('status', ['pendente', 'atrasado'])->saida()->sum('valor');

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

            Stat::make('Receitas Realizadas (Mês)', Number::currency($receitasMes, 'BRL'))
                ->description('Pagamentos confirmados neste mês')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->color('success')
                ->url(FinanceiroResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['value' => 'pago'],
                        'tipo' => ['value' => 'entrada'],
                    ],
                ])),

            Stat::make('Despesas Realizadas (Mês)', Number::currency($despesasMes, 'BRL'))
                ->description('Pagamentos confirmados neste mês')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('danger')
                ->url(FinanceiroResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['value' => 'pago'],
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

            // #8: Widget de Pendentes a Pagar
            Stat::make('Pendentes a Pagar', Number::currency($pendentesPagar, 'BRL'))
                ->description('Total a pagar (comissões, despesas)')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger')
                ->url(FinanceiroResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['value' => 'pendente'],
                        'tipo' => ['value' => 'saida'],
                    ],
                ])),
        ];
    }
}
