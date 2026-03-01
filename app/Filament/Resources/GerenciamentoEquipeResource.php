<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

/**
 * GerenciamentoEquipeResource — Gestão de usuários do tenant.
 * Fase 3: Multi-usuário com roles (dono, funcionario, secretaria).
 *
 * Dono: acesso total
 * Secretaria: acesso a agenda, clientes, OS — sem módulo financeiro
 * Funcionario: acesso só à execução das suas OSes
 */
class GerenciamentoEquipeResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Minha Equipe';

    protected static ?string $modelLabel = 'Membro';

    protected static ?string $pluralModelLabel = 'Equipe';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 80;

    /**
     * Limita aos usuários do próprio tenant (não mostra o Super Admin).
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('is_admin', false)
            ->orderBy('role');
    }

    /**
     * Limita quem pode criar/editar: apenas o 'dono' pode gerenciar equipe.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'dono' || auth()->user()?->is_admin;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('👤 Dados do Membro')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail (login)')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context) => $context === 'create')
                            ->helperText('Mín. 8 caracteres. Deixe em branco para manter a senha atual.'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('🔐 Permissões')
                    ->description('Defina o nível de acesso deste membro dentro do seu painel.')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Papel na Equipe')
                            ->options([
                                'dono' => '👑 Dono — Acesso Total',
                                'secretaria' => '📋 Secretaria — Agenda, Clientes, OS (sem financeiro)',
                                'funcionario' => '🔧 Funcionário — Apenas execução de OS',
                            ])
                            ->required()
                            ->default('funcionario')
                            ->live()
                            ->helperText('O papel define quais módulos este usuário pode acessar.'),

                        Forms\Components\Toggle::make('acesso_financeiro')
                            ->label('Acesso ao Módulo Financeiro')
                            ->helperText('Se desativado, o usuário não verá valores, recebimentos ou relatórios financeiros.')
                            ->default(false)
                            ->visible(fn(Forms\Get $get) => $get('role') !== 'dono'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn(User $record) => $record->email),

                Tables\Columns\BadgeColumn::make('role')
                    ->label('Papel')
                    ->colors([
                        'warning' => 'dono',
                        'primary' => 'secretaria',
                        'gray' => 'funcionario',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'dono' => '👑 Dono',
                        'secretaria' => '📋 Secretaria',
                        'funcionario' => '🔧 Funcionário',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('acesso_financeiro')
                    ->label('Financeiro')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Entrou em')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Papel')
                    ->options([
                        'dono' => 'Dono',
                        'secretaria' => 'Secretaria',
                        'funcionario' => 'Funcionário',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // Redefinir senha rapidamente
                Tables\Actions\Action::make('resetar_senha')
                    ->label('Resetar Senha')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn() => auth()->user()?->role === 'dono')
                    ->form([
                        Forms\Components\TextInput::make('nova_senha')
                            ->label('Nova Senha')
                            ->password()
                            ->required()
                            ->minLength(8),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['password' => Hash::make($data['nova_senha'])]);
                        Notification::make()->title('Senha redefinida com sucesso!')->success()->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn(User $record) => $record->role !== 'dono'),
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
            'index' => \App\Filament\Resources\GerenciamentoEquipeResource\Pages\ListGerenciamentoEquipe::route('/'),
            'create' => \App\Filament\Resources\GerenciamentoEquipeResource\Pages\CreateGerenciamentoEquipe::route('/create'),
            'edit' => \App\Filament\Resources\GerenciamentoEquipeResource\Pages\EditGerenciamentoEquipe::route('/{record}/edit'),
        ];
    }
}
