<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?string $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 1;

    // Prefer using the unified `Cadastro` resource in navigation — hide the legacy
    // Clientes resource to avoid duplicate menu entries.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados Pessoais')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Nome completo')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('[email protected]'),

                        Forms\Components\TextInput::make('telefone')
                            ->tel()
                            ->mask('(99) 9999-9999')
                            ->placeholder('(16) 3333-4444')
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Sempre espelhar telefone em celular quando telefone for alterado
                                $set('celular', $state);
                            }),

                        Forms\Components\TextInput::make('celular')
                            ->tel()
                            // Usamos mesma máscara de telefone para consistência solicitada
                            ->mask('(99) 9999-9999')
                            ->placeholder('(16) 99999-8888')
                            ->suffixIcon('heroicon-m-device-phone-mobile'),

                        Forms\Components\TextInput::make('cpf_cnpj')
                            ->label('CPF/CNPJ')
                            ->maxLength(255)
                            ->placeholder('000.000.000-00'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Endereço')
                    ->schema([
                        Forms\Components\TextInput::make('cep')
                            ->label('CEP')
                            ->mask('99999-999')
                            ->placeholder('14000-000')
                            ->suffixIcon('heroicon-m-magnifying-glass')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (strlen(preg_replace('/\D/', '', $state)) === 8) {
                                    $cep = preg_replace('/\D/', '', $state);

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
                                        // Silently fail - user can fill manually
                                    }
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('logradouro')
                            ->label('Rua/Avenida')
                            ->maxLength(255)
                            ->placeholder('Rua das Flores')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('numero')
                            ->label('Número')
                            ->maxLength(50)
                            ->placeholder('123')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('complemento')
                            ->maxLength(255)
                            ->placeholder('Apto 45, Bloco B')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('bairro')
                            ->maxLength(255)
                            ->placeholder('Centro')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('cidade')
                            ->maxLength(255)
                            ->placeholder('Ribeirão Preto')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('estado')
                            ->label('UF')
                            ->maxLength(2)
                            ->placeholder('SP')
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->collapsible(),

                Forms\Components\Section::make('Informações Adicionais')
                    ->schema([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(4)
                            ->placeholder('Anotações importantes sobre o cliente...')
                            ->columnSpanFull(),

                        // Feature flags (e.g., enable per-client features)
                        Forms\Components\Toggle::make('features.beta_feature_x')
                            ->label('Ativar recurso beta')
                            ->helperText('Ative para permitir que este cliente utilize o recurso beta configurado.')
                            ->inline(false)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('arquivos')
                            ->label('Arquivos Anexos')
                            ->multiple()
                            ->directory('clientes-arquivos')
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
                            ->helperText('Fotos, documentos e outros arquivos relacionados ao cliente'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-user')
                    ->iconColor(Color::hex('#2563eb')),

                Tables\Columns\ImageColumn::make('arquivos')
                    ->label('Arquivo')
                    ->getStateUsing(fn ($record) => is_array($record->arquivos) ? ($record->arquivos[0] ?? null) : $record->arquivos)
                    ->disk('public')
                    ->visibility('public')
                    ->checkFileExistence(false)
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('celular')
                    ->searchable()
                    ->icon('heroicon-m-device-phone-mobile')
                    ->iconColor(Color::hex('#10b981'))
                    ->url(fn (Cliente $record) => $record->link_whatsapp)
                    ->openUrlInNewTab()
                    ->tooltip('Abrir WhatsApp'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->copyMessage('Email copiado!')
                    ->limit(30),

                Tables\Columns\TextColumn::make('cidade')
                    ->searchable()
                    ->icon('heroicon-m-map-pin')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('registrado_por')
                    ->label('Por')
                    ->badge()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'SP' => 'São Paulo',
                        'MG' => 'Minas Gerais',
                        'RJ' => 'Rio de Janeiro',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->color(Color::hex('#2563eb')),
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                // Standard download action for first file in 'arquivos'
                \App\Filament\Actions\DownloadFileAction::make('arquivos', 'public'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informações do Cliente')
                    ->schema([
                        Infolists\Components\TextEntry::make('nome')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->icon('heroicon-m-user'),

                        Infolists\Components\TextEntry::make('email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('telefone')
                            ->icon('heroicon-m-phone')
                            ->url(fn ($state) => $state ? "tel:{$state}" : null),

                        Infolists\Components\TextEntry::make('celular')
                            ->icon('heroicon-m-device-phone-mobile')
                            ->badge()
                            ->color(Color::hex('#10b981'))
                            ->url(fn (Cliente $record) => $record->link_whatsapp)
                            ->suffix(' (WhatsApp)'),

                        Infolists\Components\TextEntry::make('cpf_cnpj')
                            ->label('CPF/CNPJ'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Arquivos')
                    ->schema([
                        Infolists\Components\ImageEntry::make('arquivos')
                            ->label('Arquivos Anexos')
                            ->disk('public')
                            ->visibility('public')
                            ->limit(10)
                            ->height(400)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('arquivos_list')
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

                Infolists\Components\Section::make('Endereço')
                    ->schema([
                        Infolists\Components\TextEntry::make('endereco_completo')
                            ->label('')
                            ->icon('heroicon-m-map-pin')
                            ->url(fn (Cliente $record) => $record->link_maps)
                            ->openUrlInNewTab()
                            ->badge()
                            ->color(Color::hex('#06b6d4'))
                            ->suffix(' 📍 Ver no Mapa')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Observações')
                    ->schema([
                        Infolists\Components\TextEntry::make('observacoes')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull()
                            ->placeholder('Sem observações cadastradas'),
                    ])
                    ->hidden(fn (Cliente $record) => empty($record->observacoes)),

                Infolists\Components\Section::make('Informações do Sistema')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Cadastrado em')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('registrado_por')
                            ->label('Registrado por')
                            ->badge(),

                        Infolists\Components\TextEntry::make('alterado_por')
                            ->label('Alterado por')
                            ->badge(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última atualização')
                            ->dateTime('d/m/Y H:i')
                            ->since(),
                    ])
                    ->columns(3)
                    ->collapsible(),
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
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'view' => Pages\ViewCliente::route('/{record}'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
