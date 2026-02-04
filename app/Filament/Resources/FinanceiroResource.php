<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceiroResource\Pages;
use App\Models\Financeiro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\TextEntry;

class FinanceiroResource extends Resource
{
    protected static ?string $model = Financeiro::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $navigationLabel = 'Transações Financeiras';
    protected static ?string $slug = 'financeiros/transacoes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Transação')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'entrada' => '💰 Entrada (Receita)',
                                'saida' => '📤 Saída (Despesa)',
                            ])
                            ->required()
                            ->default('entrada')
                            ->live(),

                        Forms\Components\Select::make('cadastro_id')
                            ->label('Cliente/Fornecedor')
                            ->relationship('cadastro', 'nome')
                            ->searchable(['nome', 'cpf_cnpj', 'email'])
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nome')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('tipo')
                                    ->options([
                                        'cliente' => 'Cliente',
                                        'loja' => 'Loja',
                                        'vendedor' => 'Vendedor',
                                        'parceiro' => 'Parceiro',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('cpf_cnpj')
                                    ->label('CPF/CNPJ')
                                    ->maxLength(18),
                            ])
                            ->getOptionLabelFromRecordUsing(fn($record) => match ($record->tipo) {
                                'cliente' => "👤 {$record->nome} (Cliente)",
                                'parceiro' => "🏢 {$record->nome} (Parceiro)",
                                'loja' => "🏪 {$record->nome} (Loja)",
                                'vendedor' => "👔 {$record->nome} (Vendedor)",
                                default => $record->nome,
                            })
                            ->required(),

                        Forms\Components\TextInput::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required(),

                                Forms\Components\Select::make('categoria_id')
                                    ->relationship('categoria', 'nome')
                                    ->label('Categoria')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nome')->required(),
                                        Forms\Components\Select::make('tipo')
                                            ->options([
                                                'financeiro_receita' => 'Receita',
                                                'financeiro_despesa' => 'Despesa',
                                            ])
                                            ->required(),
                                        Forms\Components\ColorPicker::make('cor'),
                                    ]),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pendente' => 'Pendente',
                                        'pago' => 'Pago',
                                        'atrasado' => 'Atrasado',
                                        'cancelado' => 'Cancelado',
                                    ])
                                    ->default('pendente')
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('data')
                                    ->label('Data da Transação')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\DatePicker::make('data_vencimento')
                                    ->label('Vencimento')
                                    ->required(),

                                Forms\Components\DatePicker::make('data_pagamento')
                                    ->label('Data do Pagamento')
                                    ->nullable(),
                            ]),

                        Forms\Components\Select::make('forma_pagamento')
                            ->label('Forma de Pagamento')
                            ->options([
                                'pix' => 'PIX',
                                'dinheiro' => 'Dinheiro',
                                'cartao_credito' => 'Cartão de Crédito',
                                'cartao_debito' => 'Cartão de Débito',
                                'boleto' => 'Boleto',
                                'transferencia' => 'Transferência',
                            ]),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Comprovantes e Anexos')
                    ->description('Envie comprovantes, notas fiscais e documentos relacionados (Máx: 20MB)')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('arquivos')
                            ->label('Arquivos')
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with(['categoria', 'cadastro']))
            ->columns([
                // MOBILE: Data + Descricao combinados
                Tables\Columns\TextColumn::make('data')
                    ->label('Data')
                    ->date('d/m')
                    ->sortable()
                    ->description(fn($record) => $record->descricao ? mb_substr($record->descricao, 0, 20) . (mb_strlen($record->descricao) > 20 ? '...' : '') : '-')
                    ->icon(fn($record) => $record->tipo === 'entrada' ? 'heroicon-o-arrow-down-circle' : 'heroicon-o-arrow-up-circle')
                    ->iconColor(fn($record) => $record->tipo === 'entrada' ? 'success' : 'danger'),

                // SEMPRE VISÍVEL: Tipo com ícone
                Tables\Columns\TextColumn::make('tipo')
                    ->label('')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'entrada' => '↓',
                        'saida' => '↑',
                    })
                    ->tooltip(fn(string $state): string => match ($state) {
                        'entrada' => 'Entrada (Receita)',
                        'saida' => 'Saída (Despesa)',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'entrada' => 'success',
                        'saida' => 'danger',
                    }),

                // DESKTOP ONLY: Cliente/Fornecedor
                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(15)
                    ->visibleFrom('md'),

                // DESKTOP ONLY: Descricao
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(20)
                    ->visibleFrom('lg'),

                // DESKTOP ONLY: Categoria
                Tables\Columns\TextColumn::make('categoria.nome')
                    ->label('Cat.')
                    ->badge()
                    ->color('gray')
                    ->visibleFrom('xl'),

                // SEMPRE VISÍVEL: Valor em destaque
                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn($record) => $record->tipo === 'entrada' ? 'success' : 'danger'),

                // DESKTOP ONLY: Vencimento
                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Venc.')
                    ->date('d/m')
                    ->sortable()
                    ->visibleFrom('lg'),

                // SEMPRE VISÍVEL: Status com ícone
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pago' => '✓',
                        'pendente' => '⏳',
                        'atrasado' => '!',
                        'cancelado' => '✗',
                    })
                    ->tooltip(fn(string $state): string => match ($state) {
                        'pago' => 'Pago',
                        'pendente' => 'Pendente',
                        'atrasado' => 'Atrasado',
                        'cancelado' => 'Cancelado',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pago' => 'success',
                        'pendente' => 'warning',
                        'atrasado' => 'danger',
                        'cancelado' => 'gray',
                    }),
            ])
            ->defaultSort('data', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'entrada' => 'Entradas',
                        'saida' => 'Saídas',
                    ]),

                Tables\Filters\SelectFilter::make('status'),

                Tables\Filters\Filter::make('data_range')
                    ->form([
                        Forms\Components\DatePicker::make('data_de')
                            ->label('Data de'),
                        Forms\Components\DatePicker::make('data_ate')
                            ->label('Data até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['data_de'], fn($q, $date) => $q->whereDate('data', '>=', $date))
                            ->when($data['data_ate'], fn($q, $date) => $q->whereDate('data', '<=', $date));
                    }),
            ])
            ->actions([
                // Baixar pagamento
                Tables\Actions\Action::make('baixar')
                    ->label('')
                    ->tooltip('Baixar Pagamento')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn(Financeiro $record) => $record->status === 'pendente')
                    ->requiresConfirmation()
                    ->action(function (Financeiro $record) {
                        $record->update([
                            'status' => 'pago',
                            'data_pagamento' => now()
                        ]);
                        Notification::make()
                            ->title('Pago!')
                            ->success()
                            ->send();
                    }),

                // View
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Ver')
                    ->iconButton(),

                // Edit
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar')
                    ->iconButton(),

                // PDF
                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->iconButton()
                    ->url(fn(Financeiro $record) => route('financeiro.pdf', $record))
                    ->openUrlInNewTab(),

                // Excluir
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir')
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('baixar_selecionados')
                        ->label('Baixar Selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pendente') {
                                    $record->update([
                                        'status' => 'pago',
                                        'data_pagamento' => now()
                                    ]);
                                }
                            });
                            Notification::make()
                                ->title('Pagamentos confirmados em lote!')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // CABEÇALHO FINANCEIRO
                InfolistSection::make()
                    ->schema([
                        InfolistGrid::make(3)->schema([
                            TextEntry::make('tipo')
                                ->label('Tipo')
                                ->badge()
                                ->color(fn($state) => $state === 'entrada' ? 'success' : 'danger')
                                ->formatStateUsing(fn($state) => $state === 'entrada' ? '💰 Entrada' : '💸 Saída')
                                ->size(TextEntry\TextEntrySize::Large),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn($state) => match ($state) {
                                    'pago' => 'success',
                                    'vencido' => 'danger',
                                    'pendente' => 'warning',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'pago' => '✅ Pago',
                                    'pendente' => '⏳ Pendente',
                                    'vencido' => '🔴 Vencido',
                                    'cancelado' => '❌ Cancelado',
                                    default => $state,
                                }),
                            TextEntry::make('valor')
                                ->label('Valor')
                                ->money('BRL')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold')
                                ->color(fn($record) => $record->tipo === 'entrada' ? 'success' : 'danger'),
                        ]),
                    ]),

                // INFORMAÇÕES PRINCIPAIS
                InfolistSection::make('📋 Informações da Transação')
                    ->schema([
                        InfolistGrid::make(2)->schema([
                            TextEntry::make('descricao')
                                ->label('Descrição')
                                ->columnSpanFull(),
                            TextEntry::make('categoria.nome')
                                ->label('Categoria')
                                ->badge()
                                ->color('info')
                                ->icon(fn($record) => $record->categoria?->icone ?? '📌'),
                            TextEntry::make('forma_pagamento')
                                ->label('Forma de Pagamento')
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'pix' => '💳 PIX',
                                    'dinheiro' => '💵 Dinheiro',
                                    'cartao_credito' => '💳 Cartão de Crédito',
                                    'cartao_debito' => '💳 Cartão de Débito',
                                    'boleto' => '📄 Boleto',
                                    'transferencia' => '🏦 Transferência',
                                    default => $state ?? 'Não informado',
                                }),
                        ]),
                    ]),

                // DATAS
                InfolistSection::make('📅 Datas')
                    ->schema([
                        InfolistGrid::make(3)->schema([
                            TextEntry::make('data')
                                ->label('Data do Lançamento')
                                ->date('d/m/Y')
                                ->icon('heroicon-m-calendar'),
                            TextEntry::make('data_vencimento')
                                ->label('Data de Vencimento')
                                ->date('d/m/Y')
                                ->icon('heroicon-m-calendar-days')
                                ->color(fn($record) => $record->status === 'vencido' ? 'danger' : 'gray'),
                            TextEntry::make('data_pagamento')
                                ->label('Data do Pagamento')
                                ->dateTime('d/m/Y H:i')
                                ->icon('heroicon-m-check-circle')
                                ->color('success')
                                ->placeholder('Não pago'),
                        ]),
                    ]),

                // VALORES DETALHADOS
                InfolistSection::make('💵 Detalhamento de Valores')
                    ->schema([
                        InfolistGrid::make(4)->schema([
                            TextEntry::make('valor')
                                ->label('Valor Original')
                                ->money('BRL'),
                            TextEntry::make('desconto')
                                ->label('Desconto')
                                ->money('BRL')
                                ->color('success')
                                ->placeholder('R$ 0,00'),
                            TextEntry::make('juros')
                                ->label('Juros')
                                ->money('BRL')
                                ->color('warning')
                                ->placeholder('R$ 0,00'),
                            TextEntry::make('multa')
                                ->label('Multa')
                                ->money('BRL')
                                ->color('danger')
                                ->placeholder('R$ 0,00'),
                        ]),
                        InfolistGrid::make(2)->schema([
                            TextEntry::make('valor_total')
                                ->label('Valor Total (com juros/multa)')
                                ->money('BRL')
                                ->weight('bold')
                                ->size(TextEntry\TextEntrySize::Medium)
                                ->color('info'),
                            TextEntry::make('valor_pago')
                                ->label('Valor Pago')
                                ->money('BRL')
                                ->weight('bold')
                                ->size(TextEntry\TextEntrySize::Medium)
                                ->color('success')
                                ->placeholder('R$ 0,00'),
                        ]),
                    ]),

                // VINCULAÇÕES
                InfolistSection::make('🔗 Vinculações')
                    ->schema([
                        InfolistGrid::make(3)->schema([
                            TextEntry::make('cadastro.nome')
                                ->label('Cliente/Fornecedor')
                                ->icon('heroicon-m-user')
                                ->placeholder('Não vinculado'),
                            TextEntry::make('ordemServico.numero_os')
                                ->label('Ordem de Serviço')
                                ->icon('heroicon-m-document-text')
                                ->url(fn($record) => $record->ordem_servico_id ? "/admin/ordem-servicos/{$record->ordem_servico_id}" : null)
                                ->placeholder('Não vinculado'),
                            TextEntry::make('orcamento.numero')
                                ->label('Orçamento')
                                ->icon('heroicon-m-document-text')
                                ->url(fn($record) => $record->orcamento_id ? "/admin/orcamentos/{$record->orcamento_id}" : null)
                                ->placeholder('Não vinculado'),
                        ]),
                    ])
                    ->collapsed(),

                // OBSERVAÇÕES
                InfolistSection::make('📝 Observações')
                    ->schema([
                        TextEntry::make('observacoes')
                            ->label('')
                            ->placeholder('Nenhuma observação registrada')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceiros::route('/'),
            'create' => Pages\CreateFinanceiro::route('/create'),
            'edit' => Pages\EditFinanceiro::route('/{record}/edit'),
            'view' => Pages\ViewFinanceiro::route('/{record}'),
            'dashboard' => Pages\DashboardFinanceiro::route('/dashboard'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            FinanceiroResource\Widgets\FinanceiroStatsWidget::class,
            FinanceiroResource\Widgets\FinanceiroChartWidget::class,
        ];
    }
}
