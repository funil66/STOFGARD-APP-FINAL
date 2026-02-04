<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstoqueResource\Pages;
use App\Models\Estoque;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class EstoqueResource extends Resource
{
    protected static ?string $model = Estoque::class;
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Estoque';
    protected static ?string $modelLabel = 'Item de Estoque';
    protected static ?string $pluralModelLabel = 'Estoque';

    // Submódulo do Almoxarifado
    protected static ?string $slug = 'almoxarifado/estoques';

    protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item de Estoque')
                    ->icon('heroicon-o-beaker')
                    ->schema([
                        Forms\Components\TextInput::make('item')
                            ->label('Nome do Produto')
                            ->placeholder('Ex: Impermeabilizante')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('quantidade')
                                    ->label('Quantidade (Litros)')
                                    ->numeric()
                                    ->suffix('L')
                                    ->required()
                                    ->minValue(0),

                                Forms\Components\Select::make('unidade')
                                    ->label('Unidade')
                                    ->options([
                                        'unidade' => 'Unidade',
                                        'litros' => 'Litros',
                                        'caixa' => 'Caixa',
                                        'metro' => 'Metro',
                                    ])
                                    ->default('litros')
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('minimo_alerta')
                                    ->label('Mínimo para Alerta')
                                    ->numeric()
                                    ->suffix('L')
                                    ->default(20)
                                    ->helperText('Notificação quando abaixo deste valor'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-beaker'),

                Tables\Columns\TextColumn::make('quantidade')
                    ->label('Estoque Atual')
                    ->suffix(' L')
                    ->sortable()
                    ->color(fn(Estoque $record): string => $record->cor)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('galoes')
                    ->label('Galões (20L)')
                    ->state(fn(Estoque $record): string => $record->galoes . ' 🫙')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('minimo_alerta')
                    ->label('Mínimo')
                    ->suffix(' L')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('status')
                    ->label('Status')
                    ->state(fn(Estoque $record): bool => !$record->isAbaixoDoMinimo())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('item')
            ->actions([
                Tables\Actions\Action::make('adicionar')
                    ->label('Adicionar')
                    ->tooltip('Registrar Entrada')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('litros')
                            ->label('Litros a adicionar')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('L'),
                    ])
                    ->action(function (Estoque $record, array $data) {
                        $record->increment('quantidade', $data['litros']);
                        Notification::make()
                            ->title('✅ Estoque Atualizado!')
                            ->body("Adicionados {$data['litros']}L de {$record->item}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('consumir')
                    ->label('Consumir')
                    ->tooltip('Registrar Saída')
                    ->icon('heroicon-o-minus-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('litros')
                            ->label('Litros consumidos')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('L'),
                    ])
                    ->action(function (Estoque $record, array $data) {
                        if ($record->quantidade >= $data['litros']) {
                            $record->decrement('quantidade', $data['litros']);
                            Notification::make()
                                ->title('📤 Consumo Registrado')
                                ->body("Consumidos {$data['litros']}L de {$record->item}")
                                ->success()
                                ->send();

                            // Verificar escassez
                            if ($record->isAbaixoDoMinimo()) {
                                Notification::make()
                                    ->title('⚠️ ESTOQUE BAIXO!')
                                    ->body("{$record->item}: apenas {$record->quantidade}L restantes!")
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        } else {
                            Notification::make()
                                ->title('❌ Erro')
                                ->body("Quantidade insuficiente em estoque!")
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('enviar_lista')
                    ->label('Enviar para Lista')
                    ->tooltip('Adicionar à Lista de Desejos')
                    ->icon('heroicon-o-gift')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('categoria')
                            ->label('Categoria')
                            ->options([
                                'quimico' => 'Químico',
                                'consumivel' => 'Consumível',
                                'equipamento' => 'Equipamento',
                                'acessorio' => 'Acessório',
                                'ferramenta' => 'Ferramenta',
                                'epi' => 'EPI',
                                'outro' => 'Outro',
                            ])
                            ->default('quimico')
                            ->required(),
                        Forms\Components\TextInput::make('quantidade_desejada')
                            ->label('Quantidade')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Forms\Components\Select::make('prioridade')
                            ->label('Prioridade')
                            ->options([
                                'urgente' => '🔴 Urgente',
                                'alta' => '🟠 Alta',
                                'media' => '🟡 Média',
                                'baixa' => '🟢 Baixa',
                            ])
                            ->default('media')
                            ->required(),
                    ])
                    ->action(function (Estoque $record, array $data) {
                        \App\Models\ListaDesejo::create([
                            'nome' => $record->item,
                            'descricao' => "Reposição de estoque",
                            'categoria' => $data['categoria'],
                            'quantidade_desejada' => $data['quantidade_desejada'],
                            'prioridade' => $data['prioridade'],
                            'status' => 'pendente',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('✅ Adicionado à Lista de Desejos!')
                            ->body("{$record->item} foi adicionado para compra futura")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('pdf')
                    ->label('')
                    ->tooltip('Abrir PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn(Estoque $record) => route('estoque.pdf', $record))
                    ->openUrlInNewTab(),

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
                    ->url(fn(Estoque $record) => route('estoque.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir')
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informações do Estoque')
                    ->schema([
                        InfolistGrid::make(2)
                            ->schema([
                                TextEntry::make('item')->label('Item'),
                                TextEntry::make('quantidade')->label('Quantidade')->suffix(' L'),
                                TextEntry::make('unidade')->label('Unidade'),
                                TextEntry::make('minimo')->label('Mínimo')->suffix(' L'),
                                TextEntry::make('observacoes')->label('Observações')->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEstoques::route('/'),
            'create' => Pages\CreateEstoque::route('/create'),
            'view' => Pages\ViewEstoque::route('/{record}'),
            'edit' => Pages\EditEstoque::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\ProdutoResource\Widgets\EstoqueVisualWidget::class,
        ];
    }
}
