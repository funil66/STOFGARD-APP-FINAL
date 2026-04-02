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
        // Salva dados do owner no campo 'data' do tenant (JSON)
        // O CreateTenantOwnerJob vai lê-los APÓS o banco estar pronto
        if (!empty($data['owner_name']) && !empty($data['owner_email']) && !empty($data['owner_password'])) {
            $data['pending_owner'] = [
                'name'     => trim($data['owner_name']),
                'email'    => strtolower(trim($data['owner_email'])),
                'password' => $data['owner_password'],
            ];
        }

        unset($data['owner_name'], $data['owner_email'], $data['owner_password']);

        // Defaults for new tenants
        $data['is_active']           = true;
        $data['status_pagamento']    = 'trial';
        $data['trial_termina_em']    = now()->addDays((int) env('TRIAL_DAYS', 14));
        $data['os_criadas_mes_atual'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $tenant = $this->record;

        // Cria o registro de domínio baseado no slug
        if ($tenant->slug) {
            $slug       = trim(strtolower($tenant->slug));
            $baseDomain = env('TENANT_BASE_DOMAIN', env('APP_DOMAIN', parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost'));

            $domain = str_contains($slug, '.')
                ? $slug
                : "{$slug}.{$baseDomain}";

            $tenant->domains()->firstOrCreate(['domain' => $domain]);
        }

        // Criar Usuário do Tenant (Context-Switching)
        $ownerData = $tenant->getAttribute('pending_owner') ?? $tenant->data['pending_owner'] ?? null;
        
        if ($ownerData) {
            // Cria no banco central
            $centralConn = config('tenancy.central_connection', 'pgsql');
            if (!User::on($centralConn)->where('email', $ownerData['email'])->exists()) {
                User::on($centralConn)->create([
                    'name'               => $ownerData['name'],
                    'email'              => $ownerData['email'],
                    'password'           => Hash::make($ownerData['password']),
                    'tenant_id'          => $tenant->getTenantKey(),
                    'is_admin'           => true,
                    'is_super_admin'     => false,
                    'role'               => 'dono',
                    'acesso_financeiro'  => true,
                    'email_verified_at'  => now(),
                ]);
            }

            // O SEGREDO: Roda DENTRO do contexto do tenant
            $tenant->run(function () use ($ownerData) {
                if (!User::where('email', $ownerData['email'])->exists()) {
                    User::create([
                        'name'               => $ownerData['name'],
                        'email'              => $ownerData['email'],
                        'password'           => \Illuminate\Support\Facades\Hash::make($ownerData['password']),
                        'is_admin'           => true,
                        'is_super_admin'     => false,
                        'role'               => 'dono',
                        'acesso_financeiro'  => true,
                        'email_verified_at'  => now(),
                    ]);
                }
            });
        }
    }
}
