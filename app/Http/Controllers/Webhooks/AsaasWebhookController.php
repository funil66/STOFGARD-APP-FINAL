<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Fail-fast: Token deve existir no ambiente
        $expectedToken = config('payments.asaas_webhook_token');
        abort_unless(!empty($expectedToken), 503, 'Webhook authentication not configured');

        // Mitigação contra Forgery e Timing Attacks
        $token = $request->header('asaas-access-token', '');
        if (!hash_equals($expectedToken, $token)) {
            Log::warning('[SecOps] Tentativa de falsificação no Webhook Asaas', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            abort(401, 'Unauthorized');
        }

        $event = $request->input('event');
        $payment = $request->input('payment', []);

        match ($event) {
            'PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED' => $this->pagamentoRecebido($payment),
            'PAYMENT_OVERDUE' => $this->pagamentoAtrasado($payment),
            'PAYMENT_DELETED', 'PAYMENT_REFUNDED' => $this->pagamentoCancelado($payment),
            'SUBSCRIPTION_DELETED' => $this->assinaturaCancelada($request->input('subscription', [])),
            default => Log::info("[AsaasWebhook] Evento ignorado: {$event}"),
        };

        return response()->json(['status' => 'ok']);
    }

    private function pagamentoRecebido(array $payment): void
    {
        if ($tenant = $this->findTenantBySubscription($payment)) {
            $tenant->update([
                'status_pagamento' => 'ativo',
                'is_active' => true,
                'data_vencimento' => now()->addMonth()->format('Y-m-d'),
            ]);
            Log::info("[AsaasWebhook] Tenant {$tenant->id} ativado via webhook.");
        }
    }

    private function pagamentoAtrasado(array $payment): void
    {
        if ($tenant = $this->findTenantBySubscription($payment)) {
            $tenant->update(['status_pagamento' => 'inadimplente']);
        }
    }

    private function pagamentoCancelado(array $payment): void
    {
        if ($tenant = $this->findTenantBySubscription($payment)) {
            $tenant->update(['status_pagamento' => 'inadimplente']);
        }
    }

    private function assinaturaCancelada(array $subscription): void
    {
        if ($tenant = Tenant::where('gateway_subscription_id', $subscription['id'] ?? null)->first()) {
            $tenant->update([
                'status_pagamento' => 'cancelado',
                'is_active' => false,
                'gateway_subscription_id' => null,
            ]);
        }
    }

    private function findTenantBySubscription(array $payment): ?Tenant
    {
        if (!empty($payment['subscription'])) {
            return Tenant::where('gateway_subscription_id', $payment['subscription'])->first();
        }
        if (!empty($payment['customer'])) {
            return Tenant::where('gateway_customer_id', $payment['customer'])->first();
        }
        return null;
    }
}
