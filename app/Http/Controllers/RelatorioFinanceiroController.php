<?php

namespace App\Http\Controllers;

use App\Models\TransacaoFinanceira;
use Illuminate\Http\Request;

class RelatorioFinanceiroController extends Controller
{
    public function graficoPorCategoria(Request $request)
    {
        $inicio = $request->query('inicio') ? \Carbon\Carbon::parse($request->query('inicio'))->startOfDay() : now()->startOfMonth();
        $fim = $request->query('fim') ? \Carbon\Carbon::parse($request->query('fim'))->endOfDay() : now()->endOfMonth();

        $data = TransacaoFinanceira::whereBetween('data_transacao', [$inicio, $fim])
            ->selectRaw('categoria, SUM(CASE WHEN tipo = "receita" THEN valor ELSE -valor END) as total')
            ->groupBy('categoria')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->categoria => (float) $row->total]);

        return response()->json(['inicio' => $inicio->toDateString(), 'fim' => $fim->toDateString(), 'por_categoria' => $data]);
    }
}
