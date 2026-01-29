<?php

namespace App\Observers;

use App\Models\Orcamento;
use Illuminate\Support\Facades\Log;

class OrcamentoObserver
{
    /**
     * Handle the Orcamento "saving" event.
     */
    public function saving(Orcamento $orcamento): void
    {
        // Gerar número do orçamento se não existir
        if (! $orcamento->numero_orcamento) {
            $orcamento->numero_orcamento = Orcamento::gerarNumeroOrcamento();
        }
        // Nota: cálculo de totais e outras ações foram removidas do observer
        // para evitar side-effects durante operações de save. Essas ações
        // devem ser executadas explicitamente quando necessário (ex: ao gerar PDF/OS).
    }

    /**
     * Handle the Orcamento "saved" event.
     */
    public function saved(Orcamento $orcamento): void
    {
        // Apenas log de atividade — sem geração de QR/PAYLOAD aqui
        Log::info("Orçamento {$orcamento->id} salvo (número {$orcamento->numero_orcamento}).");
    }
}
