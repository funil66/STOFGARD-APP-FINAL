<?php

namespace App\Filament\SuperAdmin\Resources\TenantResource\Pages;

use App\Services\TenantTemplateProvisioner;
use App\Filament\SuperAdmin\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
    }
}
