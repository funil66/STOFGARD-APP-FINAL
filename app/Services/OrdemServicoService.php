<?php

namespace App\Services;

use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Enums\OrcamentoStatus;
use App\Enums\OrdemServicoStatus;
use App\Enums\AgendaStatus;
use App\Enums\AgendaTipo;
use Illuminate\Support\Facades\DB;
use Exception;

class OrdemServicoService
{
    protected FinanceiroService $financeiroService;
    protected EstoqueService $estoqueService;

    // Injeção de Dependência automática do Laravel
    public function __construct(
        FinanceiroService $financeiroService,
        EstoqueService $estoqueService
    ) {
        $this->financeiroService = $financeiroService;
        $this->estoqueService = $estoqueService;
    }

    /**
     * Transforma um Orçamento Aprovado em uma Ordem de Serviço completa.
     * Transaction Atômica: Ou cria tudo, ou não cria nada.
     * 
     * @param Orcamento $orcamento
     * @param int|null $userId ID do usuário responsável (obrigatório para auditoria)
     * @throws Exception
     */
    public function aprovarOrcamento(Orcamento $orcamento, ?int $userId = null): OrdemServico
    {
        // CORREÇÃO CRÍTICA: Não permitir operações sem usuário responsável
        if (!$userId && !auth()->id()) {
            throw new Exception('Não é possível aprovar orçamento sem um usuário responsável. Operação rejeitada por questão de auditoria.');
        }

        $userId = $userId ?? auth()->id();

        if ($orcamento->status === OrcamentoStatus::Aprovado->value) {
            throw new Exception('Este orçamento já foi aprovado anteriormente.');
        }

        return DB::transaction(function () use ($orcamento, $userId) {
            // 1. Cria a OS
            $os = $this->criarAPartirDeOrcamento($orcamento, $userId);

            // 2. Aciona o Financeiro (Gera Contas a Receber)
            $this->financeiroService->gerarPreviaReceita($os);

            // 3. Aciona o Estoque (Baixa produtos se houver)
            $this->estoqueService->baixarItensDeOrcamento($orcamento);

            // 4. Cria Agenda (Agendamento do Serviço)
            $this->criarAgendaParaOS($os, $orcamento, $userId);

            // 5. Atualiza o Orçamento original
            $orcamento->update([
                'status' => OrcamentoStatus::Aprovado->value,
                'aprovado_em' => now(),
            ]);

            return $os;
        });
    }

    /**
     * Cria uma entrada na Agenda vinculada à OS.
     */
    protected function criarAgendaParaOS(OrdemServico $os, Orcamento $orcamento, int $userId): \App\Models\Agenda
    {
        $cliente = $os->cliente;
        $endereco = $cliente->endereco_completo ?? $cliente->endereco ?? null;

        return \App\Models\Agenda::create([
            'titulo' => "Serviço - " . ($cliente->nome ?? 'Cliente'),
            'descricao' => "OS #{$os->numero_os} gerada via Orçamento #{$orcamento->numero}",
            'cadastro_id' => $os->cadastro_id,
            'ordem_servico_id' => $os->id,
            'orcamento_id' => $orcamento->id,
            'tipo' => AgendaTipo::Servico->value,
            'data_hora_inicio' => $orcamento->data_prevista ?? now()->addDays(1)->setHour(9),
            'data_hora_fim' => $orcamento->data_prevista
                ? $orcamento->data_prevista->copy()->addHours(2)
                : now()->addDays(1)->setHour(11),
            'status' => AgendaStatus::Agendado->value,
            'local' => $endereco ?? 'A definir',
            'endereco_completo' => $endereco,
            'criado_por' => $userId,
        ]);
    }


    /**
     * Cria uma Ordem de Serviço a partir de um Orçamento.
     */
    public function criarAPartirDeOrcamento(Orcamento $orcamento, int $userId)
    {
        return DB::transaction(function () use ($orcamento, $userId) {
            // BLINDAGEM: Verifica se os IDs do orçamento ainda existem no banco atual
            // Se não existirem, define como NULL para evitar o erro de Foreign Key
            // UNIFICADO: Agora tudo é Cadastro
            $lojaId = \App\Models\Cadastro::find($orcamento->loja_id)?->id;
            $vendedorId = \App\Models\Cadastro::find($orcamento->vendedor_id)?->id;

            $os = OrdemServico::create([
                'orcamento_id' => $orcamento->id,
                'cadastro_id' => $orcamento->cadastro_id,
                'loja_id' => $lojaId,      // ID Validado
                'vendedor_id' => $vendedorId,  // ID Validado
                'origem' => 'orcamento',
                'status' => OrdemServicoStatus::Aberta->value,
                'valor_total' => $orcamento->valor_total,
                'data_abertura' => now(),
                'tipo_servico' => 'servico',
                'descricao_servico' => "Serviço aprovado via Orçamento #{$orcamento->numero}",
                'criado_por' => $userId,
            ]);

            // Copia Itens (Lógica mantida)
            if ($orcamento->itens()->exists()) {
                foreach ($orcamento->itens as $item) {
                    $os->itens()->create([
                        'descricao' => $item->item_nome ?? 'Serviço Diverso',
                        'quantidade' => $item->quantidade,
                        'unidade_medida' => $item->unidade ?? 'unidade',
                        'valor_unitario' => $item->valor_unitario,
                        'subtotal' => $item->subtotal ?? ($item->quantidade * $item->valor_unitario),
                        'observacoes' => $item->servico_tipo ?? null,
                    ]);
                }
            }

            // COPY MEDIA: Copia fotos do Orçamento para a OS
            // Isso garante que as evidências visuais acompanhem o processo
            $orcamento->getMedia('arquivos')->each(function ($mediaItem) use ($os) {
                // copy() duplica o arquivo físico e cria registro na nova model
                $mediaItem->copy($os, 'arquivos', 'public');
            });

            return $os;
        });
    }
}