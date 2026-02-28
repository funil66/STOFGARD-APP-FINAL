<?php

namespace App\Actions;

use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Services\OrdemServicoService;
use Illuminate\Support\Facades\Log;

/**
 * Ação: Criar Ordem de Serviço a partir de um Orçamento aprovado.
 *
 * Ponto único de entrada para aprovação de orçamentos. Delega para
 * OrdemServicoService::aprovarOrcamento() que já possui transaction atômica,
 * criação de agenda, baixa de estoque e geração de financeiro (contas a receber).
 *
 * Uso:
 *   $os = app(CreateOrdemServicoFromOrcamentoAction::class)->execute($orcamento);
 */
class CreateOrdemServicoFromOrcamentoAction
{
    public function __construct(
        private readonly OrdemServicoService $ordemServicoService
    ) {
    }

    /**
     * @throws \Exception de OrdemServicoService (orçamento já aprovado, sem usuário, etc.)
     */
    public function execute(Orcamento $orcamento, ?int $userId = null): OrdemServico
    {
        $userId = $userId ?? auth()->id();

        Log::info("[CreateOSAction] Iniciando aprovação do Orçamento #{$orcamento->numero}", [
            'orcamento_id' => $orcamento->id,
            'user_id' => $userId,
        ]);

        $os = $this->ordemServicoService->aprovarOrcamento($orcamento, $userId);

        Log::info("[CreateOSAction] OS #{$os->numero_os} criada com sucesso a partir do Orçamento #{$orcamento->numero}", [
            'os_id' => $os->id,
        ]);

        return $os;
    }
}
