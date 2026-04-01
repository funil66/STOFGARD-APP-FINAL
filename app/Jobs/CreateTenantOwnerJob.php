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
        // 1. Obtém os dados do proprietário (atributo virtual mapeado para 'data')
        $ownerData = $this->tenant->getAttribute('pending_owner');

        if (!$ownerData) {
            $ownerData = $this->tenant->data['pending_owner'] ?? null;
        }

        if (empty($ownerData['email']) || empty($ownerData['name']) || empty($ownerData['password'])) {
            Log::warning('[CreateTenantOwnerJob] Dados de proprietário não encontrados.', [
                'tenant_id' => $this->tenant->getTenantKey(),
            ]);
            return;
        }

        $centralConn = config('tenancy.central_connection', 'pgsql');

        Log::info('[CreateTenantOwnerJob] Iniciando criação...', [
            'email' => $ownerData['email'],
            'central_db' => $centralConn
        ]);

        // 2. Cria o usuário no BANCO CENTRAL (Usando conexão central explicitamente)
        $existsCentral = User::on($centralConn)->where('email', $ownerData['email'])->exists();
        
        if (!$existsCentral) {
            User::on($centralConn)->create([
                'name'               => $ownerData['name'],
                'email'              => $ownerData['email'],
                'password'           => Hash::make($ownerData['password']),
                'tenant_id'          => $this->tenant->getTenantKey(),
                'is_admin'           => true,
                'is_super_admin'     => false,
                'role'               => 'dono',
                'acesso_financeiro'  => true,
                'email_verified_at'  => now(),
            ]);
            Log::info('[CreateTenantOwnerJob] Sucesso: Usuário criado na CENTRAL.');
        }

        // 3. Cria o usuário no BANCO DO INQUILINO
        // O método $tenant->run() garante a mudança de conexão para o tenant
        $this->tenant->run(function () use ($ownerData) {
            $existsTenant = User::query()->where('email', $ownerData['email'])->exists();

            if (!$existsTenant) {
                User::query()->create([
                    'name'               => $ownerData['name'],
                    'email'              => $ownerData['email'],
                    'password'           => Hash::make($ownerData['password']),
                    'is_admin'           => true,
                    'is_super_admin'     => false,
                    'role'               => 'dono',
                    'acesso_financeiro'  => true,
                    'email_verified_at'  => now(),
                ]);
                Log::info('[CreateTenantOwnerJob] Sucesso: Usuário criado no INQUILINO.');
            }
        });
    }
}
