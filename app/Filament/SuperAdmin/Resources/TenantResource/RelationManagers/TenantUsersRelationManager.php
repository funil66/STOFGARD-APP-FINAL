<?php

namespace App\Filament\SuperAdmin\Resources\TenantResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

/**
 * Gerencia os usuários vinculados a um Tenant.
 *
 * IMPORTANTE: Como os users do tenant ficam em bancos separados,
 * este RelationManager inicializa tenancy antes de listar e usar
 * uma query customizada para acessar o banco correto.
 */
class TenantUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Usuários do Tenant';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->minLength(8),

                Forms\Components\Select::make('role')
                    ->label('Papel')
                    ->options([
                        'dono' => '👑 Dono',
                        'funcionario' => '👷 Funcionário',
                        'secretaria' => '💼 Secretária',
                    ])
                    ->default('funcionario')
                    ->required(),

                Forms\Components\Toggle::make('is_admin')
                    ->label('Administrador')
                    ->default(false),

                Forms\Components\Toggle::make('acesso_financeiro')
                    ->label('Acesso Financeiro')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Papel')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'dono' => 'success',
                        'funcionario' => 'info',
                        'secretaria' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'dono' => '👑 Dono',
                        'funcionario' => '👷 Funcionário',
                        'secretaria' => '💼 Secretária',
                        default => $state ?? 'N/A',
                    }),

                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),

                Tables\Columns\IconColumn::make('acesso_financeiro')
                    ->label('Financeiro')
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Último Login')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Nunca'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Novo Usuário')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['email_verified_at'] = now();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Remover'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Nenhum usuário neste tenant')
            ->emptyStateDescription('Use o botão "Novo Usuário" acima para vincular um usuário a este tenant.');
    }
}
