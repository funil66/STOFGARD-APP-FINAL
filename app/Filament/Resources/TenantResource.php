<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Gestão do SaaS';
    protected static ?string $modelLabel = 'Cliente (Tenant)';
    protected static ?string $pluralModelLabel = 'Clientes (Tenants)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dados do Cliente (Banco Isolado)')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('Identificador Único (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->helperText('Não use espaços. Ex: loja-do-chaves. Isso será usado para nomear o banco de dados fisico.'),

                        // O pacote Tenancy salva dados extras em JSON. Vamos colocar o 'name'
                        Forms\Components\TextInput::make('name')
                            ->label('Nome Fantasia / Razão Social')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Cliente Ativo? (Mensalidade em dia)')
                            ->default(true)
                            ->helperText('Desmarque para bloquear o acesso do cliente ao sistema.'),
                    ])->columns(2),

                Section::make('Domínios Associados')
                    ->schema([
                        Forms\Components\Repeater::make('domains')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('domain')
                                    ->label('URL de Acesso')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Ex: cliente.stofgard.com.br ou app.cliente.com.br'),
                            ])
                            ->addActionLabel('Adicionar Domínio')
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID / Slug')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable(),

                Tables\Columns\TextColumn::make('domains.domain')
                    ->label('Domínio Principal')
                    ->badge()
                    ->color('info'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Ativo'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('login_as')
                    ->label('Impersonar')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->url(fn(Tenant $record) => "http://{$record->domains->first()?->domain}/portal")
                    ->openUrlInNewTab()
                    ->tooltip('Acessar o painel logado no banco desse cliente.'),
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
