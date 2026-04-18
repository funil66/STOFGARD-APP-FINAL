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

/**
 * PixWebhookController — Processa webhooks PIX dos tenants.
 * Cada tenant tem um webhook_token único para identificação.
 *
 * Rota: POST api/webhooks/pix/{webhook_token}
 *
 * Suporta: Asaas, EFI/Gerencianet (detectado pelo payload)
 */
class PixWebhookController extends Controller
{
    public function handle(Request $request, string $webhookToken)
    {
        Log::info('[PixWebhook] Recebido', ['token' => substr($webhookToken, 0, 8) . '...', 'ip' => $request->ip()]);

        // Encontra o tenant pelo webhook_token (roda no landlord DB)
        $tenantId = $this->findTenantIdByWebhookToken($webhookToken);

        if (!$tenantId) {
            Log::warning('[PixWebhook] Tenant não encontrado para webhook_token', ['token' => $webhookToken]);
            return response()->json(['error' => 'Not found'], 404);
        }

        // Executa o processamento no contexto do banco do tenant
        tenancy()->initialize($tenantId);

        try {
            $this->processarEvento($request);
        } finally {
            tenancy()->end();
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Detecta o tipo de evento e processa adequadamente.
     */
    private function processarEvento(Request $request): void
    {
        $body = $request->all();

        // Formato Asaas
        if (isset($body['event'])) {
            $this->processarEventoAsaas($body);
            return;
        }

        // Formato EFI/Gerencianet (pix)
        if (isset($body['pix'])) {
            foreach ($body['pix'] as $pix) {
                $this->processarPixEfi($pix);
            }
            return;
        }

        Log::warning('[PixWebhook] Formato de webhook desconhecido', ['body_keys' => array_keys($body)]);
    }

    /**
     * Processa evento no formato Asaas.
     */
    private function processarEventoAsaas(array $body): void
    {
        $event = $body['event'];
        $payment = $body['payment'] ?? [];

        if (!in_array($event, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'])) {
            return;
        }

        $externalRef = $payment['externalReference'] ?? null;

        if (!$externalRef) {
            return;
        }

        // Referência no formato "orcamento_{id}"
        if (str_starts_with($externalRef, 'orcamento_')) {
            $orcamentoId = (int) str_replace('orcamento_', '', $externalRef);
            $this->confirmarPagamentoOrcamento($orcamentoId, $payment);
        }

        // Referência no formato "agendamento_{id}" (Fase 2)
        if (str_starts_with($externalRef, 'agendamento_')) {
            $agendamentoId = (int) str_replace('agendamento_', '', $externalRef);
            $this->confirmarSinalAgendamento($agendamentoId);
        }
    }

    /**
     * Processa evento no formato EFI/Gerencianet.
     */
    private function processarPixEfi(array $pix): void
    {
        $txid = $pix['txid'] ?? null;
        $valor = $pix['valor'] ?? 0;
        $status = 'pago';

        if (!$txid) {
            return;
        }

        // txid segue o padrão "orcamento{id}" ou "agendamento{id}"
        if (str_starts_with($txid, 'orcamento')) {
            $orcamentoId = (int) str_replace('orcamento', '', $txid);
            $this->confirmarPagamentoOrcamento($orcamentoId, ['netValue' => $valor]);
        }
    }

    /**
     * Confirma o pagamento de um orçamento e atualiza financeiro.
     */
    private function confirmarPagamentoOrcamento(int $orcamentoId, array $payment): void
    {
        $orcamento = Orcamento::find($orcamentoId);

        if (!$orcamento) {
            Log::warning('[PixWebhook] Orçamento não encontrado', ['orcamento_id' => $orcamentoId]);
            return;
        }

        // Idempotência: não processa se já está pago
        if ($orcamento->status_pagamento === 'pago') {
            Log::info('[PixWebhook] Orçamento já estava pago, ignorando', ['orcamento_id' => $orcamentoId]);
            return;
        }

        // Atualiza o orçamento
        $orcamento->update([
            'status_pagamento' => 'pago',
            'data_pagamento' => now(),
            'valor_pago' => $payment['netValue'] ?? $orcamento->valor_total,
        ]);

        // Atualiza o registro financeiro vinculado
        $financeiro = Financeiro::where('orcamento_id', $orcamentoId)->first();
        if ($financeiro) {
            $financeiro->update([
                'status' => 'pago',
                'data_pago' => now(),
            ]);
        }

        Log::info('[PixWebhook] Pagamento confirmado para orçamento', ['orcamento_id' => $orcamentoId]);

        // Dispara ZAP de confirmação para o cliente
        $this->enviarConfirmacaoWhatsApp($orcamento);
    }

    /**
     * Confirma o sinal de agendamento (Fase 2 — preparado mas não implementado ainda).
     */
    private function confirmarSinalAgendamento(int $agendamentoId): void
    {
        // TODO: Implementar na Fase 2
        Log::info('[PixWebhook] Confirmação de agendamento recebida (Fase 2)', ['agendamento_id' => $agendamentoId]);
    }

    /**
     * Envia mensagem de confirmação de pagamento via WhatsApp.
     */
    private function enviarConfirmacaoWhatsApp($orcamento): void
    {
        $cliente = $orcamento->cliente;
        if (!$cliente || !$cliente->celular) {
            return;
        }

        $mensagem = "✅ *Pagamento Confirmado!*\n\n"
            . "Olá, {$cliente->nome}! 🎉\n"
            . "Recebemos o pagamento do seu orçamento *#{$orcamento->numero}*.\n\n"
            . "Entraremos em contato em breve para agendar o serviço.\n\n"
            . "_Equipe " . (Configuracao::first()->empresa_nome ?? 'AUTONOMIA ILIMITADA') . "_";

        SendWhatsAppJob::dispatch($cliente->celular, $mensagem);
    }

    /**
     * Busca o tenant_id no banco landlord pelo webhook_token.
     * Executa FORA do contexto de tenant (no landlord DB).
     */
    private function findTenantIdByWebhookToken(string $webhookToken): ?string
    {
        // Busca direta pelo webhook_token no JSON data do tenant
        $tenant = \App\Models\Tenant::whereJsonContains('data->webhook_token', $webhookToken)->first();

        if (!$tenant) {
            Log::warning('[PixWebhook] Nenhum tenant encontrado para webhook_token', [
                'token_prefix' => substr($webhookToken, 0, 8),
            ]);
        }

        return $tenant?->id;
    }
}
