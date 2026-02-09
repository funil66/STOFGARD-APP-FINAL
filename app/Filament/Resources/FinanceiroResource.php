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
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;

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
                            ->getOptionLabelFromRecordUsing(fn ($record) => match ($record->tipo) {
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
            ->modifyQueryUsing(fn ($query) => $query->with(['categoria', 'cadastro']))
            ->columns([
                // MOBILE: Data + Descricao combinados
                Tables\Columns\TextColumn::make('data')
                    ->label('Data')
                    ->date('d/m')
                    ->sortable()
                    ->description(fn ($record) => $record->descricao ? mb_substr($record->descricao, 0, 20).(mb_strlen($record->descricao) > 20 ? '...' : '') : '-')
                    ->icon(fn ($record) => $record->tipo === 'entrada' ? 'heroicon-o-arrow-down-circle' : 'heroicon-o-arrow-up-circle')
                    ->iconColor(fn ($record) => $record->tipo === 'entrada' ? 'success' : 'danger'),

                // SEMPRE VISÍVEL: Tipo com ícone
                Tables\Columns\TextColumn::make('tipo')
                    ->label('')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'entrada' => '↓',
                        'saida' => '↑',
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        'entrada' => 'Entrada (Receita)',
                        'saida' => 'Saída (Despesa)',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'saida' => 'danger',
                    }),

                // Badge de Comissão
                Tables\Columns\TextColumn::make('comissao')
                    ->label('')
                    ->badge()
                    ->getStateUsing(fn (Financeiro $record) => $record->is_comissao ? ($record->comissao_paga ? 'Paga' : 'Pendente') : null)
                    ->color(fn (string $state): string => match ($state) {
                        'Paga' => 'success',
                        'Pendente' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Paga' => 'heroicon-m-check-circle',
                        'Pendente' => 'heroicon-m-clock',
                        default => '',
                    })
                    ->tooltip(fn ($record) => $record?->is_comissao ? 'Comissão '.($record->comissao_paga ? 'paga' : 'pendente') : ''),

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
                    ->color(fn ($record) => $record->tipo === 'entrada' ? 'success' : 'danger')
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
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pago' => '✓',
                        'pendente' => '⏳',
                        'atrasado' => '!',
                        'cancelado' => '✗',
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        'pago' => 'Pago',
                        'pendente' => 'Pendente',
                        'atrasado' => 'Atrasado',
                        'cancelado' => 'Cancelado',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pago' => 'success',
                        'pendente' => 'warning',
                        'atrasado' => 'danger',
                        'cancelado' => 'gray',
                    }),
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
                            ->when($data['data_de'], fn ($q, $d) => $q->whereDate('data', '>=', $d))
                            ->when($data['data_ate'], fn ($q, $d) => $q->whereDate('data', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['data_de'] && $data['data_ate']) {
                            return 'Período: '.\Carbon\Carbon::parse($data['data_de'])->format('d/m').' - '.\Carbon\Carbon::parse($data['data_ate'])->format('d/m');
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
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('cadastro', fn ($q) => $q->where('tipo', $data['value']));
                    }),

                Tables\Filters\SelectFilter::make('cadastro_id')
                    ->label('🔍 Cliente/Fornecedor')
                    ->relationship('cadastro', 'nome')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => match ($record->tipo) {
                        'cliente' => "👤 {$record->nome}",
                        'loja' => "🏪 {$record->nome}",
                        'vendedor' => "👔 {$record->nome}",
                        'arquiteto' => "📐 {$record->nome}",
                        default => $record->nome,
                    }),

                Tables\Filters\SelectFilter::make('loja_direto')
                    ->label('🏪 Loja (Direto ou via OS)')
                    ->options(fn () => \App\Models\Cadastro::where('tipo', 'loja')->pluck('nome', 'id'))
                    ->searchable()
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->where(function ($q) use ($data) {
                            $q->where('cadastro_id', $data['value'])
                                ->orWhereHas('ordemServico', fn ($os) => $os->where('loja_id', $data['value']));
                        });
                    }),

                Tables\Filters\SelectFilter::make('vendedor_direto')
                    ->label('👔 Vendedor (Direto ou via OS)')
                    ->options(fn () => \App\Models\Cadastro::where('tipo', 'vendedor')->pluck('nome', 'id'))
                    ->searchable()
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->where(function ($q) use ($data) {
                            $q->where('cadastro_id', $data['value'])
                                ->orWhereHas('ordemServico', fn ($os) => $os->where('vendedor_id', $data['value']));
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
                        if (! isset($data['value'])) {
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
                            ->when($data['vencimento_de'], fn ($q, $d) => $q->whereDate('data_vencimento', '>=', $d))
                            ->when($data['vencimento_ate'], fn ($q, $d) => $q->whereDate('data_vencimento', '<=', $d));
                    }),

                Tables\Filters\TernaryFilter::make('vencido')
                    ->label('⚠️ Vencidos')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas Vencidos')
                    ->falseLabel('Não Vencidos')
                    ->queries(
                        true: fn ($query) => $query->where('status', '!=', 'pago')->whereDate('data_vencimento', '<', now()),
                        false: fn ($query) => $query->where(fn ($q) => $q->where('status', 'pago')->orWhereDate('data_vencimento', '>=', now())),
                    ),
            ])
            ->actions([
                // Baixar pagamento
                Tables\Actions\Action::make('baixar')
                    ->label('')
                    ->tooltip('Baixar Pagamento')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn (Financeiro $record) => $record->status === 'pendente' || $record->status === 'atrasado')
                    ->requiresConfirmation()
                    ->action(function (Financeiro $record) {
                        $record->update([
                            'status' => 'pago',
                            'data_pagamento' => now(),
                        ]);
                        Notification::make()
                            ->title('Pago!')
                            ->success()
                            ->send();
                    }),

                // Estornar (Desfazer pagamento)
                Tables\Actions\Action::make('estornar')
                    ->label('')
                    ->tooltip('Estornar (Voltar para Pendente)')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->iconButton()
                    ->visible(fn (Financeiro $record) => $record->status === 'pago')
                    ->requiresConfirmation()
                    ->action(function (Financeiro $record) {
                        $record->update([
                            'status' => 'pendente',
                            'data_pagamento' => null,
                        ]);
                        Notification::make()
                            ->title('Estornado!')
                            ->body('O lançamento voltou para pendente.')
                            ->warning()
                            ->send();
                    }),

                // Pagar Comissão
                Tables\Actions\Action::make('pagar_comissao')
                    ->label('')
                    ->tooltip('Pagar Comissão')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn (Financeiro $record) => $record->is_comissao && ! $record->comissao_paga && $record->status !== 'pago')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Pagamento de Comissão')
                    ->modalDescription(fn (Financeiro $record) => 'Deseja marcar a comis são de '.($record->cadastro?->nome ?? 'N/A').' no valor de R$ '.number_format((float) $record->valor, 2, ',', '.').' como paga?')
                    ->action(function (Financeiro $record) {
                        $record->update([
                            'comissao_paga' => true,
                            'comissao_data_pagamento' => now(),
                            'status' => 'pago',
                            'data_pagamento' => now(),
                            'valor_pago' => $record->valor,
                        ]);

                        Notification::make()
                            ->title('Comissão paga com sucesso!')
                            ->body('A comissão foi marcada como paga e o lançamento foi atualizado.')
                            ->success()
                            ->send();
                    }),

                // Ver
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Ver')
                    ->iconButton(),

                // Editar
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar')
                    ->iconButton(),

                // Duplicar
                Tables\Actions\ReplicateAction::make()
                    ->label('')
                    ->tooltip('Duplicar Lançamento')
                    ->modalHeading('Duplicar Lançamento')
                    ->excludeAttributes(['status', 'data_pagamento', 'created_at', 'updated_at'])
                    ->beforeReplicaSaved(function (Financeiro $replica) {
                        $replica->status = 'pendente';
                        $replica->data_pagamento = null;
                        $replica->descricao = $replica->descricao.' (Cópia)';
                    })
                    ->iconButton(),

                // PDF
                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->iconButton()
                    ->url(fn (Financeiro $record) => route('financeiro.pdf', $record))
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
                                        'data_pagamento' => now(),
                                    ]);
                                }
                            });
                            Notification::make()
                                ->title('Pagamentos confirmados em lote!')
                                ->success()
                                ->send();
                        }),

                    // EXPORTAR SIMPLE CSV
                    Tables\Actions\BulkAction::make('exportar')
                        ->label('Exportar CSV')
                        ->icon('heroicon-o-table-cells')
                        ->action(function ($records) {
                            $headers = [
                                'Content-type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename=relatorio_financeiro.csv',
                                'Pragma' => 'no-cache',
                                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                                'Expires' => '0',
                            ];

                            $callback = function () use ($records) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, ['ID', 'Data', 'Tipo', 'Descricao', 'Categoria', 'Cliente', 'Valor', 'Status', 'Forma Pagamento']);

                                foreach ($records as $row) {
                                    fputcsv($file, [
                                        $row->id,
                                        $row->data->format('d/m/Y'),
                                        $row->tipo,
                                        $row->descricao,
                                        $row->categoria?->nome ?? '-',
                                        $row->cadastro?->nome ?? '-',
                                        number_format($row->valor, 2, ',', '.'),
                                        $row->status,
                                        $row->forma_pagamento,
                                    ]);
                                }
                                fclose($file);
                            };

                            return response()->streamDownload($callback, 'relatorio_financeiro.csv', $headers);
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
                                ->color(fn ($state) => $state === 'entrada' ? 'success' : 'danger')
                                ->formatStateUsing(fn ($state) => $state === 'entrada' ? '💰 Entrada' : '💸 Saída')
                                ->size(TextEntry\TextEntrySize::Large),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'pago' => 'success',
                                    'vencido' => 'danger',
                                    'pendente' => 'warning',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn ($state) => match ($state) {
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
                                ->color(fn ($record) => $record->tipo === 'entrada' ? 'success' : 'danger'),
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
                                ->icon(fn ($record) => $record->categoria?->icone ?? 'heroicon-o-tag'),
                            TextEntry::make('forma_pagamento')
                                ->label('Forma de Pagamento')
                                ->formatStateUsing(fn ($state) => match ($state) {
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
                                ->color(fn ($record) => $record->status === 'vencido' ? 'danger' : 'gray'),
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
                                ->url(fn ($record) => $record->ordem_servico_id ? "/admin/ordem-servicos/{$record->ordem_servico_id}" : null)
                                ->placeholder('Não vinculado'),
                            TextEntry::make('orcamento.numero')
                                ->label('Orçamento')
                                ->icon('heroicon-m-document-text')
                                ->url(fn ($record) => $record->orcamento_id ? "/admin/orcamentos/{$record->orcamento_id}" : null)
                                ->placeholder('Não vinculado'),
                        ]),
                    ])
                    ->collapsed(),

                // COMPROVANTES
                InfolistSection::make('📎 Comprovantes e Anexos')
                    ->schema([
                        \Filament\Infolists\Components\SpatieMediaLibraryImageEntry::make('arquivos')
                            ->collection('arquivos')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->getMedia('arquivos')->isNotEmpty())
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
            // Core CRUD
            'index' => Pages\ListFinanceiros::route('/'),
            'create' => Pages\CreateFinanceiro::route('/create'),

            // Dashboard e Relatórios (ANTES de {record})
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
