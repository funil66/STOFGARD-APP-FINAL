<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;

class WebhookNFSeController extends Controller
{
    /**
     * Receber atualizações de status da Nota Fiscal (NFS-e/NFe).
     * Rota de exemplo: POST /api/webhooks/nfse/{tenant_id}
     */
    public function handle(Request $request, string $tenant_id)
    {
        $tenant = Tenant::find($tenant_id);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        tenancy()->initialize($tenant);

        $payload = $request->all();

        Log::channel('single')->info("Webhook NFSe recebido para Tenant {$tenant_id}: ", $payload);

        // Placeholder para processamento de provedor (ex: FocusNFe)
        /*
        $referencia = $payload['ref'] ?? null;
        $status = $payload['status'] ?? null;

        if ($referencia && $status) {
            $nota = \App\Models\NotaFiscal::where('provedor_referencia_id', $referencia)->first();
            if ($nota) {
                $nota->update([
                    'status_sefaz' => $status,
                    'erros_processamento' => json_encode($payload['erros'] ?? [])
                ]);
            }
        }
        */

        tenancy()->end();

        return response()->json(['status' => 'success', 'message' => 'Webhook recebido com sucesso.']);
    }
}
