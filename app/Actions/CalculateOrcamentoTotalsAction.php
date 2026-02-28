<?php

namespace App\Actions;

use App\Models\Orcamento;
use App\Services\CalculoOrcamentoDTO;
use App\Services\OrcamentoCalculator;
use Illuminate\Support\Facades\Log;

/**
 * Ação: Calcular os totais de um Orçamento e persistir no banco.
 *
 * Extrai a lógica que estava no hook `static::updated()` de Orcamento.php.
 * O Model agora só sabe persistir dados — esta Action orquestra o cálculo.
 *
 * Uso:
 *   $calculo = app(CalculateOrcamentoTotalsAction::class)->execute($orcamento);
 *   // ou
 *   $calculo = app(CalculateOrcamentoTotalsAction::class)->calculate($orcamento);
 */
class CalculateOrcamentoTotalsAction
{
    public function __construct(
        private readonly OrcamentoCalculator $calculator
    ) {
    }

    /**
     * Calcula e retorna o DTO de cálculo sem persistir.
     * Use para exibição em views, PDFs e Filament forms.
     */
    public function calculate(Orcamento $orcamento): CalculoOrcamentoDTO
    {
        return $this->calculator->calcular($orcamento);
    }

    /**
     * Calcula e persiste os valores de comissão no banco.
     * Sincroniza OS e Financeiro vinculados quando o valor muda.
     * Use ao salvar o orçamento.
     */
    public function execute(Orcamento $orcamento, bool $syncModules = true): CalculoOrcamentoDTO
    {
        $calculo = $this->calculator->calcular($orcamento);

        Log::debug("[CalculateOrcamentoTotalsAction] Orçamento #{$orcamento->numero}", [
            'subtotal' => $calculo->subtotal,
            'valor_final' => $calculo->valorFinal,
            'comissao_vendedor' => $calculo->comissaoVendedor,
            'comissao_loja' => $calculo->comissaoLoja,
        ]);

        // Persiste comissões calculadas sem disparar events (evita loop)
        $orcamento->saveQuietly([
            'comissao_vendedor' => $calculo->comissaoVendedor,
            'comissao_loja' => $calculo->comissaoLoja,
        ]);

        // Propaga valor para OS e Financeiro vinculados
        if ($syncModules && ($orcamento->wasChanged('valor_final_editado') || $orcamento->wasChanged('desconto_prestador'))) {
            app(SincronizarValorModulosAction::class)->execute($orcamento);
        }

        return $calculo;
    }
}
