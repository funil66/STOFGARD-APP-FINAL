<?php

namespace App\Http\Controllers;

use App\Models\ClienteAcesso;
use App\Models\Configuracao;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\NotaFiscal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * PortalClienteController — Portal self-service do cliente final.
 * Acesso sem login/senha — via magic link (sessão controlada pelo middleware).
 */
class PortalClienteController extends Controller
{
    /**
     * Dashboard do cliente: suas OS, Orçamentos, NFs.
     */
    public function index(Request $request)
    {
        $cadastroId = Session::get('cliente_cadastro_id');
        $acesso = $request->get('cliente_acesso');
        $config = Configuracao::first();

        $orcamentos = Orcamento::where('cadastro_id', $cadastroId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $ordensServico = OrdemServico::where('cadastro_id', $cadastroId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $notasFiscais = class_exists(\App\Models\NotaFiscal::class)
            ? \App\Models\NotaFiscal::where('cadastro_id', $cadastroId)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
            : collect();

        return view('cliente.portal', compact(
            'orcamentos',
            'ordensServico',
            'notasFiscais',
            'config',
            'acesso'
        ));
    }

    /**
     * Detalhes de um orçamento específico (com PDF e botão de aprovação).
     */
    public function orcamento(Request $request, int $id)
    {
        $cadastroId = Session::get('cliente_cadastro_id');
        $config = Configuracao::first();

        $orcamento = Orcamento::where('id', $id)
            ->where('cadastro_id', $cadastroId)
            ->with(['itens', 'cliente'])
            ->firstOrFail();

        return view('cliente.orcamento', compact('orcamento', 'config'));
    }

    /**
     * Detalhes de uma OS específica.
     */
    public function ordemServico(Request $request, int $id)
    {
        $cadastroId = Session::get('cliente_cadastro_id');
        $config = Configuracao::first();

        $os = OrdemServico::where('id', $id)
            ->where('cadastro_id', $cadastroId)
            ->with(['itens', 'cliente', 'garantias'])
            ->firstOrFail();

        // Facilita o acesso na view pegando a garantia mais recente
        $os->garantia = $os->garantias->first();

        return view('cliente.ordem-servico', compact('os', 'config'));
    }

    /**
     * Detalhes de uma Nota Fiscal.
     */
    public function notaFiscal(Request $request, int $id)
    {
        $cadastroId = Session::get('cliente_cadastro_id');
        $config = Configuracao::first();

        $nf = \App\Models\NotaFiscal::where('id', $id)
            ->where('cadastro_id', $cadastroId)
            ->firstOrFail();

        return view('cliente.nota-fiscal', compact('nf', 'config'));
    }
}
