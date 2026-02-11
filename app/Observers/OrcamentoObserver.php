<?php

namespace App\Observers;

use App\Models\Configuracao;
use App\Models\Orcamento;
use App\Services\Pix\PixMasterService;
use Illuminate\Support\Facades\Log;

class OrcamentoObserver
{
    /**
     * Handle the Orcamento "saving" event.
     * Generates PIX QR Code automatically before saving if PIX is enabled.
     */
    public function saving(Orcamento $orcamento): void
    {
        // Gerar número do orçamento se não existir
        if (!$orcamento->numero_orcamento) {
            $orcamento->numero_orcamento = Orcamento::gerarNumeroOrcamento();
        }

        // Generate PIX QR Code if enabled
        if ($orcamento->pdf_incluir_pix && $orcamento->pix_chave_selecionada) {
            // Skip if already has QR code and value hasn't changed
            if ($orcamento->pix_qrcode_base64 && !$orcamento->isDirty('valor_total')) {
                return;
            }

            try {
                $this->gerarQRCodePix($orcamento);
            } catch (\Throwable $e) {
                Log::error('Erro ao gerar QR Code PIX no Orçamento', [
                    'orcamento_id' => $orcamento->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't block the save, just log the error
            }
        }
    }

    /**
     * Generates PIX QR Code using PixMasterService.
     */
    protected function gerarQRCodePix(Orcamento $orcamento): void
    {
        $pixService = app(PixMasterService::class);

        // 1. Determine Discount Percentage
        // Use per-budget setting if set, otherwise global
        // Note: Setting::get returns string/mixed, cast to float
        $percentual = $orcamento->pdf_desconto_pix_percentual !== null
            ? floatval($orcamento->pdf_desconto_pix_percentual)
            : (float) \App\Models\Setting::get('financeiro_desconto_avista', 10);

        // 2. Determine Base Value
        $valorEfetivo = $orcamento->valor_efetivo;

        // 3. Determine Final PIX Value
        // Logic: If manually edited, NO extra discount (user request: "exceto se editado manualmente o valor final")
        if (floatval($orcamento->valor_final_editado) > 0) {
            $valorPix = $valorEfetivo;
        } else {
            // If NOT edited manually, apply discount if enabled in settings/flag
            // We use 'aplicar_desconto_pix' flag which usually defaults to true or global setting
            // Also ensure we don't apply if percentual is 0
            if ($orcamento->aplicar_desconto_pix && $percentual > 0) {
                $valorPix = $valorEfetivo * (1 - ($percentual / 100));
            } else {
                $valorPix = $valorEfetivo;
            }
        }

        // Generate unique transaction ID
        $txid = 'ORC' . str_pad($orcamento->id ?? 'TEMP' . rand(1000, 9999), 6, '0', STR_PAD_LEFT) . time();

        // Generate PIX
        // Get Company Name/City from Settings
        $beneficiario = \App\Models\Setting::get('empresa_nome', 'STOFGARD');
        $cidade = \App\Models\Setting::get('empresa_cidade', 'SAO PAULO');

        $result = $pixService->gerarPix(
            chave: $orcamento->pix_chave_selecionada,
            beneficiario: substr($beneficiario, 0, 25), // Safety limit
            cidade: substr($cidade, 0, 15), // Safety limit
            identificador: $txid,
            valor: (float) $valorPix
        );

        // Save to model (will be persisted when save() completes)
        $orcamento->pix_qrcode_base64 = $result['qr_code_img'] ?? null;
        $orcamento->pix_copia_cola = $result['payload_pix'] ?? $result['payload'] ?? null;
        $orcamento->pix_txid = $txid;

        Log::info('QR Code PIX gerado com sucesso', [
            'orcamento_id' => $orcamento->id,
            'txid' => $txid,
            'valor_base' => $valorEfetivo,
            'percentual' => $percentual,
            'valor_pix' => $valorPix,
        ]);
    }

    /**
     * Handle the Orcamento "saved" event.
     */
    public function saved(Orcamento $orcamento): void
    {
        Log::info("Orçamento {$orcamento->id} salvo (número {$orcamento->numero_orcamento}).");
    }
}
