<?php

namespace App\Http\Middleware;

use App\Models\ClienteAcesso;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * ClienteAutenticado — Middleware para o portal do cliente final.
 * Verifica se a sessao tem um acesso valido (via magic link).
 */
class ClienteAutenticado
{
    public function handle(Request $request, Closure $next)
    {
        $acessoId = Session::get('cliente_acesso_id');

        if (!$acessoId) {
            return redirect()->route('cliente.magic-link.invalido')
                ->with('erro', 'Acesso necessario. Use o link enviado via WhatsApp.');
        }

        // Verifica se o acesso ainda existe na base
        $acesso = ClienteAcesso::find($acessoId);

        if (!$acesso) {
            Session::forget(['cliente_acesso_id', 'cliente_cadastro_id']);
            return redirect()->route('cliente.magic-link.invalido')
                ->with('erro', 'Sessao invalida. Use o link enviado via WhatsApp.');
        }

        // Compartilha o acesso com a requisicao
        $request->merge(['cliente_acesso' => $acesso]);

        return $next($request);
    }
}
