<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipamentoResource\Pages;
use App\Models\Equipamento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class EquipamentoResource extends Resource
{
    protected static ?string $model = Equipamento::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Equipamentos';
    protected static ?string $modelLabel = 'Equipamento';
    protected static ?string $pluralModelLabel = 'Equipamentos';

    // Submódulo do Almoxarifado
    protected static ?string $slug = 'almoxarifado/equipamentos';

    protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Equipamento')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nome')
                                    ->label('Nome do Equipamento')
                                    ->placeholder('Ex: Extratora WAP')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('codigo_patrimonio')
                                    ->label('Código Patrimônio')
                                    ->placeholder('Ex: EQP-001')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50),
                            ]),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->placeholder('Detalhes do equipamento...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'ativo' => '✅ Ativo',
                                        'manutencao' => '🔧 Em Manutenção',
                                        'baixado' => '❌ Baixado',
                                    ])
                                    ->default('ativo')
                                    ->required()
                                    ->native(false),

                                Forms\Components\DatePicker::make('data_aquisicao')
                                    ->label('Data de Aquisição'),

                                Forms\Components\TextInput::make('valor_aquisicao')
                                    ->label('Valor de Aquisição')
                                    ->numeric()
                                    ->prefix('R$'),
                            ]),

                        Forms\Components\TextInput::make('localizacao')
                            ->label('Localização')
                            ->placeholder('Onde está guardado?')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Fotos do Equipamento')
                    ->icon('heroicon-o-camera')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('arquivos')
                            ->label('Fotos')
                            ->collection('arquivos')
                            ->multiple()
                            ->image()
                            ->disk('public')
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\SpatieMediaLibraryImageColumn::make('foto')
                    ->collection('arquivos')
                    ->circular()
                    ->label(''),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Equipamento')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('codigo_patrimonio')
                    ->label('Patrimônio')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'ativo' => 'success',
                        'manutencao' => 'warning',
                        'baixado' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'ativo' => '✅ Ativo',
                        'manutencao' => '🔧 Manutenção',
                        'baixado' => '❌ Baixado',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('localizacao')
                    ->label('Local')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valor_aquisicao')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('data_aquisicao')
                    ->label('Aquisição')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nome')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ativo' => '✅ Ativos',
                        'manutencao' => '🔧 Em Manutenção',
                        'baixado' => '❌ Baixados',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('')->tooltip('Visualizar'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\Action::make('enviar_lista_desejos')
                    ->label('')
                    ->tooltip('Enviar para Lista de Desejos')
                    ->icon('heroicon-o-gift')
                    ->color('info')
                    ->action(function (Equipamento $record) {
                        \App\Models\ListaDesejo::create([
                            'nome' => $record->nome,
                            'descricao' => 'Cópia de equipamento: ' . $record->descricao,
                            'categoria' => 'equipamento',
                            'preco_estimado' => $record->valor_aquisicao,
                            'quantidade_desejada' => 1,
                            'status' => 'pendente',
                            'prioridade' => 'media',
                            'solicitado_por' => auth()->user()->name ?? 'Sistema',
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('✅ Enviado para Lista de Desejos!')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipamentos::route('/'),
            'create' => Pages\CreateEquipamento::route('/create'),
            'edit' => Pages\EditEquipamento::route('/{record}/edit'),
        ];
    }
}
