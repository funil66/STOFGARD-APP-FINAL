<?php

namespace App\Filament\Widgets;

use App\Models\Financeiro;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceiroStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $mesAtual = now()->month;
        $anoAtual = now()->year;

        // Calcular mês anterior com wrap-around
        $mesAnterior = $mesAtual - 1;
        $anoAnterior = $anoAtual;
        if ($mesAnterior < 1) { $mesAnterior = 12; $anoAnterior = $anoAtual - 1; }

        // Receitas do mês (Financeiro: tipo 'entrada', status 'pago', considerar data_pagamento)
        $receitasMes = Financeiro::entrada()
            ->pago()
            ->whereYear('data_pagamento', $anoAtual)
            ->whereMonth('data_pagamento', $mesAtual)
            ->sum('valor');

        // Despesas do mês (tipo 'saida')
        $despesasMes = Financeiro::saida()
            ->pago()
            ->whereYear('data_pagamento', $anoAtual)
            ->whereMonth('data_pagamento', $mesAtual)
            ->sum('valor');

        // Saldo do mês
        $saldoMes = $receitasMes - $despesasMes;

        // Contas a receber
        $contasReceber = Financeiro::entrada()
            ->pendente()
            ->sum('valor');

        // Contas a pagar
        $contasPagar = Financeiro::saida()
            ->pendente()
            ->sum('valor');

        // Contas vencidas
        $contasVencidas = Financeiro::vencido()
            ->count();

        // Receitas do mês anterior para comparação
        $receitasMesAnterior = Financeiro::entrada()
            ->pago()
            ->whereYear('data_pagamento', $anoAnterior)
            ->whereMonth('data_pagamento', $mesAnterior)
            ->sum('valor');

        $receitasVariacao = $receitasMesAnterior > 0
            ? (($receitasMes - $receitasMesAnterior) / $receitasMesAnterior) * 100
            : 0;

        // Despesas do mês anterior para comparação
        $despesasMesAnterior = Financeiro::saida()
            ->pago()
            ->whereYear('data_pagamento', $anoAnterior)
            ->whereMonth('data_pagamento', $mesAnterior)
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

            $tipoModel = $tipo === 'receita' ? 'entrada' : 'saida';

            $valor = Financeiro::where('tipo', $tipoModel)
                ->pago()
                ->whereDate('data_pagamento', $data)
                ->sum('valor');

            $dados[] = (float) $valor;
        }

        return $dados;
    }
}
