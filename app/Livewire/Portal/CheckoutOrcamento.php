<?php

namespace App\Livewire\Portal;

use App\Models\Orcamento;
use App\Services\AsaasGatewayService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class CheckoutOrcamento extends Component
{
    public Orcamento $orcamento;
    public string $payloadCode = '';
    public bool $isPaid = false;

    public function mount(string $uuid, AsaasGatewayService $asaasService)
    {
        // Presumimos que o Orçamento tenha uma coluna 'uuid'. Se for ID hasheado, ajuste o where()
        $this->orcamento = Orcamento::where('uuid', $uuid)->firstOrFail();

        // 1. Verifica o status do orçamento
        if ($this->orcamento->status === 'pago') {
            $this->isPaid = true;
            return;
        }

        // 2. Se a cobrança ainda não existe no gateway
        if (empty($this->orcamento->gateway_cobranca_id)) {
            try {
                // Monta o payload para criar a cobrança PIX no Asaas
                $clienteId = $this->orcamento->cliente->asaas_customer_id ?? 'CUST_DEFAULT'; 
                
                $dadosCobranca = $asaasService->criarCobranca(
                    $clienteId,
                    (float) $this->orcamento->valor_total,
                    "Pagamento Orçamento #{$this->orcamento->id}",
                    now()->addDays(2)->format('Y-m-d')
                );

                $codigoPix = $dadosCobranca['payloadCode'] ?? $dadosCobranca['pixCopiaECola'] ?? '';

                if (empty($codigoPix)) {
                    // Chamada extra para pegar a string copia e cola
                    $qrCodeData = $asaasService->obterQrCodePix($dadosCobranca['id']);
                    $codigoPix = $qrCodeData['payload'] ?? '';
                }

                $this->orcamento->update([
                    'gateway_cobranca_id' => $dadosCobranca['id'],
                    'pix_payload_code' => $codigoPix,
                ]);
                
                $this->payloadCode = $codigoPix;

            } catch (\Exception $e) {
                Log::error('[CheckoutOrcamento] Erro ao gerar PIX', ['erro' => $e->getMessage()]);
                session()->flash('error', 'Erro de comunicação com o banco. Recarregue a página.');
            }
        } else {
            // Se já tem gerado no banco, recupera
            $this->payloadCode = $this->orcamento->pix_payload_code ?? '';
        }
    }

    public function checkPaymentStatus()
    {
        // Recarrega os dados do banco de dados (que o webhook atualizou por baixo dos panos)
        $this->orcamento->refresh();

        if ($this->orcamento->status === 'pago') {
            $this->isPaid = true;
        }
    }

    public function render()
    {
        return view('livewire.portal.checkout-orcamento')
            ->layout('layouts.guest');
    }
}
