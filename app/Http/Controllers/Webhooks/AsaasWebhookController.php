<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AsaasWebhookController — Processa webhooks do Asaas para o Super Admin.
 * Gerencia eventos de pagamento de assinaturas dos Tenants.
 *
 * Rota: POST api/webhooks/asaas
 * Header obrigatório: asaas-access-token (validado contra config('services.asaas.webhook_token'))
 */
class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Valida o token de autenticação do webhook
        $token = $request->header('asaas-access-token');
        $expectedToken = config('services.asaas.webhook_token');

        if ($expectedToken && $token !== $expectedToken) {
            Log::warning('[AsaasWebhook] Token inválido recebido', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $event = $request->input('event');
        $payment = $request->input('payment', []);

        Log::info('[AsaasWebhook] Evento recebido', ['event' => $event, 'payment_id' => $payment['id'] ?? null]);

        match ($event) {
            'PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED' => $this->pagamentoRecebido($payment),
            'PAYMENT_OVERDUE' => $this->pagamentoAtrasado($payment),
            'PAYMENT_DELETED', 'PAYMENT_REFUNDED' => $this->pagamentoCancelado($payment),
            'SUBSCRIPTION_DELETED' => $this->assinaturaCancelada($request->input('subscription', [])),
            default => Log::info("[AsaasWebhook] Evento não tratado: {$event}"),
        };

        return response()->json(['status' => 'ok']);
    }

    /**
     * Pagamento da assinatura confirmado → ativa o tenant e atualiza vencimento.
     */
    private function pagamentoRecebido(array $payment): void
    {
        $tenant = $this->findTenantBySubscription($payment);

        if (!$tenant) {
            return;
        }

        $tenant->update([
            'status_pagamento' => 'ativo',
            'is_active' => true,
            'data_vencimento' => now()->addMonth()->format('Y-m-d'),
        ]);

        Log::info("[AsaasWebhook] Tenant ativado após pagamento", ['tenant_id' => $tenant->id]);
    }

    /**
     * Pagamento atrasado → marca como inadimplente (bloqueio ocorre via Job schedule).
     */
    private function pagamentoAtrasado(array $payment): void
    {
        $tenant = $this->findTenantBySubscription($payment);

        if (!$tenant) {
            return;
        }

        $tenant->update([
            'status_pagamento' => 'inadimplente',
        ]);

        Log::warning("[AsaasWebhook] Tenant marcado como inadimplente", ['tenant_id' => $tenant->id]);
    }

    /**
     * Pagamento cancelado/estornado → verifica se precisa suspender.
     */
    private function pagamentoCancelado(array $payment): void
    {
        $tenant = $this->findTenantBySubscription($payment);

        if (!$tenant) {
            return;
        }

        $tenant->update([
            'status_pagamento' => 'inadimplente',
        ]);

        Log::info("[AsaasWebhook] Pagamento cancelado para tenant", ['tenant_id' => $tenant->id]);
    }

    /**
     * Assinatura deletada pelo cliente → suspende o tenant.
     */
    private function assinaturaCancelada(array $subscription): void
    {
        $subscriptionId = $subscription['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $tenant = Tenant::where('gateway_subscription_id', $subscriptionId)->first();

        if (!$tenant) {
            return;
        }

        $tenant->update([
            'status_pagamento' => 'cancelado',
            'is_active' => false,
            'gateway_subscription_id' => null,
        ]);

        Log::info("[AsaasWebhook] Assinatura cancelada, tenant suspenso", ['tenant_id' => $tenant->id]);
    }

    /**
     * Encontra o tenant pelo ID do cliente ou da assinatura no Asaas.
     */
    private function findTenantBySubscription(array $payment): ?Tenant
    {
        // Busca pelo ID da assinatura associada ao pagamento
        $subscriptionId = $payment['subscription'] ?? null;

        if ($subscriptionId) {
            $tenant = Tenant::where('gateway_subscription_id', $subscriptionId)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // Fallback: busca pelo ID do cliente no Asaas
        $customerId = $payment['customer'] ?? null;

        if ($customerId) {
            return Tenant::where('gateway_customer_id', $customerId)->first();
        }

        Log::warning('[AsaasWebhook] Tenant não encontrado para payment', ['payment' => $payment]);

        return null;
    }
}
