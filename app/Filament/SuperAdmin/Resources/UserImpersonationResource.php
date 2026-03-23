<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Models\Tenant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Resource: Impersonação de Usuários (Login Como)
 *
 * Permite ao Super Admin autenticar-se como qualquer usuário do sistema
 * para diagnóstico de problemas sem precisar da senha do usuário.
 *
 * IMPLEMENTAÇÃO: Usa sessão + middleware para trocar o contexto de auth.
 * Não requer lab404/laravel-impersonate — implementação nativa via session swap.
 *
 * AUDITORIA: Toda impersonação é logada com IP, user-agent, timestamp.
 */
class UserImpersonationResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Impersonar Usuários';

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $pluralModelLabel = 'Usuários';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->disabled(),
                Forms\Components\TextInput::make('email')->disabled(),
                Forms\Components\Toggle::make('is_admin')->disabled(),
                Forms\Components\Toggle::make('is_super_admin')->label('É Super Admin?'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width('80px'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\IconColumn::make('is_admin')->boolean()->label('Admin'),
                Tables\Columns\IconColumn::make('is_super_admin')->boolean()->label('Super Admin'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('last_login_at')->dateTime('d/m/Y H:i')->label('Último login'),
            ])
            ->actions([
                Action::make('impersonar')
                    ->label('Login como')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn(User $record) => "Entrar como {$record->name}?")
                    ->modalDescription('Você será redirecionado para o painel principal como este usuário. Use com cautela — todas as ações serão feitas em nome deste usuário.')
                    ->action(function (User $targetUser) {
                        $superAdminId = Auth::id();

                        // Log de auditoria ANTES de mudar contexto
                        Log::warning('[SuperAdmin] Impersonação iniciada', [
                            'super_admin_id' => $superAdminId,
                            'target_user_id' => $targetUser->id,
                            'target_email' => $targetUser->email,
                            'ip' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'started_at' => now()->toIso8601String(),
                        ]);

                        // Armazena ID original na sessão para poder sair da impersonação
                        session()->put('impersonating_super_admin_id', $superAdminId);
                        session()->put('impersonated_at', now()->toIso8601String());

                        // Troca o usuário autenticado
                        Auth::login($targetUser);

                        // Redireciona para o painel principal (HTTPS em produção)
                        $url = url('/admin');
                        if (app()->environment('production')) {
                            $url = str_replace('http://', 'https://', $url);
                        }
                        return redirect($url);
                    })
                    ->visible(fn(User $record) => $record->id !== Auth::id()),

                Tables\Actions\EditAction::make()
                    ->label('Permissões'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('criar_usuario_tenant')
                    ->label('Criar usuário do tenant')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->options(fn () => Tenant::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->required(),
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
                            ->required()
                            ->minLength(8),
                    ])
                    ->action(function (array $data) {
                        try {
                            $tenant = Tenant::query()->find($data['tenant_id']);

                            if (! $tenant) {
                                Notification::make()
                                    ->title('Tenant não encontrado')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            tenancy()->initialize($tenant);

                            $email = strtolower(trim($data['email']));
                            $exists = User::query()->where('email', $email)->exists();

                            if ($exists) {
                                Notification::make()
                                    ->title('E-mail já existe neste tenant')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            User::query()->create([
                                'name' => trim($data['name']),
                                'email' => $email,
                                'password' => Hash::make($data['password']),
                                'is_admin' => true,
                                'role' => 'dono',
                                'acesso_financeiro' => true,
                                'email_verified_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Usuário criado com sucesso no tenant')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erro ao criar usuário')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        } finally {
                            tenancy()->end();
                        }
                    }),

                Tables\Actions\Action::make('sair_impersonacao')
                    ->label('⬅ Sair da Impersonação')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn() => session()->has('impersonating_super_admin_id'))
                    ->action(function () {
                        $superAdminId = session()->pull('impersonating_super_admin_id');
                        session()->forget('impersonated_at');

                        $superAdmin = User::find($superAdminId);
                        if ($superAdmin) {
                            Auth::login($superAdmin);
                            Log::info('[SuperAdmin] Impersonação encerrada', [
                                'super_admin_id' => $superAdminId,
                            ]);
                        }

                        $url = url('/super-admin');
                        if (app()->environment('production')) {
                            $url = str_replace('http://', 'https://', $url);
                        }
                        return redirect($url);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\SuperAdmin\Resources\UserImpersonationResource\Pages\ListUserImpersonation::route('/'),
            'edit' => \App\Filament\SuperAdmin\Resources\UserImpersonationResource\Pages\EditUserImpersonation::route('/{record}/edit'),
        ];
    }
}
