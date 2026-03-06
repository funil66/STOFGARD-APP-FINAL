<?php

namespace App\Http\Controllers;

use App\Models\ClienteAcesso;
use App\Models\Configuracao;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\NotaFiscal;
use App\Models\Financeiro;
use App\Models\Garantia;
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

        $faturas = Financeiro::where('cadastro_id', $cadastroId)
            ->where('tipo', 'entrada') // Apenas o que o cliente nos deve ou pagou
            ->orderBy('status', 'asc') // Pendentes primeiro
            ->orderByDesc('data_vencimento')
            ->limit(15)
            ->get();

        $garantias = Garantia::whereHas('ordemServico', function ($q) use ($cadastroId) {
            $q->where('cadastro_id', $cadastroId);
        })
            ->with(['ordemServico.itens'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $alertasAcao = collect();

        foreach ($orcamentos as $orc) {
            if (empty($orc->assinatura_hash) && $orc->status === 'pendente') {
                $alertasAcao->push([
                    'tipo' => 'Orçamento',
                    'numero' => $orc->numero_orcamento,
                    'mensagem' => 'Aguardando sua Assinatura Digital para aprovação.',
                    'link' => route('cliente.orcamento', $orc->id),
                    'cor' => 'yellow'
                ]);
            }
        }

        foreach ($ordensServico as $os) {
            if (empty($os->assinatura_hash) && in_array($os->status, ['concluida', 'aguardando'])) {
                $mensagem = $os->status === 'concluida'
                    ? 'Serviço concluído! Assine o termo de entrega.'
                    : 'Aguardando sua assinatura para autorizar o serviço.';

                $alertasAcao->push([
                    'tipo' => 'Ordem de Serviço',
                    'numero' => $os->numero_os,
                    'mensagem' => $mensagem,
                    'link' => route('cliente.os', $os->id),
                    'cor' => 'blue'
                ]);
            }
        }

        return view('cliente.portal', compact(
            'orcamentos',
            'ordensServico',
            'notasFiscais',
            'faturas',
            'garantias',
            'alertasAcao',
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
