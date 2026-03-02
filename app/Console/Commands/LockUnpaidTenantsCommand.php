<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Jobs\SendWhatsAppJob;
use Illuminate\Support\Facades\Log;

class LockUnpaidTenantsCommand extends Command
{
    protected $signature = 'iron:lock-caloteiros';
    protected $description = 'Bloqueia clientes (Tenants) que estão com a mensalidade do SaaS atrasada há mais de 3 dias';

    public function handle()
    {
        $this->info("🔭 Iron Code: Ligando a visão térmica para achar caloteiros...");

        // Busca a galera que tá ativa mas o vencimento já passou há mais de 3 dias (Tolerância)
        $caloteiros = Tenant::where('is_active', true)
            ->whereNotNull('data_vencimento')
            ->whereDate('data_vencimento', '<', now()->subDays(3))
            ->get();

        if ($caloteiros->isEmpty()) {
            $this->info("✅ Rádio limpo. Todo mundo pagou o aluguel.");
            return;
        }

        foreach ($caloteiros as $tenant) {
            // Desativa a chave de acesso do cara
            $tenant->update(['is_active' => false]);

            Log::warning("💣 Killswitch Acionado: Tenant {$tenant->id} bloqueado por falta de pagamento.");
            $this->error("🎯 Headshot: O inquilino {$tenant->id} foi pro saco.");

            // Se você tiver o número do admin do tenant, dispara o aviso do luto
            // $mensagem = "Fala chefe! O acesso ao sistema AUTONOMIA ILIMITADA foi bloqueado por falta de pagamento. Regularize o PIX para religarmos as máquinas.";
            // SendWhatsAppJob::dispatch($tenant->telefone_admin, $mensagem);
        }

        $this->info("\n🔥 Operação de limpeza concluída.");
    }
}
