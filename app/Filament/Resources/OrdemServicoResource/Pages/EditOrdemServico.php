<?php

namespace App\Filament\Resources\OrdemServicoResource\Pages;

use App\Filament\Resources\OrdemServicoResource;
use App\Models\Agenda;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOrdemServico extends EditRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected array $produtosSelecionados = [];
    protected bool $shouldSyncProducts = false;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Carregar produtos selecionados manualmente para o repeater
        $data['produtos_selecionados'] = $this->record->produtosUtilizados->map(function ($item) {
            return [
                'estoque_id' => $item->id,
                'quantidade_utilizada' => $item->pivot->quantidade_utilizada,
                'unidade' => $item->pivot->unidade,
                'observacao' => $item->pivot->observacao,
                'disponivel' => $item->quantidade,
            ];
        })->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Capturar e remover os produtos selecionados para processamento manual
        if (array_key_exists('produtos_selecionados', $data)) {
            $this->produtosSelecionados = $data['produtos_selecionados'] ?? [];
            $this->shouldSyncProducts = true;
            unset($data['produtos_selecionados']);
        }

        // Registrar quem atualizou
        $data['atualizado_por'] = strtoupper(substr(Auth::user()->name, 0, 2));

        // Unificar cadastro: preservar cadastro_id e popular campos legacy
        if (isset($data['cadastro_id'])) {
            if (str_starts_with($data['cadastro_id'], 'cliente_')) {
                $data['cliente_id'] = (int) str_replace('cliente_', '', $data['cadastro_id']);
                $data['parceiro_id'] = null;
            } elseif (str_starts_with($data['cadastro_id'], 'parceiro_')) {
                $data['parceiro_id'] = (int) str_replace('parceiro_', '', $data['cadastro_id']);
                $data['cliente_id'] = null;
            }
        }

        // Recalcular data fim de garantia quando a OS for concluída
        if (!empty($data['data_conclusao']) && !empty($data['dias_garantia'])) {
            $data['data_fim_garantia'] = \Carbon\Carbon::parse($data['data_conclusao'])
                ->addDays($data['dias_garantia']);
        } elseif (empty($data['data_conclusao'])) {
            // Se remover a data de conclusão, limpar a garantia
            $data['data_fim_garantia'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $ordemServico = $this->record;

        // Sincronizar produtos do estoque manualmente
        if ($this->shouldSyncProducts) {
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

        // Verificar se a data_prevista mudou
        if ($ordemServico->wasChanged('data_prevista')) {
            if ($ordemServico->agenda_id) {
                // Atualizar agendamento existente
                $agenda = Agenda::find($ordemServico->agenda_id);

                if ($agenda && $ordemServico->data_prevista) {
                    $agenda->update([
                        'data_hora_inicio' => $ordemServico->data_prevista->setTime(8, 0),
                        'data_hora_fim' => $ordemServico->data_prevista->setTime(18, 0),
                        'atualizado_por' => strtoupper(substr(Auth::user()->name, 0, 2)),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Agendamento atualizado!')
                        ->body("Nova data: {$ordemServico->data_prevista->format('d/m/Y')}")
                        ->icon('heroicon-o-calendar-days')
                        ->send();
                } elseif (!$ordemServico->data_prevista && $agenda) {
                    // Se removeu a data, deletar o agendamento
                    $agenda->delete();
                    $ordemServico->update(['agenda_id' => null]);

                    Notification::make()
                        ->warning()
                        ->title('Agendamento removido')
                        ->body('Data prevista foi removida')
                        ->send();
                }
            } elseif ($ordemServico->data_prevista && $ordemServico->cadastro && str_starts_with($ordemServico->cadastro_id, 'cliente_')) {
                // Criar novo agendamento se não existia e cadastro é cliente
                $cliente = $ordemServico->cadastro;
                // Criar novo agendamento se não existia
                $tipoServico = match ($ordemServico->tipo_servico) {
                    \App\Enums\ServiceType::Higienizacao->value => '🧼 Higienização',
                    \App\Enums\ServiceType::Impermeabilizacao->value => '💧 Impermeabilização',
                    \App\Enums\ServiceType::Combo->value => '🧼💧 Higienização + Impermeabilização',
                    'higienizacao_impermeabilizacao' => '🧼💧 Higienização + Impermeabilização', // Legacy support
                    default => 'Serviço',
                };

                $agenda = Agenda::create([
                    'titulo' => "Serviço - OS #{$ordemServico->numero_os}",
                    'descricao' => "{$tipoServico}\nCliente: {$cliente->nome}\n{$ordemServico->descricao_servico}",
                    'data_hora_inicio' => $ordemServico->data_prevista->setTime(8, 0),
                    'data_hora_fim' => $ordemServico->data_prevista->setTime(18, 0),
                    'dia_inteiro' => false,
                    'tipo' => 'servico',
                    'status' => 'agendado',
                    'cliente_id' => $cliente->id,
                    'cadastro_id' => $ordemServico->cadastro_id ?? ($cliente ? 'cliente_' . $cliente->id : null),
                    'ordem_servico_id' => $ordemServico->id,
                    'local' => $cliente->cidade ?? null,
                    'endereco_completo' => trim(
                        ($cliente->logradouro ?? '') . ', ' .
                        ($cliente->numero ?? '') . ' - ' .
                        ($cliente->bairro ?? '') . ' - ' .
                        ($cliente->cidade ?? '') . '/' .
                        ($cliente->estado ?? '')
                    ),
                    'cor' => '#22c55e',
                    'observacoes' => $ordemServico->observacoes,
                    'criado_por' => strtoupper(substr(Auth::user()->name, 0, 2)),
                ]);

                $ordemServico->update(['agenda_id' => $agenda->id]);

                Notification::make()
                    ->success()
                    ->title('Agendamento criado!')
                    ->body("Serviço agendado para {$ordemServico->data_prevista->format('d/m/Y')}")
                    ->icon('heroicon-o-calendar-days')
                    ->send();
            }
        }

        // Atualizar status do agendamento quando o status da OS mudar
        if ($ordemServico->wasChanged('status') && $ordemServico->agenda_id) {
            $agenda = Agenda::find($ordemServico->agenda_id);

            if ($agenda) {
                $novoStatus = match ($ordemServico->status) {
                    'em_andamento' => 'em_andamento',
                    'concluida' => 'concluido',
                    'cancelada' => 'cancelado',
                    default => 'confirmado',
                };

                $agenda->update(['status' => $novoStatus]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
