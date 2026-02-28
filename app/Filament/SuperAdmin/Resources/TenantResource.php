<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Resource: Gerenciamento de Tenants/Clientes do SaaS.
 *
 * FASE 1.2 (Multi-Tenancy) ainda não foi implementada.
 * Atualmente representa "usuários com seus dados operacionais".
 * Quando multi-tenancy for implementado (stancl/tenancy), este resource
 * será atualizado para gerenciar o model Tenant diretamente.
 *
 * ESCOPO ATUAL:
 * - Ver todos os usuários (tenants) e seus dados
 * - Ativar/desativar tenants
 * - Ver contagens de dados (orçamentos, OS, clientes por usuário)
 */
class TenantResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tenants / Empresas';

    protected static ?string $modelLabel = 'Tenant';

    protected static ?string $pluralModelLabel = 'Tenants';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Tenant')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome da Empresa')
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required(),

                        Forms\Components\Toggle::make('is_admin')
                            ->label('Admin (acesso ao painel)')
                            ->helperText('Se desativado, o usuário não consegue acessar o sistema.'),

                        Forms\Components\Toggle::make('is_super_admin')
                            ->label('Super Admin')
                            ->helperText('Só ative para membros da equipe SaaS.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Empresa / Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn(User $record) => $record->email),

                Tables\Columns\IconColumn::make('is_admin')
                    ->boolean()
                    ->label('Ativo'),

                Tables\Columns\IconColumn::make('is_super_admin')
                    ->boolean()
                    ->label('Super Admin'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                // Contadores — carregados de forma lazy para não impactar performance
                Tables\Columns\TextColumn::make('orcamentos_count')
                    ->label('Orçamentos')
                    ->counts('orcamentos')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('Status')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos'),

                Tables\Filters\TernaryFilter::make('is_super_admin')
                    ->label('Tipo')
                    ->trueLabel('Super Admins')
                    ->falseLabel('Tenants comuns'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),

                Tables\Actions\Action::make('desativar')
                    ->label('Desativar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(User $record) => $record->is_admin)
                    ->action(fn(User $record) => $record->update(['is_admin' => false])),

                Tables\Actions\Action::make('ativar')
                    ->label('Ativar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(User $record) => !$record->is_admin)
                    ->action(fn(User $record) => $record->update(['is_admin' => true])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);

        return $table;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\SuperAdmin\Resources\TenantResource\Pages\ListTenants::route('/'),
            'edit' => \App\Filament\SuperAdmin\Resources\TenantResource\Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    /**
     * Relacionamento para contagem de orçamentos por usuário.
     * Necessário para a coluna orcamentos_count com counts().
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withCount('orcamentos');
    }
}
