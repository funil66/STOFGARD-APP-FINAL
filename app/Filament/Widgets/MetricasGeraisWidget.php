<?php

namespace App\Filament\Widgets;

use App\Models\Cliente;
use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\Produto;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MetricasGeraisWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Período do mês atual
        $mesAtual = Carbon::now()->startOfMonth();
        $mesFim = Carbon::now()->endOfMonth();

        // Período do mês anterior para comparação
        $mesAnterior = Carbon::now()->subMonth()->startOfMonth();
        $mesAnteriorFim = Carbon::now()->subMonth()->endOfMonth();

        // Clientes Novos
        $clientesNovos = Cliente::whereBetween('created_at', [$mesAtual, $mesFim])->count();
        $clientesNovosAnterior = Cliente::whereBetween('created_at', [$mesAnterior, $mesAnteriorFim])->count();
        $clientesTrend = $clientesNovosAnterior > 0
            ? (($clientesNovos - $clientesNovosAnterior) / $clientesNovosAnterior) * 100
            : 0;

        // Serviços Realizados
        $servicosRealizados = OrdemServico::whereBetween('created_at', [$mesAtual, $mesFim])
            ->whereIn('status', ['concluido', 'finalizado'])
            ->count();
        $servicosAnterior = OrdemServico::whereBetween('created_at', [$mesAnterior, $mesAnteriorFim])
            ->whereIn('status', ['concluido', 'finalizado'])
            ->count();
        $servicosTrend = $servicosAnterior > 0
            ? (($servicosRealizados - $servicosAnterior) / $servicosAnterior) * 100
            : 0;

        // Receita do Mês
        $receitaMes = Financeiro::whereBetween('data_vencimento', [$mesAtual, $mesFim])
            ->where('tipo', 'receita')
            ->where('status', 'pago')
            ->sum('valor');
        $receitaAnterior = Financeiro::whereBetween('data_vencimento', [$mesAnterior, $mesAnteriorFim])
            ->where('tipo', 'receita')
            ->where('status', 'pago')
            ->sum('valor');
        $receitaTrend = $receitaAnterior > 0
            ? (($receitaMes - $receitaAnterior) / $receitaAnterior) * 100
            : 0;

        // Orçamentos Pendentes
        $orcamentosPendentes = Orcamento::where('status', 'pendente')
            ->whereDate('validade', '>=', now())
            ->count();
        $valorOrcamentos = Orcamento::where('status', 'pendente')
            ->whereDate('validade', '>=', now())
            ->sum('valor_total');

        // Produtos com Estoque Baixo
        $produtosBaixos = Produto::where('ativo', true)
            ->whereRaw('quantidade_estoque <= estoque_minimo')
            ->count();
        $produtosCriticos = Produto::where('ativo', true)
            ->whereRaw('quantidade_estoque <= estoque_minimo / 2')
            ->count();

        // Contas a Receber
        $contasReceber = Financeiro::where('tipo', 'receita')
            ->where('status', 'pendente')
            ->whereDate('data_vencimento', '<=', now()->addDays(30))
            ->sum('valor');

        return [
            Stat::make('Clientes Novos', $clientesNovos)
                ->description($clientesTrend >= 0 ? "↑ {$clientesTrend}% vs mês anterior" : '↓ '.abs($clientesTrend).'% vs mês anterior')
                ->descriptionIcon($clientesTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($clientesTrend >= 0 ? 'success' : 'danger')
                ->chart([7, 5, 10, 5, $clientesNovos]),

            Stat::make('Serviços Realizados', $servicosRealizados)
                ->description($servicosTrend >= 0 ? '↑ '.number_format($servicosTrend, 1).'% vs mês anterior' : '↓ '.number_format(abs($servicosTrend), 1).'% vs mês anterior')
                ->descriptionIcon($servicosTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($servicosTrend >= 0 ? 'success' : 'warning')
                ->chart([12, 8, 15, 10, $servicosRealizados]),

            Stat::make('Receita do Mês', 'R$ '.number_format($receitaMes, 2, ',', '.'))
                ->description($receitaTrend >= 0 ? '↑ '.number_format($receitaTrend, 1).'% vs mês anterior' : '↓ '.number_format(abs($receitaTrend), 1).'% vs mês anterior')
                ->descriptionIcon($receitaTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($receitaTrend >= 0 ? 'success' : 'danger')
                ->chart([
                    $receitaAnterior > 0 ? $receitaAnterior / 1000 : 0,
                    $receitaMes > 0 ? $receitaMes / 1000 : 0,
                ]),

            Stat::make('Orçamentos Pendentes', $orcamentosPendentes)
                ->description('R$ '.number_format($valorOrcamentos, 2, ',', '.').' em potencial')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),

            Stat::make('Estoque', $produtosBaixos.' produtos baixos')
                ->description($produtosCriticos > 0 ? $produtosCriticos.' críticos ⚠️' : 'Sob controle')
                ->descriptionIcon($produtosCriticos > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($produtosCriticos > 0 ? 'danger' : ($produtosBaixos > 0 ? 'warning' : 'success')),

            Stat::make('Contas a Receber (30d)', 'R$ '.number_format($contasReceber, 2, ',', '.'))
                ->description('Vencendo nos próximos 30 dias')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
