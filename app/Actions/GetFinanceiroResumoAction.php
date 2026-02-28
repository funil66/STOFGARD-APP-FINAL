<?php

namespace App\Actions;

use App\Models\Cadastro;
use App\Models\Financeiro;
use Illuminate\Support\Facades\Cache;

/**
 * Ação: Calcular resumo financeiro de um Cadastro (cliente/funcionário).
 *
 * Os accessors getTotalReceitasAttribute, getTotalDespesasAttribute,
 * getSaldoAttribute etc. foram removidos do Model Cadastro.php porque
 * causavam N+1 invisível em listagens (cada row da tabela disparava 4 queries).
 *
 * Esta Action calcula sob demanda com cache opcional (TTL configurável).
 *
 * Uso:
 *   $resumo = app(GetFinanceiroResumoAction::class)->execute($cadastro);
 *   $resumo['saldo']  // float
 *   $resumo['total_receitas']  // float
 *   $resumo['pendentes_receber']  // float
 */
class GetFinanceiroResumoAction
{
    /**
     * @param  int  $cacheTtlMinutes  0 para desabilitar cache
     */
    public function execute(Cadastro $cadastro, int $cacheTtlMinutes = 5): array
    {
        $cacheKey = "financeiro_resumo_{$cadastro->id}";

        if ($cacheTtlMinutes > 0) {
            return Cache::remember($cacheKey, now()->addMinutes($cacheTtlMinutes), function () use ($cadastro) {
                return $this->calcular($cadastro);
            });
        }

        return $this->calcular($cadastro);
    }

    /**
     * Invalida o cache após uma operação financeira.
     */
    public function invalidarCache(int $cadastroId): void
    {
        Cache::forget("financeiro_resumo_{$cadastroId}");
    }

    private function calcular(Cadastro $cadastro): array
    {
        $base = $cadastro->financeiros();

        $totalReceitas = (float) (clone $base)
            ->where('tipo', 'entrada')
            ->where('status', 'pago')
            ->sum('valor');

        $totalDespesas = (float) (clone $base)
            ->where('tipo', 'saida')
            ->where('status', 'pago')
            ->sum('valor');

        $pendentesReceber = (float) (clone $base)
            ->where('tipo', 'entrada')
            ->where('status', 'pendente')
            ->sum('valor');

        $pendentesPagar = (float) (clone $base)
            ->where('tipo', 'saida')
            ->where('status', 'pendente')
            ->sum('valor');

        $orcamentosAprovados = $cadastro->orcamentos()
            ->where('status', 'aprovado')
            ->count();

        $osConcluidas = $cadastro->ordensServico()
            ->whereIn('status', ['concluida', 'finalizada'])
            ->count();

        return [
            'total_receitas' => $totalReceitas,
            'total_despesas' => $totalDespesas,
            'saldo' => $totalReceitas - $totalDespesas,
            'pendentes_receber' => $pendentesReceber,
            'pendentes_pagar' => $pendentesPagar,
            'orcamentos_aprovados' => $orcamentosAprovados,
            'os_concluidas' => $osConcluidas,
        ];
    }
}
