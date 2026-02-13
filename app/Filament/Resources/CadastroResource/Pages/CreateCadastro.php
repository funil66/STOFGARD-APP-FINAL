<?php

namespace App\Filament\Resources\CadastroResource\Pages;

use App\Filament\Resources\CadastroResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCadastro extends CreateRecord
{
    protected static string $resource = CadastroResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // 1. Cria o registro principal (Cadastro)
        $record = static::getModel()::create($data);

        // 2. Verifica se é LEAD e se tem interesse em serviço
        if (($data['tipo'] ?? '') === 'lead' && !empty($data['servico_interesse'])) {
            $observacoes = [];

            if (!empty($data['mensagem_inicial'])) {
                $observacoes[] = "💬 Mensagem Inicial:\n" . $data['mensagem_inicial'];
            }

            $observacoes[] = "📅 Criado manualmente pelo Admin em " . now()->format('d/m/Y H:i');

            // 3. Cria o Orçamento (Oportunidade)
            \App\Models\Orcamento::create([
                'cadastro_id' => $record->id,
                'data_orcamento' => now(),
                'data_validade' => now()->addDays(7),
                'status' => 'rascunho',
                'etapa_funil' => 'novo',
                'tipo_servico' => $data['servico_interesse'],
                'criado_por' => auth()->user()->name ?? 'Admin',
                'valor_total' => 0.00,
                'observacoes' => implode("\n\n", $observacoes),
            ]);

            // Notifica sucesso visualmente
            \Filament\Notifications\Notification::make()
                ->title('Lead e Oportunidade criados!')
                ->body('O cliente foi cadastrado e um orçamento foi gerado no funil.')
                ->success()
                ->send();
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
