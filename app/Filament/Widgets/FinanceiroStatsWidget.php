<?php

namespace App\Filament\Widgets;

use App\Models\TransacaoFinanceira;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceiroStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $mesAtual = now()->month;
        $anoAtual = now()->year;

        // Receitas do mês
        $receitasMes = TransacaoFinanceira::receitas()
            ->pagas()
            ->doMes($mesAtual, $anoAtual)
            ->sum('valor');

        // Despesas do mês
        $despesasMes = TransacaoFinanceira::despesas()
            ->pagas()
            ->doMes($mesAtual, $anoAtual)
            ->sum('valor');

        // Saldo do mês
        $saldoMes = $receitasMes - $despesasMes;

        // Contas a receber
        $contasReceber = TransacaoFinanceira::receitas()
            ->pendentes()
            ->sum('valor');

        // Contas a pagar
        $contasPagar = TransacaoFinanceira::despesas()
            ->pendentes()
            ->sum('valor');

        // Contas vencidas
        $contasVencidas = TransacaoFinanceira::vencidas()
            ->count();

        // Receitas do mês anterior para comparação
        $receitasMesAnterior = TransacaoFinanceira::receitas()
            ->pagas()
            ->doMes($mesAtual - 1, $anoAtual)
            ->sum('valor');

        $receitasVariacao = $receitasMesAnterior > 0
            ? (($receitasMes - $receitasMesAnterior) / $receitasMesAnterior) * 100
            : 0;

        // Despesas do mês anterior para comparação
        $despesasMesAnterior = TransacaoFinanceira::despesas()
            ->pagas()
            ->doMes($mesAtual - 1, $anoAtual)
            ->sum('valor');

        $despesasVariacao = $despesasMesAnterior > 0
            ? (($despesasMes - $despesasMesAnterior) / $despesasMesAnterior) * 100
            : 0;

        return [
            Stat::make('💰 Receitas do Mês', 'R$ '.number_format($receitasMes, 2, ',', '.'))
                ->description($this->formatVariacao($receitasVariacao))
                ->descriptionIcon($receitasVariacao >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('success')
                ->chart($this->getChartData('receita')),

            Stat::make('💸 Despesas do Mês', 'R$ '.number_format($despesasMes, 2, ',', '.'))
                ->description($this->formatVariacao($despesasVariacao))
                ->descriptionIcon($despesasVariacao >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart($this->getChartData('despesa')),

            Stat::make('📊 Saldo do Mês', 'R$ '.number_format($saldoMes, 2, ',', '.'))
                ->description($saldoMes >= 0 ? 'Saldo positivo' : 'Saldo negativo')
                ->descriptionIcon($saldoMes >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($saldoMes >= 0 ? 'success' : 'danger'),

            Stat::make('📥 Contas a Receber', 'R$ '.number_format($contasReceber, 2, ',', '.'))
                ->description(TransacaoFinanceira::receitas()->pendentes()->count().' transações pendentes')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('📤 Contas a Pagar', 'R$ '.number_format($contasPagar, 2, ',', '.'))
                ->description(TransacaoFinanceira::despesas()->pendentes()->count().' transações pendentes')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('⚠️ Contas Vencidas', $contasVencidas)
                ->description($contasVencidas > 0 ? 'Requerem atenção!' : 'Nenhuma conta vencida')
                ->descriptionIcon($contasVencidas > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($contasVencidas > 0 ? 'danger' : 'success'),
        ];
    }

    protected function formatVariacao(float $variacao): string
    {
        if ($variacao == 0) {
            return 'Sem alteração em relação ao mês anterior';
        }

        $variacaoFormatada = number_format(abs($variacao), 1, ',', '.');

        if ($variacao > 0) {
            return "+{$variacaoFormatada}% em relação ao mês anterior";
        }

        return "-{$variacaoFormatada}% em relação ao mês anterior";
    }

    protected function getChartData(string $tipo): array
    {
        $dados = [];

        // Últimos 7 dias
        for ($i = 6; $i >= 0; $i--) {
            $data = now()->subDays($i);

            $valor = TransacaoFinanceira::where('tipo', $tipo)
                ->pagas()
                ->whereDate('data_pagamento', $data)
                ->sum('valor');

            $dados[] = (float) $valor;
        }

        return $dados;
    }
}
