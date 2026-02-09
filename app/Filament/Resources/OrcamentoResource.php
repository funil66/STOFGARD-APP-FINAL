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
                            ->createOptionForm(\App\Filament\Resources\CadastroResource::getFormSchema())
                            ->columnSpan(2), // Give more space

                        Forms\Components\DatePicker::make('data_orcamento')->default(now())->required(),
                        Forms\Components\DatePicker::make('data_validade')->default(now()->addDays(15)),

                        // Status logic...
                    ])->columns(4),

                // ... (Keep existing sections but ensuring they use Icons if missing)
                Forms\Components\Section::make('Comercial & Pagamento')
                    ->icon('heroicon-o-currency-dollar')
                    ->description('Gerencie as comissões e a exibição do PIX no PDF.')
                    ->schema([
                        // ... (Copy existing schema for pix keys, seller, etc.)
                        Forms\Components\Toggle::make('pdf_incluir_pix')
                            ->label('Gerar QR Code PIX')
                            ->default(true)
                            ->live(),

                        Forms\Components\Toggle::make('aplicar_desconto_pix')
                            ->label('Aplicar Desconto PIX')
                            ->default(fn() => \App\Models\Setting::get('pdf_aplicar_desconto_global', true))
                            ->visible(fn(Forms\Get $get) => $get('pdf_incluir_pix')),

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

                        // ... other fields from original
                    ])->columns(3),

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
                                    ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get, $state) => self::atualizarPrecoItem($set, $get))
                                    ->columnSpan(4),

                                Forms\Components\Select::make('servico_tipo')
                                    ->options(\App\Services\ServiceTypeManager::getOptions())
                                    ->default('higienizacao')
                                    ->live()
                                    ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get) => self::atualizarPrecoItem($set, $get))
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('quantidade')
                                    ->numeric()->default(1)->live(onBlur: true)
                                    ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get) => self::recalcularTotal($set, $get))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('valor_unitario')
                                    ->label('Unit.')
                                    ->numeric()->prefix('R$')->live(onBlur: true)
                                    ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get) => self::recalcularTotal($set, $get))
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Total')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->prefix('R$')
                                    ->columnSpan(2),
                            ])
                            ->columns(11)
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get) => self::recalcularTotal($set, $get)),
                    ]),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('valor_total')
                            ->label('VALOR TOTAL')
                            ->numeric()->prefix('R$')
                            ->extraInputAttributes(['style' => 'font-size:1.5rem;font-weight:bold;color:#16a34a;background-color:#f0fdf4;'])
                            ->readOnly()->dehydrated()->columnSpanFull(),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ===== CABEÇALHO =====
                \Filament\Infolists\Components\Section::make()
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
                                    default => 'gray',
                                }),

                            TextEntry::make('created_at')->label('Emissão')->date('d/m/Y'),
                            TextEntry::make('data_validade')->label('Validade')->date('d/m/Y')->color('warning'),
                        ]),
                        Grid::make(4)->schema([
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
                        Grid::make(4)->schema([
                            TextEntry::make('valor_total')
                                ->label('Valor Final')
                                ->money('BRL')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold')
                                ->color('success'),
                            TextEntry::make('comissao_vendedor')->label('Comissão Vend.')->money('BRL'),
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
                                        Grid::make(4)->schema([
                                            TextEntry::make('item_nome')->label('Item')->weight('bold')->columnSpan(2),
                                            TextEntry::make('quantidade')->label('Qtd'),
                                            TextEntry::make('subtotal')->label('Total')->money('BRL')->weight('bold')->color('success'),
                                        ]),
                                    ])
                                    ->grid(1),
                            ]),

                        Tabs\Tab::make('👤 Dados do Cliente')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('cliente.email')->label('E-mail')->copyable(),
                                    TextEntry::make('cliente.documento')->label('CPF/CNPJ'),
                                    TextEntry::make('cliente.cidade')->label('Cidade'),
                                    TextEntry::make('cliente.logradouro')->label('Endereço')->columnSpanFull(),
                                ]),
                            ]),

                        Tabs\Tab::make('📝 Observações')
                            ->schema([
                                TextEntry::make('observacoes')->markdown(),
                                TextEntry::make('descricao_servico')->label('Descrição Técnica')->markdown(),
                            ]),

                        Tabs\Tab::make('📁 Arquivos')
                            ->schema([
                                \Filament\Infolists\Components\SpatieMediaLibraryImageEntry::make('arquivos')
                                    ->collection('arquivos')
                                    ->disk('public'),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('valor_total')
                    ->money('BRL')
                    ->sortable()
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
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('pdf')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn(Orcamento $record) => route('orcamento.pdf', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\DeleteAction::make(),
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
