<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\Tenant;

class CreateTenantOwnerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(public readonly Tenant $tenant) {}

    public function handle(): void
    {
        $slug = trim(strtolower($this->tenant->slug ?? ''));
        
        // Use tenancy()->central to bypass tenant cache isolation!
        $ownerData = tenancy()->central(function () use ($slug) {
            return \Illuminate\Support\Facades\Cache::get('pending_owner_' . $slug);
        });

        if (!$ownerData) {
            $ownerData = $this->tenant->getAttribute('pending_owner');
        }

        if (!$ownerData) {
            $ownerData = $this->tenant->data['pending_owner'] ?? null;
        }
        
        $centralConn = config('tenancy.central_connection', config('database.default'));

        // Fallback: copy from central user se as credenciais do cache expiraram ou falharam
        if (empty($ownerData['password']) && empty($ownerData['password_already_hashed'])) {
            $centralUserFallback = \App\Models\User::on($centralConn)
                ->where('tenant_id', $this->tenant->getTenantKey())
                ->where('is_admin', true)
                ->orderBy('id', 'asc')
                ->first();
                
            if ($centralUserFallback) {
                $ownerData['name'] = $ownerData['name'] ?? $centralUserFallback->name;
                $ownerData['email'] = $ownerData['email'] ?? $centralUserFallback->email;
                $ownerData['password_already_hashed'] = $centralUserFallback->password;
            }
        }

        if (empty($ownerData['email']) || empty($ownerData['name']) || (empty($ownerData['password']) && empty($ownerData['password_already_hashed']))) {
            Log::warning('[CreateTenantOwnerJob] Dados de proprietário não encontrados.', [
                'tenant_id' => $this->tenant->getTenantKey(),
            ]);
            return;
        }

        Log::info('[CreateTenantOwnerJob] Iniciando criação...', [
            'email' => $ownerData['email'],
            'central_db' => $centralConn
        ]);

        $centralUser = User::on($centralConn)->where('email', $ownerData['email'])->first();
        
        if (!$centralUser) {
            $pass = !empty($ownerData['password_already_hashed']) ? $ownerData['password_already_hashed'] : Hash::make($ownerData['password']);
            User::on($centralConn)->create([
                'name'               => $ownerData['name'],
                'email'              => $ownerData['email'],
                'password'           => $pass,
                'tenant_id'          => $this->tenant->getTenantKey(),
                'is_admin'           => true,
                'is_super_admin'     => false,
                'email_verified_at'  => now(),
            ]);
            Log::info('[CreateTenantOwnerJob] Sucesso: Usuário criado na CENTRAL.');
        } else {
            if (empty($centralUser->tenant_id)) {
                $centralUser->update(['tenant_id' => $this->tenant->getTenantKey()]);
                Log::info('[CreateTenantOwnerJob] Sucesso: Usuário vinculado na CENTRAL.', ['tenant_id' => $this->tenant->getTenantKey()]);
            }
        }

        $this->tenant->run(function () use ($ownerData) {
            $existsTenant = User::query()->where('email', $ownerData['email'])->exists();
            if (!$existsTenant) {
                $pass = !empty($ownerData['password_already_hashed']) ? $ownerData['password_already_hashed'] : Hash::make($ownerData['password']);
                User::query()->create([
                    'name'               => $ownerData['name'],
                    'email'              => $ownerData['email'],
                    'password'           => $pass,
                    'is_admin'           => true,
                    'email_verified_at'  => now(),
                ]);
                Log::info('[CreateTenantOwnerJob] Sucesso: Usuário criado no INQUILINO.');
            }
        });
        
        // Limpa senha sensível
        $this->tenant->pending_owner = null;
        $this->tenant->save();
        
        tenancy()->central(function () use ($slug) {
            \Illuminate\Support\Facades\Cache::forget('pending_owner_' . $slug);
        });
    }
}
