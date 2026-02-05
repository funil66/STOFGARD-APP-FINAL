<?php

namespace App\Http\Controllers;

use App\Models\Financeiro;
use App\Models\Configuracao;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Http\Request;

class ExtratoPdfController extends Controller
{
    public function gerarExtrato(Request $request)
    {
        // 1. Inicia query
        $query = Financeiro::query()->with(['cadastro', 'categoria']);

        // 2. Aplica filtros da tabela Filament
        // Os filtros vêm dentro de 'tableFilters' quando enviados pelo Filament
        $filters = $request->input('tableFilters', []);

        // Filtro: Período
        $periodo = $filters['periodo'] ?? [];
        if (!empty($periodo['data_inicio'])) {
            $query->whereDate('data', '>=', $periodo['data_inicio']);
        }
        if (!empty($periodo['data_fim'])) {
            $query->whereDate('data', '<=', $periodo['data_fim']);
        }

        // Filtro: Tipo
        if (!empty($filters['tipo']['value'])) {
            $query->where('tipo', $filters['tipo']['value']);
        }

        // Filtro: Status
        if (!empty($filters['status']['value'])) {
            $query->where('status', $filters['status']['value']);
        }

        // Filtro: Categoria
        if (!empty($filters['categoria_id']['value'])) {
            $query->where('categoria_id', $filters['categoria_id']['value']);
        }

        // Filtro: Comissão Status
        if (!empty($filters['comissao_status']['value'])) {
            $val = $filters['comissao_status']['value'];
            if ($val === 'todas') {
                $query->where('is_comissao', true);
            } elseif ($val === 'paga') {
                $query->where('is_comissao', true)->where('comissao_paga', true);
            } elseif ($val === 'pendente') {
                $query->where('is_comissao', true)->where('comissao_paga', false);
            }
        }

        // Ordenação
        $query->orderBy('data', 'asc');

        // Busca dados
        $transacoes = $query->get();

        // Calcular totais
        $totalEntradas = $transacoes->where('tipo', 'entrada')->sum(fn($t) => $t->valor_pago > 0 ? $t->valor_pago : $t->valor);
        $totalSaidas = $transacoes->where('tipo', 'saida')->sum(fn($t) => $t->valor_pago > 0 ? $t->valor_pago : $t->valor);
        $saldo = $totalEntradas - $totalSaidas;

        return Pdf::view('pdf.extrato', [
            'transacoes' => $transacoes,
            'filtros' => $filters,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'saldo' => $saldo,
            'config' => Configuracao::first(),
        ])
            ->format('a4')
            ->name('Extrato-Financeiro-' . now()->format('Y-m-d') . '.pdf')
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
