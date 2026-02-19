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
    /**
     * Handle the Orcamento "updated" event.
     * Synchronizes changes to related records (Financeiro, OS, Agenda).
     */
    public function updated(Orcamento $orcamento): void
    {
        // Só sincroniza se já foi aprovado e tem registros vinculados
        if ($orcamento->status !== \App\Enums\OrcamentoStatus::Aprovado->value) {
            return;
        }

        // 1. Sincronizar Valores (Receita e Comissões)
        if ($orcamento->isDirty(['valor_total', 'valor_final_editado', 'comissao_vendedor', 'comissao_loja', 'desconto_prestador'])) {
            $this->syncFinanceiro($orcamento);
            $this->syncOrdemServicoValores($orcamento);
        }

        // 2. Sincronizar IDs (Parceiro, Vendedor, Loja, Cliente)
        if ($orcamento->isDirty(['id_parceiro', 'vendedor_id', 'loja_id', 'cadastro_id'])) {
            $this->syncIds($orcamento);
        }

        // 3. Sincronizar Observações do Orçamento → OS
        if ($orcamento->isDirty('observacoes')) {
            $this->syncObservacoes($orcamento);
        }
    }

    private function syncFinanceiro(Orcamento $orcamento): void
    {
        // A. Atualizar Receita (Entrada)
        $receita = $orcamento->financeiros()->where('tipo', 'entrada')->where('status', 'pendente')->first();
        if ($receita) {
            $novoValor = $orcamento->valor_efetivo;
            $receita->update([
                'valor' => $novoValor,
                'desconto' => max(0, floatval($orcamento->valor_total) - floatval($novoValor)),
            ]);
            Log::info("OrcamentoObserver: Receita #{$receita->id} atualizada para R$ {$novoValor}");
        }

        // B. Atualizar Comissão Vendedor
        $comissaoVendedor = $orcamento->financeiros()
            ->where('tipo', 'saida')
            ->where('is_comissao', true)
            ->where('descricao', 'LIKE', '%Comissão Vendedor%')
            ->where('status', 'pendente') // Só atualiza se ñ foi paga
            ->first();

        if (floatval($orcamento->comissao_vendedor) > 0) {
            if ($comissaoVendedor) {
                $comissaoVendedor->update(['valor' => $orcamento->comissao_vendedor]);
            } else {
                // Poderia criar aqui se não existisse, mas por hora vamos apenas atualizar
            }
        } elseif ($comissaoVendedor) {
            // Se zerou a comissão, deleta o lançamento pendente?
            // $comissaoVendedor->delete(); // Decisão de negócio: melhor manter ou zerar?
            $comissaoVendedor->update(['valor' => 0]);
        }

        // C. Atualizar Comissão Loja
        $comissaoLoja = $orcamento->financeiros()
            ->where('tipo', 'saida')
            ->where('is_comissao', true)
            ->where('descricao', 'LIKE', '%Comissão Loja%')
            ->where('status', 'pendente')
            ->first();

        if (floatval($orcamento->comissao_loja) > 0) {
            if ($comissaoLoja) {
                $comissaoLoja->update(['valor' => $orcamento->comissao_loja]);
            }
        } elseif ($comissaoLoja) {
            $comissaoLoja->update(['valor' => 0]);
        }
    }

    private function syncOrdemServicoValores(Orcamento $orcamento): void
    {
        $os = $orcamento->ordemServico;
        if ($os) {
            $os->update([
                'valor_total' => $orcamento->valor_efetivo,
                'valor_desconto' => max(0, floatval($orcamento->desconto_prestador)),
            ]);
            Log::info("OrcamentoObserver: OS #{$os->id} valores atualizados (total={$orcamento->valor_efetivo}, desconto={$orcamento->desconto_prestador}).");
        }
    }

    private function syncObservacoes(Orcamento $orcamento): void
    {
        $os = $orcamento->ordemServico;
        if ($os) {
            $os->update(['observacoes' => $orcamento->observacoes]);
            Log::info("OrcamentoObserver: OS #{$os->id} observações sincronizadas.");
        }
    }

    private function syncIds(Orcamento $orcamento): void
    {
        // 1. Update OS (Has all specific ID columns)
        if ($os = $orcamento->ordemServico) {
            $os->update([
                'id_parceiro' => $orcamento->id_parceiro,
                'vendedor_id' => $orcamento->vendedor_id,
                'loja_id' => $orcamento->loja_id,
                'cadastro_id' => $orcamento->cadastro_id, // Cliente
            ]);
        }

        // 2. Update Financeiros
        // UPDATE GLOBAL (All linked records should have the new id_parceiro)
        $orcamento->financeiros()->update(['id_parceiro' => $orcamento->id_parceiro]);

        // UPDATE ESPECÍFICO DE CADASTRO_ID
        // A. Receita (Entrada) -> Cadastro ID = Cliente
        $orcamento->financeiros()
            ->where('tipo', 'entrada')
            ->update(['cadastro_id' => $orcamento->cadastro_id]);

        // B. Comissão Vendedor -> Cadastro ID = Vendedor
        if ($orcamento->vendedor_id) {
            $orcamento->financeiros()
                ->where('tipo', 'saida')
                ->where('is_comissao', true)
                ->where('descricao', 'LIKE', '%Comissão Vendedor%')
                ->update(['cadastro_id' => $orcamento->vendedor_id]);
        }

        // C. Comissão Loja -> Cadastro ID = Loja
        if ($orcamento->loja_id) {
            $orcamento->financeiros()
                ->where('tipo', 'saida')
                ->where('is_comissao', true)
                ->where('descricao', 'LIKE', '%Comissão Loja%')
                ->update(['cadastro_id' => $orcamento->loja_id]);
        }

        // 3. Update Agendas
        // Agenda uses cadastro_id (Cliente/Legacy) and id_parceiro
        $dadosAgenda = [
            'id_parceiro' => $orcamento->id_parceiro,
            'cadastro_id' => $orcamento->cadastro_id,
        ];

        // Se mudou o cliente, atualiza título também
        if ($orcamento->isDirty('cadastro_id') && $orcamento->cliente) {
            $dadosAgenda['titulo'] = 'Serviço - ' . $orcamento->cliente->nome;
        }

        $orcamento->agendas()->update($dadosAgenda);

        Log::info("OrcamentoObserver: IDs sincronizados para OS, Financeiro e Agenda do Orçamento #{$orcamento->id}");
    }

    /**
     * Handle the Orcamento "saved" event.
     */
    public function saved(Orcamento $orcamento): void
    {
        Log::info("Orçamento {$orcamento->id} salvo (número {$orcamento->numero_orcamento}).");
    }
}
