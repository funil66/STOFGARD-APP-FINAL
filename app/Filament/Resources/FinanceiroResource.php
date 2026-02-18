<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceiroResource\Pages;
use App\Models\Financeiro;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use App\Services\FinanceiroService;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use App\Support\Filament\StofgardTable;

class FinanceiroResource extends Resource
{
    protected static ?string $model = Financeiro::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Transações Financeiras';

    protected static ?string $slug = 'financeiros';

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
                            ->createOptionForm(\App\Services\ClienteFormService::getQuickSchema())
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

                        Forms\Components\Grid::make(['default' => 1, 'sm' => 3])
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

                        Forms\Components\Grid::make(['default' => 1, 'sm' => 3])
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

                        Forms\Components\TextInput::make('id_parceiro')
                            ->label('ID Parceiro')
                            ->placeholder('Identificação da loja/vendedor')
                            ->maxLength(255),

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

                // Badge de Comissão
                Tables\Columns\TextColumn::make('comissao')
                    ->label('')
                    ->badge()
                    ->getStateUsing(fn(Financeiro $record) => $record->is_comissao ? ($record->comissao_paga ? 'Paga' : 'Pendente') : null)
                    ->color(fn(string $state): string => match ($state) {
                        'Paga' => 'success',
                        'Pendente' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Paga' => 'heroicon-m-check-circle',
                        'Pendente' => 'heroicon-m-clock',
                        default => '',
                    })
                    ->tooltip(fn($record) => $record?->is_comissao ? 'Comissão ' . ($record->comissao_paga ? 'paga' : 'pendente') : ''),

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
                    ->color(fn($record) => $record->tipo === 'entrada' ? 'success' : 'danger')
                    ->summarize(Sum::make()->money('BRL')->label('Total')),

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

