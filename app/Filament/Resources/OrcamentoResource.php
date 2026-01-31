<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrcamentoResource\Pages;
use App\Models\Orcamento;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use App\Services\OrdemServicoService;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class OrcamentoResource extends Resource
{
    protected static ?string $model = Orcamento::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $label = 'Orçamento';

    public static function form(Form $form): Form
    {
        // Carrega configurações do catálogo
        // REMOVIDO: Carregamento de configuração antiga JSON
        // Carrega opções do banco de dados (TabelaPreco) para uso no form
        // (Isso será feito dinamicamente ou via query no componente)
        return $form
            ->schema([
                // 1. CABEÇALHO
                Forms\Components\Section::make('Dados do Orçamento')
                    ->schema([
                        Forms\Components\Select::make('cadastro_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm(\App\Filament\Resources\CadastroResource::getFormSchema()),

                        Forms\Components\DatePicker::make('data_orcamento')->default(now())->required(),
                        Forms\Components\DatePicker::make('data_validade')->default(now()->addDays(15)),
                        Forms\Components\Select::make('status')
                            ->options(['rascunho' => 'Rascunho', 'enviado' => 'Enviado', 'aprovado' => 'Aprovado'])
                            ->default('rascunho')
                            ->required(),

                        Forms\Components\KeyValue::make('extra_attributes')
                            ->label('Dados Personalizados do Nicho')
                            ->keyLabel('Campo')
                            ->valueLabel('Valor')
                            ->columnSpanFull(),
                    ])->columns(4),
                // 2. COMERCIAL (AQUI ESTÁ A LÓGICA DE COMISSÃO)
                Forms\Components\Section::make('Comercial & Pagamento')
                    ->description('Gerencie as comissões e a exibição do PIX no PDF.')
                    ->schema([
                        Forms\Components\Toggle::make('pdf_incluir_pix')
                            ->label('Gerar QR Code PIX')
                            ->default(true),
                        Toggle::make('aplicar_desconto_pix')
                            ->label('Aplicar Desconto à Vista (PIX)?')
                            ->default(true)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('pix_chave_selecionada')
                            ->label('Selecionar Chave PIX')
                            ->options(function () {
                                // 1. Tenta buscar via Model (pode vir array se tiver cast)
                                $setting = \App\Models\Setting::find('financeiro_pix_keys');
                                $valor = $setting ? $setting->value : null;

                                // 2. Se não veio pelo Model, tenta via Query Builder (vem string bruta)
                                if (!$valor) {
                                    $valor = \Illuminate\Support\Facades\DB::table('settings')
                                        ->where('key', 'financeiro_pix_keys')
                                        ->value('value');
                                }

                                // 3. Normalização Bruta: Garante que temos um array
                                $dados = [];
                                if (is_array($valor)) {
                                    $dados = $valor; // Já era array
                                } elseif (is_string($valor)) {
                                    $dados = json_decode($valor, true) ?? []; // Era string, virou array
                                }

                                // 4. Monta a lista para o Dropdown
                                $opcoes = [];
                                foreach ($dados as $item) {
                                    if (!empty($item['chave'])) {
                                        // Formata: "CHAVE - TITULAR"
                                        $label = $item['chave'];
                                        if (!empty($item['titular'])) {
                                            $label .= " ({$item['titular']})";
                                        }
                                        // O valor salvo é a própria chave
                                        $opcoes[$item['chave']] = $label;
                                    }
                                }

                                return $opcoes;
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(fn(Forms\Get $get) => $get('pdf_incluir_pix'))
                            ->visible(fn(Forms\Get $get) => $get('pdf_incluir_pix'))
                            ->columnSpanFull(),
                        // Seleção de Vendedor com Trigger de Cálculo
                        Forms\Components\Select::make('vendedor_id')
                            ->label('Vendedor')
                            ->options(function () {
                                return \App\Models\Cadastro::where('tipo', 'vendedor')->orderBy('nome')->pluck('nome', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $total = (float) $get('valor_total');
                                if ($state) {
                                    $vendedor = \App\Models\Cadastro::find($state);
                                    // Puxa Loja Vinculada
                                    if ($vendedor && $vendedor->parent_id) {
                                        $set('loja_id', $vendedor->parent_id);
                                        // Calcula Loja
                                        $loja = \App\Models\Cadastro::find($vendedor->parent_id);
                                        if ($loja)
                                            $set('comissao_loja', ($total * $loja->comissao_percentual) / 100);
                                    }
                                    // Calcula Vendedor
                                    if ($vendedor) {
                                        $set('comissao_vendedor', ($total * $vendedor->comissao_percentual) / 100);
                                    }
                                } else {
                                    $set('comissao_vendedor', 0);
                                }
                            }),
                        // Seleção de Loja
                        Forms\Components\Select::make('loja_id')
                            ->label('Loja Parceira')
                            ->options(function () {
                                return \App\Models\Cadastro::where('tipo', 'loja')->orderBy('nome')->pluck('nome', 'id')->toArray();
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $total = (float) $get('valor_total');
                                if ($state) {
                                    $loja = \App\Models\Cadastro::find($state);
                                    if ($loja)
                                        $set('comissao_loja', ($total * $loja->comissao_percentual) / 100);
                                } else {
                                    $set('comissao_loja', 0);
                                }
                            }),
                        // Campos de Valor (R$)
                        Forms\Components\TextInput::make('comissao_vendedor')
                            ->label('Comissão Vend. (R$)')
                            ->prefix('R$')
                            ->numeric()
                            ->readOnly(),
                        Forms\Components\TextInput::make('comissao_loja')
                            ->label('Comissão Loja (R$)')
                            ->prefix('R$')
                            ->numeric()
                            ->readOnly(),
                    ])->columns(5),
                // 3. ITENS (MANTIDO)
                Forms\Components\Section::make('Detalhamento')
                    ->schema([
                        Forms\Components\Repeater::make('itens')
                            ->relationship('itens')
                            ->schema([
                                Forms\Components\Select::make('item_nome')
                                    ->label('Item')
                                    ->options(function () {
                                        return \App\Models\TabelaPreco::query()
                                            ->where('ativo', true)
                                            ->distinct()
                                            ->pluck('nome_item', 'nome_item')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        // Tenta encontrar um item padrão para definir unidade
                                        $item = \App\Models\TabelaPreco::where('nome_item', $state)->first();
                                        if ($item) {
                                            $set('unidade', $item->unidade_medida);
                                        }
                                        self::atualizarPrecoItem($set, $get);
                                    })->columnSpan(4),

                                Forms\Components\Select::make('servico_tipo')
                                    ->label('Tipo de Serviço')
                                    ->options(\App\Enums\ServiceType::class)
                                    ->required()
                                    ->default('higienizacao')
                                    ->live()
                                    ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get) => self::atualizarPrecoItem($set, $get))
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('quantidade')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $set('subtotal', (float) $get('quantidade') * (float) $get('valor_unitario'));
                                        self::recalcularTotal($set, $get);
                                    })->columnSpan(2),

                                Forms\Components\TextInput::make('valor_unitario')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $set('subtotal', (float) $get('quantidade') * (float) $get('valor_unitario'));
                                        self::recalcularTotal($set, $get);
                                    })->columnSpan(3),

                                Forms\Components\TextInput::make('subtotal')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->prefix('R$')
                                    ->columnSpan(3),

                                Forms\Components\Hidden::make('unidade'),
                            ])
                            ->columns(15)
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get) => self::recalcularTotal($set, $get)),
                    ]),
                // 4. TOTAL
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('valor_total')
                            ->label('VALOR TOTAL')
                            ->numeric()->prefix('R$')
                            ->extraInputAttributes(['style' => 'font-size:1.5rem;font-weight:bold;color:#16a34a;background-color:#f0fdf4;'])
                            ->readOnly()->dehydrated()->columnSpanFull(),
                        Forms\Components\Textarea::make('observacoes')->label('Observações')->columnSpanFull(),
                    ]),
                // GALERIA DE ARQUIVOS E MÍDIA
                \Filament\Forms\Components\Section::make('Galeria de Arquivos e Mídia')
                    ->collapsible()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('arquivos')
                            ->label('Anexos (Fotos, PDFs, Docs)')
                            ->collection('arquivos')
                            ->multiple()
                            ->disk('public')
                            ->maxSize(20480) // 20MB in KB
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    // --- HELPER DE PREÇOS DO CATÁLOGO ---
    public static function atualizarPrecoItem(Forms\Set $set, Forms\Get $get): void
    {
        $nomeItem = $get('item_nome');
        $tipoServico = $get('servico_tipo');

        if (!$nomeItem || !$tipoServico)
            return;

        // Busca preço na Tabela
        $preco = 0;

        if ($tipoServico === \App\Enums\ServiceType::Combo->value) {
            $higi = \App\Models\TabelaPreco::where('nome_item', $nomeItem)->where('tipo_servico', \App\Enums\ServiceType::Higienizacao->value)->value('preco_vista') ?? 0;
            $imper = \App\Models\TabelaPreco::where('nome_item', $nomeItem)->where('tipo_servico', \App\Enums\ServiceType::Impermeabilizacao->value)->value('preco_vista') ?? 0;
            $preco = $higi + $imper;
        } elseif ($tipoServico === \App\Enums\ServiceType::Outro->value) {
            return; // Não altera preço para permitir digitação manual
        } else {
            // Busca direto
            $preco = \App\Models\TabelaPreco::where('nome_item', $nomeItem)
                ->where('tipo_servico', $tipoServico)
                ->value('preco_vista') ?? 0;
        }

        $set('valor_unitario', $preco);
        $set('subtotal', (float) $get('quantidade') * $preco);

        // Chama recalculo global
        self::recalcularTotal($set, $get);
    }

    // --- FUNÇÃO CENTRAL DE CÁLCULO ---
    public static function recalcularTotal(Forms\Set $set, Forms\Get $get): void
    {
        // Coleta os itens do repeater (prioridade para o contexto corrente)
        $itens = $get('itens') ?? $get('../../itens') ?? [];

        if (!is_array($itens)) {
            $itens = [];
        }

        // Soma com conversão segura
        $total = collect($itens)->sum(function ($item) {
            return floatval($item['subtotal'] ?? 0);
        });

        // Atualiza ambos os caminhos do formulário
        $set('valor_total', $total);
        $set('../../valor_total', $total);

        // Recalcula comissões de vendedor/loja
        $vendId = $get('vendedor_id') ?? $get('../../vendedor_id');
        if ($vendId && ($v = \App\Models\Cadastro::find($vendId))) {
            $valorV = round($total * (floatval($v->comissao_percentual ?? 0) / 100), 2);
            $set('comissao_vendedor', $valorV);
            $set('../../comissao_vendedor', $valorV);
        }

        $lojaId = $get('loja_id') ?? $get('../../loja_id');
        if ($lojaId && ($l = \App\Models\Cadastro::find($lojaId))) {
            $valorL = round($total * (floatval($l->comissao_percentual ?? 0) / 100), 2);
            $set('comissao_loja', $valorL);
            $set('../../comissao_loja', $valorL);
        }
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // CABEÇALHO COM STATUS E VALORES
                Section::make('Resumo do Pedido')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('numero')
                            ->label('Número')
                            ->weight('bold')
                            ->size(TextEntry\TextEntrySize::Large),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'aprovado' => 'success',
                                'rejeitado' => 'danger',
                                'enviado' => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('created_at')
                            ->label('Emissão')
                            ->date('d/m/Y'),

                        TextEntry::make('data_validade')
                            ->label('Válido até')
                            ->date('d/m/Y')
                            ->color('danger'),
                    ]),
                // DADOS DO CLIENTE
                Section::make('Dados do Cliente')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('cliente.nome')->label('Nome')->weight('bold'),
                            TextEntry::make('cliente.telefone')->label('WhatsApp'),
                            TextEntry::make('cliente.email')->label('E-mail'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('cliente.cidade')->label('Cidade'),
                            TextEntry::make('cliente.bairro')->label('Bairro'),
                            TextEntry::make('cliente.logradouro')->label('Endereço'),
                        ]),
                    ]),
                // LISTA DE ITENS (REPEATABLE)
                Section::make('Itens do Orçamento')
                    ->schema([
                        RepeatableEntry::make('itens')
                            ->label('') // Remove label redundante
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('item_nome')->label('Item')->weight('bold'),
                                    TextEntry::make('servico_tipo')->label('Serviço')->badge(),
                                    TextEntry::make('quantidade')->label('Qtd'),
                                    TextEntry::make('subtotal')
                                        ->label('Subtotal')
                                        ->money('BRL')
                                        ->weight('bold')
                                        ->color('success'),
                                ]),
                            ])
                            ->grid(1) // Lista um embaixo do outro
                    ]),
                // TOTALIZADOR
                Section::make()
                    ->schema([
                        TextEntry::make('valor_total')
                            ->label('VALOR TOTAL')
                            ->money('BRL')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight('black')
                            ->color('success')
                            ->alignRight(),

                        TextEntry::make('observacoes')
                            ->label('Observações')
                            ->markdown(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('Nº')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable(), // Permite copiar o número com 1 clique

                Tables\Columns\TextColumn::make('cliente.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(30), // Evita que nomes gigantes quebrem a tabela

                Tables\Columns\TextColumn::make('valor_total')
                    ->money('BRL')
                    ->label('Valor')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'), // Dinheiro sempre verde

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'aprovado' => 'success',
                        'rejeitado' => 'danger',
                        'enviado' => 'warning',
                        'rascunho' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('data_validade')
                    ->label('Validade')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Escondido por padrão para limpar a tela

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Emissão')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                // Filtro Rápido por Status
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'rascunho' => 'Rascunho',
                        'enviado' => 'Enviado',
                        'aprovado' => 'Aprovado',
                        'rejeitado' => 'Rejeitado',
                    ]),
            ])
            ->actions([
                // 1. PDF (Botão de Texto Verde)
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->button() // Força estilo botão para destaque
                    ->url(fn(Orcamento $record) => route('orcamento.pdf', $record))
                    ->openUrlInNewTab(),

                // 2. Gerar OS (Aprovar e criar Ordem de Serviço) - COM MODAL DE DATA/HORA/LOCAL
                Tables\Actions\Action::make('gerar_os')
                    ->label('Aprovar & Gerar OS')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->slideOver()
                    ->modalWidth('3xl')
                    ->modalHeading('Aprovar Orçamento e Gerar OS')
                    ->modalDescription('Configure a data e horário do serviço. Após aprovação, será criada a Ordem de Serviço, o agendamento e o lançamento financeiro.')
                    ->modalSubmitActionLabel('✓ Aprovar e Criar Registros')
                    ->visible(fn(Orcamento $record) => in_array($record->status, ['rascunho', 'enviado', 'pendente']))
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('data_servico')
                                    ->label('📅 Data do Serviço')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Obrigatório para criar agenda')
                                    ->columnSpan(2),

                                Forms\Components\TimePicker::make('hora_inicio')
                                    ->label('🕐 Hora de Início')
                                    ->default('09:00')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),

                                Forms\Components\TimePicker::make('hora_fim')
                                    ->label('🕐 Hora de Término')
                                    ->default('17:00')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('local_servico')
                            ->label('📍 Local do Serviço')
                            ->required()
                            ->rows(2)
                            ->default(function ($record) {
                                $cadastro = $record->cliente;
                                return trim(implode(', ', array_filter([
                                    $cadastro?->logradouro,
                                    $cadastro && ($cadastro->numero ?? false) ? "nº {$cadastro->numero}" : null,
                                    $cadastro?->complemento,
                                    $cadastro?->bairro,
                                    $cadastro?->cidade,
                                    $cadastro?->estado,
                                    $cadastro?->cep ? "CEP: {$cadastro->cep}" : null,
                                ])));
                            })
                            ->helperText('Endereço completo onde o serviço será realizado (pode ser editado)'),

                        Forms\Components\Textarea::make('observacoes_os')
                            ->label('📝 Observações para a OS')
                            ->rows(3)
                            ->placeholder('Observações adicionais para a Ordem de Serviço...'),
                    ])
                    ->action(function (Orcamento $record, array $data): void {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            // 1. Criar Ordem de Serviço
                            $cadastro = $record->cliente;
                            $enderecoCompleto = $data['local_servico'] ?? 'Endereço não informado';

                            $osData = [
                                'numero_os' => \App\Models\OrdemServico::gerarNumeroOS(),
                                'orcamento_id' => $record->id,
                                'cadastro_id' => $record->cadastro_id,
                                'loja_id' => $record->loja_id,
                                'vendedor_id' => $record->vendedor_id,
                                'tipo_servico' => $record->tipo_servico ?? 'servico',
                                'descricao_servico' => $record->descricao_servico ?? 'Conforme orçamento ' . $record->numero,
                                'data_abertura' => now(),
                                'data_prevista' => $data['data_servico'] ?? null,
                                'status' => 'pendente',
                                'valor_total' => $record->valor_total,
                                'observacoes' => $data['observacoes_os'] ?? $record->observacoes,
                                'criado_por' => auth()->user()->name ?? auth()->id(),
                            ];

                            $os = \App\Models\OrdemServico::create($osData);

                            // Copiar itens do orçamento para a OS
                            foreach ($record->itens as $item) {
                                \App\Models\OrdemServicoItem::create([
                                    'ordem_servico_id' => $os->id,
                                    'descricao' => $item->item_nome ?? $item->descricao_item ?? 'Serviço',
                                    'quantidade' => $item->quantidade,
                                    'unidade_medida' => $item->unidade ?? $item->unidade_medida ?? 'un',
                                    'valor_unitario' => $item->valor_unitario,
                                    'subtotal' => $item->subtotal,
                                ]);
                            }

                            // 2. Criar registro na Agenda (APENAS SE TIVER DATA)
                            if (!empty($data['data_servico'])) {
                                $dataServico = \Carbon\Carbon::parse($data['data_servico']);
                                $horaInicio = \Carbon\Carbon::parse($data['hora_inicio'] ?? '09:00');
                                $horaFim = \Carbon\Carbon::parse($data['hora_fim'] ?? '17:00');

                                \App\Models\Agenda::create([
                                    'titulo' => sprintf(
                                        '%s - %s',
                                        match ($record->tipo_servico ?? 'servico') {
                                            'higienizacao' => '🧼 Higienização',
                                            'impermeabilizacao' => '💧 Impermeabilização',
                                            'higienizacao_impermeabilizacao' => '🧼💧 Hig + Imper',
                                            default => 'Serviço',
                                        },
                                        $cadastro?->nome ?? 'Cliente'
                                    ),
                                    'descricao' => $record->descricao_servico ?? ('Conforme orçamento ' . $record->numero),
                                    'cadastro_id' => $record->cadastro_id,
                                    'ordem_servico_id' => $os->id,
                                    'orcamento_id' => $record->id,
                                    'tipo' => 'servico',
                                    'data_hora_inicio' => $dataServico->copy()->setTimeFromTimeString($horaInicio->format('H:i:s')),
                                    'data_hora_fim' => $dataServico->copy()->setTimeFromTimeString($horaFim->format('H:i:s')),
                                    'status' => 'agendado',
                                    'local' => $enderecoCompleto ?: 'Endereço não informado',
                                    'endereco_completo' => $enderecoCompleto,
                                    'observacoes' => $data['observacoes_os'] ?? ('Agendado automaticamente - ' . $record->numero),
                                    'cor' => match ($record->tipo_servico ?? 'servico') {
                                        'higienizacao' => '#3b82f6',
                                        'impermeabilizacao' => '#f59e0b',
                                        'higienizacao_impermeabilizacao' => '#10b981',
                                        default => '#6b7280',
                                    },
                                    'criado_por' => auth()->id(),
                                ]);
                            }

                            // 3. Criar lançamento no Financeiro (Conta a Receber) - STATUS PENDENTE
                            \App\Models\Financeiro::create([
                                'tipo' => 'entrada',
                                'categoria' => 'servico',
                                'descricao' => sprintf(
                                    'Serviço - OS %s - Cliente: %s',
                                    $os->numero_os,
                                    $cadastro?->nome ?? 'Cliente'
                                ),
                                'valor' => $record->valor_total,
                                'data' => $data['data_servico'] ?? now(),
                                'data_vencimento' => $data['data_servico'] ?? now()->addDays(30),
                                'status' => 'pendente',
                                'forma_pagamento' => $record->forma_pagamento ?? null,
                                'cadastro_id' => $record->cadastro_id,
                                'ordem_servico_id' => $os->id,
                                'orcamento_id' => $record->id,
                            ]);

                            // 4. Atualizar orçamento com link para OS
                            $record->update([
                                'status' => 'aprovado',
                            ]);
                        });

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Orçamento Aprovado!')
                            ->body('A Ordem de Serviço, Agenda e Financeiro foram criados automaticamente.')
                            ->send();
                    }),

                // 3. VISUALIZAR (Ícone Cinza)
                Tables\Actions\ViewAction::make()
                    ->label('') // Sem texto para economizar espaço
                    ->tooltip('Visualizar Detalhes')
                    ->modalWidth('5xl'),

                // 3. EDITAR (Ícone Padrão)
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar Orçamento'),

                // 4. EDITAR VALOR FINAL (Desconto do Prestador)
                Tables\Actions\Action::make('editar_valor')
                    ->label('')
                    ->tooltip('Editar Valor Final')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->modalHeading('Editar Valor Final do Orçamento')
                    ->modalDescription('Informe o valor final acordado. A diferença será registrada como desconto do prestador.')
                    ->modalSubmitActionLabel('💰 Salvar Valor')
                    ->form([
                        Forms\Components\TextInput::make('valor_original')
                            ->label('Valor Original (calculado)')
                            ->prefix('R$')
                            ->disabled()
                            ->default(fn(Orcamento $record) => number_format($record->valor_total, 2, ',', '.')),

                        Forms\Components\TextInput::make('valor_final')
                            ->label('Valor Final Acordado')
                            ->prefix('R$')
                            ->numeric()
                            ->required()
                            ->default(fn(Orcamento $record) => $record->valor_final_editado ?? $record->valor_total)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Orcamento $record) {
                                $desconto = $record->valor_total - floatval($state);
                                $set('desconto_calculado', 'R$ ' . number_format(max(0, $desconto), 2, ',', '.'));
                            }),

                        Forms\Components\TextInput::make('desconto_calculado')
                            ->label('Desconto do Prestador')
                            ->prefix('')
                            ->disabled()
                            ->helperText('Este valor será descontado da comissão do prestador'),
                    ])
                    ->action(function (Orcamento $record, array $data): void {
                        $valorFinal = floatval($data['valor_final']);
                        $desconto = $record->valor_total - $valorFinal;

                        $record->update([
                            'valor_final_editado' => $valorFinal,
                            'desconto_prestador' => max(0, $desconto),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('💰 Valor Atualizado!')
                            ->body(sprintf(
                                'Novo valor: R$ %s | Desconto prestador: R$ %s',
                                number_format($valorFinal, 2, ',', '.'),
                                number_format(max(0, $desconto), 2, ',', '.')
                            ))
                            ->send();
                    }),

                // 5. COMPARTILHAR
                Tables\Actions\Action::make('share')
                    ->label('')
                    ->tooltip('Compartilhar')
                    ->icon('heroicon-o-share')
                    ->color('success')
                    ->action(function (Orcamento $record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Link Copiado!')
                            ->body(url("/admin/orcamentos/{$record->id}"))
                            ->success()
                            ->send();
                    }),

                // 6. WHATSAPP ACTIONS (NOVO)
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('wa_ola')
                        ->label('👋 Olá Inicial')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->url(fn(Orcamento $record) => app(\App\Services\WhatsAppService::class)->getWelcomeLink($record->cliente))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('wa_proposta')
                        ->label('📄 Enviar Proposta')
                        ->icon('heroicon-o-document-text')
                        ->url(fn(Orcamento $record) => app(\App\Services\WhatsAppService::class)->getProposalLink($record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('wa_cobrar')
                        ->label('🤔 Cobrar Resposta')
                        ->icon('heroicon-o-clock')
                        ->url(fn(Orcamento $record) => app(\App\Services\WhatsAppService::class)->getFollowUpLink($record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('wa_pix')
                        ->label('💸 Enviar PIX')
                        ->icon('heroicon-o-banknotes')
                        ->url(fn(Orcamento $record) => app(\App\Services\WhatsAppService::class)->getPaymentLink($record))
                        ->openUrlInNewTab(),
                ])
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success'),

                // 7. EXCLUIR (Ícone Vermelho)
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrcamentos::route('/'),
            'create' => Pages\CreateOrcamento::route('/create'),
            'edit' => Pages\EditOrcamento::route('/{record}/edit'),
        ];
    }
}




