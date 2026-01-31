<?php

namespace App\Filament\Cliente\Resources;

use App\Filament\Cliente\Resources\MeusServicosResource\Pages;
use App\Models\OrdemServico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeusServicosResource extends Resource
{
    protected static ?string $model = OrdemServico::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Meus Serviços';

    protected static ?string $modelLabel = 'Serviço';

    protected static ?string $slug = 'meus-servicos';

    public static function getEloquentQuery(): Builder
    {
        // Filtra apenas OS do cliente logado
        return parent::getEloquentQuery()
            ->where('cadastro_id', auth()->user()->cadastro_id ?? 0)
            ->where('status', '!=', 'rascunho');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes do Serviço')
                    ->schema([
                        Forms\Components\TextInput::make('numero_os')
                            ->label('Número OS')
                            ->readOnly(),

                        Forms\Components\TextInput::make('status')
                            ->formatStateUsing(fn(string $state): string => ucfirst($state))
                            ->readOnly(),

                        Forms\Components\DatePicker::make('data_prevista')
                            ->label('Data Agendada')
                            ->readOnly(),

                        Forms\Components\Textarea::make('descricao_servico')
                            ->label('Descrição')
                            ->columnSpanFull()
                            ->readOnly(),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações / Recomendações')
                            ->columnSpanFull()
                            ->readOnly(),
                    ])->columns(3),

                Forms\Components\Section::make('Itens do Serviço')
                    ->schema([
                        Forms\Components\Repeater::make('itens')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('descricao')
                                    ->label('Item')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('quantidade')
                                    ->label('Qtd')
                                    ->readOnly(),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_os')
                    ->label('OS')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data Solicitação')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('data_prevista')
                    ->label('Agendamento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'concluida' => 'success',
                        'cancelada' => 'danger',
                        'pendente' => 'warning',
                        'em_andamento' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detalhes'),

                // Botão para baixar garantia se concluída
                Tables\Actions\Action::make('garantia')
                    ->label('Certificado')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->url(fn(OrdemServico $record) => route('os.pdf', $record)) // Usando PDF da OS como "certificado" por enquanto
                    ->openUrlInNewTab()
                    ->visible(fn(OrdemServico $record) => in_array($record->status, ['concluida', 'finalizada'])),

                Tables\Actions\Action::make('whatsapp')
                    ->label('Suporte')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url('https://wa.me/5541999999999?text=Preciso%20de%20ajuda%20com%20minha%20OS') // Ajustar número depois
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeusServicos::route('/'),
            'view' => Pages\ViewMeusServicos::route('/{record}'),
        ];
    }
}
