<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdutoResource\Pages;
use App\Filament\Resources\ProdutoResource\RelationManagers\MovimentacoesRelationManager;
use App\Models\Produto;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\TextEntry;

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Produtos';

    // Submódulo do Almoxarifado
    protected static ?string $slug = 'almoxarifado/produtos';

    protected static ?int $navigationSort = 99;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nome')->required(),
            Forms\Components\Textarea::make('descricao')->columnSpanFull(),
            Forms\Components\TextInput::make('preco_venda')->numeric()->prefix('R$'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('#')->sortable(),
            TextColumn::make('nome')->searchable()->sortable(),
            TextColumn::make('preco_venda')->money('BRL')->sortable(),
        ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()->label('')->tooltip('Visualizar'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                
                // PDF Download
                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('Baixar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn(Produto $record) => route('produto.pdf', $record))
                    ->openUrlInNewTab(),
                
                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('Baixar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(Produto $record) => route('produto.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
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
                InfolistSection::make()
                    ->schema([
                        InfolistGrid::make(2)->schema([
                            TextEntry::make('nome')
                                ->label('Nome do Produto')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold'),
                            TextEntry::make('preco_venda')
                                ->label('Preço de Venda')
                                ->money('BRL')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->color('success'),
                        ]),
                    ]),
                
                InfolistSection::make('💰 Informações de Preço')
                    ->schema([
                        InfolistGrid::make(3)->schema([
                            TextEntry::make('preco_custo')
                                ->label('Preço de Custo')
                                ->money('BRL')
                                ->placeholder('Não informado'),
                            TextEntry::make('preco_venda')
                                ->label('Preço de Venda')
                                ->money('BRL'),
                            TextEntry::make('margem')
                                ->label('Margem')
                                ->formatStateUsing(function ($record) {
                                    if ($record->preco_custo && $record->preco_venda && $record->preco_custo > 0) {
                                        $margem = (($record->preco_venda - $record->preco_custo) / $record->preco_custo) * 100;
                                        return number_format($margem, 2) . '%';
                                    }
                                    return 'N/A';
                                })
                                ->badge()
                                ->color('success'),
                        ]),
                    ]),
                
                InfolistSection::make('📦 Estoque')
                    ->schema([
                        InfolistGrid::make(3)->schema([
                            TextEntry::make('quantidade_estoque')
                                ->label('Quantidade em Estoque')
                                ->badge()
                                ->color(fn($state) => $state > 10 ? 'success' : ($state > 0 ? 'warning' : 'danger')),
                            TextEntry::make('estoque_minimo')
                                ->label('Estoque Mínimo')
                                ->placeholder('Não definido'),
                            TextEntry::make('unidade_medida')
                                ->label('Unidade de Medida')
                                ->placeholder('UN'),
                        ]),
                    ]),
                
                InfolistSection::make('📋 Detalhes')
                    ->schema([
                        TextEntry::make('descricao')
                            ->label('Descrição')
                            ->columnSpanFull()
                            ->placeholder('Sem descrição'),
                        TextEntry::make('categoria.nome')
                            ->label('Categoria')
                            ->badge()
                            ->color('info')
                            ->placeholder('Sem categoria'),
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
            'index' => Pages\ListProdutos::route('/'),
            'create' => Pages\CreateProduto::route('/create'),
            'edit' => Pages\EditProduto::route('/{record}/edit'),
            'view' => Pages\ViewProduto::route('/{record}'),
        ];
    }
}
