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

                Forms\Components\Section::make('Data e Horário')
                    ->schema([
                        Forms\Components\DateTimePicker::make('data_hora_inicio')
                            ->label('Data/Hora Início')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now()->addHours(1)->setMinutes(0))
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('data_hora_fim')
                            ->label('Data/Hora Fim')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now()->addHours(3)->setMinutes(0))
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('dia_inteiro')
                            ->label('Dia Inteiro')
                            ->default(false)
                            ->columnSpan(2),
                    ])->columns(2),

                Forms\Components\Section::make('Vinculações')
                    ->description('Vincular a cliente, OS ou orçamento')
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

                Forms\Components\Section::make('Localização e Detalhes')
                    ->schema([
                        Forms\Components\Textarea::make('local')
                            ->label('Local')
                            ->rows(2)
                            ->placeholder('Endereço onde o serviço será realizado')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações Internas')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\ColorPicker::make('cor')
                            ->label('Cor no Calendário')
                            ->default('#3b82f6')
                            ->columnSpan(1),
                    ])->collapsible(),

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
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->weight('bold')
                    ->limit(40),

                Tables\Columns\TextColumn::make('cliente.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->toggleable(),

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

                Tables\Columns\TextColumn::make('ordemServico.numero_os')
                    ->label('OS')
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('concluir')
                    ->label('Concluir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Agenda $record) => $record->status !== 'concluido')
                    ->requiresConfirmation()
                    ->action(function (Agenda $record) {
                        $record->update(['status' => 'concluido']);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Agendamento Concluído!')
                            ->send();
                    }),

                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
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

                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('Baixar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn(Agenda $record) => route('agenda.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('Baixar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(Agenda $record) => route('agenda.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make()->label('')->tooltip('Visualizar'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
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
