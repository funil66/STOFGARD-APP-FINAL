<?php

namespace App\Filament\SuperAdmin\Resources\TenantResource\Pages;

use App\Models\User;
use App\Services\TenantTemplateProvisioner;
use App\Filament\SuperAdmin\Resources\TenantResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected array $ownerData = [];

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->ownerData = [
            'name' => trim((string) ($data['owner_name'] ?? '')),
            'email' => strtolower(trim((string) ($data['owner_email'] ?? ''))),
            'password' => (string) ($data['owner_password'] ?? ''),
        ];

        unset($data['owner_name'], $data['owner_email'], $data['owner_password']);

        // Defaults for new tenants
        $data['is_active'] = true;
        $data['status_pagamento'] = 'trial';
        $data['trial_termina_em'] = now()->addDays((int) env('TRIAL_DAYS', 14));
        $data['os_criadas_mes_atual'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $tenant = $this->record;

        // Create domain from slug (supports custom full domain/subdomain)
        if ($tenant->slug) {
            $slug = trim(strtolower($tenant->slug));
            $baseDomain = env('TENANT_BASE_DOMAIN', env('APP_DOMAIN', parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost'));

            $domain = str_contains($slug, '.')
                ? $slug
                : "{$slug}.{$baseDomain}";

            $tenant->domains()->firstOrCreate([
                'domain' => $domain,
            ]);
        }

        // Garante baseline visual/funcional idêntico ao tenant referência (STOFGARD)
        app(TenantTemplateProvisioner::class)->apply($tenant);

        if (
            !empty($this->ownerData['name'])
            && !empty($this->ownerData['email'])
            && !empty($this->ownerData['password'])
        ) {
            $initialized = false;

            try {
                tenancy()->initialize($tenant);
                $initialized = true;

                $exists = User::query()->where('email', $this->ownerData['email'])->exists();

                if (! $exists) {
                    User::query()->create([
                        'name' => $this->ownerData['name'],
                        'email' => $this->ownerData['email'],
                        'password' => Hash::make($this->ownerData['password']),
                        'is_admin' => true,
                        'role' => 'dono',
                        'acesso_financeiro' => true,
                        'email_verified_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('Tenant criado, mas usuário inicial não foi criado')
                    ->body($e->getMessage())
                    ->warning()
                    ->persistent()
                    ->send();
            } finally {
                if ($initialized) {
                    tenancy()->end();
                }
            }
        }
    }
}
