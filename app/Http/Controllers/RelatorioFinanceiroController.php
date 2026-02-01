<?php

namespace App\Http\Controllers;

use App\Models\Financeiro;
use Illuminate\Http\Request;

class RelatorioFinanceiroController extends Controller
{
    public function graficoPorCategoria(Request $request)
    {
        $inicio = $request->query('inicio') ? \Carbon\Carbon::parse($request->query('inicio'))->startOfDay() : now()->startOfMonth();
        $fim = $request->query('fim') ? \Carbon\Carbon::parse($request->query('fim'))->endOfDay() : now()->endOfMonth();

        $data = Financeiro::whereBetween('data', [$inicio, $fim])
            ->selectRaw('categoria_id, SUM(CASE WHEN tipo = "receita" THEN valor ELSE -valor END) as total')
            ->groupBy('categoria_id')
            ->with('categoria:id,nome')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->categoria?->nome ?? 'Sem categoria' => (float) $row->total]);

        return response()->json(['inicio' => $inicio->toDateString(), 'fim' => $fim->toDateString(), 'por_categoria' => $data]);
    }
}
