<?php

namespace App\Actions;

use App\Models\Orcamento;
use App\Services\OrcamentoFormService;
use Illuminate\Support\Facades\Log;

/**
 * Ação: Sincronizar o valor do Orçamento para OS e Financeiro vinculados.
 *
 * Anteriormente estava como hook no Model Orcamento::booted() => static::updated().
 * Movida para Action para dar controle explícito de quando/como a sincronização ocorre.
 *
 * Uso:
 *   app(SincronizarValorModulosAction::class)->execute($orcamento);
 */
class SincronizarValorModulosAction
{
    /**
     * Propaga o valor efetivo do orçamento para todos os módulos vinculados.
     * - Ordem de Serviço: atualiza valor_total e valor_desconto
     * - Financeiro Principal: atualiza valor e desconto
     */
    public function execute(Orcamento $orcamento): void
    {
        Log::info("[SincronizarValorModulosAction] Propagando valor do Orçamento #{$orcamento->numero}", [
            'valor_efetivo' => $orcamento->valor_efetivo,
        ]);

        OrcamentoFormService::sincronizarValorModulos($orcamento);
    }
}
