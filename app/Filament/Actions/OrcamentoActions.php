<?php

namespace App\Filament\Actions;

use App\Models\Orcamento;
use App\Services\StofgardSystem;
use Filament\Actions\Action; // For page actions
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction; // For table actions

class OrcamentoActions
{
    /**
     * Retorna a ação de Aprovar Orçamento para uso em Pages (View/Edit)
     */
    public static function getAprovarAction(): Action
    {
        return Action::make('aprovar')
            ->label('Aprovar e Gerar OS')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn(Orcamento $record) => in_array($record->status, ['rascunho', 'pendente', 'enviado']))
            ->slideOver()
            ->modalWidth('3xl')
            ->modalHeading('Aprovar Orçamento e Gerar OS')
            ->modalDescription('Configure a data e horário do serviço. Após aprovação, será criada a Ordem de Serviço, o agendamento e o lançamento financeiro.')
            ->modalSubmitActionLabel('✓ Aprovar e Criar Registros')
            ->form(self::getAprovarFormSchema())
            ->action(fn(Orcamento $record, array $data) => self::handleAprovar($record, $data));
    }

    /**
     * Retorna a ação de Aprovar Orçamento para uso em Tables
     */
    public static function getAprovarTableAction(): TableAction
    {
        return TableAction::make('aprovar')
            ->label('Aprovar e Gerar OS')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn(Orcamento $record) => in_array($record->status, ['rascunho', 'pendente', 'enviado']))
            ->slideOver()
            ->modalWidth('3xl')
            ->modalHeading('Aprovar Orçamento e Gerar OS')
            ->modalDescription('Configure a data e horário do serviço.')
            ->modalSubmitActionLabel('✓ Aprovar e Criar Registros')
            ->form(self::getAprovarFormSchema())
            ->action(fn(Orcamento $record, array $data) => self::handleAprovar($record, $data));
    }

    /**
     * Schema do formulário de Aprovação (comum para Page e Table)
     */
    public static function getAprovarFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                ->schema([
                    Forms\Components\DateTimePicker::make('data_servico')
                        ->label('📅 Data e Hora de Início')
                        ->required() // Se é agendamento, melhor ser required, ou nullable se opcional
                        ->native(false)
                        ->displayFormat('d/m/Y H:i')
                        ->seconds(false)
                        ->helperText('Informe a data e hora prevista para início do serviço')
                        ->columnSpan(1),

                    Forms\Components\TimePicker::make('hora_fim')
                        ->label('🕐 Hora de Término (estimada)')
                        ->default('17:00')
                        ->native(false)
                        ->seconds(false)
                        ->columnSpan(1),
                ]),

            Forms\Components\Textarea::make('local_servico')
                ->label('📍 Local do Serviço')
                ->required()
                ->rows(2)
                ->default(function ($record) {
                    $cadastro = $record->cliente;
                    return $cadastro ? $cadastro->formatEnderecoCompleto() : '';
                })
                ->helperText('Endereço completo onde o serviço será realizado (pode ser editado)'),

            Forms\Components\Textarea::make('observacoes_os')
                ->label('📝 Observações para a OS')
                ->rows(3)
                ->placeholder('Observações adicionais para a Ordem de Serviço...'),
        ];
    }

    /**
     * Lógica de execução da Aprovação
     */
    public static function handleAprovar(Orcamento $record, array $data): void
    {
        try {
            $stofgard = app(StofgardSystem::class);

            // Extract Date and Time from DateTimePicker
            $dataServico = null;
            $horaInicio = null;

            if (!empty($data['data_servico'])) {
                $carbonData = \Carbon\Carbon::parse($data['data_servico']);
                $dataServico = $carbonData->format('Y-m-d');
                $horaInicio = $carbonData->format('H:i');
            }

            $stofgard->aprovarOrcamento($record, auth()->id(), [
                'data_servico' => $dataServico,
                'hora_inicio' => $horaInicio,
                'hora_fim' => $data['hora_fim'] ?? null,
                'local_servico' => $data['local_servico'] ?? null,
                'observacoes' => $data['observacoes_os'] ?? null,
            ]);

            Notification::make()
                ->title('Orçamento Aprovado!')
                ->body('A Ordem de Serviço, Agenda e Financeiro foram criados automaticamente.')
                ->success()
                ->send();

            // Tenta redirecionar se estiver em contexto HTTP, mas em TableAction isso é opcional
            // Em Page Action, o redirect pode ser útil
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao aprovar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
