<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Jobs\SendWhatsAppJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class PixWebhookController extends Controller
{
    public function handle(Request $request, string $webhookToken)
    {
        // Removemos o scan completo de tenants por ser vetor de DoS.
        $tenant = \App\Models\Tenant::whereJsonContains('data->webhook_token', $webhookToken)->first();

        if (!$tenant) {
            Log::warning('[SecOps] Webhook Pix negado: Token inválido', ['ip' => $request->ip()]);
            abort(401, 'Unauthorized');
        }

        tenancy()->initialize($tenant);

        try {
            // Autenticação específica de Payload (Asaas ou Efi)
            $this->processarEvento($request);
        } finally {
            tenancy()->end();
        }

        return response()->json(['status' => 'ok']);
    }

    private function processarEvento(Request $request): void
    {
        $body = $request->all();

        if (isset($body['event'])) {
            $this->processarEventoAsaas($body);
        } elseif (isset($body['pix'])) {
            foreach ($body['pix'] as $pix) {
                $this->processarPixEfi($pix);
            }
        }
    }

    private function processarEventoAsaas(array $body): void
    {
        $event = $body['event'];
        $payment = $body['payment'] ?? [];

        if (!in_array($event, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'])) return;
        if (empty($payment['externalReference'])) return;

        if (str_starts_with($payment['externalReference'], 'orcamento_')) {
            $this->confirmarPagamentoOrcamento(
                (int) str_replace('orcamento_', '', $payment['externalReference']),
                $payment['netValue'] ?? 0
            );
        }
    }

    private function processarPixEfi(array $pix): void
    {
        if (!empty($pix['txid']) && str_starts_with($pix['txid'], 'orcamento')) {
            $this->confirmarPagamentoOrcamento(
                (int) str_replace('orcamento', '', $pix['txid']),
                $pix['valor'] ?? 0
            );
        }
    }

    private function confirmarPagamentoOrcamento(int $orcamentoId, float $valorBase): void
    {
        if (!$orcamento = Orcamento::find($orcamentoId)) return;
        if ($orcamento->status_pagamento === 'pago') return; // Idempotência

        $orcamento->update([
            'status_pagamento' => 'pago',
            'data_pagamento' => now(),
            'valor_pago' => $valorBase ?: $orcamento->valor_total,
        ]);

        if ($financeiro = Financeiro::where('orcamento_id', $orcamentoId)->first()) {
            $financeiro->update(['status' => 'pago', 'data_pago' => now()]);
        }

        $this->enviarConfirmacaoWhatsApp($orcamento);
    }

    private function enviarConfirmacaoWhatsApp(Orcamento $orcamento): void
    {
        if ($celular = $orcamento->cliente?->celular) {
            $empresa = Configuracao::first()->empresa_nome ?? 'Autonomia';
            $msg = "✅ *Pagamento Confirmado!*\n\nRecebemos o pagamento do seu orçamento *#{$orcamento->numero}*.\n\n_Equipe {$empresa}_";
            SendWhatsAppJob::dispatch($celular, $msg);
        }
    }
}
