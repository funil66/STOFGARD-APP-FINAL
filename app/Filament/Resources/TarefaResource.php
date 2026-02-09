<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TarefaResource\Pages;
use App\Models\Tarefa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TarefaResource extends Resource
{
    protected static ?string $model = Tarefa::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Tarefas';

    // Submódulo da Agenda
    protected static ?string $slug = 'tarefas';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes da Tarefa')
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('responsavel_id')
                                    ->label('Responsável')
                                    ->relationship('responsavel', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('prioridade')
                                    ->options([
                                        'baixa' => '🟢 Baixa',
                                        'media' => '🟡 Média',
                                        'alta' => '🟠 Alta',
                                        'urgente' => '🔴 Urgente',
                                    ])
                                    ->default('media')
                                    ->required(),

                                Forms\Components\DatePicker::make('data_vencimento')
                                    ->label('Vencimento')
                                    ->native(false),
                            ]),

                        Forms\Components\ToggleButtons::make('status')
                            ->options([
                                'pendente' => 'Pendente',
                                'em_andamento' => 'Em Andamento',
                                'concluida' => 'Concluída',
                                'cancelada' => 'Cancelada',
                            ])
                            ->icons([
                                'pendente' => 'heroicon-o-clock',
                                'em_andamento' => 'heroicon-o-play',
                                'concluida' => 'heroicon-o-check-circle',
                                'cancelada' => 'heroicon-o-x-circle',
                            ])
                            ->colors([
                                'pendente' => 'gray',
                                'em_andamento' => 'info',
                                'concluida' => 'success',
                                'cancelada' => 'danger',
                            ])
                            ->default('pendente')
                            ->inline()
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('criado_por')
                            ->default(fn() => auth()->id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),

                Tables\Columns\TextColumn::make('responsavel.name')
                    ->label('Responsável')
                    ->icon('heroicon-o-user')
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('prioridade')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'urgente' => 'danger',
                        'alta' => 'warning',
                        'media' => 'info',
                        'baixa' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'concluida' => 'success',
                        'cancelada' => 'danger',
                        'em_andamento' => 'info',
                        'pendente' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vence em')
                    ->date('d/m')
                    ->sortable()
                    ->description(fn(Tarefa $record) => $record->data_vencimento ? $record->data_vencimento->diffForHumans() : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'em_andamento' => 'Em Andamento',
                        'concluida' => 'Concluída',
                    ]),
                Tables\Filters\SelectFilter::make('responsavel_id')
                    ->label('Responsável')
                    ->relationship('responsavel', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Visualizar')
                    ->iconButton(),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar')
                    ->iconButton(),

                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('Baixar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(Tarefa $record) => route('tarefa.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir')
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make()
                    ->schema([
                        InfolistGrid::make(2)->schema([
                            TextEntry::make('titulo')
                                ->label('Título da Tarefa')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold'),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn($state) => match ($state) {
                                    'concluida' => 'success',
                                    'em_andamento' => 'warning',
                                    'pendente' => 'info',
                                    default => 'gray'
                                }),
                        ]),
                    ]),
                InfolistSection::make('📋 Detalhes')
                    ->schema([
                        TextEntry::make('descricao')
                            ->label('Descrição')
                            ->columnSpanFull(),
                        InfolistGrid::make(3)->schema([
                            TextEntry::make('data_vencimento')
                                ->label('Vencimento')
                                ->date('d/m/Y'),
                            TextEntry::make('prioridade')
                                ->badge()
                                ->color(fn($state) => match ($state) {
                                    'alta' => 'danger',
                                    'media' => 'warning',
                                    'baixa' => 'success',
                                    default => 'gray'
                                }),
                            TextEntry::make('responsavel.name')
                                ->label('Responsável')
                                ->placeholder('Não atribuído'),
                        ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTarefas::route('/'),
            'create' => Pages\CreateTarefa::route('/create'),
            'edit' => Pages\EditTarefa::route('/{record}/edit'),
            'view' => Pages\ViewTarefa::route('/{record}'),
        ];
    }
}
