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
 * MagicLinkController — Autenticacao do cliente final via link temporario.
 *
 * Flow:
 * 1. Tenant aprova OS/Orcamento
 * 2. Sistema gera token -> envia WhatsApp com link 
 * 3. Cliente clica -> token validado -> sessao do cliente ativa
 * 4. Cliente acessa portal /cliente com suas OS, Orcamentos e NFs
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
            return redirect()->route('cliente.magic-link.invalido')
                ->with('erro', 'Link invalido ou nao encontrado.');
        }

        if (!$acesso->estaValido()) {
            return redirect()->route('cliente.magic-link.invalido')
                ->with('erro', 'Este link ja foi utilizado ou expirou. Solicite um novo link.');
        }

        // Marca como usado
        $acesso->marcarComoUsado(
            $request->ip(),
            $request->userAgent() ?? 'unknown'
        );

        // Salva na sessao (autenticacao do cliente, nao usa auth() do Laravel)
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
     * Pagina de link invalido/expirado.
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
        return redirect('/')->with('mensagem', 'Voce saiu do portal.');
    }
}
