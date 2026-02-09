<?php

namespace App\Services;

use App\Models\Equipamento;
use App\Models\ListaDesejo;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EquipamentoService
{
    /**
     * Envia um equipamento para a lista de desejos.
     *
     * @param Equipamento $equipamento
     * @return void
     */
    public static function enviarParaListaDesejos(Equipamento $equipamento): void
    {
        ListaDesejo::create([
            'nome' => $equipamento->nome,
            'descricao' => 'Cópia de equipamento: ' . $equipamento->descricao,
            'categoria' => 'equipamento',
            'preco_estimado' => $equipamento->valor_aquisicao,
            'quantidade_desejada' => 1,
            'status' => 'pendente',
            'prioridade' => 'media',
            'solicitado_por' => Auth::user()->name ?? 'Sistema',
        ]);

        Notification::make()
            ->title('✅ Enviado para Lista de Desejos!')
            ->success()
            ->send();
    }
}
