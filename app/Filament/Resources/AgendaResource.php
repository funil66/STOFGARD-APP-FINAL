<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgendaResource\Pages;
use App\Models\Agenda;
use App\Models\Cadastro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;

class AgendaResource extends Resource
{
    protected static ?string $model = Agenda::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Agenda';

    protected static ?string $modelLabel = 'Agendamento';

    protected static ?string $pluralModelLabel = 'Agendamentos';

    protected static ?string $navigationGroup = 'Operacional';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Agendamento')
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Higienização - João Silva')
                            ->columnSpan(2),

                        Forms\Components\Select::make('tipo')
                            ->label('Tipo de Serviço')
                            ->options([
                                'servico' => '🧼 Serviço',
                                'visita' => '👁️ Visita Técnica',
                                'reuniao' => '🤝 Reunião',
                                'outro' => '📌 Outro',
                            ])
                            ->default('servico')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'agendado' => '📅 Agendado',
                                'em_andamento' => '🔄 Em Andamento',
                                'concluido' => '✅ Concluído',
                                'cancelado' => '❌ Cancelado',
                            ])
                            ->default('agendado')
                            ->required()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('🕒 Data e Horário')
                    ->description('Defina quando o agendamento acontecerá')
                    ->schema([
                        Forms\Components\DateTimePicker::make('data_hora_inicio')
                            ->label('Data/Hora Início')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now()->addHours(1)->setMinutes(0))
                            ->helperText('Horário de início da atividade')
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('data_hora_fim')
                            ->label('Data/Hora Fim')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now()->addHours(3)->setMinutes(0))
                            ->helperText('Horário previsto de término')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('dia_inteiro')
                            ->label('Evento de Dia Inteiro')
                            ->default(false)
                            ->helperText('Marque se o agendamento ocupar o dia todo')
                            ->columnSpan(2),
                    ])->columns(2),

                Forms\Components\Section::make('🔗 Vínculos e Relacionamentos')
                    ->description('Associe este agendamento a um cliente, OS ou orçamento')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('cadastro_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nome', fn(Builder $query) => $query->where('tipo', 'cliente'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nome')->required(),
                                Forms\Components\TextInput::make('celular')->mask('(99) 99999-9999'),
                                Forms\Components\Select::make('tipo')->options(['cliente' => 'Cliente'])->default('cliente')->hidden(),
                            ])
                            ->columnSpan(2),

                        Forms\Components\Select::make('ordem_servico_id')
                            ->label('Ordem de Serviço')
                            ->relationship('ordemServico', 'numero_os')
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\Select::make('orcamento_id')
                            ->label('Orçamento')
                            ->relationship('orcamento', 'numero')
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('📍 Localização e Descrição')
                    ->description('Informe onde e o que será realizado')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Textarea::make('local')
                            ->label('Local do Serviço')
                            ->rows(2)
                            ->placeholder('Ex: Rua das Flores, 123 - Centro - Ribeirão Preto/SP')
                            ->helperText('Endereço completo onde o serviço será executado')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição Detalhada')
                            ->rows(3)
                            ->placeholder('Descreva os detalhes do serviço, materiais necessários, observações importantes...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações Internas')
                            ->rows(2)
                            ->placeholder('Anotações visíveis apenas pela equipe')
                            ->helperText('⚠️ Estas informações não serão visíveis para o cliente')
                            ->columnSpanFull(),

                        Forms\Components\ColorPicker::make('cor')
                            ->label('Cor no Calendário')
                            ->default('#3b82f6')
                            ->helperText('Escolha uma cor para identificar visualmente no calendário')
                            ->columnSpan(1),
                    ]),

                Forms\Components\Section::make('✅ Checklist de Tarefas')
                    ->description('Lista de tarefas a serem executadas neste agendamento')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('extra_attributes.tarefas')
                            ->label('')
                            ->schema([
                                Forms\Components\Checkbox::make('concluida')
                                    ->label('Concluída')
                                    ->inline(false),
                                Forms\Components\TextInput::make('descricao')
                                    ->label('Descrição da Tarefa')
                                    ->required()
                                    ->placeholder('Ex: Separar equipamentos')
                                    ->columnSpan(2),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('➕ Adicionar Tarefa')
                            ->columnSpanFull()
                            ->grid(1),
                    ]),

                Forms\Components\Section::make('🔔 Lembretes e Notificações')
                    ->description('Configure quando você quer ser lembrado deste agendamento')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('minutos_antes_lembrete')
                                ->label('Lembrete Antes do Evento')
                                ->options([
                                    15 => '15 minutos antes',
                                    30 => '30 minutos antes',
                                    60 => '1 hora antes',
                                    120 => '2 horas antes',
                                    1440 => '1 dia antes',
                                    2880 => '2 dias antes',
                                ])
                                ->default(60)
                                ->helperText('Sistema enviará notificação no tempo selecionado'),
                            Forms\Components\Toggle::make('lembrete_enviado')
                                ->label('Lembrete já enviado')
                                ->disabled()
                                ->helperText('Marcado automaticamente após envio'),
                        ]),
                    ]),

                Forms\Components\Section::make('Central de Arquivos')
                    ->description('Envie fotos, documentos e comprovantes (Máx: 20MB).')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('arquivos')
                            ->label('Arquivos e Mídia')
                            ->collection('arquivos')
                            ->multiple()
                            ->disk('public')
                            ->maxSize(20480)
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Hidden::make('criado_por')
                    ->default(fn() => Auth::id() ?? 1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data_hora_inicio')
                    ->label('📅 Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn($record) => $record->titulo),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-m-user')
                    ->placeholder('Não vinculado'),

                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'servico' => 'info',
                        'visita' => 'warning',
                        'reuniao' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'servico' => 'Serviço',
                        'visita' => 'Visita',
                        'reuniao' => 'Reunião',
                        default => 'Outro',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'concluido' => 'success',
                        'em_andamento' => 'warning',
                        'cancelado' => 'danger',
                        default => 'info',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'agendado' => 'Agendado',
                        'em_andamento' => 'Em Andamento',
                        'concluido' => 'Concluído',
                        'cancelado' => 'Cancelado',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('local')
                    ->label('Local')
                    ->icon('heroicon-m-map-pin')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ordemServico.numero_os')
                    ->label('OS')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_hora_inicio', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'agendado' => 'Agendado',
                        'em_andamento' => 'Em Andamento',
                        'concluido' => 'Concluído',
                        'cancelado' => 'Cancelado',
                    ]),

                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'servico' => 'Serviço',
                        'visita' => 'Visita',
                        'reuniao' => 'Reunião',
                        'outro' => 'Outro',
                    ]),

                Tables\Filters\Filter::make('data_hora_inicio')
                    ->form([
                        Forms\Components\DatePicker::make('data_de')
                            ->label('De'),
                        Forms\Components\DatePicker::make('data_ate')
                            ->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['data_de'],
                                fn(Builder $query, $date): Builder => $query->whereDate('data_hora_inicio', '>=', $date),
                            )
                            ->when(
                                $data['data_ate'],
                                fn(Builder $query, $date): Builder => $query->whereDate('data_hora_inicio', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // 1. VISUALIZAR (Olho)
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Ver Detalhes'),
                
                // 2. EDITAR (Lápis)
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar'),

                // 3. CONCLUIR (Check Verde)
                Tables\Actions\Action::make('concluir')
                    ->label('')
                    ->tooltip('Marcar como Concluído')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Agenda $record) => !in_array($record->status, ['concluido', 'cancelado']))
                    ->requiresConfirmation()
                    ->action(function (Agenda $record) {
                        $record->update(['status' => 'concluido']);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Agendamento Concluído!')
                            ->send();
                    }),

                // 4. CANCELAR (X Vermelho)
                Tables\Actions\Action::make('cancelar')
                    ->label('')
                    ->tooltip('Cancelar Agendamento')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Agenda $record) => $record->status === 'agendado')
                    ->requiresConfirmation()
                    ->action(function (Agenda $record) {
                        $record->update(['status' => 'cancelado']);
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Agendamento Cancelado')
                            ->send();
                    }),

                // 5. EXCLUIR (Lixeira)
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('marcar_concluido')
                        ->label('Marcar como Concluído')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 'concluido'])),

                    Tables\Actions\BulkAction::make('marcar_cancelado')
                        ->label('Marcar como Cancelado')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 'cancelado'])),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\CalendarioAgenda::route('/'),
            'create' => Pages\CreateAgenda::route('/create'),
            'edit' => Pages\EditAgenda::route('/{record}/edit'),
            'view' => Pages\ViewAgenda::route('/{record}'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ===== CABEÇALHO DO AGENDAMENTO =====
                InfolistSection::make()
                    ->schema([
                        InfolistGrid::make(3)->schema([
                            TextEntry::make('titulo')
                                ->label('Título')
                                ->weight('bold')
                                ->columnSpan(2)
                                ->size(TextEntry\TextEntrySize::Large),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'concluido' => 'success',
                                    'cancelado' => 'danger',
                                    'em_andamento' => 'warning',
                                    default => 'info',
                                })
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'agendado' => '📅 Agendado',
                                    'em_andamento' => '🔄 Em Andamento',
                                    'concluido' => '✅ Concluído',
                                    'cancelado' => '❌ Cancelado',
                                    default => $state,
                                }),
                        ]),
                        InfolistGrid::make(4)->schema([
                            TextEntry::make('tipo')
                                ->label('Tipo')
                                ->badge()
                                ->color(fn($state) => match ($state) {
                                    'servico' => 'info',
                                    'visita' => 'warning',
                                    'reuniao' => 'success',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'servico' => '🧼 Serviço',
                                    'visita' => '👁️ Visita Técnica',
                                    'reuniao' => '🤝 Reunião',
                                    'outro' => '📌 Outro',
                                    default => $state,
                                }),
                            TextEntry::make('data_hora_inicio')
                                ->label('Início')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-clock'),
                            TextEntry::make('data_hora_fim')
                                ->label('Término')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-clock'),
                            TextEntry::make('dia_inteiro')
                                ->label('Dia Inteiro')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'gray')
                                ->formatStateUsing(fn($state) => $state ? 'Sim' : 'Não'),
                        ]),
                    ]),

                // ===== VINCULAÇÕES =====
                InfolistSection::make('🔗 Vinculações')
                    ->schema([
                        InfolistGrid::make(3)->schema([
                            TextEntry::make('cadastro.nome')
                                ->label('Cliente')
                                ->icon('heroicon-m-user')
                                ->url(fn($record) => $record->cadastro_url)
                                ->color('primary')
                                ->placeholder('Não vinculado'),
                            TextEntry::make('tipo_servico_exibicao')
                                ->label('Tipo de Serviço')
                                ->icon('heroicon-m-wrench-screwdriver')
                                ->badge()
                                ->color('info')
                                ->getStateUsing(function ($record) {
                                    // Prioriza OS, depois Orçamento
                                    if ($record->ordem_servico_id && $record->ordemServico) {
                                        return \App\Services\ServiceTypeManager::getLabel($record->ordemServico->tipo_servico ?? 'servico');
                                    }
                                    if ($record->orcamento_id && $record->orcamento) {
                                        return \App\Services\ServiceTypeManager::getLabel($record->orcamento->tipo_servico ?? 'servico');
                                    }
                                    return null;
                                })
                                ->placeholder('Não vinculado'),
                            TextEntry::make('orcamento.numero')
                                ->label('Orçamento')
                                ->icon('heroicon-m-document-text')
                                ->url(fn($record) => $record->orcamento_id 
                                    ? \App\Filament\Resources\OrcamentoResource::getUrl('view', ['record' => $record->orcamento_id]) 
                                    : null)
                                ->color('primary')
                                ->placeholder('Não vinculado'),
                        ]),
                        InfolistGrid::make(1)->schema([
                            TextEntry::make('ordemServico.numero_os')
                                ->label('Ordem de Serviço')
                                ->icon('heroicon-m-clipboard-document-check')
                                ->url(fn($record) => $record->ordem_servico_id 
                                    ? \App\Filament\Resources\OrdemServicoResource::getUrl('view', ['record' => $record->ordem_servico_id]) 
                                    : null)
                                ->color('primary')
                                ->placeholder('Não vinculada'),
                        ]),
                    ])
                    ->collapsible(),

                // ===== LOCALIZAÇÃO =====
                InfolistSection::make('📍 Local do Serviço')
                    ->schema([
                        InfolistGrid::make(1)->schema([
                            TextEntry::make('local')
                                ->label('')
                                ->icon('heroicon-m-map-pin')
                                ->url(fn($record) => $record->endereco_maps, true)
                                ->placeholder('Local não informado'),
                            TextEntry::make('endereco_completo')
                                ->label('Endereço Completo')
                                ->placeholder('Endereço não informado')
                                ->visible(fn($record) => $record->endereco_completo && $record->endereco_completo !== $record->local),
                        ]),
                    ])
                    ->collapsible(),

                // ===== DESCRIÇÃO E OBSERVAÇÕES =====
                InfolistSection::make('📝 Detalhes')
                    ->schema([
                        InfolistGrid::make(1)->schema([
                            TextEntry::make('descricao')
                                ->label('Descrição')
                                ->markdown()
                                ->placeholder('Sem descrição'),
                            TextEntry::make('observacoes')
                                ->label('Observações Internas')
                                ->markdown()
                                ->placeholder('Sem observações'),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // ===== CHECKLIST DE TAREFAS =====
                InfolistSection::make('✅ Checklist de Tarefas')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('extra_attributes.tarefas')
                            ->label('')
                            ->schema([
                                Infolists\Components\IconEntry::make('concluida')
                                    ->label('')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                                TextEntry::make('descricao')
                                    ->label('Tarefa')
                                    ->weight(fn($record) => $record['concluida'] ?? false ? 'normal' : 'bold')
                                    ->color(fn($record) => $record['concluida'] ?? false ? 'gray' : 'primary'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                        TextEntry::make('tarefas_vazio')
                            ->label('')
                            ->default('Nenhuma tarefa cadastrada')
                            ->visible(fn($record) => empty($record->extra_attributes['tarefas'] ?? [])),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn($record) => !empty($record->extra_attributes['tarefas'] ?? []) || true),

                // ===== LEMBRETES E NOTIFICAÇÕES =====
                InfolistSection::make('🔔 Lembretes')
                    ->schema([
                        InfolistGrid::make(2)->schema([
                            TextEntry::make('minutos_antes_lembrete')
                                ->label('Lembrete Configurado')
                                ->badge()
                                ->formatStateUsing(fn($state) => match((int)$state) {
                                    15 => '15 min antes',
                                    30 => '30 min antes',
                                    60 => '1h antes',
                                    120 => '2h antes',
                                    1440 => '1 dia antes',
                                    2880 => '2 dias antes',
                                    default => $state . ' min antes',
                                }),
                            TextEntry::make('lembrete_enviado')
                                ->label('Status do Lembrete')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'warning')
                                ->formatStateUsing(fn($state) => $state ? '✅ Enviado' : '⏳ Pendente'),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // ===== INFORMAÇÕES DO SISTEMA =====
                InfolistSection::make('ℹ️ Informações do Sistema')
                    ->schema([
                        InfolistGrid::make(3)->schema([
                            TextEntry::make('created_at')
                                ->label('Criado em')
                                ->dateTime('d/m/Y H:i'),
                            TextEntry::make('updated_at')
                                ->label('Atualizado em')
                                ->dateTime('d/m/Y H:i'),
                            TextEntry::make('cor')
                                ->label('Cor no Calendário')
                                ->badge()
                                ->color(fn($state) => $state ?? 'gray'),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'agendado')
            ->whereDate('data_hora_inicio', '>=', now())
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
