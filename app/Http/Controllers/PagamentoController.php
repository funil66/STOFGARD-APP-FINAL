<?php

namespace App\Http\Controllers;

use App\Models\Financeiro;
use App\Services\PixService;
use Illuminate\Support\Facades\Log;

class PagamentoController extends Controller
{
    /**
     * Página pública de pagamento PIX
     */
    public function pix(string $hash)
    {
        try {
            $financeiro = Financeiro::where('link_pagamento_hash', $hash)
                ->with('cliente')
                ->firstOrFail();

            // Se já pago, mostrar confirmação
            if ($financeiro->status === 'pago') {
                return view('pagamento.pago', compact('financeiro'));
            }

            // Se PIX expirado ou não existe, gerar novo
            if (empty($financeiro->pix_txid) ||
                $financeiro->pix_status === 'expirado' ||
                ($financeiro->pix_expiracao && $financeiro->pix_expiracao->isPast())) {

                $pixService = new PixService;
                $pixService->criarCobranca($financeiro);
                $financeiro->refresh();
            }

            return view('pagamento.pix', compact('financeiro'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return view('pagamento.erro', [
                'mensagem' => 'Link de pagamento inválido ou expirado.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar página de pagamento', [
                'hash' => $hash,
                'error' => $e->getMessage(),
            ]);

            return view('pagamento.erro', [
                'mensagem' => 'Erro ao carregar página de pagamento. Tente novamente mais tarde.',
            ]);
        }
    }

    /**
     * Verifica status do pagamento (AJAX)
     */
    public function verificarStatus(string $hash)
    {
        try {
            $financeiro = Financeiro::where('link_pagamento_hash', $hash)
                ->firstOrFail();

            return response()->json([
                'status' => $financeiro->status,
                'pago' => $financeiro->status === 'pago',
                'valor_pago' => $financeiro->valor_pago,
                'data_pagamento' => $financeiro->data_pagamento?->format('d/m/Y H:i'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao verificar status',
            ], 404);
        }
    }
}
