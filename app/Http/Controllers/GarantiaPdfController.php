<?php

namespace App\Http\Controllers;

use App\Models\Garantia;
use App\Models\OrdemServico;
use App\Models\PerfilGarantia;
use App\Services\ServiceTypeManager;
use Filament\Notifications\Notification;

class GarantiaPdfController extends BasePdfQueueController
{
    public function gerarPdf(Garantia $garantia)
    {
        $config = $this->loadConfig();
        $garantia->load(['ordemServico.cliente', 'ordemServico.itens', 'ordemServico.produtosUtilizados', 'ordemServico.perfilGarantia']);

        return $this->enqueuePdf(
            'pdf.certificado_garantia',
            [
                'garantia' => $garantia,
                'os' => $garantia->ordemServico,
                'config' => $config,
            ],
            'garantia',
            $garantia,
            []
        );
    }

    public function gerarPorOrdemServico(OrdemServico $ordemServico)
    {
        $ordemServico->load(['cliente', 'itens', 'produtosUtilizados', 'perfilGarantia']);

        if ($ordemServico->status !== 'concluida') {
            Notification::make()
                ->title('OS ainda não concluída')
                ->body('Conclua a ordem de serviço para gerar o certificado de garantia.')
                ->warning()
                ->send();

            return back();
        }

        $garantia = $ordemServico->garantias()->latest()->first();

        if (!$garantia) {
            $tiposServico = collect($ordemServico->itens ?? [])
                ->map(fn ($item) => $item->servico_tipo)
                ->filter(fn ($tipo) => filled($tipo))
                ->unique()
                ->values();

            if ($tiposServico->isEmpty()) {
                $tiposServico = collect([$ordemServico->tipo_servico ?? 'servico']);
            }

            $garantiaCriada = null;

            foreach ($tiposServico as $tipoServico) {
                $perfilId = ServiceTypeManager::getPerfilGarantiaId((string) $tipoServico);
                $perfil = $perfilId ? PerfilGarantia::find($perfilId) : null;

                if (!$perfil || empty($perfil->dias_garantia)) {
                    continue;
                }

                $garantiaCriada = Garantia::create([
                    'ordem_servico_id' => $ordemServico->id,
                    'tipo_servico' => $tipoServico,
                    'data_inicio' => $ordemServico->data_conclusao ?? now(),
                    'dias_garantia' => (int) $perfil->dias_garantia,
                    'data_fim' => ($ordemServico->data_conclusao ?? now())->copy()->addDays((int) $perfil->dias_garantia),
                    'status' => 'ativa',
                    'observacoes' => 'Perfil de garantia: ' . $perfil->nome,
                ]);
            }

            if (!$garantiaCriada) {
                Notification::make()
                    ->title('Perfil de garantia não configurado')
                    ->body('Defina um perfil de garantia para os serviços da OS antes de gerar o certificado.')
                    ->danger()
                    ->send();

                return back();
            }

            $garantia = $garantiaCriada;
        }

        return $this->gerarPdf($garantia);
    }
}
