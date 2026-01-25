<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CadastroResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CadastroResource extends Resource {
    protected static ?string $model = Cliente::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Gestão de Cadastros';
    protected static ?string $modelLabel = 'Cadastro';
    protected static ?int $navigationSort = 1;

    // Mostrar no menu lateral
    protected static bool $shouldRegisterNavigation = true;

    // --- 1. VISUALIZAÇÃO (Onde você quer ver os dados bonitos) ---
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Identificação')
                    ->schema([
                        Infolists\Components\TextEntry::make('nome')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\TextEntry::make('tipo')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'cliente' => 'info',
                                'loja', 'parceiro', 'arquiteto' => 'purple',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        Infolists\Components\TextEntry::make('cpf_cnpj')
                            ->label('Documento'),
                    ])->columns(3),
                Infolists\Components\Section::make('Canais de Contato')
                    ->schema([
                        Infolists\Components\TextEntry::make('celular')
                            ->label('WhatsApp / Celular')
                            ->url(fn ($state) => 'https://wa.me/55' . preg_replace('/\D/', '', $state))
                            ->openUrlInNewTab()
                            ->icon('heroicon-m-chat-bubble-left-right')
                            ->color('success')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('telefone')
                            ->label('Telefone Fixo')
                            ->placeholder('-'),
                    ])->columns(3),
                Infolists\Components\Section::make('Endereço')
                    ->schema([
                        Infolists\Components\TextEntry::make('endereco_completo')
                            ->label('Logradouro')
                            ->state(fn ($record) => "{$record->endereco}, {$record->numero} - {$record->bairro}")
                            ->icon('heroicon-m-map-pin')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('cidade_uf')
                            ->label('Cidade')
                            ->state(fn ($record) => "{$record->cidade} / {$record->estado}"),
                        Infolists\Components\TextEntry::make('cep'),
                    ])->columns(4),
                
                Infolists\Components\Section::make('Dados Bancários (Parceiros)')
                    ->schema([
                        Infolists\Components\TextEntry::make('chave_pix')
                            ->label('Chave Pix (Comissão)')
                            ->icon('heroicon-m-banknotes')
                            ->copyable()
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('comissao_percentual')
                            ->label('Comissão (%)')
                            ->formatStateUsing(fn ($state) => $state ? (float) $state . '%' : '0%'),
                        Infolists\Components\TextEntry::make('dados_bancarios')
                            ->label('Dados Bancários')
                            ->placeholder('-'),
                    ])
                    ->visible(fn ($record) => in_array($record->tipo, ['loja', 'parceiro', 'arquiteto'])),

                \Filament\Infolists\Components\Section::make('Galeria de Arquivos')
                    ->schema([
                        \Filament\Infolists\Components\SpatieMediaLibraryImageEntry::make('documentos')
                            ->collection('documentos_cadastro')
                            ->label('Documentos Anexados')
                            ->hiddenLabel()
                            ->columns(4), // Grid de imagens
                    ])
                    ->collapsible(),
            ]);
    }
    // --- 2. FORMULÁRIO (Cadastro e Edição) ---
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados Principais')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->options([
                                'cliente' => 'Cliente Final',
                                'parceiro' => 'Parceiro',
                                'loja' => 'Loja Parceira',
                                'arquiteto' => 'Arquiteto/Designer',
                            ])
                            ->default('cliente')
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome Completo / Razão Social')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cpf_cnpj')
                            ->label('CPF / CNPJ')
                            ->maxLength(18),
                    ])->columns(3),
                Forms\Components\Section::make('Contato')
                    ->schema([
                        Forms\Components\TextInput::make('celular')
                            ->label('Celular (WhatsApp)')
                            ->mask('(99) 99999-9999')
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('telefone')
                            ->label('Telefone Fixo (Opcional)')
                            ->mask('(99) 9999-9999')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->columnSpan(1),
                    ])->columns(3),
                Forms\Components\Section::make('Endereço')
                    ->schema([
                        Forms\Components\TextInput::make('cep')
                            ->mask('99999-999')
                            ->live(onBlur: true), 
                            // Futuramente: Adicionar ViaCep aqui
                        Forms\Components\TextInput::make('endereco')->label('Rua/Av'),
                        Forms\Components\TextInput::make('numero')->label('Nº'),
                        Forms\Components\TextInput::make('bairro'),
                        Forms\Components\TextInput::make('complemento'),
                        Forms\Components\TextInput::make('cidade')->default('Ribeirão Preto'),
                        Forms\Components\TextInput::make('estado')->default('SP')->maxLength(2),
                    ])->columns(4),
                
                Forms\Components\Section::make('Parceria & Financeiro')
                    ->schema([
                        Forms\Components\TextInput::make('chave_pix')
                            ->label('Chave Pix')
                            ->prefixIcon('heroicon-m-banknotes'),
                        Forms\Components\TextInput::make('comissao_percentual')
                            ->label('Comissão (%)')
                            ->numeric()
                            ->suffix('%')
                            ->maxValue(100)
                            ->default(0),
                        Forms\Components\Textarea::make('dados_bancarios')
                            ->label('Dados Bancários Completos')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => in_array($get('tipo'), ['loja', 'arquiteto', 'parceiro'])),

                // Adicionar Componente de Upload Spatie (Arquivos do Cadastro)
                Forms\Components\Section::make('Documentos & Arquivos')
                    ->schema([
                        \Filament\Forms\Components\SpatieMediaLibraryFileUpload::make('documentos')
                            ->label('Anexos (Contratos, Docs, Fotos)')
                            ->collection('documentos_cadastro') // Coleção Spatie
                            ->multiple()
                            ->reorderable()
                            ->openable()
                            ->downloadable()
                            ->maxSize(51200) // 50MB (em KB)
                            ->rules(['max:51200']) // Validação Laravel (em KB)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
    // --- 3. TABELA (Listagem) ---
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
                    ->color(fn (string $state): string => match ($state) {
                        'cliente' => 'info',
                        'loja', 'parceiro' => 'success',
                        'arquiteto' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('celular')
                    ->icon('heroicon-m-phone'),
                Tables\Columns\TextColumn::make('cidade'),
            ])
            ->actions([
                // Botões expostos diretamente (sem grupo) para teste
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->iconButton()
                    ->action(function ($record) {
                        return response()->streamDownload(function () use ($record) {
                            echo \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.ficha-cadastro', ['record' => $record])->output();
                        }, 'ficha-' . \Illuminate\Support\Str::slug($record->nome) . '.pdf');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Excluir Selecionados')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Deletar registros selecionados?')
                        ->modalDescription('Tem certeza? Essa ação não pode ser desfeita.')
                        ->modalSubmitActionLabel('Sim, excluir tudo'),
                ])
                ->label('Ações')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('primary'),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\RelationManagers\AuditsRelationManager::class,
        ];
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
