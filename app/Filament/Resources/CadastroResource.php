<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CadastroResource\Pages;
use App\Models\Cadastro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Support\Filament\StofgardTable;
use Filament\Infolists\Components\Livewire;

class CadastroResource extends Resource
{
    protected static ?string $model = Cadastro::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $label = 'Cadastro';

    protected static ?string $pluralLabel = 'Cadastros';

    // --- INFOLIST PREMIUM (CLIENTE 360) ---
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ===== CABEÇALHO DO CADASTRO =====
                Section::make()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                            TextEntry::make('nome')
                                ->label('Nome / Razão Social')
                                ->weight('bold')
                                ->columnSpan(['default' => 1, 'sm' => 2])
                                ->size(TextEntry\TextEntrySize::Large),
                            TextEntry::make('tipo')
                                ->badge()
                                ->color(fn(string $state): string => \App\Models\Categoria::where('slug', $state)->where('tipo', 'cadastro_tipo')->value('cor') ?? 'gray'),
                            TextEntry::make('documento')
                                ->label('Documento')
                                ->icon('heroicon-m-identification')
                                ->copyable(),
                        ]),
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                            TextEntry::make('telefone')
                                ->label('WhatsApp')
                                ->icon('heroicon-m-chat-bubble-left-right')
                                ->url(fn($state) => 'https://wa.me/55' . preg_replace('/\D/', '', $state), true),
                            TextEntry::make('email')
                                ->label('E-mail')
                                ->icon('heroicon-m-envelope')
                                ->copyable(),
                            TextEntry::make('cidade')
                                ->label('Localização')
                                ->formatStateUsing(fn($record) => "{$record->cidade}/{$record->estado}"),
                            TextEntry::make('created_at')
                                ->label('Cliente desde')
                                ->date('d/m/Y'),
                        ]),
                    ]),

                // ===== ENDEREÇO (INTERATIVO) =====
                Section::make('📍 Endereço')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                            TextEntry::make('endereco_completo')
                                ->label('Endereço Completo')
                                ->state(fn($record) => "{$record->logradouro}, {$record->numero} - {$record->bairro}")
                                ->icon('heroicon-m-map-pin')
                                ->url(fn($record) => "https://www.google.com/maps/search/?api=1&query=" . urlencode("{$record->logradouro}, {$record->numero} - {$record->bairro}, {$record->cidade} - {$record->estado}"), true)
                                ->color('primary')
                                ->columnSpan(['default' => 1, 'sm' => 2, 'lg' => 2]),
                            TextEntry::make('complemento')
                                ->label('Complemento')
                                ->placeholder('-'),
                            TextEntry::make('cep')
                                ->label('CEP')
                                ->icon('heroicon-m-hashtag'),
                        ]),
                    ])
                    ->collapsible(),

                // ===== RESUMO FINANCEIRO (CARDS) =====
                Section::make('💰 Resumo Financeiro')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 5])->schema([
                            TextEntry::make('total_receitas')
                                ->label('💵 Total Recebido')
                                ->money('BRL')
                                ->color('success')
                                ->weight('bold')
                                ->size(TextEntry\TextEntrySize::Large),
                            TextEntry::make('pendentes_receber')
                                ->label('⏳ A Receber')
                                ->money('BRL')
                                ->color('warning')
                                ->weight('bold'),
                            TextEntry::make('orcamentos_aprovados_count')
                                ->label('📋 Orç. Aprovados')
                                ->color('primary')
                                ->weight('bold'),
                            TextEntry::make('os_concluidas_count')
                                ->label('🛠️ OS Concluídas')
                                ->color('success')
                                ->weight('bold'),
                            TextEntry::make('saldo')
                                ->label('📊 Saldo')
                                ->money('BRL')
                                ->color(fn($state) => $state >= 0 ? 'success' : 'danger')
                                ->weight('bold'),
                        ]),
                    ])
                    ->collapsible(),

                // ===== CHAT WHATSAPP =====
                Section::make('Central de Atendimento (WhatsApp)')
                    ->schema([
                        Livewire::make(\App\Livewire\WhatsappChat::class)
                    ])
                    ->collapsible(),

                // ===== ABAS DE HISTÓRICO =====
                Infolists\Components\Tabs::make('Histórico Completo')
                    ->tabs([
                        // ABA 1: ORÇAMENTOS
                        Infolists\Components\Tabs\Tab::make('📋 Orçamentos')
                            ->badge(fn($record) => $record->orcamentos()->count())
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('orcamentos')
                                    ->label('')
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 6])->schema([
                                            TextEntry::make('numero')
                                                ->label('Nº')
                                                ->weight('bold')
                                                ->url(fn($record) => \App\Filament\Resources\OrcamentoResource::getUrl('view', ['record' => $record->id]))
                                                ->color('primary'),
                                            TextEntry::make('status')
                                                ->badge()
                                                ->color(fn($state) => match ($state) {
                                                    'aprovado' => 'success',
                                                    'cancelado', 'rejeitado' => 'danger',
                                                    'enviado' => 'warning',
                                                    default => 'gray',
                                                }),
                                            TextEntry::make('descricao_servico')->label('Serviço')->limit(30),
                                            TextEntry::make('created_at')->label('Data')->date('d/m/Y'),
                                            TextEntry::make('valor_efetivo')->label('Valor')->money('BRL')->color('success')->weight('bold'),
                                            TextEntry::make('id')
                                                ->label('')
                                                ->formatStateUsing(fn() => 'Gerar PDF')
                                                ->url(fn($record) => route('orcamento.pdf', $record), true)
                                                ->icon('heroicon-o-document-arrow-down')
                                                ->color('primary'),
                                        ]),
                                    ])
                                    ->grid(1)
                                    ->hidden(fn($record) => $record->orcamentos()->count() === 0),
                                Infolists\Components\TextEntry::make('empty_orcamentos')
                                    ->label('')
                                    ->default('Nenhum orçamento encontrado.')
                                    ->visible(fn($record) => $record->orcamentos()->count() === 0),
                            ]),

                        // ABA 2: ORDENS DE SERVIÇO
                        Infolists\Components\Tabs\Tab::make('🛠️ Ordens de Serviço')
                            ->badge(fn($record) => $record->ordensServico()->count())
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('ordensServico')
                                    ->label('')
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 6])->schema([
                                            TextEntry::make('numero_os')
                                                ->label('OS')
                                                ->weight('bold')
                                                ->url(fn($record) => \App\Filament\Resources\OrdemServicoResource::getUrl('view', ['record' => $record->id]))
                                                ->color('primary'),
                                            TextEntry::make('tipo_servico')->label('Tipo'),
                                            TextEntry::make('status')
                                                ->badge()
                                                ->color(fn($state) => match ($state) {
                                                    'concluida', 'finalizada' => 'success',
                                                    'cancelada' => 'danger',
                                                    'em_andamento' => 'warning',
                                                    default => 'info',
                                                }),
                                            TextEntry::make('data_prevista')->label('Agendado')->dateTime('d/m/Y H:i'),
                                            TextEntry::make('valor_total')->label('Total')->money('BRL'),
                                            TextEntry::make('id')
                                                ->label('')
                                                ->formatStateUsing(fn() => 'Gerar PDF')
                                                ->url(fn($record) => route('os.pdf', $record), true)
                                                ->icon('heroicon-o-document-arrow-down')
                                                ->color('primary'),
                                        ]),
                                    ])
                                    ->grid(1)
                                    ->hidden(fn($record) => $record->ordensServico()->count() === 0),
                                Infolists\Components\TextEntry::make('empty_os')
                                    ->label('')
                                    ->default('Nenhuma ordem de serviço encontrada.')
                                    ->visible(fn($record) => $record->ordensServico()->count() === 0),
                            ]),

                        // ABA 3: FINANCEIRO
                        Infolists\Components\Tabs\Tab::make('💰 Financeiro')
                            ->badge(fn($record) => $record->financeiros()->count())
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('financeiros')
                                    ->label('')
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 6])->schema([
                                            TextEntry::make('tipo')
                                                ->badge()
                                                ->color(fn($state) => $state === 'entrada' ? 'success' : 'danger')
                                                ->formatStateUsing(fn($state) => $state === 'entrada' ? '💵 Entrada' : '💸 Saída'),
                                            TextEntry::make('descricao')
                                                ->label('Descrição')
                                                ->limit(40)
                                                ->url(fn($record) => \App\Filament\Resources\FinanceiroResource::getUrl('view', ['record' => $record->id]))
                                                ->color('primary'),
                                            TextEntry::make('status')
                                                ->badge()
                                                ->color(fn($state) => match ($state) {
                                                    'pago' => 'success',
                                                    'cancelado' => 'danger',
                                                    default => 'warning',
                                                }),
                                            TextEntry::make('data')->label('Data')->date('d/m/Y'),
                                            TextEntry::make('data_vencimento')->label('Vencimento')->date('d/m/Y'),
                                            TextEntry::make('valor')->label('Valor')->money('BRL')->weight('bold'),
                                        ]),
                                    ])
                                    ->grid(1)
                                    ->hidden(fn($record) => $record->financeiros()->count() === 0),
                                Infolists\Components\TextEntry::make('empty_financeiro')
                                    ->label('')
                                    ->default('Nenhum lançamento financeiro encontrado.')
                                    ->visible(fn($record) => $record->financeiros()->count() === 0),
                            ]),

                        // ABA 4: AGENDA
                        Infolists\Components\Tabs\Tab::make('📅 Agenda')
                            ->badge(fn($record) => $record->agendas()->count())
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('agendas')
                                    ->label('')
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 5])->schema([
                                            TextEntry::make('titulo')->label('Evento')->weight('bold'),
                                            TextEntry::make('status')
                                                ->badge()
                                                ->color(fn($state) => match ($state) {
                                                    'concluido' => 'success',
                                                    'cancelado' => 'danger',
                                                    'em_andamento' => 'warning',
                                                    default => 'info',
                                                }),
                                            TextEntry::make('data_hora_inicio')->label('Data/Hora')->dateTime('d/m/Y H:i'),
                                            TextEntry::make('local')->label('Local'),
                                            TextEntry::make('descricao')->label('Descrição')->limit(50),
                                        ]),
                                    ])
                                    ->grid(1)
                                    ->hidden(fn($record) => $record->agendas()->count() === 0),
                                Infolists\Components\TextEntry::make('empty_agenda')
                                    ->label('')
                                    ->default('Nenhum agendamento encontrado.')
                                    ->visible(fn($record) => $record->agendas()->count() === 0),
                            ]),

                        // ABA 5: VENDEDORES (apenas para Lojas)
                        Infolists\Components\Tabs\Tab::make('🧑‍💼 Vendedores')
                            ->badge(fn($record) => $record->vendedores()->count())
                            ->visible(fn($record) => $record->tipo === 'loja')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('vendedores')
                                    ->label('')
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                                            TextEntry::make('nome')->label('Vendedor')->weight('bold'),
                                            TextEntry::make('telefone')->label('Telefone'),
                                            TextEntry::make('email')->label('E-mail'),
                                            TextEntry::make('comissao_percentual')
                                                ->label('Comissão')
                                                ->suffix('%'),
                                        ]),
                                    ])
                                    ->grid(1)
                                    ->hidden(fn($record) => $record->vendedores()->count() === 0),
                                Infolists\Components\TextEntry::make('empty_vendedores')
                                    ->label('')
                                    ->default('Nenhum vendedor vinculado a esta loja.')
                                    ->visible(fn($record) => $record->vendedores()->count() === 0),
                            ]),

                        // ABA 6: ARQUIVOS
                        Infolists\Components\Tabs\Tab::make('📁 Arquivos')
                            ->badge(fn($record) => $record->getMedia('arquivos')->count())
                            ->schema([
                                \Filament\Infolists\Components\SpatieMediaLibraryImageEntry::make('arquivos')
                                    ->label('Galeria de Imagens')
                                    ->collection('arquivos')
                                    ->size(200)
                                    ->square()
                                    ->extraImgAttributes(['class' => 'rounded-lg shadow-md'])
                                    ->disk('public'),

                                \Filament\Infolists\Components\TextEntry::make('arquivos_list')
                                    ->label('Lista de Documentos')
                                    ->html()
                                    ->getStateUsing(function ($record) {
                                        $files = $record->getMedia('arquivos');
                                        if ($files->isEmpty())
                                            return '<span class="text-gray-500 text-sm">Nenhum documento anexado.</span>';

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

                        // ABA 7: HISTÓRICO DE ALTERAÇÕES
                        Infolists\Components\Tabs\Tab::make('📜 Histórico')
                            ->icon('heroicon-m-clock')
                            ->badge(fn($record) => $record->audits()->count())
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('audits')
                                    ->label('')
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
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
                                        Section::make('Detalhes da Alteração')
                                            ->schema([
                                                Infolists\Components\KeyValueEntry::make('old_values')
                                                    ->label('Antes')
                                                    ->keyLabel('Campo')
                                                    ->valueLabel('Valor')
                                                    ->visible(fn($record) => !empty($record->old_values)),
                                                Infolists\Components\KeyValueEntry::make('new_values')
                                                    ->label('Depois')
                                                    ->keyLabel('Campo')
                                                    ->valueLabel('Valor')
                                                    ->visible(fn($record) => !empty($record->new_values)),
                                            ])
                                            ->columns(2)
                                            ->visible(fn($record) => $record->event === 'updated'),
                                    ])
                                    ->grid(1)
                                    ->contained(false),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(self::getFormSchema());
    }

    // Reutilizável
    public static function getFormSchema(): array
    {
        return \App\Services\ClienteFormService::getFullSchema();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    // Fallback de cores se o seed não rodou ou categoria não existe
                    ->color(
                        fn(string $state): string =>
                        \App\Models\Categoria::where('slug', $state)->where('tipo', 'cadastro_tipo')->value('cor')
                        ?? match ($state) {
                            'cliente', 'concluida', 'finalizada', 'pago', 'receita' => 'success',
                            'lead', 'em_andamento', 'pendente' => 'warning',
                            'fornecedor', 'cancelado', 'rejeitado', 'despesa' => 'danger',
                            'parceiro', 'arquiteto' => 'primary',
                            'loja', 'orcamento' => 'info',
                            default => 'gray',
                        }
                    )
                    ->visibleFrom('sm'),
                Tables\Columns\TextColumn::make('documento')
                    ->label('CPF/CNPJ')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('telefone')
                    ->label('WhatsApp')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->url(fn($state) => 'https://wa.me/55' . preg_replace('/\D/', '', $state), true)
                    ->color('success')
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('cidade')
                    ->label('Cidade')
                    ->visibleFrom('lg'),
            ])
            ->actions(
                StofgardTable::defaultActions(
                    view: true,
                    edit: true,
                    delete: true,
                    extraActions: [
                        Tables\Actions\Action::make('pdf')
                            ->label('Gerar PDF')
                            ->icon('heroicon-o-document-text')
                            ->color('success')
                            ->tooltip('Gerar PDF em fila')
                            ->url(fn(Cadastro $record) => route('cadastro.pdf', $record))
                            ->hidden(fn() => request()->header('user-agent') && preg_match('/Mobile|Android|iPhone/i', request()->header('user-agent'))),

                        Tables\Actions\Action::make('criar_acesso')
                            ->label('Gerar Acesso')
                            ->tooltip('Criar login para o Portal do Cliente')
                            ->icon('heroicon-o-key')
                            ->color('warning')
                            ->visible(fn() => tenancy()->tenant?->temAcessoPremium() ?? true)
                            ->form([
                                Forms\Components\TextInput::make('email')
                                    ->label('E-mail de Acesso')
                                    ->email()
                                    ->required()
                                    ->default(fn(Cadastro $record) => $record->email),
                                Forms\Components\TextInput::make('password')
                                    ->label('Senha Inicial')
                                    ->password()
                                    ->required()
                                    ->minLength(6),
                            ])
                            ->action(function (Cadastro $record, array $data) {
                                \App\Models\User::updateOrCreate(
                                    ['email' => $data['email']],
                                    [
                                        'name' => $record->nome,
                                        'password' => bcrypt($data['password']),
                                        'is_cliente' => true,
                                        'cadastro_id' => $record->id,
                                    ]
                                );

                                \Filament\Notifications\Notification::make()
                                    ->title('Acesso do cliente criado com sucesso!')
                                    ->success()
                                    ->send();
                            }),
                    ]
                )
            )
            ->bulkActions(
                StofgardTable::defaultBulkActions([
                    // Tables\Actions\DeleteBulkAction::make(), // Já incluído no default
                ])
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCadastros::route('/'),
            'create' => Pages\CreateCadastro::route('/create'),
            'view' => Pages\ViewCadastro::route('/{record}'),
            'edit' => Pages\EditCadastro::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return !auth()->user()?->isFuncionario();
    }
}
