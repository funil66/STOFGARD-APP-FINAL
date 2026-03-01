<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SuspenderTenantInadimplenteJob — Bloqueia tenants inadimplentes após carência.
 *
 * Deve ser agendado diariamente (Schedule::job) às 08:00.
 * Tolerância: 5 dias após vencimento antes de suspender acesso.
 */
class SuspenderTenantInadimplenteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Dias de carência após o vencimento antes de suspender o tenant.
     */
    private const DIAS_CARENCIA = 5;

    public function handle(): void
    {
        Log::info('[SuspenderTenantJob] Iniciando varredura de tenants inadimplentes');

        // 1. Tenants em trial expirado → muda para inadimplente
        $trialExpirados = Tenant::where('status_pagamento', 'trial')
            ->whereNotNull('trial_termina_em')
            ->where('trial_termina_em', '<', now()->startOfDay())
            ->get();

        foreach ($trialExpirados as $tenant) {
            $tenant->update(['status_pagamento' => 'inadimplente']);
            Log::warning('[SuspenderTenantJob] Trial expirado → inadimplente', ['tenant_id' => $tenant->id]);
        }

        // 2. Tenants inadimplentes além da carência → suspende acesso
        $dataCorte = now()->subDays(self::DIAS_CARENCIA)->startOfDay();

        $inadimplentes = Tenant::where('status_pagamento', 'inadimplente')
            ->where(function ($query) use ($dataCorte) {
                $query->where('data_vencimento', '<', $dataCorte)
                    ->orWhereNull('data_vencimento'); // sem vencimento = suspende também
            })
            ->where('is_active', true) // só suspende se ainda estava ativo
            ->get();

        foreach ($inadimplentes as $tenant) {
            $tenant->update([
                'is_active' => false,
                'status_pagamento' => 'suspenso',
            ]);

            Log::warning('[SuspenderTenantJob] Tenant suspenso por inadimplência', [
                'tenant_id' => $tenant->id,
                'data_vencimento' => $tenant->data_vencimento,
            ]);
        }

        $totalProcessados = $trialExpirados->count() + $inadimplentes->count();

        Log::info('[SuspenderTenantJob] Varredura concluída', [
            'trials_expirados' => $trialExpirados->count(),
            'tenants_suspensos' => $inadimplentes->count(),
            'total' => $totalProcessados,
        ]);
    }
}
