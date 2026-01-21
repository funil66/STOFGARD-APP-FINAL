<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransacaoFinanceiraResource\Pages;
use App\Models\TransacaoFinanceira;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransacaoFinanceiraResource extends Resource
{
    protected static ?string $model = TransacaoFinanceira::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Financeiro';

    protected static ?string $modelLabel = 'Transação';

    protected static ?string $pluralModelLabel = 'Financeiro';

    protected static ?string $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'transacao-financeiras';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tipo e Categoria')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo de Transação')
                            ->options([
                                'receita' => '💰 Receita',
                                'despesa' => '💸 Despesa',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Ajusta categorias e status baseado no tipo
                                if ($state === 'receita') {
                                    $set('status', 'pendente');
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('categoria')
                            ->label('Categoria')
                            ->options(function (Forms\Get $get) {
                                $tipo = $get('tipo');

                                if ($tipo === 'receita') {
                                    return [
                                        'servico' => '🧼 Serviço',
                                        'produto' => '📦 Produto',
                                    ];
                                }

                                return [
                                    'comissao' => '💵 Comissão',
                                    'salario' => '👤 Salário',
                                    'fornecedor' => '🏪 Fornecedor',
                                    'aluguel' => '🏢 Aluguel',
                                    'energia' => '⚡ Energia',
                                    'agua' => '💧 Água',
                                    'internet' => '🌐 Internet',
                                    'telefone' => '📞 Telefone',
                                    'combustivel' => '⛽ Combustível',
                                    'manutencao' => '🔧 Manutenção',
                                    'marketing' => '📢 Marketing',
                                    'impostos' => '🏛️ Impostos',
                                    'equipamentos' => '🛠️ Equipamentos',
                                    'material' => '📋 Material de Consumo',
                                    'outros' => '📌 Outros',
                                ];
                            })
                            ->required()
                            ->searchable()
                            ->live(),
                    ])->columns(2),

                Forms\Components\Section::make('Informações Financeiras')
                    ->schema([
                        Forms\Components\TextInput::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('valor')
                            ->label('Valor')
                            ->required()
                            ->numeric()
                            ->prefix('R$')
                            ->step(0.01)
                            ->minValue(0.01)
                            ->live(debounce: 500),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pendente' => 'Pendente',
                                'pago' => 'Pago',
                                'vencido' => 'Vencido',
                                'cancelado' => 'Cancelado',
                            ])
                            ->required()
                            ->default('pendente')
                            ->live(),
                    ])->columns(2),

                Forms\Components\Section::make('Datas')
                    ->schema([
                        Forms\Components\DatePicker::make('data_transacao')
                            ->label('Data da Transação')
                            ->required()
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->native(false),

                        Forms\Components\DatePicker::make('data_vencimento')
                            ->label('Data de Vencimento')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->visible(fn (Forms\Get $get) => $get('status') !== 'pago'),

                        Forms\Components\DatePicker::make('data_pagamento')
                            ->label('Data de Pagamento')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->visible(fn (Forms\Get $get) => $get('status') === 'pago')
                            ->required(fn (Forms\Get $get) => $get('status') === 'pago'),
                    ])->columns(3),

                Forms\Components\Section::make('Método de Pagamento')
                    ->schema([
                        Forms\Components\Select::make('metodo_pagamento')
                            ->label('Método de Pagamento')
                            ->options([
                                'dinheiro' => '💵 Dinheiro',
                                'pix' => '📱 PIX',
                                'cartao_credito' => '💳 Cartão de Crédito',
                                'cartao_debito' => '💳 Cartão de Débito',
                                'transferencia' => '🏦 Transferência',
                                'boleto' => '📄 Boleto',
                                'cheque' => '📝 Cheque',
                                'outro' => '📌 Outro',
                            ])
                            ->visible(fn (Forms\Get $get) => $get('status') === 'pago')
                            ->required(fn (Forms\Get $get) => $get('status') === 'pago'),

                        Forms\Components\FileUpload::make('comprovante')
                            ->label('Comprovante')
                            ->disk('public')
                            ->directory('comprovantes')
                            ->image()
                            ->imagePreviewHeight('180')
                            ->panelLayout('grid')
                            ->imageEditor()
                            ->maxSize(5120)
                            ->visible(fn (Forms\Get $get) => $get('status') === 'pago'),
                    ])->columns(2)->collapsed(),

                Forms\Components\Section::make('Vínculos')
                    ->schema([
                                Forms\Components\Select::make('ordem_servico_id')
                            ->label('Ordem de Serviço')
                            ->relationship('ordemServico', 'numero_os')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                // Formulário simplificado para criar OS
                            ])
                            ->visible(fn (Forms\Get $get) => in_array($get('categoria'), ['servico', 'produto', 'comissao'])),

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

                        Forms\Components\Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nome')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('tipo') === 'receita'),

                        Forms\Components\Select::make('parceiro_id')
                            ->label('Parceiro')
                            ->relationship('parceiro', 'nome')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('categoria') === 'comissao'),
                    ])->columns(3)->collapsed(),

                Forms\Components\Section::make('Parcelamento')
                    ->schema([
                        Forms\Components\TextInput::make('parcela_numero')
                            ->label('Parcela')
                            ->numeric()
                            ->minValue(1)
                            ->disabled(),

                        Forms\Components\TextInput::make('parcela_total')
                            ->label('Total de Parcelas')
                            ->numeric()
                            ->minValue(1)
                            ->disabled(),
                    ])->columns(2)->collapsed()->visible(fn ($record) => $record?->isParcela()),

                Forms\Components\Section::make('Observações')
                    ->schema([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('conciliado')
                            ->label('Transação Conciliada')
                            ->helperText('Marque se a transação foi conciliada com o extrato bancário'),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data_transacao')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (TransacaoFinanceira $record) => $record->data_vencimento ?
                        'Venc: '.$record->data_vencimento->format('d/m/Y') : null
                    ),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'receita' => 'success',
                        'despesa' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'receita' => '💰 Receita',
                        'despesa' => '💸 Despesa',
                    }),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(40)
                    ->weight(FontWeight::Bold)
                    ->description(fn (TransacaoFinanceira $record) => $record->isParcela() ? "Parcela {$record->getParcelaTexto()}" : null
                    ),

                Tables\Columns\TextColumn::make('categoria')
                    ->label('Categoria')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(function (string $state): string {
                        $emojis = [
                            'servico' => '🧼',
                            'produto' => '📦',
                            'comissao' => '💵',
                            'salario' => '👤',
                            'fornecedor' => '🏪',
                            'aluguel' => '🏢',
                            'energia' => '⚡',
                            'agua' => '💧',
                            'internet' => '🌐',
                            'telefone' => '📞',
                            'combustivel' => '⛽',
                            'manutencao' => '🔧',
                            'marketing' => '📢',
                            'impostos' => '🏛️',
                            'equipamentos' => '🛠️',
                            'material' => '📋',
                            'outros' => '📌',
                        ];

                        return ($emojis[$state] ?? '').' '.ucfirst($state);
                    }),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color(fn (TransacaoFinanceira $record) => $record->isReceita() ? 'success' : 'danger'
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pago' => 'success',
                        'pendente' => 'warning',
                        'vencido' => 'danger',
                        'cancelado' => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pago' => 'heroicon-o-check-circle',
                        'pendente' => 'heroicon-o-clock',
                        'vencido' => 'heroicon-o-exclamation-triangle',
                        'cancelado' => 'heroicon-o-x-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('metodo_pagamento')
                    ->label('Método')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '-';
                        }

                        return match ($state) {
                            'dinheiro' => '💵 Dinheiro',
                            'pix' => '📱 PIX',
                            'cartao_credito' => '💳 Crédito',
                            'cartao_debito' => '💳 Débito',
                            'transferencia' => '🏦 Transfer.',
                            'boleto' => '📄 Boleto',
                            'cheque' => '📝 Cheque',
                            'outro' => '📌 Outro',
                            default => $state,
                        };
                    }),

                Tables\Columns\IconColumn::make('conciliado')
                    ->label('Conciliado')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ImageColumn::make('comprovante')
                    ->label('Comprovante')
                    ->getStateUsing(fn ($record) => $record->comprovante)
                    ->disk('public')
                    ->visibility('public')
                    ->checkFileExistence(false)
                    ->openUrlInNewTab()
                    ->square()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cadastro')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('parceiro.nome')
                    ->label('Parceiro')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_transacao', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'receita' => '💰 Receita',
                        'despesa' => '💸 Despesa',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pago' => 'Pago',
                        'pendente' => 'Pendente',
                        'vencido' => 'Vencido',
                        'cancelado' => 'Cancelado',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('categoria')
                    ->label('Categoria')
                    ->options([
                        'servico' => 'Serviço',
                        'produto' => 'Produto',
                        'comissao' => 'Comissão',
                        'salario' => 'Salário',
                        'fornecedor' => 'Fornecedor',
                        'aluguel' => 'Aluguel',
                        'energia' => 'Energia',
                        'agua' => 'Água',
                        'internet' => 'Internet',
                        'telefone' => 'Telefone',
                        'combustivel' => 'Combustível',
                        'manutencao' => 'Manutenção',
                        'marketing' => 'Marketing',
                        'impostos' => 'Impostos',
                        'equipamentos' => 'Equipamentos',
                        'material' => 'Material',
                        'outros' => 'Outros',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('este_mes')
                    ->label('Este Mês')
                    ->query(fn (Builder $query): Builder => $query->doMes()),

                Tables\Filters\Filter::make('vencidas')
                    ->label('Vencidas')
                    ->query(fn (Builder $query): Builder => $query->vencidas())
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('pagar')
                    ->label('Pagar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TransacaoFinanceira $record) => ! $record->isPago())
                    ->form([
                        Forms\Components\DatePicker::make('data_pagamento')
                            ->label('Data de Pagamento')
                            ->required()
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->native(false),

                        Forms\Components\Select::make('metodo_pagamento')
                            ->label('Método de Pagamento')
                            ->options([
                                'dinheiro' => '💵 Dinheiro',
                                'pix' => '📱 PIX',
                                'cartao_credito' => '💳 Cartão de Crédito',
                                'cartao_debito' => '💳 Cartão de Débito',
                                'transferencia' => '🏦 Transferência',
                                'boleto' => '📄 Boleto',
                                'cheque' => '📝 Cheque',
                                'outro' => '📌 Outro',
                            ])
                            ->required(),
                    ])
                    ->action(function (TransacaoFinanceira $record, array $data) {
                        $record->marcarComoPago(
                            \Carbon\Carbon::parse($data['data_pagamento']),
                            $data['metodo_pagamento']
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Pagamento registrado!')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                \App\Filament\Actions\DownloadFileAction::make('comprovante', 'public')->label('Download'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),

                    Tables\Actions\BulkAction::make('marcar_pago')
                        ->label('Marcar como Pago')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (! $record->isPago()) {
                                    $record->marcarComoPago();
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Transações marcadas como pagas!')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhuma transação financeira')
            ->emptyStateDescription('Crie sua primeira transação clicando no botão abaixo.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informações Principais')
                    ->schema([
                        Infolists\Components\TextEntry::make('tipo')
                            ->label('Tipo')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'receita' ? 'success' : 'danger')
                            ->formatStateUsing(fn (string $state): string => $state === 'receita' ? '💰 Receita' : '💸 Despesa'
                            ),

                        Infolists\Components\TextEntry::make('categoria')
                            ->label('Categoria')
                            ->badge(),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pago' => 'success',
                                'pendente' => 'warning',
                                'vencido' => 'danger',
                                'cancelado' => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('descricao')
                            ->label('Descrição')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('valor')
                            ->label('Valor')
                            ->money('BRL')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold),

                        Infolists\Components\TextEntry::make('metodo_pagamento')
                            ->label('Método de Pagamento')
                            ->badge()
                            ->visible(fn ($record) => $record->metodo_pagamento),
                    ])->columns(3),

                Infolists\Components\Section::make('Datas')
                    ->schema([
                        Infolists\Components\TextEntry::make('data_transacao')
                            ->label('Data da Transação')
                            ->date('d/m/Y'),

                        Infolists\Components\TextEntry::make('data_vencimento')
                            ->label('Data de Vencimento')
                            ->date('d/m/Y')
                            ->visible(fn ($record) => $record->data_vencimento),

                        Infolists\Components\TextEntry::make('data_pagamento')
                            ->label('Data de Pagamento')
                            ->date('d/m/Y')
                            ->visible(fn ($record) => $record->data_pagamento),
                    ])->columns(3),

                Infolists\Components\Section::make('Vínculos')
                    ->schema([
                        Infolists\Components\TextEntry::make('ordemServico.numero_os')
                            ->label('Ordem de Serviço')
                            ->visible(fn ($record) => $record->ordem_servico_id),

                        Infolists\Components\TextEntry::make('cliente.nome')
                            ->label('Cliente')
                            ->visible(fn ($record) => $record->cliente_id),

                        Infolists\Components\TextEntry::make('parceiro.nome')
                            ->label('Parceiro')
                            ->visible(fn ($record) => $record->parceiro_id),
                    ])->columns(3)->visible(fn ($record) => $record->ordem_servico_id || $record->cliente_id || $record->parceiro_id
                    ),

                Infolists\Components\Section::make('Parcelamento')
                    ->schema([
                        Infolists\Components\TextEntry::make('parcela_numero')
                            ->label('Parcela'),

                        Infolists\Components\TextEntry::make('parcela_total')
                            ->label('Total de Parcelas'),
                    ])->columns(2)->visible(fn ($record) => $record->isParcela()),

                Infolists\Components\Section::make('Observações')
                    ->schema([
                        Infolists\Components\TextEntry::make('observacoes')
                            ->label('Observações')
                            ->columnSpanFull(),

                        Infolists\Components\IconEntry::make('conciliado')
                            ->label('Conciliado')
                            ->boolean(),

                        Infolists\Components\ImageEntry::make('comprovante')
                            ->label('Comprovante')
                            ->disk('public')
                            ->visibility('public')
                            ->height(400)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => $record->comprovante),

                        Infolists\Components\TextEntry::make('comprovante_actions')
                            ->label('Ações de Arquivo')
                            ->html()
                            ->getStateUsing(function ($record) {
                                if (empty($record->comprovante)) {
                                    return '';
                                }

                                $entries = [];

                                foreach ((array) data_get($record, 'comprovante') as $path) {
                                    $name = basename($path);

                                    $downloadUrl = route('admin.files.download', [
                                        'model' => base64_encode(get_class($record)),
                                        'record' => $record->getKey(),
                                        'path' => base64_encode($path),
                                    ]);

                                    $deleteUrl = \Illuminate\Support\Facades\URL::signedRoute('admin.files.delete', [
                                        'model' => base64_encode(get_class($record)),
                                        'record' => $record->getKey(),
                                        'path' => base64_encode($path),
                                    ], now()->addHour());

                                    $entries[] = "<div class='py-1'><a href='{$downloadUrl}' target='_blank' class='text-sm text-blue-600 underline'>{$name}</a> · <a href='{$downloadUrl}?download=1' class='text-sm'>Baixar</a> · <a href='{$deleteUrl}' class='text-sm text-red-600 ml-2' onclick=\"return confirm('Excluir arquivo?')\">Excluir</a></div>";
                                }

                                return implode('', $entries);
                            })
                            ->columnSpanFull(),
                    ])->visible(fn ($record) => $record->observacoes || $record->conciliado || $record->comprovante),

                Infolists\Components\Section::make('Sistema')
                    ->schema([
                        Infolists\Components\TextEntry::make('criado_por')
                            ->label('Criado Por'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Criado Em')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('atualizado_por')
                            ->label('Atualizado Por'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Atualizado Em')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(4)->collapsed(),
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
            'index' => Pages\ListTransacaoFinanceiras::route('/'),
            'create' => Pages\CreateTransacaoFinanceira::route('/create'),
            'view' => Pages\ViewTransacaoFinanceira::route('/{record}'),
            'edit' => Pages\EditTransacaoFinanceira::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pendente')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