                Tables\Columns\TextColumn::make('id_parceiro')
                    ->label('ID Parceiro')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // ========================================
                // GRUPO 1: PERÍODO
                // ========================================
                Tables\Filters\SelectFilter::make('periodo')
                    ->label('⏰ Período')
                    ->options([
                        'hoje' => 'Hoje',
                        'ontem' => 'Ontem',
                        'esta_semana' => 'Esta Semana',
                        'este_mes' => 'Este Mês',
                        'mes_passado' => 'Mês Passado',
                        'ultimos_90_dias' => 'Últimos 90 Dias',
                        'este_ano' => 'Este Ano',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value']) {
                            'hoje' => $query->whereDate('data', now()),
                            'ontem' => $query->whereDate('data', now()->subDay()),
                            'esta_semana' => $query->whereBetween('data', [now()->startOfWeek(), now()->endOfWeek()]),
                            'este_mes' => $query->whereMonth('data', now()->month)->whereYear('data', now()->year),
                            'mes_passado' => $query->whereMonth('data', now()->subMonth()->month)->whereYear('data', now()->subMonth()->year),
                            'ultimos_90_dias' => $query->whereDate('data', '>=', now()->subDays(90)),
                            'este_ano' => $query->whereYear('data', now()->year),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('data_range')
                    ->label('📅 Período Personalizado')
                    ->form([
                        Forms\Components\DatePicker::make('data_de')->label('De'),
                        Forms\Components\DatePicker::make('data_ate')->label('Até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['data_de'], fn($q, $d) => $q->whereDate('data', '>=', $d))
                            ->when($data['data_ate'], fn($q, $d) => $q->whereDate('data', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['data_de'] && $data['data_ate']) {
                            return 'Período: ' . \Carbon\Carbon::parse($data['data_de'])->format('d/m') . ' - ' . \Carbon\Carbon::parse($data['data_ate'])->format('d/m');
                        }

                        return null;
                    }),

                // ========================================
                // GRUPO 2: TIPO E STATUS
                // ========================================
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('💰 Tipo')
                    ->options([
                        'entrada' => '↓ Receitas (Entradas)',
                        'saida' => '↑ Despesas (Saídas)',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('📊 Status')
                    ->options([
                        'pendente' => '⏳ Pendente',
                        'pago' => '✅ Pago',
                        'atrasado' => '🔴 Atrasado',
                        'cancelado' => '❌ Cancelado',
                    ])
                    ->multiple(),

                // ========================================
                // GRUPO 3: PESSOAS E RELACIONAMENTOS
                // ========================================
                Tables\Filters\SelectFilter::make('tipo_cadastro')
                    ->label('👥 Tipo de Pessoa')
                    ->options([
                        'cliente' => '👤 Clientes',
                        'loja' => '🏪 Lojas',
                        'vendedor' => '👔 Vendedores',
                        'arquiteto' => '📐 Arquitetos',
                        'parceiro' => '🤝 Parceiros',
                        'funcionario' => '👷 Funcionários',
                    ])
                    ->query(function ($query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        return $query->whereHas('cadastro', fn($q) => $q->where('tipo', $data['value']));
                    }),

                Tables\Filters\SelectFilter::make('cadastro_id')
                    ->label('🔍 Cliente/Fornecedor')
                    ->relationship('cadastro', 'nome')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn($record) => match ($record->tipo) {
                        'cliente' => "👤 {$record->nome}",
                        'loja' => "🏪 {$record->nome}",
                        'vendedor' => "👔 {$record->nome}",
                        'arquiteto' => "📐 {$record->nome}",
                        default => $record->nome,
                    }),

                Tables\Filters\SelectFilter::make('loja_direto')
                    ->label('🏪 Loja (Direto ou via OS)')
                    ->options(fn() => \App\Models\Cadastro::where('tipo', 'loja')->pluck('nome', 'id'))
                    ->searchable()
                    ->query(function ($query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        return $query->where(function ($q) use ($data) {
                            $q->where('cadastro_id', $data['value'])
                                ->orWhereHas('ordemServico', fn($os) => $os->where('loja_id', $data['value']));
                        });
                    }),

                Tables\Filters\SelectFilter::make('vendedor_direto')
                    ->label('👔 Vendedor (Direto ou via OS)')
                    ->options(fn() => \App\Models\Cadastro::where('tipo', 'vendedor')->pluck('nome', 'id'))
                    ->searchable()
                    ->query(function ($query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        return $query->where(function ($q) use ($data) {
                            $q->where('cadastro_id', $data['value'])
                                ->orWhereHas('ordemServico', fn($os) => $os->where('vendedor_id', $data['value']));
                        });
                    }),

                // ========================================
                // GRUPO 4: CATEGORIZAÇÃO
                // ========================================
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('🏷️ Categoria')
                    ->relationship('categoria', 'nome')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('forma_pagamento')
                    ->label('💳 Forma de Pagamento')
                    ->options([
                        'pix' => '📱 PIX',
                        'dinheiro' => '💵 Dinheiro',
                        'cartao_credito' => '💳 Cartão de Crédito',
                        'cartao_debito' => '💳 Cartão de Débito',
                        'boleto' => '📄 Boleto',
                        'transferencia' => '🏦 Transferência',
                    ])
                    ->multiple(),

                // ========================================
                // GRUPO 5: VINCULAÇÃO
                // ========================================
                Tables\Filters\SelectFilter::make('vinculacao')
                    ->label('🔗 Vinculação')
                    ->options([
                        'com_os' => '📋 Com Ordem de Serviço',
                        'com_orcamento' => '📝 Com Orçamento',
                        'avulso' => '📌 Avulso (Sem Vínculo)',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value']) {
                            'com_os' => $query->whereNotNull('ordem_servico_id'),
                            'com_orcamento' => $query->whereNotNull('orcamento_id'),
                            'avulso' => $query->whereNull('ordem_servico_id')->whereNull('orcamento_id'),
                            default => $query,
                        };
                    }),

                // ========================================
                // GRUPO 6: COMISSÕES
                // ========================================
                Tables\Filters\SelectFilter::make('comissao_status')
                    ->label('💼 Comissões')
                    ->options([
                        'pendente' => '⏳ Comissões Pendentes',
                        'paga' => '✅ Comissões Pagas',
                        'todas' => '📋 Todas as Comissões',
                    ])
                    ->query(function ($query, array $data) {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'pendente' => $query->comissaoPendente(),
                            'paga' => $query->comissaoPaga(),
                            'todas' => $query->where('is_comissao', true),
                            default => $query,
                        };
                    }),

                // ========================================
                // GRUPO 7: VENCIMENTO
                // ========================================
                Tables\Filters\Filter::make('vencimento')
                    ->label('📆 Vencimento')
                    ->form([
                        Forms\Components\DatePicker::make('vencimento_de')->label('Vence a partir de'),
                        Forms\Components\DatePicker::make('vencimento_ate')->label('Vence até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['vencimento_de'], fn($q, $d) => $q->whereDate('data_vencimento', '>=', $d))
                            ->when($data['vencimento_ate'], fn($q, $d) => $q->whereDate('data_vencimento', '<=', $d));
                    }),

                Tables\Filters\TernaryFilter::make('vencido')
                    ->label('⚠️ Vencidos')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas Vencidos')
                    ->falseLabel('Não Vencidos')
                    ->queries(
                        true: fn($query) => $query->where('status', '!=', 'pago')->whereDate('data_vencimento', '<', now()),
                        false: fn($query) => $query->where(fn($q) => $q->where('status', 'pago')->orWhereDate('data_vencimento', '>=', now())),
                    ),
            ])
            ->actions(
                StofgardTable::defaultActions(
                    view: true,
                    edit: true,
                    delete: true,
                    extraActions: [
                        // Baixar pagamento (com modal de dados)
                        Tables\Actions\Action::make('baixar')
                            ->label('Baixar Pagamento')
                            ->tooltip('Registrar Pagamento')
                            ->icon('heroicon-s-check-circle')
                            ->color('success')
                            ->visible(fn(Financeiro $record) => $record->status === 'pendente' || $record->status === 'atrasado')
                            ->modalHeading('Registrar Pagamento')
                            ->modalDescription(fn(Financeiro $record) => 'Valor total: R$ ' . number_format(floatval($record->valor), 2, ',', '.') . '. Para pagamento parcial, informe um valor menor.')
                            ->form([
                                Forms\Components\TextInput::make('valor_pago')
                                    ->label('Valor Pago (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->default(fn(Financeiro $record) => $record->valor)
                                    ->helperText('Informe um valor menor que o total para pagamento parcial (será gerado um novo registro com o saldo restante).'),
                                Forms\Components\Select::make('forma_pagamento')
                                    ->label('Forma de Pagamento')
                                    ->options([
                                        'pix' => 'PIX',
                                        'dinheiro' => 'Dinheiro',
                                        'cartao_credito' => 'Cartão de Crédito',
                                        'cartao_debito' => 'Cartão de Débito',
                                        'boleto' => 'Boleto',
                                        'transferencia' => 'Transferência',
                                    ])
                                    ->default(fn(Financeiro $record) => $record->forma_pagamento)
                                    ->required(),
                                Forms\Components\DatePicker::make('data_pagamento')
                                    ->label('Data do Pagamento')
                                    ->default(now())
                                    ->required(),
                            ])
                            ->action(fn(Financeiro $record, array $data) => FinanceiroService::baixarPagamento($record, $data)),

                        // Estornar
                        Tables\Actions\Action::make('estornar')
                            ->label('Estornar')
                            ->tooltip('Estornar (Voltar para Pendente)')
                            ->icon('heroicon-s-arrow-path')
                            ->color('warning')
                            // ->iconButton()
                            ->visible(fn(Financeiro $record) => $record->status === 'pago')
                            ->requiresConfirmation()
                            ->action(fn(Financeiro $record) => FinanceiroService::estornarPagamento($record)),

                        // Pagar Comissão
                        Tables\Actions\Action::make('pagar_comissao')
                            ->label('Pagar Comissão')
                            ->tooltip('Pagar Comissão e Gerar Despesa')
                            ->icon('heroicon-s-banknotes')
                            ->color('success')
                            ->visible(fn(Financeiro $record) => $record->is_comissao && !$record->comissao_paga && $record->status !== 'pago')
                            ->requiresConfirmation()
                            ->modalHeading('Pagamento de Comissão')
                            ->modalDescription('Confirme os dados abaixo para registrar o pagamento da comissão e gerar a despesa financeira.')
                            ->form([
                                Forms\Components\DatePicker::make('data_pagamento')
                                    ->label('Data do Pagamento')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor da Comissão (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(fn(Financeiro $record) => $record->valor)
                                    ->required(),
                                Forms\Components\Select::make('beneficiario_id')
                                    ->label('Beneficiário (Vendedor/Parceiro)')
                                    ->options(\App\Models\Cadastro::parceiros()->pluck('nome', 'id'))
                                    ->searchable()
                                    ->default(fn(Financeiro $record) => $record->id_parceiro ? \App\Models\Cadastro::where('nome', $record->id_parceiro)->value('id') : null) // Tenta achar pelo nome armazenado em id_parceiro (que as vezes é string) ou null
                                    ->helperText('Selecione quem receberá a comissão para vincular na despesa.'),
                            ])
                            ->action(fn(Financeiro $record, array $data) => FinanceiroService::pagarComissao($record, $data)),

                        // Duplicar
                        Tables\Actions\ReplicateAction::make()
                            ->label('Duplicar')
                            ->tooltip('Duplicar Lançamento')
                            ->modalHeading('Duplicar Lançamento')
                            ->excludeAttributes(['status', 'data_pagamento', 'created_at', 'updated_at'])
                            ->beforeReplicaSaved(function (Financeiro $replica) {
                                $replica->status = 'pendente';
                                $replica->data_pagamento = null;
                                $replica->descricao = $replica->descricao . ' (Cópia)';
                            })
                        // ->iconButton()
                        ,

                        // PDF
                        Tables\Actions\Action::make('pdf')
                            ->label('Baixar PDF')
                            ->tooltip('Baixar PDF')
                            ->icon('heroicon-s-document-text')
                            ->color('success')
                            // ->iconButton()
                            ->url(fn(Financeiro $record) => route('financeiro.pdf', $record))
                            ->openUrlInNewTab(),
                    ]
                )
            )
            ->bulkActions(
                StofgardTable::defaultBulkActions([
                    Tables\Actions\BulkAction::make('baixar_selecionados')
                        ->label('Baixar Selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn($records) => FinanceiroService::baixarEmLote($records)),

                    // EXPORTAR SIMPLE CSV
                    Tables\Actions\BulkAction::make('exportar')
                        ->label('Exportar CSV')
                        ->icon('heroicon-o-table-cells')
                        ->action(fn($records) => FinanceiroService::gerarCsvExportacao($records)),
                ])
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ===== CABEÇALHO =====
                InfolistSection::make()
                    ->schema([
                        InfolistGrid::make(4)->schema([
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
                                    'atrasado' => 'danger',
                                    'pendente' => 'warning',
                                    'cancelado' => 'gray',
                                    default => 'gray',
                                }),
                            TextEntry::make('data')
                                ->label('Data')
                                ->date('d/m/Y'),
                            TextEntry::make('categoria.nome')
                                ->label('Categoria')
                                ->badge()
                                ->color('info'),
                        ]),
                        InfolistGrid::make(4)->schema([
                            TextEntry::make('cadastro.nome')
                                ->label('Cliente/Fornecedor')
                                ->icon('heroicon-m-user')
                                ->url(fn($record) => $record->cadastro_id
                                    ? \App\Filament\Resources\CadastroResource::getUrl('view', ['record' => $record->cadastro_id])
                                    : null)
                                ->color('primary')
                                ->placeholder('Não vinculado')
                                ->columnSpan(2),
                            TextEntry::make('forma_pagamento')
                                ->label('Forma Pagamento')
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'pix' => '💳 PIX',
                                    'dinheiro' => '💵 Dinheiro',
                                    'cartao_credito' => '💳 Crédito',
                                    'cartao_debito' => '💳 Débito',
                                    'boleto' => '📄 Boleto',
                                    'transferencia' => '🏦 Transfer.',
                                    default => $state ?? '-',
                                }),
                            TextEntry::make('data_vencimento')
                                ->label('Vencimento')
                                ->date('d/m/Y')
                                ->color(fn($record) => $record->status === 'atrasado' ? 'danger' : 'gray'),
                            TextEntry::make('id_parceiro')
                                ->label('ID Parceiro')
                                ->badge()
                                ->color('info')
                                ->placeholder('-'),
                        ]),
                    ]),

                // ===== RESUMO FINANCEIRO =====
                InfolistSection::make('💰 Resumo de Valores')
                    ->schema([
                        InfolistGrid::make(5)->schema([
                            TextEntry::make('valor')
                                ->label('💵 Valor')
                                ->money('BRL')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold')
                                ->color(fn($record) => $record->tipo === 'entrada' ? 'success' : 'danger'),
                            TextEntry::make('desconto')
                                ->label('🎁 Desconto')
                                ->money('BRL')
                                ->color('success')
                                ->placeholder('R$ 0,00'),
                            TextEntry::make('juros')
                                ->label('📈 Juros')
                                ->money('BRL')
                                ->color('warning')
                                ->placeholder('R$ 0,00'),
                            TextEntry::make('valor_pago')
                                ->label('✅ Pago')
                                ->money('BRL')
                                ->weight('bold')
                                ->color('success')
                                ->placeholder('R$ 0,00'),
                            TextEntry::make('saldo')
                                ->label('📊 Saldo')
                                ->money('BRL')
                                ->weight('bold')
                                ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                                ->state(fn($record) => ($record->valor + ($record->juros ?? 0) - ($record->desconto ?? 0)) - ($record->valor_pago ?? 0))
                                ->placeholder('R$ 0,00'),
                        ]),
                    ])
                    ->collapsible(),

                // ===== ABAS =====
                \Filament\Infolists\Components\Tabs::make('Detalhes')
                    ->tabs([
                        // ABA 1: INFORMAÇÕES
                        \Filament\Infolists\Components\Tabs\Tab::make('📋 Informações')
                            ->schema([
                                InfolistGrid::make(2)->schema([
                                    TextEntry::make('descricao')
                                        ->label('Descrição')
                                        ->columnSpanFull()
                                        ->weight('bold'),
                                    TextEntry::make('data_pagamento')
                                        ->label('Data do Pagamento')
                                        ->dateTime('d/m/Y H:i')
                                        ->icon('heroicon-m-check-circle')
                                        ->color('success')
                                        ->placeholder('Não pago'),
                                    TextEntry::make('observacoes')
                                        ->label('Observações')
                                        ->placeholder('Nenhuma observação')
                                        ->columnSpanFull(),
                                ]),
                            ]),

                        // ABA 2: VINCULAÇÕES
                        \Filament\Infolists\Components\Tabs\Tab::make('🔗 Vinculações')
                            ->schema([
                                InfolistGrid::make(2)->schema([
                                    TextEntry::make('ordemServico.numero_os')
                                        ->label('Ordem de Serviço')
                                        ->icon('heroicon-m-clipboard-document-check')
                                        ->url(fn($record) => $record->ordem_servico_id
                                            ? \App\Filament\Resources\OrdemServicoResource::getUrl('view', ['record' => $record->ordem_servico_id])
                                            : null)
                                        ->color('primary')
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
                            ]),

                        // ABA 3: COMPROVANTES
                        \Filament\Infolists\Components\Tabs\Tab::make('📎 Comprovantes')
                            ->badge(fn($record) => $record->getMedia('arquivos')->count())
                            ->schema([
                                \Filament\Infolists\Components\SpatieMediaLibraryImageEntry::make('arquivos_imagens')
                                    ->label('Imagens')
                                    ->collection('arquivos')
                                    ->disk('public'),

                                \Filament\Infolists\Components\TextEntry::make('arquivos_list')
                                    ->label('Lista de Comprovantes/Documentos')
                                    ->html()
                                    ->getStateUsing(function ($record) {
                                        $files = $record->getMedia('arquivos');
                                        if ($files->isEmpty())
                                            return '<span class="text-gray-500 text-sm">Nenhum arquivo anexado.</span>';

                                        $html = '<ul class="list-disc pl-4 space-y-1">';
                                        foreach ($files as $file) {
                                            $url = $file->getUrl();
                                            $name = $file->file_name;
                                            $size = $file->human_readable_size;
                                            $html .= "<li><a href='{$url}' target='_blank' class='text-primary-600 hover:underline'>{$name}</a> <span class='text-xs text-gray-500'>({$size})</span></li>";
                                        }
                                        $html .= '</ul>';
                                        return $html;
                                    }),
                            ]),

                        // ABA 4: HISTÓRICO DE ALTERAÇÕES
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
                                                ->placeholder('Sistema/Automático'),
                                            TextEntry::make('event')
                                                ->label('Ação')
                                                ->badge()
                                                ->formatStateUsing(fn(string $state): string => match ($state) {
                                                    'created' => 'Criação',
                                                    'updated' => 'Edição',
                                                    'deleted' => 'Exclusão',
                                                    'restored' => 'Restauração',
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

    public static function getPages(): array
    {
        return [
            // Core CRUD
            'index' => Pages\ListFinanceiros::route('/'),
            'create' => Pages\CreateFinanceiro::route('/create'),

            // Dashboard e Relatórios (ANTES de {record})
            'dashboard' => Pages\FinanceiroDashboard::route('/dashboard'),
            'analise' => Pages\FinanceiroAnalise::route('/analise'),
            'extratos' => Pages\Extratos::route('/extratos'),

            // Visualizações por Status (ANTES de {record})
            'receitas' => Pages\ListReceitas::route('/receitas'),
            'despesas' => Pages\ListDespesas::route('/despesas'),
            'pendentes' => Pages\ListPendentes::route('/pendentes'),
            'atrasadas' => Pages\ListAtrasadas::route('/atrasadas'),

            // Páginas Analíticas (ANTES de {record})
            'analise-vendedores' => Pages\AnaliseVendedores::route('/analise/vendedores'),
            'analise-lojas' => Pages\AnaliseLojas::route('/analise/lojas'),
            'analise-categorias' => Pages\AnaliseCategorias::route('/analise/categorias'),
            'comissoes' => Pages\Comissoes::route('/comissoes'),

            // Rotas com parâmetros dinâmicos (DEVEM VIR POR ÚLTIMO)
            'edit' => Pages\EditFinanceiro::route('/{record}/edit'),
            'view' => Pages\ViewFinanceiro::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [

        ];
    }
}
