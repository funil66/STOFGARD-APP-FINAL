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
        if (! $orcamento->numero_orcamento) {
            $orcamento->numero_orcamento = Orcamento::gerarNumeroOrcamento();
        }

        // Generate PIX QR Code if enabled
        if ($orcamento->pdf_incluir_pix && $orcamento->pix_chave_selecionada) {
            // Skip if already has QR code and value hasn't changed
            if ($orcamento->pix_qrcode_base64 && ! $orcamento->isDirty('valor_total')) {
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
        $config = Configuracao::first();

        if (! $config) {
            Log::warning('Configuração não encontrada para gerar PIX');

            return;
        }

        $pixService = app(PixMasterService::class);

        // Get the correct amount (with PIX discount if applicable)
        // PRIORITY 1: Manually edited final value (Already agreed upon, NO EXTRA DISCOUNT)
        if ($orcamento->valor_final_editado && $orcamento->valor_final_editado > 0) {
            $valor = $orcamento->valor_final_editado;
        }
        // PRIORITY 2: Calculated total with PIX discount (If enabled)
        elseif ($orcamento->aplicar_desconto_pix && $config->percentual_desconto_pix > 0) {
            $desconto = ($orcamento->valor_total * $config->percentual_desconto_pix) / 100;
            $valor = $orcamento->valor_total - $desconto;
        }
        // PRIORITY 3: Standard total (No discount)
        else {
            $valor = $orcamento->valor_total;
        }

        // Generate unique transaction ID
        $txid = 'ORC'.str_pad($orcamento->id ?? 'TEMP'.rand(1000, 9999), 6, '0', STR_PAD_LEFT).time();

        // Generate PIX
        $result = $pixService->gerarPix(
            chave: $orcamento->pix_chave_selecionada,
            beneficiario: $config->empresa_nome ?? 'STOFGARD',
            cidade: $config->empresa_cidade ?? 'SAO PAULO',
            identificador: $txid,
            valor: (float) $valor
        );

        // Save to model (will be persisted when save() completes)
        $orcamento->pix_qrcode_base64 = $result['qr_code_img'] ?? null;
        $orcamento->pix_copia_cola = $result['payload_pix'] ?? $result['payload'] ?? null;
        $orcamento->pix_txid = $txid;

        Log::info('QR Code PIX gerado com sucesso', [
            'orcamento_id' => $orcamento->id,
            'txid' => $txid,
            'valor' => $valor,
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
