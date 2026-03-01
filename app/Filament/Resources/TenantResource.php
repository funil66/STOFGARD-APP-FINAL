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
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('🏢 Dados do Cliente')
                            ->schema([
                                Forms\Components\TextInput::make('id')
                                    ->label('Identificador Único (Slug)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->disabled(fn(string $operation): bool => $operation === 'edit')
                                    ->helperText('Não use espaços. Ex: loja-do-chaves.'),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nome Fantasia / Razão Social')
                                    ->required(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Acesso Liberado?')
                                    ->default(true),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('🌐 Domínios')
                            ->schema([
                                Forms\Components\Repeater::make('domains')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('domain')
                                            ->label('URL de Acesso')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->helperText('Ex: cliente.stofgard.com.br'),
                                    ])
                            ]),

                        Forms\Components\Tabs\Tab::make('💳 Assinatura e Faturamento')
                            ->schema([
                                Forms\Components\Select::make('status_pagamento')
                                    ->label('Status da Assinatura')
                                    ->options([
                                        'ativo' => '✅ Ativo (Em Dia)',
                                        'trial' => '⏳ Em Período de Testes',
                                        'inadimplente' => '⚠️ Inadimplente',
                                        'cancelado' => '❌ Cancelado / Suspenso',
                                    ])
                                    ->default('trial')
                                    ->required(),

                                Forms\Components\TextInput::make('limite_os_mes')
                                    ->label('Limite de OS por Mês')
                                    ->numeric()
                                    ->default(50)
                                    ->helperText('Use 999999 para ilimitado'),

                                Forms\Components\DatePicker::make('data_vencimento')
                                    ->label('Data de Vencimento (Próxima Cobrança)'),

                                Forms\Components\DatePicker::make('trial_termina_em')
                                    ->label('Fim do Período de Testes'),

                                Forms\Components\TextInput::make('gateway_customer_id')
                                    ->label('Asaas Customer ID')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Preenchido automaticamente pelo Asaas API'),

                                Forms\Components\TextInput::make('gateway_subscription_id')
                                    ->label('Asaas Subscription ID')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Preenchido automaticamente pelo Asaas API'),
                            ])->columns(2),
                    ])->columnSpanFull()
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

                Tables\Columns\TextColumn::make('status_pagamento')
                    ->label('Status Assinatura')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'ativo' => 'success',
                        'trial' => 'info',
                        'inadimplente' => 'warning',
                        'cancelado' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Acesso Liberado'),

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
