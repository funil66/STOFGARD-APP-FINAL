<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CadastroResource\Pages;
use App\Models\Cadastro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

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
                        Grid::make(4)->schema([
                            TextEntry::make('nome')
                                ->label('Nome / Razão Social')
                                ->weight('bold')
                                ->columnSpan(2)
                                ->size(TextEntry\TextEntrySize::Large),
                            TextEntry::make('tipo')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'cliente' => 'info',
                                    'loja' => 'success',
                                    'vendedor' => 'warning',
                                    'arquiteto' => 'primary',
                                    default => 'gray',
                                }),
                            TextEntry::make('documento')
                                ->label('Documento')
                                ->icon('heroicon-m-identification')
                                ->copyable(),
                        ]),
                        Grid::make(4)->schema([
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

                // ===== RESUMO FINANCEIRO (CARDS) =====
                Section::make('💰 Resumo Financeiro')
                    ->schema([
                        Grid::make(5)->schema([
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
                                        Grid::make(6)->schema([
                                            TextEntry::make('numero')->label('Nº')->weight('bold'),
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
                                            TextEntry::make('valor_total')->label('Valor')->money('BRL')->color('success')->weight('bold'),
                                            TextEntry::make('id')
                                                ->label('')
                                                ->formatStateUsing(fn() => 'Ver PDF')
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
                                        Grid::make(6)->schema([
                                            TextEntry::make('numero_os')->label('OS')->weight('bold'),
                                            TextEntry::make('tipo_servico')->label('Tipo'),
                                            TextEntry::make('status')
                                                ->badge()
                                                ->color(fn($state) => match ($state) {
                                                    'concluida', 'finalizada' => 'success',
                                                    'cancelada' => 'danger',
                                                    'em_andamento' => 'warning',
                                                    default => 'info',
                                                }),
                                            TextEntry::make('data_prevista')->label('Agendado')->date('d/m/Y'),
                                            TextEntry::make('valor_total')->label('Total')->money('BRL'),
                                            TextEntry::make('id')
                                                ->label('')
                                                ->formatStateUsing(fn() => 'Ver PDF')
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
                                        Grid::make(6)->schema([
                                            TextEntry::make('tipo')
                                                ->badge()
                                                ->color(fn($state) => $state === 'entrada' ? 'success' : 'danger')
                                                ->formatStateUsing(fn($state) => $state === 'entrada' ? '💵 Entrada' : '💸 Saída'),
                                            TextEntry::make('descricao')->label('Descrição')->limit(40),
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
                                        Grid::make(5)->schema([
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
                                        Grid::make(4)->schema([
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
                            ->schema([
                                \Filament\Infolists\Components\SpatieMediaLibraryImageEntry::make('arquivos')
                                    ->label('Galeria de Documentos')
                                    ->collection('arquivos')
                                    ->size(200)
                                    ->square()
                                    ->extraImgAttributes(['class' => 'rounded-lg shadow-md'])
                                    ->columnSpanFull(),
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
        return [
            Forms\Components\Section::make('Classificação & Vínculos')
                ->schema([
                    Forms\Components\Select::make('tipo')
                        ->options([
                            'cliente' => 'Cliente Final',
                            'loja' => 'Loja (Ponto Fixo)',
                            'vendedor' => 'Vendedor (Interno)',
                            'parceiro' => 'Parceiro de Negócios',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn($state, Forms\Set $set) => $state === 'parceiro' ? $set('especialidade', 'Arquiteto') : null),

                    Forms\Components\TextInput::make('especialidade')
                        ->label('Ramo de Atividade / Profissão')
                        ->placeholder('Ex: Arquiteto, Advogado, Zelador')
                        ->visible(fn(Forms\Get $get) => in_array($get('tipo'), ['parceiro', 'loja']))
                        ->columnSpan(1),

                    Forms\Components\Select::make('parent_id')
                        ->label('Loja Vinculada')
                        ->relationship('loja', 'nome', fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('tipo', 'loja'))
                        ->visible(fn(Forms\Get $get) => $get('tipo') === 'vendedor')
                        ->searchable(),

                    // CAMPO DE COMISSÃO
                    Forms\Components\TextInput::make('comissao_percentual')
                        ->label('Comissão Padrão (%)')
                        ->numeric()
                        ->suffix('%')
                        ->default(0)
                        ->visible(fn(Forms\Get $get) => in_array($get('tipo'), ['vendedor', 'loja', 'parceiro']))
                        ->helperText('Porcentagem que será aplicada automaticamente nos orçamentos.'),
                ])->columns(3),
            Forms\Components\Section::make('Dados Principais')
                ->schema([
                    Forms\Components\TextInput::make('nome')->required()->columnSpan(2),
                    Forms\Components\TextInput::make('documento')->label('CPF / CNPJ')
                        ->unique(ignoreRecord: true)
                        ->mask(\Filament\Support\RawJs::make(<<<'JS'
                            $input.length > 14 ? '99.999.999/9999-99' : '999.999.999-99'
                        JS)),
                    Forms\Components\TextInput::make('rg_ie')->label('RG / Inscrição'),
                ])->columns(4),
            Forms\Components\Section::make('Contato & Endereço')
                ->schema([
                    Forms\Components\TextInput::make('email')->email()->columnSpan(2),
                    Forms\Components\TextInput::make('telefone')->mask('(99) 99999-9999')->required(),
                    Forms\Components\TextInput::make('cep')->mask('99999-999')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (strlen(preg_replace('/[^0-9]/', '', $state)) === 8) {
                                $data = \Illuminate\Support\Facades\Http::get("https://viacep.com.br/ws/{$state}/json/")->json();
                                if (!isset($data['erro'])) {
                                    $set('logradouro', $data['logradouro']);
                                    $set('bairro', $data['bairro']);
                                    $set('cidade', $data['localidade']);
                                    $set('estado', $data['uf']);
                                }
                            }
                        }),
                    Forms\Components\TextInput::make('logradouro')->required(),
                    Forms\Components\TextInput::make('numero')->required(),
                    Forms\Components\TextInput::make('bairro')->required(),
                    Forms\Components\TextInput::make('cidade')->required(),
                    Forms\Components\TextInput::make('estado')->maxLength(2)->required(),
                    Forms\Components\TextInput::make('complemento'),
                ])->columns(4),
            Forms\Components\Section::make('Central de Arquivos')
                ->description('Envie fotos, documentos e comprovantes (Máx: 20MB).')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Toggle::make('pdf_mostrar_documentos')
                        ->label('Exibir Documentos no PDF?')
                        ->helperText('Se marcado, os documentos anexados aparecerão no PDF da ficha cadastral.')
                        ->default(fn() => \App\Models\Setting::get('pdf_mostrar_documentos_global', true))
                        ->columnSpanFull(),

                    Forms\Components\SpatieMediaLibraryFileUpload::make('arquivos')
                        ->label('Anexos (Até 20MB)')
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
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cliente' => 'info',
                        'loja' => 'success',
                        'vendedor' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('telefone')->label('WhatsApp')->icon('heroicon-m-phone'),
                Tables\Columns\TextColumn::make('cidade')->label('Cidade'),
            ])
            ->actions([
                // 1. PDF (Verde Destaque)
                Tables\Actions\Action::make('pdf')
                    ->label('Ficha')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->button()
                    ->url(fn(Cadastro $record) => route('cadastro.pdf', $record))
                    ->openUrlInNewTab(),
                // 2. VISUALIZAR (Olho)
                Tables\Actions\ViewAction::make()->label('')->tooltip('Ver Detalhes'),
                // 3. EDITAR (Lápis)
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                // 4. BAIXAR PDF
                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('Baixar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(Cadastro $record) => route('cadastro.pdf', $record))
                    ->openUrlInNewTab(),
                // 5. EXCLUIR (Lixeira)
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
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
}
