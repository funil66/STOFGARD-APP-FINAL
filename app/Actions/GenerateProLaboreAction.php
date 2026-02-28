<?php

namespace App\Actions;

use App\Models\Cadastro;
use App\Models\Financeiro;
use App\Services\ProLaboreCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ação: Gerar/Calcular Pro-Labore dos Sócios para um período.
 *
 * Usa ProLaboreCalculator para calcular lucro, reserva e distribuição,
 * depois opcionalmente cria registros de Financeiro (saída) para cada sócio.
 *
 * Uso (apenas cálculo, sem persistir):
 *   $resultado = app(GenerateProLaboreAction::class)->calcular($inicio, $fim);
 *
 * Uso (calcular e persistir):
 *   $resultado = app(GenerateProLaboreAction::class)->execute($inicio, $fim);
 */
class GenerateProLaboreAction
{
    public function __construct(
        private readonly ProLaboreCalculator $calculator
    ) {
    }

    /**
     * Calcula sem persistir. Uso em previews e telas de confirmação.
     */
    public function calcular(Carbon $inicio, Carbon $fim): array
    {
        $lucro = $this->calculator->calcularLucroLiquido($inicio, $fim);
        $reserva = $this->calculator->calcularReserva($lucro);
        $lucroDisponivel = max(0, $lucro - $reserva);
        $distribuicao = $this->calculator->calcularDistribuicao($lucroDisponivel);

        return [
            'periodo_inicio' => $inicio->toDateString(),
            'periodo_fim' => $fim->toDateString(),
            'lucro_bruto' => $lucro,
            'reserva' => $reserva,
            'lucro_disponivel' => $lucroDisponivel,
            'distribuicao' => $distribuicao,
        ];
    }

    /**
     * Calcula E persiste registros de Financeiro (saída) para cada sócio.
     * Operação atômica — ou cria tudo ou não cria nada.
     *
     * @throws \Exception se lucro <= 0 ou não houver sócios configurados
     */
    public function execute(Carbon $inicio, Carbon $fim): array
    {
        $resultado = $this->calcular($inicio, $fim);

        if ($resultado['lucro_disponivel'] <= 0) {
            throw new \Exception('Lucro disponível insuficiente para distribuição de pró-labore.');
        }

        if (empty($resultado['distribuicao'])) {
            throw new \Exception('Nenhum sócio configurado. Configure os sócios em Configurações → Pró-Labore.');
        }

        DB::transaction(function () use ($resultado, $inicio, $fim) {
            foreach ($resultado['distribuicao'] as $socio) {
                Financeiro::create([
                    'tipo' => 'saida',
                    'status' => 'pendente',
                    'descricao' => "Pró-Labore {$inicio->format('M/Y')} — {$socio['nome']}",
                    'valor' => $socio['valor'],
                    'data_vencimento' => $fim->copy()->addDay(),
                    'categoria_id' => null, // Categoria de pró-labore (configurável)
                    'is_prolabore' => true,
                    'prolabore_socio' => $socio['nome'],
                    'prolabore_percentual' => $socio['percentual'],
                ]);
            }
        });

        Log::info('[GenerateProLaboreAction] Pró-labore gerado com sucesso', [
            'periodo' => "{$inicio->toDateString()} → {$fim->toDateString()}",
            'total' => $resultado['lucro_disponivel'],
            'socios' => count($resultado['distribuicao']),
        ]);

        return $resultado;
    }
}
