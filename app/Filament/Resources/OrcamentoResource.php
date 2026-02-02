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

                                // 4. Monta a lista para o Dropdown com informações detalhadas
                                $opcoes = [];
                                foreach ($dados as $item) {
                                    if (!empty($item['chave'])) {
                                        // Formata: "TIPO: CHAVE - TITULAR (STATUS)"
                                        $tipo = ucfirst($item['tipo'] ?? 'N/A');
                                        $titular = $item['titular'] ?? 'Sem titular';
                                        $validada = ($item['validada'] ?? false) ? '✓' : '⚠';
                                        
                                        $label = "{$tipo}: {$item['chave']} ({$titular}) {$validada}";
                                        
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
                            ->helperText('Chaves PIX disponíveis. ✓ = Validada, ⚠ = Não validada')
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
                                    })->columnSpan(5),

                                Forms\Components\Select::make('servico_tipo')
                                    ->label('Serviço')
                                    ->options(\App\Services\ServiceTypeManager::getOptions())
                                    ->required()
                                    ->default('higienizacao')
                                    ->live()
                                    ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get) => self::atualizarPrecoItem($set, $get))
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('quantidade')
                                    ->label('Qtd')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $set('subtotal', (float) $get('quantidade') * (float) $get('valor_unitario'));
                                        self::recalcularTotal($set, $get);
                                    })->columnSpan(1),

                                Forms\Components\TextInput::make('valor_unitario')
                                    ->label('Vlr Unit.')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $set('subtotal', (float) $get('quantidade') * (float) $get('valor_unitario'));
                                        self::recalcularTotal($set, $get);
                                    })->columnSpan(2),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Total')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->prefix('R$')
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('descricao')
                                    ->label('Descrição (opcional)')
                                    ->placeholder('Detalhes adicionais...')
                                    ->maxLength(500)
                                    ->columnSpan(3),

                                Forms\Components\Hidden::make('unidade'),
                            ])
                            ->columns(15)
                            ->live()
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => ($state['item_nome'] ?? 'Item') . ' - ' . \App\Services\ServiceTypeManager::getLabel($state['servico_tipo'] ?? ''))
                            ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get) => self::recalcularTotal($set, $get)),
                    ]),

                // (SEÇÃO DE FOTOS REMOVIDA PARA EVITAR DUPLICIDADE)

                // 5. TOTAL
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('valor_total')
                            ->label('VALOR TOTAL')
                            ->numeric()->prefix('R$')
                            ->extraInputAttributes(['style' => 'font-size:1.5rem;font-weight:bold;color:#16a34a;background-color:#f0fdf4;'])
                            ->readOnly()->dehydrated()->columnSpanFull(),
                        Forms\Components\Textarea::make('observacoes')->label('Observações')->columnSpanFull(),
                    ]),
                // GALERIA DE ARQUIVOS E MÍDIA (CONSOLIDADO)
                \Filament\Forms\Components\Section::make('Fotos e Arquivos')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Toggle::make('pdf_mostrar_fotos')
                            ->label('Exibir Imagens e Fotos no PDF?')
                            ->helperText('Se marcado, as imagens deste painel aparecerão no PDF gerado.')
                            ->default(fn() => \App\Models\Setting::get('pdf_mostrar_fotos_global', true))
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('arquivos')
                            ->label('Upload de Fotos e Documentos')
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

        // Adiciona valores de "extra_attributes" (Nicho)
        $extras = $get('extra_attributes') ?? $get('../../extra_attributes') ?? [];
        if (is_array($extras)) {
            foreach ($extras as $key => $value) {
                // Tenta limpar e converter para float se for string numérica
                if (is_numeric($value)) {
                    $total += (float) $value;
                } elseif (is_string($value)) {
                    // Remove R$, pontos de milhar, troca vírgula por ponto
                    $clean = preg_replace('/[^0-9,]/', '', $value); // 1.200,50 -> 1200,50
                    $clean = str_replace(',', '.', $clean); // 1200.50
                    if (is_numeric($clean)) {
                        $total += (float) $clean;
                    }
                }
            }
        }

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
                // ===== CABEÇALHO DO ORÇAMENTO =====
                Section::make()
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('numero')
                                ->label('Número')
                                ->weight('bold')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->copyable(),

                            TextEntry::make('status')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'aprovado' => 'success',
                                    'rejeitado', 'cancelado' => 'danger',
                                    'enviado' => 'warning',
                                    'pendente' => 'info',
                                    default => 'gray',
                                }),

                            TextEntry::make('created_at')
                                ->label('Emissão')
                                ->date('d/m/Y')
                                ->icon('heroicon-m-calendar'),

                            TextEntry::make('data_validade')
                                ->label('Válido até')
                                ->date('d/m/Y')
                                ->color(fn($record) => $record->data_validade && $record->data_validade < now() ? 'danger' : 'success'),
                        ]),
                        Grid::make(4)->schema([
                            TextEntry::make('cliente.nome')
                                ->label('Cliente')
                                ->icon('heroicon-m-user')
                                ->weight('bold')
                                ->url(fn($record) => $record->cadastro_id 
                                    ? \App\Filament\Resources\CadastroResource::getUrl('view', ['record' => $record->cadastro_id]) 
                                    : null)
                                ->color('primary'),
                            TextEntry::make('cliente.telefone')
                                ->label('WhatsApp')
                                ->icon('heroicon-m-chat-bubble-left-right')
                                ->url(fn($state) => $state ? 'https://wa.me/55' . preg_replace('/\D/', '', $state) : null, true),
                            TextEntry::make('vendedor.nome')
                                ->label('Vendedor')
                                ->icon('heroicon-m-user-circle'),
                            TextEntry::make('loja.nome')
                                ->label('Loja')
                                ->icon('heroicon-m-building-storefront'),
                        ]),
                    ]),

                // ===== RESUMO FINANCEIRO =====
                Section::make('💰 Resumo Financeiro')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('valor_total')
                                ->label('💵 Valor Total')
                                ->money('BRL')
                                ->color('success')
                                ->weight('bold')
                                ->size(TextEntry\TextEntrySize::Large),
                            TextEntry::make('comissao_vendedor')
                                ->label('👤 Comissão Vendedor')
                                ->money('BRL')
                                ->color('warning'),
                            TextEntry::make('comissao_loja')
                                ->label('🏪 Comissão Loja')
                                ->money('BRL')
                                ->color('warning'),
                            TextEntry::make('tipo_servico')
                                ->label('🛠️ Tipo Serviço')
                                ->badge()
                                ->color('primary'),
                        ]),
                    ])
                    ->collapsible(),

                // ===== DADOS DO CLIENTE =====
                Section::make('👤 Dados do Cliente')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('cliente.nome')
                                ->label('Nome')
                                ->weight('bold'),
                            TextEntry::make('cliente.telefone')
                                ->label('WhatsApp')
                                ->url(fn($state) => $state ? 'https://wa.me/55' . preg_replace('/\D/', '', $state) : null, true),
                            TextEntry::make('cliente.email')
                                ->label('E-mail')
                                ->copyable(),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('cliente.cidade')
                                ->label('Cidade'),
                            TextEntry::make('cliente.bairro')
                                ->label('Bairro'),
                            TextEntry::make('cliente.logradouro')
                                ->label('Endereço'),
                        ]),
                    ])
                    ->collapsible(),

                // ===== DADOS PERSONALIZADOS DO NICHO =====
                Section::make('🏷️ Dados Personalizados')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('extra_attributes')
                            ->label('')
                            ->keyLabel('Campo')
                            ->valueLabel('Valor')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn($record) => !empty($record->extra_attributes)),

                // ===== ITENS DO ORÇAMENTO =====
                Section::make('📋 Itens do Orçamento')
                    ->schema([
                        RepeatableEntry::make('itens')
                            ->label('')
                            ->schema([
                                Grid::make(5)->schema([
                                    TextEntry::make('item_nome')
                                        ->label('Item')
                                        ->weight('bold')
                                        ->columnSpan(2),
                                    TextEntry::make('servico_tipo')
                                        ->label('Serviço')
                                        ->badge()
                                        ->color('info'),
                                    TextEntry::make('quantidade')
                                        ->label('Qtd')
                                        ->alignCenter(),
                                    TextEntry::make('subtotal')
                                        ->label('Subtotal')
                                        ->money('BRL')
                                        ->weight('bold')
                                        ->color('success'),
                                ]),
                            ])
                            ->grid(1),
                    ])
                    ->collapsible(),

                // ===== TOTALIZADOR =====
                Section::make()
                    ->schema([
                        Grid::make(1)->schema([
                            TextEntry::make('valor_total')
                                ->label('VALOR TOTAL')
                                ->money('BRL')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold')
                                ->color('success'),
                        ]),
                    ]),

                // ===== OBSERVAÇÕES =====
                Section::make('📝 Observações')
                    ->schema([
                        TextEntry::make('observacoes')
                            ->label('')
                            ->markdown()
                            ->placeholder('Sem observações')
                            ->columnSpanFull(),
                        TextEntry::make('descricao_servico')
                            ->label('Descrição do Serviço')
                            ->markdown()
                            ->placeholder('Sem descrição'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // ===== INFORMAÇÕES DO SISTEMA =====
                Section::make('ℹ️ Informações do Sistema')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('created_at')
                                ->label('Criado em')
                                ->dateTime('d/m/Y H:i'),
                            TextEntry::make('updated_at')
                                ->label('Atualizado em')
                                ->dateTime('d/m/Y H:i'),
                            TextEntry::make('criado_por')
                                ->label('Criado por'),
                            TextEntry::make('id')
                                ->label('ID'),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
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

                Tables\Columns\TextColumn::make('servico_tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => \App\Services\ServiceTypeManager::getColor($state))
                    ->formatStateUsing(fn(string $state): string => \App\Services\ServiceTypeManager::getLabel($state))
                    ->sortable(),
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
                                return $cadastro?->formatEnderecoCompleto() ?? '';
                            })
                            ->helperText('Endereço completo onde o serviço será realizado (pode ser editado)'),

                        Forms\Components\Textarea::make('observacoes_os')
                            ->label('📝 Observações para a OS')
                            ->rows(3)
                            ->placeholder('Observações adicionais para a Ordem de Serviço...')
                            ->columnSpanFull(),

                        Forms\Components\Section::make('Ajuste de Valores')
                            ->description('Defina o valor final acordado. A diferença será lançada como desconto.')
                            ->schema([
                                Forms\Components\TextInput::make('valor_original')
                                    ->label('Valor Original')
                                    ->prefix('R$')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(fn(Orcamento $record) => number_format((float) $record->valor_total, 2, ',', '.')),

                                Forms\Components\TextInput::make('valor_final')
                                    ->label('Valor Final Acordado')
                                    ->prefix('R$')
                                    ->numeric()
                                    ->required()
                                    ->default(fn(Orcamento $record) => $record->valor_final_editado ?? $record->valor_total)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Orcamento $record) {
                                        $val = floatval($state);
                                        $desconto = (float) $record->valor_total - $val;
                                        $set('desconto_calculado', number_format(max(0, $desconto), 2, ',', '.'));
                                    }),

                                Forms\Components\TextInput::make('desconto_calculado')
                                    ->label('Desconto do Prestador (Previsto)')
                                    ->prefix('R$')
                                    ->disabled()
                                    ->dehydrated(false) // Apenas visual
                            ])->columns(3),
                    ])
                    ->action(function (Orcamento $record, array $data): void {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            // 0. Preparar dados
                            $cadastro = $record->cliente;
                            $enderecoCompleto = $data['local_servico'] ?? 'Endereço não informado';

                            // 1. Atualizar valores do Orçamento antes de gerar OS
                            $valorFinal = floatval($data['valor_final']);
                            $desconto = $record->valor_total - $valorFinal;

                            $record->update([
                                'valor_final_editado' => $valorFinal,
                                'desconto_prestador' => max(0, $desconto),
                            ]);

                            // 1. Criar Ordem de Serviço (Sem disparar eventos para evitar Agenda duplicada)
                            $os = \App\Models\OrdemServico::withoutEvents(function () use ($record, $data, $valorFinal) {
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
                                    'valor_total' => $valorFinal,
                                    'observacoes' => $data['observacoes_os'] ?? $record->observacoes,
                                    'extra_attributes' => $record->extra_attributes,
                                    'criado_por' => auth()->user()->name ?? auth()->id(),
                                ];

                                return \App\Models\OrdemServico::create($osData);
                            });

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
                                        \App\Services\ServiceTypeManager::getLabel($record->tipo_servico ?? 'servico'),
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
                                    'extra_attributes' => $record->extra_attributes, // COPY DYNAMIC ATTRIBUTES
                                    'cor' => \App\Services\ServiceTypeManager::getColor($record->tipo_servico ?? 'servico'),
                                    'criado_por' => auth()->id(),
                                ]);
                            }

                            // 3. Criar lançamento no Financeiro (Conta a Receber) - STATUS PENDENTE
                            \App\Models\Financeiro::create([
                                'tipo' => 'entrada',
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
                                'extra_attributes' => $record->extra_attributes, // COPY DYNAMIC ATTRIBUTES
                            ]);

                            // 4. CRIAR LANÇAMENTOS DE COMISSÃO (Despesas)
                            // 4.1 Comissão do Vendedor
                            if (!empty($record->comissao_vendedor) && $record->comissao_vendedor > 0) {
                                $categoriaVendedor = \App\Models\Categoria::where('slug', 'comissao-vendedor')->first();
                                $vendedor = $record->vendedor;
                                
                                \App\Models\Financeiro::create([
                                    'tipo' => 'saida',
                                    'descricao' => sprintf(
                                        'Comissão Vendedor - OS %s - %s',
                                        $os->numero_os,
                                        $vendedor?->nome ?? 'Vendedor não identificado'
                                    ),
                                    'valor' => $record->comissao_vendedor,
                                    'data' => $data['data_servico'] ?? now(),
                                    'data_vencimento' => $data['data_servico'] ?? now()->addDays(30),
                                    'status' => 'pendente',
                                    'categoria_id' => $categoriaVendedor?->id,
                                    'cadastro_id' => $record->vendedor_id, // Associa ao vendedor
                                    'ordem_servico_id' => $os->id,
                                    'orcamento_id' => $record->id,
                                    'observacoes' => sprintf(
                                        'Comissão de %.2f%% sobre venda total de R$ %s',
                                        $vendedor?->comissao_percentual ?? 0,
                                        number_format($record->valor_total, 2, ',', '.')
                                    ),
                                ]);
                            }

                            // 4.2 Comissão da Loja
                            if (!empty($record->comissao_loja) && $record->comissao_loja > 0) {
                                $categoriaLoja = \App\Models\Categoria::where('slug', 'comissao-loja')->first();
                                $loja = $record->loja;
                                
                                \App\Models\Financeiro::create([
                                    'tipo' => 'saida',
                                    'descricao' => sprintf(
                                        'Comissão Loja - OS %s - %s',
                                        $os->numero_os,
                                        $loja?->nome ?? 'Loja não identificada'
                                    ),
                                    'valor' => $record->comissao_loja,
                                    'data' => $data['data_servico'] ?? now(),
                                    'data_vencimento' => $data['data_servico'] ?? now()->addDays(30),
                                    'status' => 'pendente',
                                    'categoria_id' => $categoriaLoja?->id,
                                    'cadastro_id' => $record->loja_id, // Associa à loja
                                    'ordem_servico_id' => $os->id,
                                    'orcamento_id' => $record->id,
                                    'observacoes' => sprintf(
                                        'Comissão de %.2f%% sobre venda total de R$ %s',
                                        $loja?->comissao_percentual ?? 0,
                                        number_format($record->valor_total, 2, ',', '.')
                                    ),
                                ]);
                            }

                            // 5. Atualizar orçamento com link para OS
                            $record->update([
                                'status' => 'aprovado',
                            ]);
                        });

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Orçamento Aprovado!')
                            ->body('A Ordem de Serviço, Agenda, Financeiro e Comissões foram criados automaticamente.')
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
                            ->default(fn(Orcamento $record) => number_format((float) $record->valor_total, 2, ',', '.')),

                        Forms\Components\TextInput::make('valor_final')
                            ->label('Valor Final Acordado')
                            ->prefix('R$')
                            ->numeric()
                            ->required()
                            ->default(fn(Orcamento $record) => $record->valor_final_editado ?? $record->valor_total)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Orcamento $record) {
                                $desconto = (float) $record->valor_total - floatval($state);
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
                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('Baixar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(Orcamento $record) => route('orcamento.pdf', $record))
                    ->openUrlInNewTab(),

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
            'view' => Pages\ViewOrcamento::route('/{record}'),
            'edit' => Pages\EditOrcamento::route('/{record}/edit'),
        ];
    }
}




