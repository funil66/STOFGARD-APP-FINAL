<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceiroResource\Pages;
use App\Models\Financeiro;
use App\Services\PixService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class FinanceiroResource extends Resource
{
    protected static ?string $model = Financeiro::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    // Não registrar no menu de navegação (oculto)
    protected static bool $shouldRegisterNavigation = false;

    // Bloqueia acesso direto via URL
    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $navigationLabel = 'Financeiro';

    protected static ?string $modelLabel = 'Registro Financeiro';

    protected static ?string $pluralModelLabel = 'Financeiro';

    protected static ?string $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'entrada' => '💵 Entrada (Receber)',
                                'saida' => '💸 Saída (Pagar)',
                            ])
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('cadastro_id')
                            ->label('Cadastro (Cliente, Loja ou Vendedor)')
                            ->options(function () {
                                $clientes = \App\Models\Cliente::all(['id', 'nome']);
                                $parceiros = \App\Models\Parceiro::all(['id', 'nome', 'tipo']);
                                $options = [];
                                foreach ($clientes as $c) {
                                    $options['cliente_' . $c->id] = 'Cliente: ' . $c->nome;
                                }
                                foreach ($parceiros as $p) {
                                    $tipo = ucfirst($p->tipo ?? 'Parceiro');
                                    $options['parceiro_' . $p->id] = $tipo . ': ' . $p->nome;
                                }
                                return $options;
                            })
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('orcamento_id')
                            ->label('Orçamento')
                            ->relationship('orcamento', 'id')
                            ->searchable(),

                        Forms\Components\Select::make('ordem_servico_id')
                            ->label('Ordem de Serviço')
                            ->relationship('ordemServico', 'id')
                            ->searchable(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Detalhes Financeiros')
                    ->schema([
                        Forms\Components\TextInput::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('categoria')
                            ->label('Categoria')
                            ->options([
                                'servico' => 'Serviço',
                                'produto' => 'Produto',
                                'despesa' => 'Despesa Operacional',
                                'salario' => 'Salário',
                                'fornecedor' => 'Fornecedor',
                                'outros' => 'Outros',
                            ])
                            ->native(false)
                            ->searchable(),

                        Forms\Components\TextInput::make('valor')
                            ->label('Valor')
                            ->required()
                            ->numeric()
                            ->prefix('R$')
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\TextInput::make('desconto')
                            ->label('Desconto')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\TextInput::make('juros')
                            ->label('Juros')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\TextInput::make('multa')
                            ->label('Multa')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Datas e Status')
                    ->schema([
                        Forms\Components\DatePicker::make('data')
                            ->label('Data')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('data_vencimento')
                            ->label('Data de Vencimento')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DateTimePicker::make('data_pagamento')
                            ->label('Data de Pagamento')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pendente' => 'Pendente',
                                'pago' => 'Pago',
                                'cancelado' => 'Cancelado',
                                'atrasado' => 'Atrasado',
                            ])
                            ->required()
                            ->default('pendente')
                            ->native(false),

                        Forms\Components\Select::make('forma_pagamento')
                            ->label('Forma de Pagamento')
                            ->options([
                                'dinheiro' => 'Dinheiro',
                                'pix' => 'PIX',
                                'cartao_credito' => 'Cartão de Crédito',
                                'cartao_debito' => 'Cartão de Débito',
                                'boleto' => 'Boleto',
                                'transferencia' => 'Transferência',
                            ])
                            ->native(false),

                        Forms\Components\TextInput::make('valor_pago')
                            ->label('Valor Pago')
                            ->numeric()
                            ->prefix('R$'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Observações')
                    ->schema([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'entrada',
                        'danger' => 'saida',
                    ])
                    ->icons([
                        'heroicon-o-arrow-down-circle' => 'entrada',
                        'heroicon-o-arrow-up-circle' => 'saida',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === 'entrada' ? 'Entrada' : 'Saída'),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->descricao),

                Tables\Columns\TextColumn::make('cadastro_id')
                    ->label('Cadastro')
                    ->getStateUsing(function ($record) {
                        if ($record->cadastro) {
                            return $record->cadastro->nome;
                        }
                        if ($record->cliente) {
                            return $record->cliente->nome;
                        }
                        return '-';
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('categoria')
                    ->label('Categoria')
                    ->badge()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($record) => $record->tipo === 'entrada' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('data')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pendente',
                        'success' => 'pago',
                        'danger' => 'cancelado',
                        'danger' => 'atrasado',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\IconColumn::make('pix_ativo')
                    ->label('PIX')
                    ->boolean()
                    ->trueIcon('heroicon-o-qr-code')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->pix_ativo ? 'PIX Ativo' : 'Sem PIX')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('forma_pagamento')
                    ->label('Forma Pagamento')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'saida' => 'Saída',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                        'atrasado' => 'Atrasado',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('vencidos')
                    ->query(fn (Builder $query): Builder => $query->vencido())
                    ->label('Vencidos')
                    ->toggle(),
            ])
            ->actions([
                Action::make('gerarPix')
                    ->label('Gerar PIX')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->visible(fn ($record) => $record->tipo === 'entrada' &&
                        $record->status === 'pendente' &&
                        empty($record->pix_txid)
                    )
                    ->action(function ($record) {
                        try {
                            $pixService = new PixService;
                            $result = $pixService->criarCobranca($record);

                            Notification::make()
                                ->title('PIX Gerado com Sucesso!')
                                ->body('QR Code e link de pagamento disponíveis')
                                ->success()
                                ->duration(5000)
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erro ao Gerar PIX')
                                ->body($e->getMessage())
                                ->danger()
                                ->duration(10000)
                                ->send();
                        }
                    })

                Action::make('confirmar_pix')
                    ->label('Confirmar Pagamento')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pendente' && $record->tipo === 'entrada')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'pago', 'data_pagamento' => now()]);

                        Notification::make()->title('Pagamento Confirmado no Caixa!')->success()->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Gerar PIX')
                    ->modalDescription('Deseja gerar um QR Code PIX para este pagamento?')
                    ->modalSubmitActionLabel('Sim, gerar PIX'),

                Action::make('verPix')
                    ->label('Ver PIX')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->visible(fn ($record) => ! empty($record->pix_qrcode_base64))
                    ->modalContent(fn ($record) => new HtmlString('
                        <div class="text-center space-y-4 p-4">
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Valor a Pagar</p>
                                <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                                    R$ '.number_format($record->valor_total, 2, ',', '.').'
                                </p>
                            </div>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-4">
                                <img src="data:image/png;base64,'.$record->pix_qrcode_base64.'" 
                                     alt="QR Code PIX" 
                                     class="w-64 h-64 mx-auto mb-4">
                            </div>
                            
                            <div class="space-y-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Código Copia e Cola:</p>
                                <div class="flex gap-2">
                                    <input type="text" 
                                           value="'.$record->pix_copia_cola.'"
                                           class="flex-1 px-3 py-2 border rounded-lg text-xs bg-gray-50 dark:bg-gray-900"
                                           readonly
                                           onclick="this.select()">
                                </div>
                            </div>
                            
                            '.($record->pix_expiracao ? '
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Expira em: '.$record->pix_expiracao->format('d/m/Y H:i').'
                            </p>' : '').'
                        </div>
                    '))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->slideOver(),

                Action::make('copiarLink')
                    ->label('Copiar Link')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->visible(fn ($record) => ! empty($record->link_pagamento_hash))
                    ->action(function ($record) {
                        $pixService = new PixService;
                        $link = $pixService->gerarLinkPagamento($record);

                        Notification::make()
                            ->title('Link de Pagamento')
                            ->body($link)
                            ->success()
                            ->duration(10000)
                            ->send();
                    }),

                Action::make('marcarPago')
                    ->label('Marcar como Pago')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pendente')
                    ->form([
                        Forms\Components\DateTimePicker::make('data_pagamento')
                            ->label('Data do Pagamento')
                            ->required()
                            ->default(now())
                            ->native(false),

                        Forms\Components\TextInput::make('valor_pago')
                            ->label('Valor Pago')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->default(fn ($record) => $record->valor_total),

                        Forms\Components\Select::make('forma_pagamento')
                            ->label('Forma de Pagamento')
                            ->options([
                                'dinheiro' => 'Dinheiro',
                                'pix' => 'PIX',
                                'cartao_credito' => 'Cartão de Crédito',
                                'cartao_debito' => 'Cartão de Débito',
                                'boleto' => 'Boleto',
                                'transferencia' => 'Transferência',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'pago',
                            'data_pagamento' => $data['data_pagamento'],
                            'valor_pago' => $data['valor_pago'],
                            'forma_pagamento' => $data['forma_pagamento'],
                        ]);

                        Notification::make()
                            ->title('Pagamento Confirmado!')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListFinanceiros::route('/'),
            'create' => Pages\CreateFinanceiro::route('/create'),
            'edit' => Pages\EditFinanceiro::route('/{record}/edit'),
        ];
    }
}
