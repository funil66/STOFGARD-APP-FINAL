<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrcamentoResource\Pages;
use App\Models\Orcamento;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use App\Services\OrcamentoFormService;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrcamentoResource extends Resource
{
    protected static ?string $model = Orcamento::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Comercial';

    protected static ?string $label = 'Orçamento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. CABEÇALHO
                Forms\Components\Section::make('Dados do Orçamento')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Select::make('cadastro_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm(\App\Services\ClienteFormService::getFullSchema())
                            ->columnSpan(2), // Give more space


                        Forms\Components\DatePicker::make('data_orcamento')->default(now())->required(),
                        Forms\Components\DatePicker::make('data_validade')->default(now()->addDays(15)),

                        Forms\Components\Hidden::make('status')
                            ->default('rascunho'),
                    ])->columns(['default' => 1, 'lg' => 4]),

                // ... (Keep existing sections but ensuring they use Icons if missing)
                Forms\Components\Section::make('Comercial & Pagamento')
                    ->icon('heroicon-o-currency-dollar')
                    ->description('Gerencie as comissões e a exibição do PIX no PDF.')
                    ->schema([
                        // ... (Copy existing schema for pix keys, seller, etc.)
                        Forms\Components\Toggle::make('pdf_incluir_pix')
                            ->label('Gerar QR Code PIX')
                            ->helperText('Controla se o QR Code e copia e cola aparecem no PDF')
                            ->default(true)
                            ->live(),

                        Forms\Components\Toggle::make('aplicar_desconto_pix')
                            ->label('Aplicar Desconto PIX')
                            ->helperText('Exibe o valor do desconto PIX abaixo do total (não altera o valor final)')
                            ->default(fn() => \App\Models\Setting::get('pdf_aplicar_desconto_global', true))
                            ->live(),

                        Forms\Components\Select::make('pix_chave_selecionada')
                            ->label('Selecionar Chave PIX')
                            ->options(function () {
                                $setting = \App\Models\Setting::find('financeiro_pix_keys');
                                $valor = $setting ? $setting->value : null;
                                if (!$valor) {
                                    $valor = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'financeiro_pix_keys')->value('value');
                                }
                                $dados = [];
                                if (is_array($valor)) {
                                    $dados = $valor;
                                } elseif (is_string($valor)) {
                                    $dados = json_decode($valor, true) ?? [];
                                }
                                $opcoes = [];
                                foreach ($dados as $item) {
                                    if (!empty($item['chave'])) {
                                        $label = ucfirst($item['tipo'] ?? 'N/A') . ": {$item['chave']}";
                                        $opcoes[$item['chave']] = $label;
                                    }
                                }

                                return $opcoes;
                            })
                            ->searchable()
                            ->preload()
                            ->required(fn(Forms\Get $get) => $get('pdf_incluir_pix'))
                            ->visible(fn(Forms\Get $get) => $get('pdf_incluir_pix'))
                            ->columnSpanFull(),

                        Forms\Components\Select::make('vendedor_id')
                            ->label('Vendedor')
                            ->options(fn() => \App\Models\Cadastro::where('tipo', 'vendedor')->pluck('nome', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                // Recalculate total to update commission
                                \App\Services\OrcamentoFormService::recalcularTotal($set, $get);
                            }),

                        Forms\Components\Select::make('loja_id')
                            ->label('Loja/Parceiro')
                            ->options(fn() => \App\Models\Cadastro::whereIn('tipo', ['loja', 'vendedor'])->pluck('nome', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                \App\Services\OrcamentoFormService::recalcularTotal($set, $get);
                            }),

                        Forms\Components\TextInput::make('id_parceiro')
                            ->label('ID Parceiro')
                            ->helperText('Identificador fornecido pelo parceiro comercial')
                            ->maxLength(100),

                        // --- #2a: Configurações de exibição do PDF ---
                        Forms\Components\Fieldset::make('Configurações do PDF')
                            ->schema([
                                Forms\Components\Toggle::make('pdf_mostrar_comissoes')
                                    ->label('Exibir Comissões no PDF')
                                    ->default(true),

                                Forms\Components\Toggle::make('pdf_mostrar_parcelamento')
                                    ->label('Exibir Parcelamento no PDF')
                                    ->default(true),

                                // --- #2b: Alíquotas per-orçamento ---
                                Forms\Components\TextInput::make('pdf_desconto_pix_percentual')
                                    ->label('Desconto PIX (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->placeholder(fn() => (\App\Models\Setting::get('financeiro_desconto_avista', 10)) . '% (padrão)')
                                    ->helperText('Deixe vazio para usar o padrão de Configurações'),
                            ])->columns(3),

                    ])->columns(['default' => 1, 'lg' => 3]),

                Forms\Components\Section::make('Detalhamento')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Forms\Components\Repeater::make('itens')
                            ->relationship('itens')
                            ->schema([
                                Forms\Components\Select::make('item_nome')
                                    ->label('Item')
                                    ->options(fn() => \App\Models\TabelaPreco::where('ativo', true)->pluck('nome_item', 'nome_item'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nome_item')
                                            ->label('Nome do Serviço Customizado')
                                            ->required(),
                                    ])
                                    ->createOptionModalHeading('Adicionar Item Customizado')
                                    ->createOptionAction(fn($action) => $action->label('Adicionar Item Customizado'))
                                    ->createOptionUsing(function ($data) {
                                        // Retorna apenas o nome para uso direto
                                        return $data['nome_item'];
                                    })
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state, $livewire) {
                                        self::atualizarPrecoItem($set, $get);
                                        // Trigger parent recalculate
                                        self::recalcularTotalFromRepeaterItem($set, $get, $livewire);
                                    })
                                    ->columnSpan(['default' => 1, 'md' => 4]),

                                Forms\Components\Select::make('servico_tipo')
                                    ->label('Tipo de Serviço')
                                    ->options(\App\Services\ServiceTypeManager::getOptions())
                                    ->default('higienizacao')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $livewire) {
                                        self::atualizarPrecoItem($set, $get);
                                        // Trigger parent recalculate
                                        self::recalcularTotalFromRepeaterItem($set, $get, $livewire);
                                    })
                                    ->columnSpan(['default' => 1, 'md' => 3]),

                                Forms\Components\TextInput::make('quantidade')
                                    ->numeric()->default(1)->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $livewire) {
                                        // Recalculate subtotal for this item
                                        $quantidade = floatval($get('quantidade') ?? 0);
                                        $valorUnitario = floatval($get('valor_unitario') ?? 0);
                                        $set('subtotal', $quantidade * $valorUnitario);

                                        // Trigger parent recalculate
                                        self::recalcularTotalFromRepeaterItem($set, $get, $livewire);
                                    })
                                    ->columnSpan(['default' => 1, 'md' => 1]),

                                Forms\Components\TextInput::make('valor_unitario')
                                    ->label('Unit.')
                                    ->numeric()->prefix('R$')->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $livewire) {
                                        // Recalculate subtotal for this item
                                        $quantidade = floatval($get('quantidade') ?? 0);
                                        $valorUnitario = floatval($get('valor_unitario') ?? 0);
                                        $set('subtotal', $quantidade * $valorUnitario);

                                        // Trigger parent recalculate
                                        self::recalcularTotalFromRepeaterItem($set, $get, $livewire);
                                    })
                                    ->columnSpan(['default' => 1, 'md' => 2]),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Total')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->numeric()
                                    ->prefix('R$')
                                    ->columnSpan(['default' => 1, 'md' => 2]),
                            ])
                            ->columns(['default' => 1, 'md' => 12])
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                self::recalcularTotal($set, $get);
                            })
                            ->addActionLabel('Adicionar Item')
                            ->reorderable(false)
                            ->deleteAction(
                                fn($action) => $action->after(function (Forms\Set $set, Forms\Get $get) {
                                    self::recalcularTotal($set, $get);
                                })
                            ),
                    ]),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('valor_total')
                            ->label('VALOR TOTAL (Calculado)')
                            ->numeric()->prefix('R$')
                            ->extraInputAttributes(['style' => 'font-size:1.5rem;font-weight:bold;color:#16a34a;background-color:#f0fdf4;'])
                            ->readOnly()->dehydrated()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('valor_final_editado')
                            ->label('VALOR FINAL (Editável)')
                            ->numeric()->prefix('R$')
                            ->placeholder('Deixe vazio para usar o valor calculado')
                            ->helperText('Edite aqui para aplicar desconto/acréscimo manual')
                            ->extraInputAttributes(['style' => 'font-size:1.5rem;font-weight:bold;color:#2563eb;background-color:#eff6ff;'])
                            ->default(null) // Permite null
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $valorTotal = floatval($get('valor_total') ?? 0);
                                $valorEditado = $state === null ? null : floatval($state);

                                if ($valorEditado === null) {
                                    $set('desconto_prestador', 0);
                                    return;
                                }

                                // Calcula o desconto (pode ser negativo se for acréscimo)
                                $desconto = $valorTotal - $valorEditado;
                                $set('desconto_prestador', $desconto);

                                // #1: Recalcula comissões baseadas no novo valor final
                                self::recalcularTotal($set, $get);
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('desconto_prestador')
                            ->label('Desconto/Acréscimo')
                            ->numeric()->prefix('R$')
                            ->readOnly()
                            ->dehydrated()
                            ->default(0) // Garante que nunca seja null no submit
                            ->formatStateUsing(fn($state) => $state ?? 0) // Garante visualização 0 se null
                            ->helperText('Calculado automaticamente (positivo = desconto, negativo = acréscimo)')
                            ->extraInputAttributes(fn($state) => [
                                'style' => floatval($state ?? 0) > 0
                                    ? 'color:#dc2626;font-weight:bold;'
                                    : 'color:#16a34a;font-weight:bold;'
                            ])
                            ->columnSpan(1),
                    ])->columns(['default' => 1, 'md' => 3]),

                // Prévia do desconto PIX (somente leitura, abaixo dos totais)
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('desconto_pix_preview')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                if (!$get('aplicar_desconto_pix')) {
                                    return new \Illuminate\Support\HtmlString('');
                                }
                                $valorTotal = floatval($get('valor_total') ?? 0);
                                if ($valorTotal <= 0) {
                                    return new \Illuminate\Support\HtmlString('<span style="color:#16a34a;font-size:0.85rem;">Informe os itens para calcular o desconto PIX.</span>');
                                }
                                $percentual = floatval(\App\Models\Setting::get('financeiro_desconto_avista', 10));
                                $valorDesconto = $valorTotal * ($percentual / 100);
                                $valorAvista = $valorTotal - $valorDesconto;
                                return new \Illuminate\Support\HtmlString(
                                    '<div style="display:flex;gap:20px;align-items:center;">' .
                                    '<span style="font-size:0.85rem;color:#64748b;">Desconto PIX (' . $percentual . '%): </span>' .
                                    '<strong style="color:#16a34a;font-size:1rem;">- R$ ' . number_format($valorDesconto, 2, ',', '.') . '</strong>' .
                                    '<span style="font-size:0.8rem;color:#64748b;">→ À vista: </span>' .
                                    '<strong style="color:#0ea5e9;font-size:1rem;">R$ ' . number_format($valorAvista, 2, ',', '.') . '</strong>' .
                                    '<span style="font-size:0.75rem;color:#94a3b8;">(valor total não alterado)</span>' .
                                    '</div>'
                                );
                            })
                            ->visible(fn(Forms\Get $get) => (bool) $get('aplicar_desconto_pix'))
                            ->columnSpanFull(),
                    ])
                    ->visible(fn(Forms\Get $get) => (bool) $get('aplicar_desconto_pix'))
                    ->extraAttributes(['style' => 'background:#f0fdf4;border:1px solid #bbf7d0;padding:8px 16px;border-radius:8px;'])
                    ->columnSpanFull(),

                // Campos Hidden para persistir comissões calculadas
                Forms\Components\Hidden::make('comissao_vendedor')->dehydrated(),
                Forms\Components\Hidden::make('comissao_loja')->dehydrated(),

                Forms\Components\Section::make('Observações')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações / Notas Internas')
                            ->rows(4)
                            ->placeholder('Anotações sobre o orçamento, negociação, condições especiais...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Central de Arquivos')
                    ->icon('heroicon-o-paper-clip')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Toggle::make('pdf_mostrar_fotos')->label('Exibir no PDF'),
                        SpatieMediaLibraryFileUpload::make('arquivos')
                            ->collection('arquivos')
                            ->multiple()
                            ->disk('public'),
                    ]),
            ]);
    }

    // Helper functions (delegated to Service)
    public static function atualizarPrecoItem(Forms\Set $set, Forms\Get $get): void
    {
        \App\Services\OrcamentoFormService::atualizarPrecoItem($set, $get);
    }

    public static function recalcularTotal(Forms\Set $set, Forms\Get $get): void
    {
        \App\Services\OrcamentoFormService::recalcularTotal($set, $get);
    }

    /**
     * Helper to recalculate total from within repeater item context
     */
    public static function recalcularTotalFromRepeaterItem(Forms\Set $set, Forms\Get $get, $livewire): void
    {
        // Get all form data from the livewire instance
        $formData = $livewire->data ?? [];

        // Calculate total from all items
        $itens = $formData['itens'] ?? [];
        $total = 0;

        foreach ($itens as $item) {
            $quantidade = floatval($item['quantidade'] ?? 0);
            $valorUnitario = floatval($item['valor_unitario'] ?? 0);
            $total += $quantidade * $valorUnitario;
        }

        // Use relative paths from repeater item to parent
        $set('../../valor_total', number_format($total, 2, '.', ''));

        // Base para comissão: valor editado (se definido) ou total calculado
        $valorEditado = floatval($formData['valor_final_editado'] ?? 0);
        $baseComissao = $valorEditado > 0 ? $valorEditado : $total;

        // Calculate commissions if needed
        $vendedorId = $formData['vendedor_id'] ?? null;
        if ($vendedorId) {
            $vendedor = \App\Models\Cadastro::find($vendedorId);
            if ($vendedor && $vendedor->comissao_percentual > 0) {
                $comissao = ($baseComissao * $vendedor->comissao_percentual) / 100;
                $set('../../comissao_vendedor', number_format($comissao, 2, '.', ''));
            } else {
                $set('../../comissao_vendedor', 0);
            }
        } else {
            $set('../../comissao_vendedor', 0);
        }

        $lojaId = $formData['loja_id'] ?? null;
        if ($lojaId) {
            $loja = \App\Models\Cadastro::find($lojaId);
            if ($loja && $loja->comissao_percentual > 0) {
                $comissaoLoja = ($baseComissao * $loja->comissao_percentual) / 100;
                $set('../../comissao_loja', number_format($comissaoLoja, 2, '.', ''));
            } else {
                $set('../../comissao_loja', 0);
            }
        } else {
            $set('../../comissao_loja', 0);
        }
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ===== CABEÇALHO =====
                \Filament\Infolists\Components\Section::make()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 4])->schema([
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
                                    default => 'gray',
                                }),

                            TextEntry::make('created_at')->label('Emissão')->date('d/m/Y'),
                            TextEntry::make('data_validade')->label('Validade')->date('d/m/Y')->color('warning'),
                            TextEntry::make('id_parceiro')->label('ID Parceiro')->badge()->color('info'),
                        ]),
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                            TextEntry::make('cliente.nome')
                                ->label('Cliente')
                                ->icon('heroicon-m-user')
                                ->weight('bold')
                                ->url(fn($record) => \App\Filament\Resources\CadastroResource::getUrl('view', ['record' => $record->cadastro_id])),
                            TextEntry::make('cliente.telefone')
                                ->label('WhatsApp')
                                ->url(fn($state) => $state ? 'https://wa.me/55' . preg_replace('/\D/', '', $state) : null, true),
                            TextEntry::make('vendedor.nome')->label('Vendedor'),
                            TextEntry::make('loja.nome')->label('Loja'),
                        ]),
                    ]),

                // ===== RESUMO FINANCEIRO =====
                \Filament\Infolists\Components\Section::make('💰 Resumo Financeiro')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 4])->schema([
                            TextEntry::make('valor_total')
                                ->label('Valor Calculado')
                                ->money('BRL')
                                ->size(TextEntry\TextEntrySize::Medium)
                                ->weight('bold')
                                ->color('success'),

                            TextEntry::make('valor_efetivo')
                                ->label('Valor Final (Efetivo)')
                                ->money('BRL')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold')
                                ->color('primary'),

                            TextEntry::make('desconto_prestador')
                                ->label('Desconto/Acréscimo')
                                ->money('BRL')
                                ->color(fn($state) => floatval($state ?? 0) > 0 ? 'danger' : 'success')
                                ->weight('bold')
                                ->formatStateUsing(
                                    fn($state) =>
                                    floatval($state ?? 0) > 0
                                    ? '- R$ ' . number_format($state, 2, ',', '.')
                                    : ($state < 0 ? '+ R$ ' . number_format(abs($state), 2, ',', '.') : 'Sem ajuste')
                                ),

                            TextEntry::make('comissao_vendedor')->label('Comissão Vend.')->money('BRL'),
                        ]),
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                            TextEntry::make('comissao_loja')->label('Comissão Loja')->money('BRL'),
                        ]),
                    ])
                    ->collapsible(),

                // ===== ABAS (TABS) =====
                Tabs::make('Detalhes do Orçamento')
                    ->tabs([
                        Tabs\Tab::make('📋 Itens e Serviços')
                            ->schema([
                                RepeatableEntry::make('itens')
                                    ->label('')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 4])->schema([
                                            TextEntry::make('item_nome')->label('Item')->weight('bold')->columnSpan(2),
                                            TextEntry::make('quantidade')->label('Qtd'),
                                            TextEntry::make('subtotal')->label('Total')->money('BRL')->weight('bold')->color('success'),
                                        ]),
                                    ])
                                    ->grid(1),
                            ]),

                        Tabs\Tab::make('👤 Dados do Cliente')
                            ->schema([
                                Grid::make(['default' => 1, 'sm' => 3])->schema([
                                    TextEntry::make('cliente.email')->label('E-mail')->copyable(),
                                    TextEntry::make('cliente.documento')->label('CPF/CNPJ'),
                                    TextEntry::make('cliente.cidade')->label('Cidade'),
                                    TextEntry::make('cliente.logradouro')
                                        ->label('Endereço')
                                        ->columnSpanFull()
                                        ->icon('heroicon-m-map-pin')
                                        ->url(fn($record) => "https://www.google.com/maps/search/?api=1&query=" . urlencode("{$record->cliente->logradouro}, {$record->cliente->numero} - {$record->cliente->bairro}, {$record->cliente->cidade} - {$record->cliente->estado}"), true)
                                        ->color('primary'),
                                ]),
                            ]),

                        Tabs\Tab::make('📝 Observações')
                            ->schema([
                                TextEntry::make('observacoes')->markdown(),
                                TextEntry::make('descricao_servico')->label('Descrição Técnica')->markdown(),
                            ]),

                        Tabs\Tab::make('📁 Arquivos')
                            ->schema([
                                \Filament\Infolists\Components\SpatieMediaLibraryImageEntry::make('arquivos_imagens')
                                    ->label('Imagens')
                                    ->collection('arquivos')
                                    ->disk('public'),

                                \Filament\Infolists\Components\TextEntry::make('arquivos_list')
                                    ->label('Lista de Documentos')
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

                        Tabs\Tab::make('📜 Histórico')
                            ->icon('heroicon-m-clock')
                            ->badge(fn($record) => $record->audits()->count())
                            ->schema([
                                RepeatableEntry::make('audits')
                                    ->label('')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 4])->schema([
                                            TextEntry::make('user.name')
                                                ->label('Usuário')
                                                ->icon('heroicon-m-user'),
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
                                                ->label('Data')
                                                ->dateTime('d/m/Y H:i:s'),
                                            TextEntry::make('ip_address')
                                                ->label('IP')
                                                ->icon('heroicon-m-globe-alt')
                                                ->copyable(),
                                        ]),
                                        \Filament\Infolists\Components\KeyValueEntry::make('old_values')
                                            ->label('Valores Antigos')
                                            ->visible(fn($state) => !empty($state)),
                                        \Filament\Infolists\Components\KeyValueEntry::make('new_values')
                                            ->label('Novos Valores')
                                            ->visible(fn($state) => !empty($state)),
                                    ])
                                    ->grid(1)
                                    ->contained(false),
                                TextEntry::make('sem_historico')
                                    ->label('')
                                    ->default('Nenhuma alteração registrada.')
                                    ->visible(fn($record) => $record->audits()->count() === 0),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('Orçamento')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('cliente.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->url(fn($record) => \App\Filament\Resources\CadastroResource::getUrl('view', ['record' => $record->cadastro_id]))
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('valor_efetivo')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(query: fn($query, $direction) => $query->orderByRaw("CASE WHEN CAST(valor_final_editado AS REAL) > 0 THEN valor_final_editado ELSE valor_total END {$direction}"))
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'aprovado' => 'success',
                        'rejeitado' => 'danger',
                        'enviado' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->date('d/m')->label('Data'),
                Tables\Columns\TextColumn::make('id_parceiro')
                    ->label('ID Parceiro')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vendedor.nome')
                    ->label('Vendedor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('loja.nome')
                    ->label('Loja')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions(
                \App\Support\Filament\StofgardTable::defaultActions(
                    view: true,
                    edit: true,
                    delete: true,
                    extraActions: [
                        Tables\Actions\Action::make('gerar_pdf_background')
                            ->label('Gerar PDF (Fila)')
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('success')
                            ->requiresConfirmation()
                            ->modalHeading('Gerar Documento Pesado')
                            ->modalDescription('O PDF será gerado em segundo plano para não travar sua tela. Você receberá uma notificação quando estiver pronto.')
                            ->action(function (Orcamento $record) {
                                $settingsArray = \App\Models\Setting::pluck('value', 'key')->toArray();
                                $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
                                foreach ($jsonFields as $k) {
                                    if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                                        $settingsArray[$k] = json_decode($settingsArray[$k], true);
                                    }
                                }
                                $config = (object) $settingsArray;

                                $htmlContent = view('pdf.orcamento', ['orcamento' => $record, 'config' => $config])->render();

                                \App\Jobs\ProcessPdfJob::dispatch(
                                    $record->id,
                                    'orcamento',
                                    auth()->id(),
                                    $htmlContent
                                );

                                \Filament\Notifications\Notification::make()
                                    ->title('🚀 Fogo na Bomba!')
                                    ->body('O PDF do Orçamento está sendo gerado no servidor. Avisaremos quando estiver pronto.')
                                    ->success()
                                    ->send();
                            }),

                        Tables\Actions\Action::make('gerar_contrato_background')
                            ->label('Gerar Contrato (Fila)')
                            ->icon('heroicon-o-scale')
                            ->color('info')
                            ->requiresConfirmation()
                            ->modalHeading('Gerar Contrato Jurídico')
                            ->modalDescription('O contrato será gerado usando o layout e os textos definidos em Configurações. Deseja iniciar a geração em background?')
                            ->visible(fn() => tenancy()->tenant?->temAcessoPremium() ?? true)
                            ->action(function (Orcamento $record) {
                                $settingsArray = \App\Models\Setting::pluck('value', 'key')->toArray();
                                $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
                                foreach ($jsonFields as $k) {
                                    if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                                        $settingsArray[$k] = json_decode($settingsArray[$k], true);
                                    }
                                }
                                $config = (object) $settingsArray;
                                // Adiciona as configurações do tenant
                                $tenantConfig = \App\Models\Configuracao::first();
                                $config->empresa_logo = $tenantConfig->empresa_logo ?? null;
                                $config->empresa_nome = $tenantConfig->empresa_nome ?? null;
                                $config->empresa_cnpj = $tenantConfig->empresa_cnpj ?? null;
                                $config->cores_pdf = $tenantConfig->cores_pdf ?? ['primaria' => '#2563eb'];
                                $config->texto_contrato_padrao = $tenantConfig->texto_contrato_padrao ?? null;
                                $config->termos_garantia = $tenantConfig->termos_garantia ?? null;

                                $htmlContent = view('pdf.contrato', ['orcamento' => $record, 'config' => $config])->render();

                                \App\Jobs\ProcessPdfJob::dispatch(
                                    $record->id,
                                    'contrato',
                                    auth()->id(),
                                    $htmlContent
                                );

                                \Filament\Notifications\Notification::make()
                                    ->title('⚖️ A Lei é Dura, Mas é a Lei')
                                    ->body('O Contrato está sendo gerado em background. Avisaremos assim que o PDF estiver pronto.')
                                    ->success()
                                    ->send();
                            }),

                        // #5: Aprovar e Gerar OS direto da lista (Unificado)
                        \App\Filament\Actions\OrcamentoActions::getAprovarTableAction(),

                        // #5: Enviar WhatsApp direto da lista

                        // #5: Enviar WhatsApp com Script via Evolution API
                        Tables\Actions\Action::make('whatsapp_evolution')
                            ->label('WhatsApp (Scripts)')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->color('success')
                            ->tooltip('Disparar Zap via Evolution API')
                            ->visible(fn() => tenancy()->tenant?->temAcessoPremium() ?? true)
                            ->form([
                                Forms\Components\Select::make('script')
                                    ->label('Escolha um Script de Vendas')
                                    ->options([
                                        'cobranca' => 'Agitar Fechamento (Cobrança)',
                                        'promocao' => 'Desconto de Oportunidade',
                                        'vencido' => 'Orçamento Expirando',
                                        'personalizado' => 'Personalizado',
                                    ])
                                    ->default('cobranca')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state, Orcamento $record) {
                                        $nomeCliente = $record->cliente?->nome ?? 'Cliente';
                                        $linkPdf = \Illuminate\Support\Facades\URL::signedRoute(
                                            'orcamento.public_stream',
                                            ['orcamento' => $record->id],
                                            now()->addDays(7)
                                        );

                                        $textos = [
                                            'cobranca' => "Olá {$nomeCliente}, tudo bem? Aqui é a equipe da " . ($record->loja->nome ?? 'Autonomia Ilimitada') . "!\n\nTe mandei um orçamento e queria saber se você conseguiu analisar a nossa proposta. Podemos fechar?\n\n📄 Veja o seu orçamento:\n{$linkPdf}",
                                            'promocao' => "E aí {$nomeCliente}, tudo joia? Tenho uma boa notícia hoje.\n\nSe a gente fechar esse serviço do orçamento #{$record->id} hoje, consigo te dar um desconto especial ou uma condição melhor.\nVamos nessa?\n\n📄 Confira sua proposta aqui:\n{$linkPdf}",
                                            'vencido' => "Oi {$nomeCliente}! Seu orçamento conosco está prestes a expirar. Como os materiais variam bastante de preço, se não fecharmos logo o valor pode subir na próxima semana.\n\nAinda faz sentido pra você organizar isso?\n\n📄 Lembrando que o seu link é este:\n{$linkPdf}",
                                            'personalizado' => "Olá {$nomeCliente}, segue o orçamento: \n\n{$linkPdf}"
                                        ];

                                        $set('mensagem', $textos[$state] ?? $textos['personalizado']);
                                    }),

                                Forms\Components\Textarea::make('mensagem')
                                    ->label('Mensagem a Enviar')
                                    ->rows(8)
                                    ->required()
                                    ->default(function (Orcamento $record) {
                                        $nomeCliente = $record->cliente?->nome ?? 'Cliente';
                                        $linkPdf = \Illuminate\Support\Facades\URL::signedRoute(
                                            'orcamento.public_stream',
                                            ['orcamento' => $record->id],
                                            now()->addDays(7)
                                        );
                                        return "Olá {$nomeCliente}, tudo bem? Aqui é a equipe da " . ($record->loja->nome ?? 'nossa empresa') . "!\n\nTe mandei um orçamento e queria saber se você conseguiu analisar a nossa proposta. Podemos fechar?\n\n📄 Veja o seu orçamento:\n{$linkPdf}";
                                    }),
                            ])
                            ->action(function (array $data, Orcamento $record) {
                                $phone = preg_replace('/[^0-9]/', '', $record->cliente?->telefone ?? '');

                                if (empty($phone) || strlen($phone) < 10) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Telefone Inválido')
                                        ->body('O cliente precisa ter um número de WhatsApp cadastrado corretamente.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                \App\Jobs\SendWhatsAppJob::dispatch($phone, $data['mensagem'], tenancy()->tenant?->slug ?? 'default');

                                \Filament\Notifications\Notification::make()
                                    ->title('Zap a caminho!')
                                    ->body("O script de vendas foi jogado na fila da Evolution para {$phone}!")
                                    ->success()
                                    ->send();
                            }),

                        // #6a: Editar Valor Final direto da lista
                        Tables\Actions\Action::make('editar_valor')
                            ->label('Editar Valor Final')
                            ->icon('heroicon-o-currency-dollar')
                            ->color('warning')
                            ->tooltip('Ajustar valor final rapidamente')
                            ->form([
                                Forms\Components\TextInput::make('valor_final_editado')
                                    ->label('Valor Final (R$)')
                                    ->numeric()->prefix('R$')
                                    ->default(fn($record) => $record->valor_final_editado > 0 ? $record->valor_final_editado : $record->valor_total)
                                    ->helperText(fn($record) => 'Valor calculado: R$ ' . number_format($record->valor_total, 2, ',', '.')),
                            ])
                            ->action(function ($record, array $data) {
                                $valorEditado = floatval($data['valor_final_editado'] ?? 0);
                                $valorTotal = floatval($record->valor_total);
                                $desconto = $valorTotal - $valorEditado;

                                $record->update([
                                    'valor_final_editado' => $valorEditado > 0 ? $valorEditado : null,
                                    'desconto_prestador' => $desconto,
                                ]);

                                // Propagar para módulos vinculados (OS, Financeiro)
                                \App\Services\OrcamentoFormService::sincronizarValorModulos($record->fresh());

                                \Filament\Notifications\Notification::make()
                                    ->title('Valor atualizado!')
                                    ->body($desconto > 0
                                        ? 'Desconto do prestador: R$ ' . number_format($desconto, 2, ',', '.')
                                        : ($desconto < 0
                                            ? 'Acréscimo: R$ ' . number_format(abs($desconto), 2, ',', '.')
                                            : 'Sem desconto aplicado'))
                                    ->success()->send();
                            }),
                    ]
                )
            )
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
            'view' => Pages\ViewOrcamento::route('/{record}'),
        ];
    }

    public static function canAccess(): bool
    {
        return !auth()->user()?->isFuncionario();
    }
}
