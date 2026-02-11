<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgendaResource\Pages;
use App\Models\Agenda;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Support\Filament\StofgardTable;

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
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('id_parceiro')
                            ->label('ID Parceiro')
                            ->placeholder('Identificação da loja/vendedor')
                            ->maxLength(255)
                            ->columnSpan(2),
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
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('cadastro_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nome')
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
                            ->columnSpan(1),

                        Forms\Components\Select::make('orcamento_id')
                            ->label('Orçamento')
                            ->relationship('orcamento', 'numero')
                            ->searchable()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('📍 Localização e Descrição')
                    ->description('Endereço e detalhes do agendamento')
                    ->collapsible()
                    ->collapsed()
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
                    ->description('Lista de verificação para este agendamento')
                    ->collapsible()
                    ->collapsed()
                    ->description('Crie uma lista de tarefas para organizar este agendamento')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('extra_attributes.tarefas')
                            ->label('Lista de Tarefas')
                            ->schema([
                                Forms\Components\TextInput::make('descricao')
                                    ->label('Descrição da Tarefa')
                                    ->required()
                                    ->placeholder('Ex: Separar equipamentos, Confirmar com cliente, Preparar materiais')
                                    ->columnSpan(2),
                                Forms\Components\Checkbox::make('concluida')
                                    ->label('Concluída')
                                    ->default(false)
                                    ->inline(false),
                            ])
                            ->columns(['default' => 1, 'sm' => 3])
                            ->defaultItems(0)
                            ->addActionLabel('➕ Adicionar Nova Tarefa')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['descricao'] ?? null)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('🔔 Lembretes e Notificações')
                    ->description('Configure alertas para este agendamento')
                    ->collapsible()
                    ->collapsed()
                    ->description('Receba um lembrete antes do agendamento acontecer')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('minutos_antes_lembrete')
                                ->label('⏰ Enviar Lembrete')
                                ->options([
                                    15 => '🟢 15 minutos antes',
                                    30 => '🟢 30 minutos antes',
                                    60 => '🟡 1 hora antes',
                                    120 => '🟡 2 horas antes',
                                    1440 => '🔵 1 dia antes',
                                    2880 => '🔵 2 dias antes',
                                ])
                                ->default(60)
                                ->native(false)
                                ->helperText('Sistema enviará notificação automática automática no tempo selecionado'),
                            Forms\Components\Toggle::make('lembrete_enviado')
                                ->label('✅ Status do Lembrete')
                                ->disabled()
                                ->helperText('Marcado automaticamente pelo sistema após envio')
                                ->visible(fn($record) => $record?->lembrete_enviado ?? false),
                        ]),
                    ]),

                Forms\Components\Section::make('Central de Arquivos')
                    ->description('Anexe documentos, fotos e arquivos')
                    ->collapsible()
                    ->collapsed()
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
                // MOBILE: Data + Título combinados
                Tables\Columns\TextColumn::make('data_hora_inicio')
                    ->label('Data')
                    ->dateTime('d/m H:i')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->description(fn($record) => $record->titulo ? mb_substr($record->titulo, 0, 25) . (mb_strlen($record->titulo) > 25 ? '...' : '') : '-')
                    ->icon('heroicon-o-calendar-days'),

                // DESKTOP ONLY: Título separado
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(25)
                    ->visibleFrom('md'),

                // DESKTOP ONLY: Cliente
                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->icon('heroicon-m-user')
                    ->placeholder('-')
                    ->visibleFrom('lg'),

                // SEMPRE VISÍVEL: Tipo com ícone
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'servico' => '🧼',
                        'visita' => '👁️',
                        'reuniao' => '🤝',
                        default => '📌',
                    })
                    ->tooltip(fn(string $state): string => match ($state) {
                        'servico' => 'Serviço',
                        'visita' => 'Visita',
                        'reuniao' => 'Reunião',
                        default => 'Outro',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'servico' => 'info',
                        'visita' => 'warning',
                        'reuniao' => 'success',
                        default => 'gray',
                    }),

                // SEMPRE VISÍVEL: Status com ícone
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'concluido' => '✓',
                        'em_andamento' => '⏳',
                        'cancelado' => '✗',
                        default => '📅',
                    })
                    ->tooltip(fn(string $state): string => match ($state) {
                        'agendado' => 'Agendado',
                        'em_andamento' => 'Em Andamento',
                        'concluido' => 'Concluído',
                        'cancelado' => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'concluido' => 'success',
                        'em_andamento' => 'warning',
                        'cancelado' => 'danger',
                        default => 'info',
                    }),

                // DESKTOP ONLY: Local
                Tables\Columns\TextColumn::make('local')
                    ->label('Local')
                    ->icon('heroicon-m-map-pin')
                    ->limit(20)
                    ->visibleFrom('xl'),

                // DESKTOP ONLY: OS vinculada
                Tables\Columns\TextColumn::make('ordemServico.numero_os')
                    ->label('OS')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->visibleFrom('xl'),
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
            ->actions(
                StofgardTable::defaultActions(
                    view: true,
                    edit: true,
                    delete: true,
                    extraActions: [
                        // Concluir
                        Tables\Actions\Action::make('concluir')
                            ->label('Concluir')
                            ->tooltip('Marcar Concluído')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            // ->iconButton() // Removed iconButton to fit better in dropdown or keep if preferred, but StofgardTable handles basic ones. Custom ones can stay as is.
                            // Actually, StofgardTable merges extraActions. If we want them in the dropdown, we just pass them.
                            ->visible(fn(Agenda $record) => !in_array($record->status, ['concluido', 'cancelado']))
                            ->requiresConfirmation()
                            ->action(function (Agenda $record) {
                                $record->update(['status' => 'concluido']);
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Concluído!')
                                    ->send();
                            }),

                        // Cancelar
                        Tables\Actions\Action::make('cancelar')
                            ->label('Cancelar')
                            ->tooltip('Cancelar')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            // ->iconButton()
                            ->visible(fn(Agenda $record) => $record->status === 'agendado')
                            ->requiresConfirmation()
                            ->action(function (Agenda $record) {
                                $record->update(['status' => 'cancelado']);
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('Cancelado')
                                    ->send();
                            }),
                    ]
                )
            )
            ->bulkActions(
                StofgardTable::defaultBulkActions([
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
                ])
            );
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
                        InfolistGrid::make(4)->schema([
                            TextEntry::make('titulo')
                                ->label('Título')
                                ->weight('bold')->columnSpan(2)
                                ->size(TextEntry\TextEntrySize::Large),
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
                                    'visita' => '👁️ Visita',
                                    'reuniao' => '🤝 Reunião',
                                    'outro' => '📌 Outro',
                                    default => $state,
                                }),
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
                            TextEntry::make('data_hora_inicio')
                                ->label('Início')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-clock'),
                            TextEntry::make('data_hora_fim')
                                ->label('Término')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-clock'),
                            TextEntry::make('cadastro.nome')
                                ->label('Cliente')
                                ->icon('heroicon-m-user')
                                ->placeholder('Não informado'),
                            TextEntry::make('ordemServico.numero_os')
                                ->label('OS')
                                ->icon('heroicon-m-clipboard-document-check')
                                ->placeholder('-'),
                            TextEntry::make('id_parceiro')
                                ->label('ID Parceiro')
                                ->badge()
                                ->color('info')
                                ->placeholder('-'),
                        ]),
                    ]),

                // ===== RESUMO =====
                InfolistSection::make('📊 Resumo do Agendamento')
                    ->schema([
                        InfolistGrid::make(4)->schema([
                            TextEntry::make('duracao')
                                ->label('⏱️ Duração')
                                ->weight('bold')
                                ->state(fn($record) => $record->data_hora_inicio && $record->data_hora_fim
                                    ? \Carbon\Carbon::parse($record->data_hora_inicio)->diff(\Carbon\Carbon::parse($record->data_hora_fim))->format('%H:%I')
                                    : '-')
                                ->suffix(' horas')
                                ->color('info'),
                            TextEntry::make('dia_inteiro')
                                ->label('📅 Dia Inteiro')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'gray')
                                ->formatStateUsing(fn($state) => $state ? 'Sim' : 'Não'),
                            TextEntry::make('lembrete_config')
                                ->label('🔔 Lembrete')
                                ->badge()
                                ->formatStateUsing(fn($record) => match ((int) $record->minutos_antes_lembrete) {
                                    15 => '15 min',
                                    30 => '30 min',
                                    60 => '1h',
                                    120 => '2h',
                                    1440 => '1 dia',
                                    2880 => '2 dias',
                                    default => ($record->minutos_antes_lembrete ?? 0) . ' min',
                                })
                                ->color('warning'),
                            TextEntry::make('lembrete_status')
                                ->label('📬 Status Envio')
                                ->badge()
                                ->color(fn($record) => $record->lembrete_enviado ? 'success' : 'warning')
                                ->formatStateUsing(fn($record) => $record->lembrete_enviado ? '✅ Enviado' : '⏳ Pendente'),
                        ]),
                    ])
                    ->collapsible(),

                // ===== ABAS =====
                \Filament\Infolists\Components\Tabs::make('Detalhes')
                    ->tabs([
                        // ABA 1: DETALHES
                        \Filament\Infolists\Components\Tabs\Tab::make('📝 Detalhes')
                            ->schema([
                                InfolistGrid::make(1)->schema([
                                    TextEntry::make('local')
                                        ->label('Local')
                                        ->icon('heroicon-m-map-pin')
                                        ->url(fn($record) => $record->endereco_maps, true)
                                        ->placeholder('Local não informado'),
                                    TextEntry::make('descricao')
                                        ->label('Descrição')
                                        ->markdown()
                                        ->placeholder('Sem descrição'),
                                    TextEntry::make('observacoes')
                                        ->label('Observações Internas')
                                        ->markdown()
                                        ->placeholder('Sem observações'),
                                ]),
                            ]),

                        // ABA 2: VINCULAÇÕES
                        \Filament\Infolists\Components\Tabs\Tab::make('🔗 Vinculações')
                            ->schema([
                                InfolistGrid::make(2)->schema([
                                    TextEntry::make('orcamento.numero')
                                        ->label('Orçamento')
                                        ->icon('heroicon-m-document-text')
                                        ->url(fn($record) => $record->orcamento_id
                                            ? \App\Filament\Resources\OrcamentoResource::getUrl('view', ['record' => $record->orcamento_id])
                                            : null)
                                        ->color('primary')
                                        ->placeholder('Não vinculado'),
                                    TextEntry::make('tipo_servico_exibicao')
                                        ->label('Tipo de Serviço')
                                        ->icon('heroicon-m-wrench-screwdriver')
                                        ->badge()
                                        ->color('info')
                                        ->getStateUsing(function ($record) {
                                            if ($record->ordem_servico_id && $record->ordemServico) {
                                                return \App\Services\ServiceTypeManager::getLabel($record->ordemServico->tipo_servico ?? 'servico');
                                            }
                                            if ($record->orcamento_id && $record->orcamento) {
                                                return \App\Services\ServiceTypeManager::getLabel($record->orcamento->tipo_servico ?? 'servico');
                                            }
                                            return null;
                                        })
                                        ->placeholder('Não vinculado'),
                                ]),
                            ]),

                        // ABA 3: CHECKLIST
                        \Filament\Infolists\Components\Tabs\Tab::make('✅ Checklist')
                            ->badge(fn($record) => count($record->extra_attributes['tarefas'] ?? []))
                            ->schema([
                                \Filament\Infolists\Components\RepeatableEntry::make('extra_attributes.tarefas')
                                    ->label('')
                                    ->schema([
                                        \Filament\Infolists\Components\IconEntry::make('concluida')
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
                                    ->columnSpanFull()
                                    ->hidden(fn($record) => empty($record->extra_attributes['tarefas'] ?? [])),
                                TextEntry::make('tarefas_vazio')
                                    ->label('')
                                    ->default('Nenhuma tarefa cadastrada')
                                    ->visible(fn($record) => empty($record->extra_attributes['tarefas'] ?? [])),
                            ]),

                        // ABA 4: HISTÓRICO
                        \Filament\Infolists\Components\Tabs\Tab::make('📜 Histórico')
                            ->icon('heroicon-m-clock')
                            ->badge(fn($record) => $record->audits()->count())
                            ->schema([
                                \Filament\Infolists\Components\RepeatableEntry::make('audits')
                                    ->label('')
                                    ->schema([
                                        InfolistGrid::make(4)->schema([
                                            TextEntry::make('user.name')
                                                ->label('Usuário')
                                                ->icon('heroicon-m-user')
                                                ->placeholder('Sistema'),
                                            TextEntry::make('event')
                                                ->label('Ação')
                                                ->badge()
                                                ->formatStateUsing(fn(string $state): string => match ($state) {
                                                    'created' => 'Criação',
                                                    'updated' => 'Edição',
                                                    'deleted' => 'Exclusão',
                                                    default => ucfirst($state),
                                                })
                                                ->color(fn(string $state): string => match ($state) {
                                                    'created' => 'success',
                                                    'updated' => 'warning',
                                                    'deleted' => 'danger',
                                                    default => 'gray',
                                                }),
                                            TextEntry::make('created_at')
                                                ->label('Data/Hora')
                                                ->dateTime('d/m/Y H:i:s'),
                                            TextEntry::make('ip_address')
                                                ->label('IP')
                                                ->icon('heroicon-m-globe-alt')
                                                ->copyable(),
                                        ]),
                                    ])
                                    ->grid(1)
                                    ->contained(false),
                                TextEntry::make('sem_historico')
                                    ->label('')
                                    ->default('Nenhuma alteração registrada.')
                                    ->visible(fn($record) => $record->audits()->count() === 0),
                            ]),
                    ])
                    ->columnSpanFull(),
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
