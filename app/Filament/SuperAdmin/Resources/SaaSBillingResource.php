<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\SaaSBillingResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class SaaSBillingResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Faturamento (SaaS)';
    protected static ?string $modelLabel = 'Inquilino';
    protected static ?string $pluralModelLabel = 'Inquilinos';
    protected static ?string $navigationGroup = 'Gestão SaaS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome do Inquilino')
                    ->disabled(),
                Forms\Components\Select::make('plan')
                    ->label('Plano Atual')
                    ->options([
                        'start' => 'Start',
                        'pro' => 'Pro',
                        'elite' => 'Elite',
                    ]),
                Forms\Components\Select::make('status_pagamento')
                    ->label('Status Pagamento')
                    ->options([
                        'trial' => 'Trial',
                        'ativo' => 'Ativo',
                        'inadimplente' => 'Inadimplente',
                        'cancelado' => 'Cancelado',
                    ]),
                Forms\Components\DatePicker::make('data_vencimento')
                    ->label('Vencimento ou Final do Ciclo'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan')
                    ->badge()
                    ->label('Plano')
                    ->colors([
                        'primary' => 'start',
                        'success' => 'pro',
                        'warning' => 'elite',
                    ]),
                Tables\Columns\TextColumn::make('status_pagamento')
                    ->badge()
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['ativo', 'trial']),
                        'danger' => fn ($state) => in_array($state, ['inadimplente', 'cancelado', 'bloqueado']),
                    ]),
                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_pagamento')
                    ->options([
                        'trial' => 'Trial',
                        'ativo' => 'Ativo',
                        'inadimplente' => 'Inadimplente',
                        'bloqueado' => 'Bloqueado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('desbloquear')
                    ->label('Desbloquear')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record): bool => $record->status_pagamento === 'bloqueado' || $record->status_pagamento === 'inadimplente')
                    ->action(function (Tenant $record) {
                        $record->update(['status_pagamento' => 'ativo', 'is_active' => true]);
                    }),
                Action::make('bloquear')
                    ->label('Bloquear')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record): bool => in_array($record->status_pagamento, ['ativo', 'trial']))
                    ->action(function (Tenant $record) {
                        $record->update(['status_pagamento' => 'bloqueado', 'is_active' => false]);
                    }),
                Action::make('sincronizar')
                    ->label('Sync Asaas')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (Tenant $record) {
                        // Dummy action por enquanto, depois rodar a classe real de Sincronia.
                        \Filament\Notifications\Notification::make()
                            ->title('Sincronia iniciada')
                            ->body('O status do cliente foi atualizado com o Asaas.')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ManageSaaSBillings::route('/'),
        ];
    }
}
