<?php

namespace App\Jobs;

use App\Models\OrdemServico;
use App\Models\Tenant;
use App\Models\Cupom;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReferralMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    protected $ordemServicoId;
    protected $tenantId;

    public function __construct(int $ordemServicoId, string $tenantId)
    {
        $this->ordemServicoId = $ordemServicoId;
        $this->tenantId = $tenantId;
    }

    public function handle(): void
    {
        // 1. Inicializar Tenant manualmente se necessário
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            Log::error("SendReferralMessageJob: Tenant não encontrado ({$this->tenantId})");
            return;
        }

        tenancy()->initialize($tenant);

        // 2. Verificar Plano Elite
        if (!$tenant->isElite()) {
            return;
        }

        // 3. Carregar OS e Cliente
        $os = OrdemServico::with('cliente')->find($this->ordemServicoId);
        if (!$os || !$os->cliente || !$os->cliente->telefone) {
            return;
        }

        // 4. Gerar Cupom
        $cupom = Cupom::create([
            'cliente_indicador_id' => $os->cliente_id,
            'desconto_percentual' => 10.00,
            'data_expiracao' => now()->addMonths(3),
        ]);

        // 5. Montar Mensagem e Disparar
        // O ideal é configurar numa tabela de Settings, mas como MVP, deixamos dinâmico.
        $mensagem = "Olá {$os->cliente->nome_fantasia}!\n\n";
        $mensagem .= "Notamos que o seu serviço (OS #{$os->id}) foi concluído com sucesso. Esperamos que tenha gostado!\n\n";
        $mensagem .= "Você sabia que pode ganhar 10% de desconto no próximo serviço? Basta indicar um amigo usando seu cupom exclusivo:\n\n";
        $mensagem .= "🎟️ *{$cupom->codigo}*\n\n";
        $mensagem .= "Quando seu amigo fechar o serviço conosco e informar este código, o desconto será creditado para você!\n";
        $mensagem .= "Agradecemos a confiança. 🤝";

        // Usar a instância padrão ou ler de config
        $instancia = $tenant->whatsapp_instance ?? 'default';

        SendWhatsAppJob::dispatch($os->cliente->telefone, $mensagem, $instancia);
    }
}
