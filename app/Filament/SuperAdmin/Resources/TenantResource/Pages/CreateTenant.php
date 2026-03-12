<?php

namespace App\Filament\SuperAdmin\Resources\TenantResource\Pages;

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

        // Create domain from slug
        if ($tenant->slug) {
            $baseDomain = env('APP_DOMAIN', 'localhost');
            $tenant->domains()->firstOrCreate([
                'domain' => $tenant->slug . '.' . $baseDomain,
            ]);
        }
    }
}
