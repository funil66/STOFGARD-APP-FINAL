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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Sempre gerar novo número da OS no momento do salvamento
        $data['numero_os'] = \App\Models\OrdemServico::gerarNumeroOS();

        // Registrar quem criou
        $data['criado_por'] = strtoupper(substr(Auth::user()->name, 0, 2));

        // Unificar cadastro: preservar cadastro_id e popular campos legacy
        if (isset($data['cadastro_id'])) {
            // manter o cadastro_id (string) para o novo padrão
            if (str_starts_with($data['cadastro_id'], 'cliente_')) {
                $data['cliente_id'] = (int)str_replace('cliente_', '', $data['cadastro_id']);
                $data['parceiro_id'] = null;
            } elseif (str_starts_with($data['cadastro_id'], 'parceiro_')) {
                $data['parceiro_id'] = (int)str_replace('parceiro_', '', $data['cadastro_id']);
                $data['cliente_id'] = null;
            }
        }

        // Calcular data fim de garantia APENAS se a OS já foi concluída
        if (! empty($data['data_conclusao']) && ! empty($data['dias_garantia'])) {
            $data['data_fim_garantia'] = \Carbon\Carbon::parse($data['data_conclusao'])
                ->addDays($data['dias_garantia']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $ordemServico = $this->record;

        // Criar agendamento automaticamente se houver data_prevista E o cadastro for um cliente
        if ($ordemServico->data_prevista && $ordemServico->cadastro && str_starts_with($ordemServico->cadastro_id, 'cliente_')) {
            $cliente = $ordemServico->cadastro;
            $tipoServico = match ($ordemServico->tipo_servico) {
                'higienizacao' => '🧼 Higienização',
                'impermeabilizacao' => '💧 Impermeabilização',
                'higienizacao_impermeabilizacao' => '🧼💧 Higienização + Impermeabilização',
                default => 'Serviço',
            };

            $agenda = Agenda::create([
                'titulo' => "Serviço - OS #{$ordemServico->numero_os}",
                'descricao' => "{$tipoServico}\nCliente: {$cliente->nome}\n{$ordemServico->descricao_servico}",
                'data_hora_inicio' => $ordemServico->data_prevista->setTime(8, 0), // 8h da manhã
                'data_hora_fim' => $ordemServico->data_prevista->setTime(18, 0), // 6h da tarde
                'dia_inteiro' => false,
                'tipo' => 'servico',
                'status' => 'agendado',
                'cliente_id' => $cliente->id,
                'cadastro_id' => $ordemServico->cadastro_id ?? ($cliente ? 'cliente_' . $cliente->id : null),
                'ordem_servico_id' => $ordemServico->id,
                'local' => $cliente->cidade ?? null,
                'endereco_completo' => trim(
                    ($cliente->logradouro ?? '').', '.
                    ($cliente->numero ?? '').' - '.
                    ($cliente->bairro ?? '').' - '.
                    ($cliente->cidade ?? '').'/' .
                    ($cliente->estado ?? '')
                ),
                'cor' => '#22c55e', // Verde para serviços
                'observacoes' => $ordemServico->observacoes,
                'criado_por' => strtoupper(substr(Auth::user()->name, 0, 2)),
            ]);

            // Atualizar OS com o ID da agenda
            $ordemServico->update(['agenda_id' => $agenda->id]);

            Notification::make()
                ->success()
                ->title('Agendamento criado!')
                ->body("Serviço agendado para {$ordemServico->data_prevista->format('d/m/Y')}")
                ->icon('heroicon-o-calendar-days')
                ->iconColor('success')
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
