<?php

namespace App\Http\Controllers;

use App\Models\ClienteAcesso;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * MagicLinkController — Autenticação do cliente final via link temporário.
 *
 * Flow:
 * 1. Tenant aprova OS/Orçamento
 * 2. Sistema gera token → envia WhatsApp com link `/cliente/acesso/{token}`
 * 3. Cliente clica → token validado → sessão do cliente ativa
 * 4. Cliente acessa portal /cliente com suas OS, Orçamentos e NFs
 */
class MagicLinkController extends Controller
{
    /**
     * Consome o magic link e autentica o cliente.
     */
    public function consumir(Request $request, string $token)
    {
        $acesso = ClienteAcesso::where('token', $token)->first();

        if (!$acesso) {
            return redirect()->route('magic-link.invalido')
                ->with('erro', 'Link inválido ou não encontrado.');
        }

        if (!$acesso->estaValido()) {
            return redirect()->route('magic-link.invalido')
                ->with('erro', 'Este link já foi utilizado ou expirou. Solicite um novo link.');
        }

        // Marca como usado
        $acesso->marcarComoUsado(
            $request->ip(),
            $request->userAgent() ?? 'unknown'
        );

        // Salva na sessão (autenticação do cliente, não usa auth() do Laravel)
        Session::put('cliente_acesso_id', $acesso->id);
        Session::put('cliente_cadastro_id', $acesso->cadastro_id);
        Session::put('cliente_motivo', $acesso->motivo);

        // Redireciona para o destino correto
        $destino = match ($acesso->motivo) {
            'orcamento' => route('cliente.orcamento', ['id' => $acesso->resource_id]),
            'os' => route('cliente.os', ['id' => $acesso->resource_id]),
            'nota_fiscal' => route('cliente.nota-fiscal', ['id' => $acesso->resource_id]),
            default => route('cliente.portal'),
        };

        Log::info('[MagicLink] Cliente autenticado', [
            'cadastro_id' => $acesso->cadastro_id,
            'motivo' => $acesso->motivo,
        ]);

        return redirect($destino);
    }

    /**
     * Página de link inválido/expirado.
     */
    public function invalido()
    {
        return view('cliente.link-invalido');
    }

    /**
     * Logout do portal do cliente.
     */
    public function logout()
    {
        Session::forget(['cliente_acesso_id', 'cliente_cadastro_id', 'cliente_motivo']);
        return redirect('/')->with('mensagem', 'Você saiu do portal.');
    }
}
