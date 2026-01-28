<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrcamentoResource\Pages;
use App\Models\Orcamento;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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

class OrcamentoResource extends Resource
{
    protected static ?string $model = Orcamento::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $label = 'Orçamento';

    public static function form(Form $form): Form
    {
        // Carrega configurações do catálogo
        $settingValue = \App\Models\Setting::get('catalogo_servicos_v2');
        $rawCatalogo = is_string($settingValue) ? json_decode($settingValue, true) : ($settingValue ?? []);
        if (!is_array($rawCatalogo)) $rawCatalogo = [];
        $catalogoMap = collect($rawCatalogo)->mapWithKeys(fn($item) => isset($item['nome']) ? [$item['nome'] => $item] : [])->toArray();
        $opcoesItens = !empty($catalogoMap) ? array_combine(array_keys($catalogoMap), array_keys($catalogoMap)) : [];
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
                    ->options(['rascunho'=>'Rascunho', 'enviado'=>'Enviado', 'aprovado'=>'Aprovado'])
                    ->default('rascunho')
                    ->required(),
            ])->columns(4),
        // 2. COMERCIAL (AQUI ESTÁ A LÓGICA DE COMISSÃO)
        Forms\Components\Section::make('Comercial & Pagamento')
            ->description('Gerencie as comissões e a exibição do PIX no PDF.')
            ->schema([
                Forms\Components\Toggle::make('pdf_incluir_pix')
                    ->label('Gerar QR Code PIX')
                    ->default(true),
                Forms\Components\Select::make('pix_chave_selecionada')
                    ->label('Escolha a Chave PIX')
                    ->options(function () {
                        $raw = \App\Models\Setting::where('key', 'financeiro_pix_keys')->value('value');
                        $data = is_string($raw) ? json_decode($raw, true) : ($raw ?? []);
                        
                        $options = [];
                        if (is_array($data)) {
                            foreach ($data as $item) {
                                if (!empty($item['chave'])) {
                                    // Mostra Chave + Titular no dropdown
                                    $options[$item['chave']] = $item['chave'] . ' - ' . ($item['titular'] ?? '');
                                }
                            }
                        }
                        return $options;
                    })
                    ->searchable()
                    ->preload() // Carrega a lista na hora
                    ->live()
                    ->required(fn (Forms\Get $get) => $get('pdf_incluir_pix'))
                    ->visible(fn (Forms\Get $get) => $get('pdf_incluir_pix'))
                    ->columnSpanFull(),
                // Seleção de Vendedor com Trigger de Cálculo
                Forms\Components\Select::make('vendedor_id')
                    ->label('Vendedor')
                    ->options(function () { return \App\Models\Cadastro::where('tipo', 'vendedor')->orderBy('nome')->pluck('nome','id')->toArray(); })
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
                                if ($loja) $set('comissao_loja', ($total * $loja->comissao_percentual) / 100);
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
                    ->options(function () { return \App\Models\Cadastro::where('tipo', 'loja')->orderBy('nome')->pluck('nome','id')->toArray(); })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                        $total = (float) $get('valor_total');
                        if ($state) {
                            $loja = \App\Models\Cadastro::find($state);
                            if ($loja) $set('comissao_loja', ($total * $loja->comissao_percentual) / 100);
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
                            ->options($opcoesItens)
                            ->searchable()->required()->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) use ($catalogoMap) {
                                if ($dados = $catalogoMap[$state] ?? null) $set('unidade', $dados['unidade'] ?? 'un');
                            })->columnSpan(4),
                        Forms\Components\Select::make('servico_tipo')
                            ->options(['higienizacao'=>'Higienização','impermeabilizacao'=>'Impermeabilização','combo'=>'Combo','outro'=>'Outro'])
                            ->required()->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) use ($catalogoMap) {
                                $dados = $catalogoMap[$get('item_nome')] ?? null;
                                $valor = 0;
                                if ($dados) {
                                    $h = (float)($dados['preco_higi']??0); $i = (float)($dados['preco_imper']??0);
                                    $valor = match($state){ 'higienizacao'=>$h, 'impermeabilizacao'=>$i, 'combo'=>$h+$i, default=>0 };
                                }
                                $set('valor_unitario', $valor);
                                $set('subtotal', $valor * (float)$get('quantidade'));
                                self::recalcularTotal($set, $get);
                            })->columnSpan(3),
                        Forms\Components\TextInput::make('quantidade')
                            ->numeric()->default(1)->required()->live(onBlur:true)
                            ->afterStateUpdated(function(Forms\Set $set, Forms\Get $get){
                                $set('subtotal', (float)$get('quantidade') * (float)$get('valor_unitario'));
                                self::recalcularTotal($set, $get);
                            })->columnSpan(2),
                        Forms\Components\TextInput::make('valor_unitario')
                            ->numeric()->prefix('R$')->live(onBlur:true)
                            ->afterStateUpdated(function(Forms\Set $set, Forms\Get $get){
                                $set('subtotal', (float)$get('quantidade') * (float)$get('valor_unitario'));
                                self::recalcularTotal($set, $get);
                            })->columnSpan(3),
                        Forms\Components\TextInput::make('subtotal')
                            ->disabled()->dehydrated()->numeric()->prefix('R$')->columnSpan(3),
                        Forms\Components\Hidden::make('unidade'),
                    ])
                    ->columns(15)
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::recalcularTotal($set, $get)),
            ]),
        // 4. TOTAL
        Forms\Components\Section::make()
            ->schema([
                Forms\Components\TextInput::make('valor_total')
                    ->label('VALOR TOTAL')
                    ->numeric()->prefix('R$')
                    ->extraInputAttributes(['style'=>'font-size:1.5rem;font-weight:bold;color:#16a34a;background-color:#f0fdf4;'])
                    ->readOnly()->dehydrated()->columnSpanFull(),
                Forms\Components\Textarea::make('observacoes')->label('Observações')->columnSpanFull(),
            ]),
    ]);
}

    // --- FUNÇÃO CENTRAL DE CÁLCULO ---
    public static function recalcularTotal(Forms\Set $set, Forms\Get $get): void
    {
        // Coleta os itens do repeater (prioridade para o contexto corrente)
        $itens = $get('itens') ?? $get('../../itens') ?? [];

        if (! is_array($itens)) {
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
                            ->color(fn (string $state): string => match ($state) {
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
                    ->color(fn (string $state): string => match ($state) {
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
                    ->url(fn (Orcamento $record) => route('orcamento.pdf', $record))
                    ->openUrlInNewTab(),

                // 2. Gerar OS (Aprovar e criar Ordem de Serviço)
                Tables\Actions\Action::make('gerar_os')
                    ->label('Gerar OS')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar e Gerar Ordem de Serviço')
                    ->modalDescription('Isso aprovará o orçamento e criará uma nova OS. Deseja continuar?')
                    ->visible(fn (Orcamento $record) => $record->status !== 'aprovado' && ! $record->ordemServico)
                    ->action(function (Orcamento $record) {
                        // 1. Aprova o Orçamento
                        $record->update(['status' => 'aprovado']);

                        // 2. Cria a OS
                        \App\Models\OrdemServico::create([
                            'numero_os' => \App\Models\OrdemServico::gerarNumeroOS(),
                            'orcamento_id' => $record->id,
                            'cadastro_id' => $record->cadastro_id,
                            'vendedor_id' => $record->vendedor_id,
                            'loja_id' => $record->loja_id,
                            'valor_total' => $record->valor_total,
                            'status' => 'pendente',
                            'observacoes' => $record->observacoes,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Ordem de Serviço Gerada com Sucesso!')
                            ->success()
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

                // 4. EXCLUIR (Ícone Vermelho)
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




