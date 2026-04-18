<?php

namespace App\Filament\Widgets;

use App\Models\OrdemServico;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class MapaServicosWidget extends Widget
{
    protected static string $view = 'filament.widgets.mapa-servicos-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public function getDadosMapa(): array
    {
        $ordensDoDia = OrdemServico::with('cliente')
            ->whereDate('created_at', Carbon::today())
            ->where('status', '!=', 'cancelado')
            ->get();

        $dados = [];

        foreach ($ordensDoDia as $os) {
            // Verifica se tem geo_lat salvo na OS (via Fase 1) ou faz fallback para um valor padrão
            $lat = $os->assinatura_geo_lat ?? $os->cliente?->latitude ?? -21.1704;
            $lng = $os->assinatura_geo_lng ?? $os->cliente?->longitude ?? -47.8103;

            $dados[] = [
                'id' => $os->id,
                'cliente_nome' => $os->cliente?->nome ?? 'Cliente Não Identificado',
                'servico_descricao' => $os->descricao ?? 'Serviço Agendado',
                'lat' => (float) $lat,
                'lng' => (float) $lng,
            ];
        }

        return $dados;
    }

    protected function getViewData(): array
    {
        return [
            'locais' => $this->getDadosMapa(),
        ];
    }
}
