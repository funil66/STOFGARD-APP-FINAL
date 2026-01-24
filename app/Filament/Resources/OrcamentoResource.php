<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrcamentoResource\Pages;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\TabelaPreco;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\OrdemServico;
use App\Models\TransacaoFinanceira;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrcamentoResource extends Resource
{
    protected static ?string $model = Orcamento::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Orçamentos';

    protected static ?string $modelLabel = 'Orçamento';

    protected static ?string $pluralModelLabel = 'Orçamentos';

    protected static ?string $navigationGroup = 'Operacional';

    protected static ?int $navigationSort = 2;

    public static function getItensHigienizacaoOptions(): array
    {
        return TabelaPreco::ativos()
            ->where('tipo_servico', 'higienizacao')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => "🧼 {$item->nome_item}"];
            })
            ->toArray();
    }

    public static function getItensImpermeabilizacaoOptions(): array
    {
        return TabelaPreco::ativos()
            ->where('tipo_servico', 'impermeabilizacao')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => "💧 {$item->nome_item}"];
            })
            ->toArray();
    }

    public static function getClientesOptions(): array
    {
        return Cliente::orderBy('nome')
            ->get()
            ->mapWithKeys(function ($c) {
                $label = $c->nome;
                if ($c->celular) {
                    $label .= " ({$c->celular})";
                }

                return [$c->id => $label];
            })
            ->toArray();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. SEÇÃO DE NEGOCIAÇÃO (NO TOPO)
                Forms\Components\Section::make('Negociação e Condições de Pagamento')
                    ->description('Defina as regras comerciais para este orçamento.')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Toggle::make('aplicar_desconto_pix')
                                ->label('Aplicar 10% no Pix/Dinheiro')
                                ->default(true)
                                ->live(),
                            Forms\Components\Toggle::make('repassar_taxas')
                                ->label('Repassar Taxas de Parcelamento (Cliente Paga)')
                                ->helperText('Desligue para assumir os juros (Cortesia Stofgard).')
                                ->default(true)
                                ->live(),
                        ])->columns(2),
                        // SIMULADOR EM TEMPO REAL (6x)
                        Forms\Components\Placeholder::make('simulador_pagamento')
                            ->label('Simulação de Pagamento')
                            ->content(function (\Filament\Forms\Get $get) {
                                $valorTotal = floatval($get('valor_total') ?? 0);
                                if ($valorTotal <= 0) return 'Adicione itens para ver a simulação.';
                                $aplicarPix = $get('aplicar_desconto_pix');
                                $repassar = $get('repassar_taxas');
                                
                                // Busca config
                                $config = \App\Models\Configuracao::first();
                                $descontoPixPct = $config?->desconto_pix ?? 10;
                                $taxas = $config?->taxas_parcelamento ?? []; 
                                $valorPix = $valorTotal * (1 - ($descontoPixPct / 100));
                                
                                // HTML Table
                                $html = '<div class=\"overflow-x-auto\"><table class=\"w-full text-sm text-left text-gray-500 dark:text-gray-400\">';
                                $html .= '<thead class=\"text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400\"><tr><th class=\"px-4 py-2\">Forma</th><th class=\"px-4 py-2 text-right\">Valor</th></tr></thead><tbody>';
                                
                                // Pix
                                if ($aplicarPix) {
                                    $html .= '<tr class=\"border-b dark:border-gray-700 bg-green-50 dark:bg-green-900/20\"><td class=\"px-4 py-2 font-bold text-green-700 dark:text-green-400\">À Vista (Pix/Dinheiro)</td><td class=\"px-4 py-2 text-right font-bold text-green-700 dark:text-green-400\">R$ ' . number_format($valorPix, 2, ',', '.') . ' (-'.$descontoPixPct.'%)</td></tr>';
                                }
                                // 1x
                                $html .= '<tr class=\"border-b dark:border-gray-700\"><td class=\"px-4 py-2\">Crédito 1x / Boleto</td><td class=\"px-4 py-2 text-right\">R$ ' . number_format($valorTotal, 2, ',', '.') . '</td></tr>';
                                // 2x a 6x
                                for ($i = 2; $i <= 6; $i++) {
                                    $coeficiente = isset($taxas[$i]) ? floatval($taxas[$i]) : 1;
                                    // Se repassar=true, multiplica pelo coeficiente. Se false, valor cheio.
                                    $totalParcelado = $repassar ? ($valorTotal * $coeficiente) : $valorTotal;
                                    $valorParcela = $totalParcelado / $i;
                                    
                                    $infoTaxa = ($repassar && $coeficiente > 1) ? '' : ' <span class=\"text-xs text-blue-600 font-bold\">(Sem Juros)</span>';
                                    
                                    $html .= '<tr class=\"border-b dark:border-gray-700\"><td class=\"px-4 py-2\">'.$i.'x'.$infoTaxa.'</td><td class=\"px-4 py-2 text-right\">R$ ' . number_format($valorParcela, 2, ',', '.') . ' <span class=\"text-xs text-gray-400\">(Total: R$ ' . number_format($totalParcelado, 2, ',', '.') . ')</span></td></tr>';
                                }
                                $html .= '</tbody></table></div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),
                // 2. SEÇÃO DE INFORMAÇÕES
                Forms\Components\Section::make('Informações do Orçamento')
                    ->schema([
                        Forms\Components\Select::make('cliente_id')
                            ->relationship('cliente', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([\App\Filament\Resources\ClienteResource::class, 'form'])
                            ->columnSpan(2),
                        Forms\Components\DatePicker::make('data')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('validade')
                            ->default(now()->addDays(7))
                            ->required(),
                    ])->columns(4),
                // 3. SEÇÃO DE ITENS (Mantenha a lógica existente de Repeater aqui)
                Forms\Components\Section::make('Itens do Orçamento')
                    ->schema([
                        Forms\Components\Repeater::make('itens')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('item')->required()->columnSpan(2),
                                Forms\Components\TextInput::make('quantidade')->numeric()->default(1)->live()->columnSpan(1),
                                Forms\Components\TextInput::make('valor_unitario')->numeric()->prefix('R$')->live()->columnSpan(1),
                                Forms\Components\TextInput::make('subtotal')->numeric()->prefix('R$')->disabled()->dehydrated()->columnSpan(1)
                                    ->placeholder(fn ($get) => 'R$ ' . number_format((floatval($get('quantidade')??0) * floatval($get('valor_unitario')??0)), 2, ',', '.'))
                            ])
                            ->columns(5)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Recalcula total simples
                                $total = collect($state)->sum(fn ($item) => floatval($item['quantidade']??0) * floatval($item['valor_unitario']??0));
                                $set('valor_total', $total);
                            }),
                    ]),
                // 4. SEÇÃO DE VALORES TOTAIS (LIMPA - SEM DUPLICIDADE)
                Forms\Components\Section::make('Valores Totais')
                    ->schema([
                        Forms\Components\TextInput::make('valor_total')
                            ->label('Valor Total dos Itens (Base)')
                            ->numeric()
                            ->prefix('R$')
                            ->disabled() // O valor base não se mexe, é a soma dos itens
                            ->dehydrated()
                            ->columnSpan(1),
                            
                        Forms\Components\Select::make('forma_pagamento')
                            ->options([
                                'pix' => 'Pix',
                                'dinheiro' => 'Dinheiro',
                                'cartao_credito' => 'Cartão de Crédito',
                                'boleto' => '📄 Boleto Bancário',
                            ])
                            ->columnSpan(1),
                            
                        Forms\Components\Select::make('status')
                            ->options(['pendente' => 'Pendente', 'aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado'])
                            ->default('pendente')
                            ->required()
                            ->columnSpan(1),
                    ])->columns(3),
                Forms\Components\Section::make('Observações')
                    ->schema([
                        Forms\Components\Textarea::make('observacoes')->columnSpanFull(),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_orcamento')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cadastro')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('tipo_servico')
                    ->label('Serviço')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'higienizacao' => 'info',
                        'impermeabilizacao' => 'success',
                        'higienizacao_impermeabilizacao' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'higienizacao' => '🧼 Higienização',
                        'impermeabilizacao' => '💧 Impermeabilização',
                        'higienizacao_impermeabilizacao' => '🧼💧 Hig. + Imper.',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->sortable()
                    ->alignment('right')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('data_orcamento')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_validade')
                    ->label('Válido até')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record): string => $record->expirado() ? 'danger' : 'success')
                    ->description(function ($record): string {
                        if ($record->status === 'convertido') {
                            return 'Convertido em OS';
                        }
                        if ($record->expirado()) {
                            return 'Expirado';
                        }
                        $dias = $record->diasRestantes();

                        return $dias > 0 ? "Restam {$dias} dias" : '';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'warning',
                        'aprovado' => 'success',
                        'recusado' => 'danger',
                        'expirado' => 'gray',
                        'convertido' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendente' => '⏳ Pendente',
                        'aprovado' => '✅ Aprovado',
                        'recusado' => '❌ Recusado',
                        'expirado' => '⌛ Expirado',
                        'convertido' => '🔄 Convertido',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('parceiro.nome')
                    ->label('Parceiro')
                    ->searchable()
                    ->toggleable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('criado_por')
                    ->label('Criado por')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'aprovado' => 'Aprovado',
                        'recusado' => 'Recusado',
                        'expirado' => 'Expirado',
                        'convertido' => 'Convertido',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('tipo_servico')
                    ->label('Tipo de Serviço')
                    ->options([
                        'higienizacao' => 'Higienização',
                        'impermeabilizacao' => 'Impermeabilização',
                        'higienizacao_impermeabilizacao' => 'Higienização + Impermeabilização',
                    ]),

                Tables\Filters\SelectFilter::make('cadastro_id')
                    ->label('Cadastro')
                    ->options(function () {
                        $clientes = \App\Models\Cliente::all()->mapWithKeys(fn($c) => [
                            'cliente_' . $c->id => '🧑 Cliente: ' . $c->nome
                        ]);
                        $parceiros = \App\Models\Parceiro::all()->mapWithKeys(fn($p) => [
                            'parceiro_' . $p->id => ($p->tipo === 'loja' ? '🏪 Loja: ' : '🧑‍💼 Vendedor: ') . $p->nome
                        ]);
                        return $clientes->union($parceiros)->toArray();
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('aprovarOrcamento')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($record) {
                        $record->update(['status' => 'aprovado']);

                        $ordemServico = OrdemServico::create([
                            'cliente_id' => $record->cliente_id,
                            'itens' => $record->itens,
                            'valor_total' => $record->valor_total,
                            'descricao' => $record->descricao,
                            'status' => 'aberta',
                        ]);

                        TransacaoFinanceira::create([
                            'tipo' => 'receita',
                            'status' => 'pendente',
                            'categoria' => 'Serviço Prestado',
                            'descricao' => 'Receita prevista Ref. Orçamento #' . $record->numero_orcamento,
                            'valor_previsto' => $record->valor_total,
                            'data_vencimento' => now(),
                            'origem_type' => OrdemServico::class,
                            'origem_id' => $ordemServico->id,
                        ]);

                        Notification::make()
                            ->title('Orçamento aprovado!')
                            ->body('OS e Lançamento Financeiro gerados com sucesso.')
                            ->success()
                            ->send();

                        return redirect(OrdemServicoResource::getUrl('edit', ['record' => $ordemServico->id]));
                    }),
                Tables\Actions\Action::make('visualizar_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (Orcamento $record): string => route('orcamento.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Orcamento $record): bool => $record->status !== 'convertido'),
                \App\Filament\Actions\DownloadFileAction::make('documentos', 'public')->label('Download'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informações do Orçamento')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('numero_orcamento')
                                    ->label('Número'),
                                Infolists\Components\TextEntry::make('data_orcamento')
                                    ->label('Data')
                                    ->date('d/m/Y'),
                                Infolists\Components\TextEntry::make('data_validade')
                                    ->label('Válido até')
                                    ->date('d/m/Y')
                                    ->badge()
                                    ->color(fn ($record): string => $record->expirado() ? 'danger' : 'success'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Cliente')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('cliente.nome')
                                    ->label('Nome'),
                                Infolists\Components\TextEntry::make('cliente.celular')
                                    ->label('Celular'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Serviço')
                    ->schema([
                        Infolists\Components\TextEntry::make('tipo_servico')
                            ->label('Tipo de Serviço')
                            ->badge(),
                        Infolists\Components\TextEntry::make('descricao_servico')
                            ->label('Descrição')
                            ->columnSpanFull(),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('area_m2')
                                    ->label('Área')
                                    ->suffix(' m²'),
                                Infolists\Components\TextEntry::make('valor_m2')
                                    ->label('Valor por m²')
                                    ->money('BRL'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Valores')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('valor_subtotal')
                                    ->label('Subtotal')
                                    ->money('BRL'),
                                Infolists\Components\TextEntry::make('valor_desconto')
                                    ->label('Desconto')
                                    ->money('BRL'),
                                Infolists\Components\TextEntry::make('desconto_pix_aplicado')
                                    ->label('Desconto PIX')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? '10% Aplicado' : 'Não aplicado')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                Infolists\Components\TextEntry::make('valor_total')
                                    ->label('Valor Total')
                                    ->money('BRL')
                                    ->size('lg')
                                    ->weight('bold'),
                            ]),
                        Infolists\Components\TextEntry::make('forma_pagamento')
                            ->label('Forma de Pagamento')
                            ->badge(),
                    ]),

                Infolists\Components\Section::make('Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('ordemServico.numero_os')
                            ->label('Ordem de Serviço Gerada')
                            ->visible(fn ($record) => $record->ordem_servico_id !== null),
                        Infolists\Components\TextEntry::make('parceiro.nome')
                            ->label('Parceiro')
                            ->visible(fn ($record) => $record->parceiro_id !== null),
                        Infolists\Components\TextEntry::make('numero_pedido_parceiro')
                            ->label('Nº Pedido Parceiro')
                            ->visible(fn ($record) => $record->numero_pedido_parceiro !== null),
                    ]),

                Infolists\Components\Section::make('Observações')
                    ->schema([
                        Infolists\Components\TextEntry::make('observacoes')
                            ->label('Observações para o Cliente')
                            ->columnSpanFull(),
                    ])
                ,

                Infolists\Components\Section::make("Arquivos")
                    ->schema([
                        Infolists\Components\ImageEntry::make("documentos")
                            ->label("Anexos do Orçamento")
                            ->disk("public")
                            ->visibility("public")
                            ->limit(10)
                            ->height(400)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('documentos_list')
                            ->label('Ações de Arquivo')
                            ->html()
                            ->getStateUsing(function ($record) {
                                if (empty($record->documentos)) {
                                    return '';
                                }

                                $entries = [];

                                foreach ((array) data_get($record, 'documentos') as $path) {
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
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make("Observações")
                    ->schema([
                        Infolists\Components\TextEntry::make("observacoes_internas")
                            ->label("Internas")
                            ->columnSpanFull()
                            ->placeholder("Sem observações internas"),
                    ])
                    ->collapsed(),

                Infolists\Components\Section::make('Informações do Sistema')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('criado_por')
                                    ->label('Criado por')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Criado em')
                                    ->dateTime('d/m/Y H:i'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Atualizado em')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ])
                    ->collapsed(),

                Infolists\Components\Section::make('Valores Totais')
                    ->schema([
                        Infolists\Components\TextEntry::make('valor_vista')
                            ->label('Valor à Vista (Pix/Dinheiro)')
                            ->state(fn ($record) => $record->valor_total * 0.90)
                            ->money('BRL')
                            ->color('success')
                            ->weight('bold')
                            ->helperText('Já aplicado desconto padrão de 10%.'),

                        Infolists\Components\TextEntry::make('regra_pagamento')
                            ->label('Condições')
                            ->default('Tabela base refere-se a valor a prazo (Cartão). Desconto de 10% exclusivo para Pix/Dinheiro.')
                            ->columnSpanFull()
                            ->icon('heroicon-o-information-circle')
                            ->color('gray'),
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
            'index' => Pages\ListOrcamentos::route('/'),
            'create' => Pages\CreateOrcamento::route('/create'),
            'view' => Pages\ViewOrcamento::route('/{record}'),
            'edit' => Pages\EditOrcamento::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
