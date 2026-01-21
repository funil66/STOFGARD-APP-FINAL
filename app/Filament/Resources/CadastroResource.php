<?php

namespace App\Filament\Resources;


use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions as Actions;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use App\Models\Cliente;
use App\Models\Parceiro;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Filament\Resources\CadastroResource\Pages as Pages;

class CadastroResource extends Resource
{
    protected static ?string $model = Cliente::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Cadastro';
    protected static ?string $modelLabel = 'Cadastro';
    protected static ?string $pluralModelLabel = 'Cadastros';
    protected static ?string $navigationGroup = 'Gestão';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados Pessoais')
                    ->schema([
                        Forms\Components\Select::make('tipo_cadastro')
                            ->label('Tipo')
                            ->options([
                                'cliente' => 'Cliente',
                                'loja' => 'Loja',
                                'vendedor' => 'Vendedor',
                            ])
                            ->default('cliente')
                            ->required()
                            ->native(false)
                            ->helperText('Selecione o tipo de cadastro. Para alterar o tipo de um registro existente, crie um novo registro como Loja/Vendedor.')
                            ->columnSpan(2)
                            ->disabled(fn () => request()->routeIs('*edit*')),

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
                                // Mirror telefone into celular by default
                                $set('celular', $state);
                            }),

                        Forms\Components\TextInput::make('celular')
                            ->tel()
                            ->mask('(99) 99999-9999')
                            ->placeholder('(16) 99999-8888')
                            ->suffixIcon('heroicon-m-device-phone-mobile'),

                        // Comissão padrão (exibida para lojas e vendedores)
                        Forms\Components\TextInput::make('percentual_comissao')
                            ->label('% Comissão Padrão')
                            ->numeric()
                            ->default(10)
                            ->suffix('%')
                            ->visible(fn ($get) => in_array($get('tipo_cadastro'), ['loja', 'vendedor']))
                            ->columnSpan(1),

                        // Se for vendedor, vincular a uma loja
                        Forms\Components\Select::make('loja_id')
                            ->label('Loja vinculada')
                            ->options(fn () => \App\Models\Parceiro::where('tipo', 'loja')->pluck('nome', 'id'))
                            ->visible(fn ($get) => $get('tipo_cadastro') === 'vendedor')
                            ->required(fn ($get) => $get('tipo_cadastro') === 'vendedor')
                            ->columnSpan(1),

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



                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cliente' => 'Cliente',
                        'loja' => 'Loja',
                        'vendedor' => 'Vendedor',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('celular')
                    ->label('Celular')
                    ->searchable()
                    ->icon('heroicon-m-device-phone-mobile')
                    ->iconColor(Color::hex('#10b981'))
                    ->url(fn ($record) => isset($record->celular) && $record->celular ? "https://wa.me/55" . preg_replace('/\D/', '', $record->celular) : null)
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
                Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('cadastros.show', ['uuid' => $record->uuid ?? $record->getKey()]))
                    ->openUrlInNewTab(false),

                Actions\Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => \App\Filament\Resources\ClienteResource::getUrl('edit', ['record' => $record->getKey()]))
                    ->openUrlInNewTab(false),

                Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('cadastros.download', ['uuid' => $record->uuid]))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->arquivos)),

                Actions\DeleteAction::make()
                    ->requiresConfirmation(),

                Actions\RestoreAction::make()
                    ->visible(fn ($record) => method_exists($record, 'trashed') ? $record->trashed() : false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('download')
                        ->label('Download Arquivos')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            $zip = new ZipArchive;
                            $zipFileName = 'arquivos_selecionados.zip';
                            $tempDir = storage_path('app/temp');
                            if (!is_dir($tempDir)) {
                                mkdir($tempDir, 0755, true);
                            }
                            $zipPath = $tempDir . '/' . $zipFileName;

                            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                                foreach ($records as $record) {
                                    $files = $record->arquivos ?? [];
                                    foreach ($files as $file) {
                                        $filePath = Storage::disk('public')->path($file);
                                        if (file_exists($filePath)) {
                                            $zip->addFile($filePath, $record->nome . '/' . basename($file));
                                        }
                                    }
                                }
                                $zip->close();

                                return response()->download($zipPath)->deleteFileAfterSend(true);
                            } else {
                                Notification::make()
                                    ->title('Erro ao criar arquivo zip.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Download de Arquivos')
                        ->modalDescription('Isso irá baixar um arquivo ZIP com todos os arquivos dos cadastros selecionados.')
                        ->modalSubmitActionLabel('Download'),

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
                Infolists\Components\Section::make('Informações do Cadastro')
                    ->schema([
                        Infolists\Components\TextEntry::make('nome')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->icon('heroicon-m-user'),

                        Infolists\Components\TextEntry::make('email')
                            ->icon('heroicon-m-envelope')
                            ->copyable()
                            ->copyMessage('Email copiado!'),

                        Infolists\Components\TextEntry::make('telefone')
                            ->icon('heroicon-m-phone')
                            ->url(fn ($state) => $state ? "tel:{$state}" : null),

                        Infolists\Components\TextEntry::make('celular')
                            ->icon('heroicon-m-device-phone-mobile')
                            ->badge()
                            ->color(Color::hex('#10b981'))
                            ->url(fn ($record) => $record->link_whatsapp)
                            ->openUrlInNewTab()
                            ->suffix(' (WhatsApp)'),

                        Infolists\Components\TextEntry::make('cpf_cnpj')
                            ->label('CPF/CNPJ'),

                        Infolists\Components\TextEntry::make('endereco_completo')
                            ->label('Endereço')
                            ->icon('heroicon-m-map-pin')
                            ->url(fn (\App\Models\Cliente $record) => $record->link_maps)
                            ->openUrlInNewTab()
                            ->badge()
                            ->color(Color::hex('#06b6d4'))
                            ->suffix(' 📍 Ver no Mapa')
                            ->columnSpanFull(),
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
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('Observações')
                    ->schema([
                        Infolists\Components\TextEntry::make('observacoes')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull()
                            ->placeholder('Sem observações cadastradas'),
                    ])
                    ->collapsible()
                    ->hidden(fn (\App\Models\Cliente $record) => empty($record->observacoes)),

                Infolists\Components\Section::make('Informações do Sistema')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Cadastrado em')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('registrado_por')
                            ->label('Registrado por')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCadastros::route('/'),
            'create' => Pages\CreateCadastro::route('/create'),
            'edit' => Pages\EditCadastro::route('/{record}/edit'),
            'view' => Pages\ViewCadastro::route('/{record}'),
        ];
    }
}
