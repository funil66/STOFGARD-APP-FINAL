<?php

namespace App\Filament\SuperAdmin\Resources\UserImpersonationResource\Pages;

use App\Models\Tenant;
use App\Models\User;
use App\Filament\SuperAdmin\Resources\UserImpersonationResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Filament\Resources\Pages\ListRecords;

class ListUserImpersonation extends ListRecords
{
    protected static string $resource = UserImpersonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('criar_usuario_tenant')
                ->label('Criar usuário do tenant')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Select::make('tenant_id')
                        ->label('Tenant')
                        ->options(fn () => Tenant::query()->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('password')
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
        ];
    }
}
