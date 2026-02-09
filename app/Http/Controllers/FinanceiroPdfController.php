<?php

namespace App\Http\Controllers;

use App\Models\Financeiro;
use App\Models\Configuracao;
use App\Services\PdfService;
use Illuminate\Http\Request;

class FinanceiroPdfController extends Controller
{
    public function __construct(protected PdfService $pdfService)
    {
    }

    public function gerarPdf(Financeiro $financeiro)
    {
        return $this->pdfService->generate(
            'pdf.financeiro',
            [
                'financeiro' => $financeiro->load(['categoria', 'cadastro', 'ordemServico', 'orcamento']),
                'config' => Configuracao::first()
            ],
            "Financeiro-{$financeiro->id}.pdf"
        );
    }

    public function gerarRelatorioMensal(Request $request)
    {
        $mes = $request->query('mes', now()->month);
        $ano = $request->query('ano', now()->year);

        $inicio = \Carbon\Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
        $fim = \Carbon\Carbon::createFromDate($ano, $mes, 1)->endOfMonth();

        // 1. Resumo Financeiro
        $entradas = Financeiro::whereBetween('data', [$inicio, $fim])
            ->where('tipo', 'entrada')
            ->sum('valor');

        $saidas = Financeiro::whereBetween('data', [$inicio, $fim])
            ->where('tipo', 'saida')
            ->sum('valor');

        $saldo = $entradas - $saidas;

        // 2. Despesas por Categoria
        $porCategoria = Financeiro::whereBetween('data', [$inicio, $fim])
            ->where('tipo', 'saida')
            ->selectRaw('categoria_id, sum(valor) as total')
            ->groupBy('categoria_id')
            ->with('categoria')
            ->orderByDesc('total')
            ->get();

        // 3. Extrato Completo
        $transacoes = Financeiro::whereBetween('data', [$inicio, $fim])
            ->with(['categoria', 'cadastro'])
            ->orderBy('data')
            ->get();

        $config = Configuracao::first();

        return $this->pdfService->generate(
            'pdf.financeiro_mensal',
            [
                'mes' => $mes,
                'ano' => $ano,
                'entradas' => $entradas,
                'saidas' => $saidas,
                'saldo' => $saldo,
                'porCategoria' => $porCategoria,
                'transacoes' => $transacoes,
                'config' => $config
            ],
            "Relatorio-Financeiro-{$mes}-{$ano}.pdf"
        );
    }
}
