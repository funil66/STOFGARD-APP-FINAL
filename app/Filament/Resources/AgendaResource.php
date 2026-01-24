<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgendaResource\Pages;
use App\Models\Agenda;
use App\Models\Cliente;
use App\Models\GoogleToken;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AgendaResource extends Resource
{
    protected static ?string $model = Agenda::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Agenda';

    protected static ?string $modelLabel = 'Evento';

    protected static ?string $pluralModelLabel = 'Agenda';

    protected static ?string $navigationGroup = 'Operacional';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Seção: Informações do Evento
                Forms\Components\Section::make('Informações do Evento')
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Visita ao cliente, Serviço agendado...')
                            ->columnSpan(2),

                        Forms\Components\Select::make('tipo')
                            ->label('Tipo de Evento')
                            ->options([
                                'visita' => '🚗 Visita',
                                'servico' => '🧼 Serviço',
                                'follow_up' => '📞 Follow-up',
                                'reuniao' => '👥 Reunião',
                                'outro' => '📋 Outro',
                            ])
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'agendado' => 'Agendado',
                                'confirmado' => 'Confirmado',
                                'em_andamento' => 'Em Andamento',
                                'concluido' => 'Concluído',
                                'cancelado' => 'Cancelado',
                            ])
                            ->default('agendado')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->placeholder('Detalhes sobre o evento...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Seção: Data e Hora
                Forms\Components\Section::make('Data e Hora')
                    ->schema([
                        Forms\Components\DateTimePicker::make('data_hora_inicio')
                            ->label('Data/Hora Início')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now())
                            ->live()
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('data_hora_fim')
                            ->label('Data/Hora Fim')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now()->addHour())
                            ->after('data_hora_inicio')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('dia_inteiro')
                            ->label('Evento de dia inteiro')
                            ->helperText('Desativa horários específicos')
                            ->live()
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                // Seção: Vinculações
                Forms\Components\Section::make('Vinculações')
                    ->schema([
                        Forms\Components\Select::make('cadastro_id')
                            ->label('Cadastro (Cliente, Loja ou Vendedor)')
                            ->options(function () {
                                $clientes = \App\Models\Cliente::all()->mapWithKeys(fn($c) => [
                                    'cliente_' . $c->id => '🧑 Cliente: ' . $c->nome
                                ]);
                                $parceiros = \App\Models\Parceiro::all()->mapWithKeys(fn($p) => [
                                    'parceiro_' . $p->id => ($p->tipo === 'loja' ? '🏪 Loja: ' : '🧑‍💼 Vendedor: ') . $p->nome
                                ]);
                                return $clientes->union($parceiros)->toArray();
                            })
                            ->searchable()
                            ->required(false)
                            ->helperText('Selecione um cliente, loja ou vendedor para este evento.')
                            ->columnSpan(1),

                        Forms\Components\Select::make('ordem_servico_id')
                            ->label('Ordem de Serviço')
                            ->relationship('ordemServico', 'numero_os')
                            ->searchable()
                            ->preload()
                            ->helperText('Vincular a uma OS existente')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Seção: Localização
                Forms\Components\Section::make('Localização')
                    ->schema([
                        Forms\Components\TextInput::make('local')
                            ->label('Local')
                            ->placeholder('Ex: Escritório, Residência cliente...')
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('endereco_completo')
                            ->label('Endereço Completo')
                            ->rows(2)
                            ->placeholder('Rua, número, bairro, cidade...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Seção: Lembretes e Configurações
                Forms\Components\Section::make('Lembretes e Configurações')
                    ->schema([
                        Forms\Components\Select::make('minutos_antes_lembrete')
                            ->label('Lembrete antes do evento')
                            ->options([
                                15 => '15 minutos antes',
                                30 => '30 minutos antes',
                                60 => '1 hora antes',
                                120 => '2 horas antes',
                                1440 => '1 dia antes',
                            ])
                            ->default(60)
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\ColorPicker::make('cor')
                            ->label('Cor do Evento')
                            ->default('#3b82f6')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->placeholder('Anotações adicionais...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Indicador de cor
                Tables\Columns\ColorColumn::make('cor')
                    ->label('')
                    ->width('4px'),

                // Título e descrição
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Evento')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Agenda $record): string => $record->descricao ? \Str::limit($record->descricao, 50) : '')
                    ->wrap(),

                // Tipo com badge
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'visita' => 'info',
                        'servico' => 'success',
                        'follow_up' => 'warning',
                        'reuniao' => 'gray',
                        'outro' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'visita' => '🚗 Visita',
                        'servico' => '🧼 Serviço',
                        'follow_up' => '📞 Follow-up',
                        'reuniao' => '👥 Reunião',
                        'outro' => '📋 Outro',
                        default => $state,
                    }),

                // Data e hora
                Tables\Columns\TextColumn::make('data_hora_inicio')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (Agenda $record): string => $record->dia_inteiro ? '🕐 Dia Inteiro' :
                        'Até '.$record->data_hora_fim->format('H:i')
                    ),

                // Status com badge colorido
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'agendado' => 'gray',
                        'confirmado' => 'info',
                        'em_andamento' => 'warning',
                        'concluido' => 'success',
                        'cancelado' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                // Cliente vinculado
                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cadastro')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—')
                    ->url(fn (Agenda $record) => $record->cadastro_url)
                    ->openUrlInNewTab(),

                // Local (link to maps if available)
                Tables\Columns\TextColumn::make('local')
                    ->label('Local')
                    ->searchable()
                    ->toggleable()
                    ->limit(30)
                    ->placeholder('—')
                    ->url(fn (Agenda $record) => $record->endereco_maps)
                    ->openUrlInNewTab(),

                // OS vinculada
                Tables\Columns\TextColumn::make('ordemServico.numero_os')
                    ->label('OS')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                // Google Calendar sync
                Tables\Columns\IconColumn::make('google_event_id')
                    ->label('Google')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Criado por
                Tables\Columns\TextColumn::make('criado_por')
                    ->label('Criado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_hora_inicio', 'desc')
            ->filters([
                // Filtro por tipo
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo de Evento')
                    ->options([
                        'visita' => '🚗 Visita',
                        'servico' => '🧼 Serviço',
                        'follow_up' => '📞 Follow-up',
                        'reuniao' => '👥 Reunião',
                        'outro' => '📋 Outro',
                    ]),

                // Filtro por status
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'agendado' => 'Agendado',
                        'confirmado' => 'Confirmado',
                        'em_andamento' => 'Em Andamento',
                        'concluido' => 'Concluído',
                        'cancelado' => 'Cancelado',
                    ]),

                // Filtro por período
                Tables\Filters\Filter::make('hoje')
                    ->label('Hoje')
                    ->query(fn (Builder $query): Builder => $query->whereDate('data_hora_inicio', today())),

                Tables\Filters\Filter::make('esta_semana')
                    ->label('Esta Semana')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('data_hora_inicio', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ])),

                Tables\Filters\Filter::make('proximo_mes')
                    ->label('Próximo Mês')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('data_hora_inicio', [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ])),
            ])
            ->actions([
                // Ação: Marcar como concluído
                Tables\Actions\Action::make('concluir')
                    ->label('Concluir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Agenda $record): bool => ! in_array($record->status, ['concluido', 'cancelado']))
                    ->requiresConfirmation()
                    ->action(function (Agenda $record) {
                        $record->update([
                            'status' => 'concluido',
                            'atualizado_por' => strtoupper(substr(Auth::user()->name, 0, 2)),
                        ]);
                    }),

                // Ação: Sincronizar com Google Calendar
                Tables\Actions\Action::make('sincronizar_google')
                    ->label('Sync Google')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Agenda $record): bool => ! $record->google_event_id)
                    ->action(function (Agenda $record) {
                        $token = GoogleToken::where('user_id', Auth::id())->first();

                        if (! $token) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sem credenciais do Google')
                                ->body('Conecte o Google Calendar em Configurações antes de sincronizar.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $googleService = new \App\Services\GoogleCalendarService($token);
                        $googleEventId = $googleService->createEvent($record);

                        if ($googleEventId) {
                            $record->update(['google_event_id' => $googleEventId]);
                            \Filament\Notifications\Notification::make()
                                ->title('Sincronizado com Google Calendar')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro ao sincronizar')
                                ->body('Verifique sua conexão com o Google Calendar')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    // Ação em massa: Marcar como concluído
                    Tables\Actions\BulkAction::make('concluir_multiplos')
                        ->label('Concluir Selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update([
                                'status' => 'concluido',
                                'atualizado_por' => strtoupper(substr(Auth::user()->name, 0, 2)),
                            ]));
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informações do Evento')
                    ->schema([
                        Infolists\Components\TextEntry::make('titulo')
                            ->label('Título')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('tipo')
                            ->label('Tipo')
                            ->badge(),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge(),

                        Infolists\Components\TextEntry::make('descricao')
                            ->label('Descrição')
                            ->placeholder('Nenhuma descrição')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Data e Hora')
                    ->schema([
                        Infolists\Components\TextEntry::make('data_hora_inicio')
                            ->label('Início')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('data_hora_fim')
                            ->label('Fim')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\IconEntry::make('dia_inteiro')
                            ->label('Dia Inteiro')
                            ->boolean(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Infolists\Components\Section::make('Vinculações')
                    ->schema([
                        Infolists\Components\TextEntry::make('cadastro.nome')
                            ->label('Cadastro'),

                        Infolists\Components\TextEntry::make('ordem_servico.numero_os')
                            ->label('Ordem de Serviço')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('Localização')
                    ->schema([
                        Infolists\Components\TextEntry::make('local')
                            ->label('Local')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('endereco_completo')
                            ->label('Endereço')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Informações do Sistema')
                    ->schema([
                        Infolists\Components\TextEntry::make('criado_por')
                            ->label('Criado por'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última atualização')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgendas::route('/'),
            'create' => Pages\CreateAgenda::route('/create'),
            'view' => Pages\ViewAgenda::route('/{record}'),
            'edit' => Pages\EditAgenda::route('/{record}/edit'),
        ];
    }
}
