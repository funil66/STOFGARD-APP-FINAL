<?php

namespace App\Filament\Widgets;

use App\Models\Financeiro;
use App\Models\Cadastro;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DashboardPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '300s';

    protected function getStats(): array
    {
        // 1. Serviço mais lucrativo (Categorias de Entrada Pagas)
        $servicoLucrativo = Financeiro::entrada()
            ->pago()
            ->select('categoria_id', DB::raw('SUM(valor_pago) as total'))
            ->whereNotNull('categoria_id')
            ->groupBy('categoria_id')
            ->orderByDesc('total')
            ->with('categoria')
            ->first();

        $nomeServico = $servicoLucrativo && $servicoLucrativo->categoria ? $servicoLucrativo->categoria->nome : 'Nenhum dado';
        $valorServico = $servicoLucrativo ? 'R$ ' . number_format($servicoLucrativo->total, 2, ',', '.') : 'R$ 0,00';

        // 2. Cliente que mais dá prejuízo (Inadimplência - Vencidos não pagos ou maiores saídas)
        $clientePrejuizo = Financeiro::pendente()
            ->vencido()
            ->select('cadastro_id', DB::raw('SUM(valor) as total_divida'))
            ->whereNotNull('cadastro_id')
            ->groupBy('cadastro_id')
            ->orderByDesc('total_divida')
            ->with('cadastro')
            ->first();

        if (!$clientePrejuizo) {
            // Fallback: Cliente com mais devoluções/saídas
            $clientePrejuizo = Financeiro::saida()
                ->select('cadastro_id', DB::raw('SUM(valor) as total_divida'))
                ->whereNotNull('cadastro_id')
                ->groupBy('cadastro_id')
                ->orderByDesc('total_divida')
                ->with('cadastro')
                ->first();
            $labelPrejuizo = 'Maior Custo por Cliente';
            $descricaoPrejuizo = 'Cliente com mais saídas/reembolsos';
        } else {
            $labelPrejuizo = 'Maior Inadimplência';
            $descricaoPrejuizo = 'Cliente com maior dívida vencida';
        }

        $nomeCliente = $clientePrejuizo && $clientePrejuizo->cadastro ? $clientePrejuizo->cadastro->nome : 'Nenhum dado';
        $valorPrejuizo = $clientePrejuizo ? 'R$ ' . number_format($clientePrejuizo->total_divida, 2, ',', '.') : 'R$ 0,00';

        // 3. Taxa de Conversão de Inadimplência Geral
        $totalVencido = Financeiro::pendente()->vencido()->sum('valor');
        $totalRecebido = Financeiro::entrada()->pago()->doMes()->sum('valor_pago');
        
        $saudeFinanceira = 'Excelente';
        $corSaude = 'success';
        if ($totalRecebido > 0 && ($totalVencido / $totalRecebido) > 0.2) {
            $saudeFinanceira = 'Atenção';
            $corSaude = 'warning';
        } elseif ($totalRecebido == 0 && $totalVencido > 0) {
            $saudeFinanceira = 'Crítica';
            $corSaude = 'danger';
        }

        return [
            Stat::make('Serviço Mais Lucrativo', $nomeServico)
                ->description("Total gerado: {$valorServico}")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'title' => 'Serviço que trouxe mais faturamento histórico',
                ]),
                
            Stat::make($labelPrejuizo, $nomeCliente)
                ->description("Montante: {$valorPrejuizo} ({$descricaoPrejuizo})")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'title' => 'Verifique o cadastro deste cliente para cobrança',
                ]),

            Stat::make('Saúde Financeira do Mês', $saudeFinanceira)
                ->description("Recebido: R$ " . number_format($totalRecebido, 2, ',', '.') . " | Inadimplência: R$ " . number_format($totalVencido, 2, ',', '.'))
                ->descriptionIcon($corSaude === 'success' ? 'heroicon-m-check-badge' : 'heroicon-m-shield-exclamation')
                ->color($corSaude),
        ];
    }
}
