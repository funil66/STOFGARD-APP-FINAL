<?php

namespace App\Filament\Resources\OrdemServicoResource\Pages;

use App\Filament\Resources\OrdemServicoResource;
use App\Models\Agenda;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOrdemServico extends CreateRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected array $produtosSelecionados = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Capturar e remover os produtos selecionados para processamento manual
        $this->produtosSelecionados = $data['produtos_selecionados'] ?? [];
        unset($data['produtos_selecionados']);

        // Sempre gerar novo número da OS no momento do salvamento
        $data['numero_os'] = \App\Models\OrdemServico::gerarNumeroOS();

        // Registrar quem criou
        $data['criado_por'] = strtoupper(substr(Auth::user()->name, 0, 2));

        // Unificar cadastro: preservar cadastro_id e popular campos legacy
        if (isset($data['cadastro_id'])) {
            // manter o cadastro_id (string) para o novo padrão
            if (str_starts_with($data['cadastro_id'], 'cliente_')) {
                $data['cliente_id'] = (int) str_replace('cliente_', '', $data['cadastro_id']);
                $data['parceiro_id'] = null;
            } elseif (str_starts_with($data['cadastro_id'], 'parceiro_')) {
                $data['parceiro_id'] = (int) str_replace('parceiro_', '', $data['cadastro_id']);
                $data['cliente_id'] = null;
            }
        }

        // Calcular data fim de garantia APENAS se a OS já foi concluída
        if (!empty($data['data_conclusao']) && !empty($data['dias_garantia'])) {
            $data['data_fim_garantia'] = \Carbon\Carbon::parse($data['data_conclusao'])
                ->addDays($data['dias_garantia']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $ordemServico = $this->record;

        // Sincronizar produtos do estoque manualmente
        if (!empty($this->produtosSelecionados)) {
            $syncData = [];
            foreach ($this->produtosSelecionados as $item) {
                if (!empty($item['estoque_id'])) {
                    $syncData[$item['estoque_id']] = [
                        'quantidade_utilizada' => $item['quantidade_utilizada'],
                        'unidade' => $item['unidade'],
                        'observacao' => $item['observacao'] ?? null,
                    ];
                }
            }
            $ordemServico->produtosUtilizados()->sync($syncData);
        }

        // A criação da agenda foi movida e centralizada no OrdemServicoObserver
        // para garantir que seja gerada consistentemente tanto via formulário
        // quanto via aprovação de orçamento, evitando duplicações.
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
