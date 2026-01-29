<?php

namespace App\Observers;

use App\Models\OrdemServico;
use App\Models\Agenda;
use App\Models\Estoque;
use Illuminate\Support\Facades\Auth;

class OrdemServicoObserver
{
    /**
     * Handle the OrdemServico "created" event.
     */
    public function created(OrdemServico $os): void
    {
        // Cria agendamento automático ao criar a OS
        Agenda::create([
            'titulo' => 'Serviço - ' . ($os->cliente->nome ?? 'Cliente'),
            'descricao' => "Ordem de Serviço {$os->numero_os}",
            'cliente_id' => $os->cliente_id ?? null,
            'cadastro_id' => $os->cadastro_id,
            'ordem_servico_id' => $os->id,
            
            // --- CORREÇÃO AQUI ---
            // Dependendo da sua migration, o campo pode se chamar 'tipo' ou 'tipo_servico'
            // Vou enviar os dois para garantir (o model filtra o que não existe se estiver no fillable)
            'tipo' => 'servico', 
            'tipo_servico' => 'servico', 
            
            'data_hora_inicio' => $os->data_inicio ?? now(),
            'data_hora_fim' => $os->data_inicio ? \Carbon\Carbon::parse($os->data_inicio)->addHours(2) : now()->addHours(2),
            'status' => 'agendado',
            'local' => $os->cliente->endereco ?? 'Endereço não informado',
            'endereco_completo' => $os->cliente->endereco_completo ?? null,
            'criado_por' => Auth::id() ?? 1,
        ]);
    }

    /**
     * Handle the OrdemServico "updated" event.
     */
    public function updated(OrdemServico $os): void
    {
        // Se a OS for concluída, baixa o estoque
        if ($os->isDirty('status') && $os->status === 'concluido') {
            foreach ($os->itens as $item) {
                if ($item->produto_id) {
                    Estoque::create([
                        'produto_id' => $item->produto_id,
                        'tipo' => 'saida',
                        'quantidade' => $item->quantidade,
                        'motivo' => "Uso na OS {$os->numero_os}",
                        'data_movimento' => now(),
                        'criado_por' => Auth::id() ?? 1, // Garantia também aqui
                    ]);
                }
            }
        }
    }
}

