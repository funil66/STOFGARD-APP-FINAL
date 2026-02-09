<?php

namespace App\Http\Controllers;

use App\Models\Financeiro;
use App\Models\Configuracao;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Http\Request;

class FinanceiroPdfController extends Controller
{
    public function gerarPdf(Financeiro $financeiro)
    {
        return $this->renderPdf($financeiro);
    }

    private function renderPdf(Financeiro $financeiro)
    {
        // Garante diretório temporário
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return Pdf::view('pdf.financeiro', [
            'financeiro' => $financeiro->load(['categoria', 'cadastro', 'ordemServico', 'orcamento']),
            'config' => Configuracao::first()
        ])
            ->format('a4')
            ->name("Financeiro-{$financeiro->id}.pdf")
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                    ->setChromePath(config('services.browsershot.chrome_path', '/usr/bin/google-chrome'))
                    ->setNodeBinary(config('services.browsershot.node_path', '/usr/bin/node'))
                    ->setNpmBinary(config('services.browsershot.npm_path', '/usr/bin/npm'))
                    ->setOption('args', ['--disable-web-security', '--no-sandbox', '--disable-setuid-sandbox'])
                    ->timeout(60);
            })
            ->download();
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

        return Pdf::view('pdf.financeiro_mensal', [
            'mes' => $mes,
            'ano' => $ano,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $saldo,
            'porCategoria' => $porCategoria,
            'transacoes' => $transacoes,
            'config' => $config
        ])
            ->format('a4')
            ->name("Relatorio-Financeiro-{$mes}-{$ano}.pdf")
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                    ->setChromePath(config('services.browsershot.chrome_path', '/usr/bin/google-chrome'))
                    ->setNodeBinary(config('services.browsershot.node_path', '/usr/bin/node'))
                    ->setNpmBinary(config('services.browsershot.npm_path', '/usr/bin/npm'))
                    ->setOption('args', ['--disable-web-security', '--no-sandbox', '--disable-setuid-sandbox'])
                    ->timeout(60);
            })
            ->download();
    }
}
