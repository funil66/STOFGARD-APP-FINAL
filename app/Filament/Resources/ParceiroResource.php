<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParceiroResource\Pages;
use App\Models\Parceiro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\ParceiroResource\RelationManagers as RelationManagers;

class ParceiroResource extends Resource
{
    protected static ?string $model = Parceiro::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Parceiros';

    protected static ?string $modelLabel = 'Parceiro';

    protected static ?string $pluralModelLabel = 'Parceiros';

    protected static ?string $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 2;

    // Use the unified `Cadastro` resource for navigation; keep this resource available
    // but excluded from the sidebar to avoid confusion.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tipo de Parceiro')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'loja' => '🏪 Loja Parceira',
                                'vendedor' => '👤 Vendedor',
                            ])
                            ->required()
                            ->native(false)
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('ativo')
                            ->label('Ativo')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(2),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Dados Cadastrais')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome Fantasia / Nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('razao_social')
                            ->label('Razão Social')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('cnpj_cpf')
                            ->label('CNPJ / CPF')
                            ->mask(fn ($get) => strlen($get('cnpj_cpf') ?? '') > 14 ? '99.999.999/9999-99' : '999.999.999-99')
                            ->placeholder('000.000.000-00 ou 00.000.000/0000-00')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('percentual_comissao')
                            ->label('% Comissão Padrão')
                            ->numeric()
                            ->default(10)
                            ->suffix('%')
                            ->required()
                            ->columnSpan(2),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Contato')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255)
                            ->suffixIcon('heroicon-o-envelope')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('telefone')
                            ->label('Telefone')
                            ->tel()
                            ->mask('(99) 9999-9999')
                            ->placeholder('(00) 0000-0000')
                            ->suffixIcon('heroicon-o-phone')
                            ->columnSpan(1)
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Mirror telefone into celular by default
                                $set('celular', $state);
                            }),

                        Forms\Components\TextInput::make('celular')
                            ->label('Celular')
                            ->mask('(99) 99999-9999')
                            ->placeholder('(00) 00000-0000')
                            ->suffixIcon('heroicon-o-device-phone-mobile')
                            ->columnSpan(1),

                        Forms\Components\Select::make('loja_id')
                            ->label('Loja vinculada')
                            ->options(fn () => \App\Models\Parceiro::where('tipo', 'loja')->pluck('nome', 'id'))
                            ->visible(fn ($get) => $get('tipo') === 'vendedor')
                            ->required(fn ($get) => $get('tipo') === 'vendedor')
                            ->columnSpan(2),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Endereço')
                    ->schema([
                        Forms\Components\TextInput::make('cep')
                            ->label('CEP')
                            ->mask('99999-999')
                            ->placeholder('00000-000')
                            ->suffixIcon('heroicon-o-magnifying-glass')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (strlen($state) === 9) {
                                    $cep = str_replace('-', '', $state);
                                    try {
                                        $response = \Illuminate\Support\Facades\Http::get("https://viacep.com.br/ws/{$cep}/json/");
                                        if ($response->successful() && ! isset($response->json()['erro'])) {
                                            $data = $response->json();
                                            $set('logradouro', $data['logradouro'] ?? '');
                                            $set('bairro', $data['bairro'] ?? '');
                                            $set('cidade', $data['localidade'] ?? '');
                                            $set('estado', $data['uf'] ?? '');
                                        }
                                    } catch (\Exception $e) {
                                        // Silently fail
                                    }
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('logradouro')
                            ->label('Logradouro')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('numero')
                            ->label('Número')
                            ->maxLength(50)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('complemento')
                            ->label('Complemento')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('bairro')
                            ->label('Bairro')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('cidade')
                            ->label('Cidade')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('estado')
                            ->label('UF')
                            ->maxLength(2)
                            ->columnSpan(2),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Estatísticas')
                    ->schema([
                        Forms\Components\TextInput::make('total_vendas')
                            ->label('Total de Vendas')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->suffix('OS')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('total_comissoes')
                            ->label('Total Comissões')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->prefix('R$')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Informações Adicionais')
                    ->schema([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('arquivos')
                            ->label('Arquivos Anexos')
                            ->multiple()
                            ->directory('parceiros-arquivos')
                            ->visibility('public')
                            ->disk('public')
                            ->image()
                            ->imagePreviewHeight('180')
                            ->panelLayout('grid')
                            ->imageEditor()
                            ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1'])
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->reorderable()
                            ->columnSpanFull()
                            ->helperText('Fotos, documentos e outros arquivos relacionados ao parceiro'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'loja' => 'success',
                        'vendedor' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'loja' => '🏪 Loja',
                        'vendedor' => '👤 Vendedor',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),

                Tables\Columns\TextColumn::make('celular')
                    ->label('Celular')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->url(fn ($record) => $record->link_whatsapp)
                    ->openUrlInNewTab()
                    ->color('success')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cidade')
                    ->label('Cidade')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('arquivos')
                    ->label('Arquivos')
                    ->html()
                    ->formatStateUsing(function ($state, $record) {
                        if (empty($record->arquivos)) {
                            return '';
                        }

                        $entries = [];

                        foreach ($record->arquivos as $path) {
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

                            $entries[] = "<div class='inline-flex items-center gap-2'><a href='{$downloadUrl}' target='_blank' class='text-xs text-blue-600 underline'>{$name}</a> <a href='{$downloadUrl}?download=1' class='text-xs ml-2' title='Baixar'>⤓</a> <a href='{$deleteUrl}' class='text-xs text-red-600 ml-2' onclick=\"return confirm('Excluir arquivo?')\">✖</a></div>";
                        }

                        return implode('', $entries);
                    }),

                Tables\Columns\TextColumn::make('percentual_comissao')
                    ->label('% Comissão')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_vendas')
                    ->label('Vendas')
                    ->numeric()
                    ->suffix(' OS')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_comissoes')
                    ->label('Comissões')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('registrado_por')
                    ->label('Por')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'loja' => 'Loja Parceira',
                        'vendedor' => 'Vendedor',
                    ]),

                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Somente Ativos')
                    ->falseLabel('Somente Inativos'),

                Tables\Filters\SelectFilter::make('cidade')
                    ->label('Cidade')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn ($record) => $record->link_whatsapp)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => ! empty($record->celular)),

                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Informações do Parceiro')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('tipo_label')
                            ->label('Tipo')
                            ->badge()
                            ->color(fn ($record) => $record->tipo === 'loja' ? 'success' : 'info'),

                        \Filament\Infolists\Components\TextEntry::make('nome')
                            ->label('Nome')
                            ->weight('bold')
                            ->size('lg'),

                        \Filament\Infolists\Components\IconEntry::make('ativo')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),

                        \Filament\Infolists\Components\TextEntry::make('percentual_comissao')
                            ->label('Comissão Padrão')
                            ->suffix('%')
                            ->badge()
                            ->color('warning'),
                    ])
                    ->columns(4),

                \Filament\Infolists\Components\Section::make('Dados Cadastrais')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('razao_social')
                            ->label('Razão Social')
                            ->placeholder('Não informado'),

                        \Filament\Infolists\Components\TextEntry::make('cnpj_cpf')
                            ->label('CNPJ / CPF')
                            ->copyable()
                            ->placeholder('Não informado'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                \Filament\Infolists\Components\Section::make('Contato')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('email')
                            ->label('E-mail')
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->placeholder('Não informado'),

                        \Filament\Infolists\Components\TextEntry::make('telefone')
                            ->label('Telefone')
                            ->icon('heroicon-o-phone')
                            ->placeholder('Não informado'),

                        \Filament\Infolists\Components\TextEntry::make('celular')
                            ->label('Celular / WhatsApp')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->url(fn ($record) => $record->link_whatsapp)
                            ->openUrlInNewTab()
                            ->color('success')
                            ->placeholder('Não informado'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                \Filament\Infolists\Components\Section::make('Endereço')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('endereco_completo')
                            ->label('Endereço Completo')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                \Filament\Infolists\Components\Section::make('Estatísticas')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('total_vendas')
                            ->label('Total de Vendas')
                            ->suffix(' OS')
                            ->badge()
                            ->color('info'),

                        \Filament\Infolists\Components\TextEntry::make('total_comissoes')
                            ->label('Total em Comissões')
                            ->money('BRL')
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                \Filament\Infolists\Components\Section::make('Arquivos')
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('arquivos')
                            ->label('Arquivos Anexos')
                            ->disk('public')
                            ->visibility('public')
                            ->limit(10)
                            ->height(400)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),

                        \Filament\Infolists\Components\TextEntry::make('arquivos_list')
                            ->label('Ações de Arquivo')
                            ->html()
                            ->getStateUsing(function ($record) {
                                if (empty($record->arquivos)) {
                                    return '';
                                }

                                $entries = [];

                                foreach ($record->arquivos as $path) {
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
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                \Filament\Infolists\Components\Section::make('Observações')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('observacoes')
                            ->label('Observações')
                            ->placeholder('Nenhuma observação')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                \Filament\Infolists\Components\Section::make('Informações do Sistema')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('registrado_por')
                            ->label('Registrado por')
                            ->badge()
                            ->color('gray'),

                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Cadastrado em')
                            ->dateTime('d/m/Y H:i'),

                        \Filament\Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última atualização')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VendedoresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParceiros::route('/'),
            'create' => Pages\CreateParceiro::route('/create'),
            'view' => Pages\ViewParceiro::route('/{record}'),
            'edit' => Pages\EditParceiro::route('/{record}/edit'),
        ];
    }
}
