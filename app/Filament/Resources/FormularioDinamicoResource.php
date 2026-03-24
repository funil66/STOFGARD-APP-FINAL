<?php

namespace App\Filament\Resources;

use App\Models\FormularioDinamico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * FormularioDinamicoResource — Construtor visual de formulários por tenant.
 * Permite criar "anamneses" customizadas que aparecem dentro das OSes.
 */
class FormularioDinamicoResource extends Resource
{
    protected static ?string $model = FormularioDinamico::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Formulários';

    protected static ?string $modelLabel = 'Formulário';

    protected static ?string $pluralModelLabel = 'Formulários Dinâmicos';

    protected static ?string $navigationGroup = 'Operacional';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificação')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome do Formulário')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Anamnese Estética, Vistoria Técnica'),

                        Forms\Components\Select::make('tipo_servico')
                            ->label('Vincular ao Tipo de Serviço')
                            ->options(\App\Services\ServiceTypeManager::getOptions())
                            ->searchable()
                            ->nullable()
                            ->helperText('Se vinculado, aparece automaticamente em OSes deste tipo. Deixe em branco para mostrar em todos.'),

                        Forms\Components\Toggle::make('ativo')
                            ->label('Ativo')
                            ->default(true),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição Interna')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('🧱 Campos do Formulário')
                    ->description('Arraste e solte para ordenar. Clique em "Adicionar" para criar um novo campo.')
                    ->schema([
                        Forms\Components\Builder::make('campos')
                            ->label('')
                            ->blocks([
                                // Campo de texto simples
                                Forms\Components\Builder\Block::make('texto')
                                    ->label('📝 Campo de Texto')
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label('Pergunta / Rótulo')
                                            ->required()
                                            ->placeholder('Ex: Nome completo do paciente'),
                                        Forms\Components\TextInput::make('placeholder')
                                            ->label('Texto de exemplo')
                                            ->placeholder('Ex: João Silva'),
                                        Forms\Components\Toggle::make('obrigatorio')
                                            ->label('Campo obrigatório')
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                // Campo numérico
                                Forms\Components\Builder\Block::make('numero')
                                    ->label('🔢 Campo Numérico')
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label('Pergunta / Rótulo')
                                            ->required(),
                                        Forms\Components\TextInput::make('sufixo')
                                            ->label('Unidade (ex: kg, cm, anos)')
                                            ->nullable(),
                                        Forms\Components\Toggle::make('obrigatorio')
                                            ->label('Campo obrigatório')
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                // Campo de texto longo
                                Forms\Components\Builder\Block::make('textarea')
                                    ->label('📄 Texto Longo')
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label('Pergunta / Rótulo')
                                            ->required(),
                                        Forms\Components\TextInput::make('placeholder')
                                            ->label('Texto de exemplo')
                                            ->nullable(),
                                        Forms\Components\Toggle::make('obrigatorio')
                                            ->label('Campo obrigatório')
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                // Campo de seleção (Select)
                                Forms\Components\Builder\Block::make('select')
                                    ->label('📋 Seleção (Dropdown)')
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label('Pergunta / Rótulo')
                                            ->required(),
                                        Forms\Components\Repeater::make('opcoes')
                                            ->label('Opções')
                                            ->schema([
                                                Forms\Components\TextInput::make('valor')
                                                    ->label('Opção')
                                                    ->required(),
                                            ])
                                            ->addActionLabel('Adicionar opção')
                                            ->minItems(2)
                                            ->columnSpanFull(),
                                        Forms\Components\Toggle::make('obrigatorio')
                                            ->label('Campo obrigatório')
                                            ->default(false),
                                    ]),

                                // Sim/Não (Toggle)
                                Forms\Components\Builder\Block::make('booleano')
                                    ->label('✅ Sim / Não')
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label('Pergunta')
                                            ->required()
                                            ->placeholder('Ex: Possui alergia a produtos químicos?'),
                                        Forms\Components\Toggle::make('obrigatorio')
                                            ->label('Campo obrigatório')
                                            ->default(false),
                                    ]),

                                // Upload de foto
                                Forms\Components\Builder\Block::make('foto')
                                    ->label('📷 Upload de Foto')
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label('Descrição da foto')
                                            ->required()
                                            ->placeholder('Ex: Foto antes do serviço'),
                                        Forms\Components\Select::make('disk')
                                            ->label('Armazenamento')
                                            ->options([
                                                'public' => 'Local (public)',
                                                's3' => 'AWS S3 (recomendado)',
                                            ])
                                            ->default('public'),
                                        Forms\Components\Toggle::make('obrigatorio')
                                            ->label('Campo obrigatório')
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                // Divisor / Título de seção
                                Forms\Components\Builder\Block::make('secao')
                                    ->label('📌 Título de Seção')
                                    ->schema([
                                        Forms\Components\TextInput::make('titulo')
                                            ->label('Título da seção')
                                            ->required()
                                            ->placeholder('Ex: Informações de Saúde'),
                                        Forms\Components\Textarea::make('descricao')
                                            ->label('Descrição (opcional)')
                                            ->rows(2),
                                    ]),
                            ])
                            ->collapsible()
                            ->reorderable()
                            ->columnSpanFull()
                            ->addActionLabel('+ Adicionar Campo'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn(FormularioDinamico $record) => $record->descricao),

                Tables\Columns\TextColumn::make('tipo_servico')
                    ->label('Tipo de Serviço')
                    ->badge()
                    ->placeholder('Todos os tipos'),

                Tables\Columns\TextColumn::make('campos')
                    ->label('Campos')
                    ->formatStateUsing(fn($state) => count($state ?? []) . ' campos')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('ativo')
                    ->boolean()
                    ->label('Ativo'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Status')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => \App\Filament\Resources\FormularioDinamicoResource\Pages\ListFormulariosDinamicos::route('/'),
            'create' => \App\Filament\Resources\FormularioDinamicoResource\Pages\CreateFormularioDinamico::route('/create'),
            'edit' => \App\Filament\Resources\FormularioDinamicoResource\Pages\EditFormularioDinamico::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $tenant = function_exists('tenancy') ? tenancy()->tenant : null;
        $isPremium = $tenant ? $tenant->temAcessoPremium() : false;

        return $isPremium && !auth()->user()?->isFuncionario();
    }
}
