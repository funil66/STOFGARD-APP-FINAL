<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfiguracaoResource\Pages;
use App\Models\Configuracao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use Filament\Forms\Get;

class ConfiguracaoResource extends Resource
{
    protected static ?string $model = Configuracao::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configurações';

    protected static ?string $modelLabel = 'Configuração';

    protected static ?string $pluralModelLabel = 'Configurações';

    protected static ?string $navigationGroup = 'Administração';

    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Configuração')
                    ->schema([
                        Forms\Components\Select::make('grupo')
                            ->label('Grupo')
                            ->options([
                                'empresa' => '🏢 Empresa',
                                'financeiro' => '💰 Financeiro',
                                'nfse' => '📄 Nota Fiscal',
                                'sistema' => '⚙️ Sistema',
                                'notificacoes' => '🔔 Notificações',
                            ])
                            ->required()
                            ->native(false)
                            ->searchable(),

                        Forms\Components\TextInput::make('chave')
                            ->label('Chave')
                            ->helperText('Identificador único dentro do grupo (ex: razao_social, pix_chave)')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Select::make('tipo')
                            ->label('Tipo de Valor')
                            ->options([
                                'text' => 'Texto',
                                'number' => 'Número',
                                'boolean' => 'Sim/Não',
                                'json' => 'JSON',
                                'file' => 'Arquivo',
                            ])
                            ->default('text')
                            ->required()
                            ->native(false)
                            ->live(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Valor')
                    ->schema([
                        Forms\Components\Textarea::make('valor')
                            ->label('Valor')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->hidden(fn (Get $get) => in_array($get('tipo'), ['boolean', 'file'])),

                        Forms\Components\Toggle::make('valor')
                            ->label('Valor')
                            ->hidden(fn (Get $get) => $get('tipo') !== 'boolean'),

                        Forms\Components\FileUpload::make('valor')
                            ->label('Valor')
                            ->disk('public')
                            ->directory('configuracoes')
                            ->hidden(fn (Get $get) => $get('tipo') !== 'file'),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->helperText('Explique para que serve esta configuração')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('grupo')
                    ->label('Grupo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'empresa' => 'info',
                        'financeiro' => 'success',
                        'nfse' => 'warning',
                        'sistema' => 'gray',
                        'notificacoes' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'empresa' => '🏢 Empresa',
                        'financeiro' => '💰 Financeiro',
                        'nfse' => '📄 Nota Fiscal',
                        'sistema' => '⚙️ Sistema',
                        'notificacoes' => '🔔 Notificações',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('chave')
                    ->label('Chave')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Chave copiada!')
                    ->copyMessageDuration(1500),

                Tables\Columns\ImageColumn::make('valor')
                    ->label('Valor')
                    ->disk('public')
                    ->visibility('public')
                    ->checkFileExistence(false)
                    ->square()
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->tipo === 'file'),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->valor)
                    ->visible(fn ($record) => $record->tipo !== 'file'),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('grupo')
            ->filters([
                Tables\Filters\SelectFilter::make('grupo')
                    ->label('Grupo')
                    ->options([
                        'empresa' => '🏢 Empresa',
                        'financeiro' => '💰 Financeiro',
                        'nfse' => '📄 Nota Fiscal',
                        'sistema' => '⚙️ Sistema',
                        'notificacoes' => '🔔 Notificações',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'text' => 'Texto',
                        'number' => 'Número',
                        'boolean' => 'Sim/Não',
                        'json' => 'JSON',
                        'file' => 'Arquivo',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                \App\Filament\Actions\DownloadFileAction::make('valor', 'public')->label('Download')->visible(fn ($record) => $record->tipo === 'file'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListConfiguracaos::route('/'),
            'create' => Pages\CreateConfiguracao::route('/create'),
            'edit' => Pages\EditConfiguracao::route('/{record}/edit'),
        ];
    }

    // Restrição de acesso: apenas admin (`is_admin`) ou usuário principal `allisson@stofgard.com.br`
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return ($user->is_admin === true) || ($user->email === 'allisson@stofgard.com.br');
    }
}
