<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\ContratoRecorrente;
use App\Models\Financeiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\EnviarReciboWhatsAppJob;

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
        // 1. Processamento Autônomo: Contrato de Cliente Final (Tenant -> Cliente)
        if (!empty($payment['subscription'])) {
            $contrato = ContratoRecorrente::where('gateway_subscription_id', $payment['subscription'])->first();
            
            if ($contrato) {
                // Gera o registro no Financeiro automaticamente como PAGO
                $financeiro = Financeiro::create([
                    'cliente_id' => $contrato->cliente_id,
                    'tipo' => 'receita',
                    'categoria_id' => null, // Defina um default se necessário
                    'descricao' => 'Faturamento Automático - Assinatura ' . $contrato->id,
                    'valor' => $payment['value'],
                    'data_vencimento' => date('Y-m-d', strtotime($payment['dueDate'])),
                    'data_pagamento' => date('Y-m-d', strtotime($payment['paymentDate'] ?? now())),
                    'status' => 'pago',
                    'forma_pagamento' => $payment['billingType'] ?? 'PIX',
                ]);

                Log::info("[AsaasWebhook] Receita recorrente gerada (Financeiro: {$financeiro->id}) para o Contrato: {$contrato->id}.");

                // Despacha o envio do recibo pelo WhatsApp se o Job existir
                if (class_exists(EnviarReciboWhatsAppJob::class)) {
                    EnviarReciboWhatsAppJob::dispatch($financeiro);
                }
                
                return; // Fluxo finalizado para o cliente
            }
        }

        // 2. Fluxo Legado: Pagamento do Próprio Tenant (SaaS SuperAdmin)
        if ($tenant = $this->findTenantBySubscription($payment)) {
            $tenant->update([
                'status_pagamento' => 'ativo',
                'is_active' => true,
                'data_vencimento' => now()->addMonth()->format('Y-m-d'),
            ]);
            Log::info("[AsaasWebhook] Tenant SaaS {$tenant->id} ativado via webhook.");
        }
    }

    private function pagamentoAtrasado(array $payment): void
    {
        // Tratamento para Cliente (Contrato)
        if (!empty($payment['subscription'])) {
            $contrato = ContratoRecorrente::where('gateway_subscription_id', $payment['subscription'])->first();
            if ($contrato) {
                $contrato->update(['status' => 'inativo']);
                Log::info("[AsaasWebhook] Contrato {$contrato->id} suspenso por Inadimplência.");
                return;
            }
        }

        // Tratamento para Tenant SaaS
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
        // Cliente cancelou o contrato
        if (!empty($subscription['id'])) {
            $contrato = ContratoRecorrente::where('gateway_subscription_id', $subscription['id'])->first();
            if ($contrato) {
                $contrato->update([
                    'status' => 'inativo',
                    'gateway_subscription_id' => null,
                ]);
                Log::info("[AsaasWebhook] Contrato {$contrato->id} cancelado na operadora.");
                return;
            }
        }

        // Tenant cancelou a assinatura do SaaS
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
