<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Agenda; // O teu model de agendamentos
use App\Jobs\SendWhatsAppJob; // O Job que já temos a funcionar nas trincheiras
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAgendaConfirmationsCommand extends Command
{
    // O nome do gatilho no terminal
    protected $signature = 'iron:agenda-confirmations';
    protected $description = 'Dispara WhatsApp de confirmação para os agendamentos do dia seguinte (Exclusivo PRO e ELITE)';

    public function handle()
    {
        $this->info("🤖 Iron Code: A iniciar o Bot de Confirmação de Agendamentos...");

        // 1. A TRANCA: Só vamos rodar isto para quem te paga bem e está ativo
        $tenantsAtivos = Tenant::where('is_active', true)
            ->whereIn('plano', ['pro', 'elite'])
            ->get();

        if ($tenantsAtivos->isEmpty()) {
            $this->info("Nenhum inquilino PRO/ELITE ativo no momento.");
            return;
        }

        // Definimos a data de "amanhã"
        $amanha = Carbon::tomorrow()->format('Y-m-d');

        foreach ($tenantsAtivos as $tenant) {
            /** @var \App\Models\Tenant $tenant */
            // 2. Mergulhamos no Banco de Dados (Bunker) específico deste cliente
            $tenant->run(function () use ($tenant, $amanha) {

                // NOTA: Ajusta 'data_inicio' para o nome exato da tua coluna de data na tabela agendas
                $agendas = Agenda::with('cadastro') // Assume que tem relação com o Cliente/Cadastro
                    ->whereDate('data_hora_inicio', $amanha) // ou 'data', 'data_inicio', dependendo da tua migration
                    ->get();

                foreach ($agendas as $agenda) {
                    $cliente = $agenda->cadastro ?? null;

                    // Se não tiver cliente ou telefone associado, ignoramos
                    if (!$cliente || empty($cliente->telefone)) {
                        continue;
                    }

                    // Prepara os dados para a mensagem
                    $hora = Carbon::parse($agenda->data_hora_inicio)->format('H:i'); // Ajusta a coluna de hora se necessário
                    $nomeCliente = explode(' ', trim($cliente->nome))[0]; // Pega só o primeiro nome para ser amigável
                    $nomeEmpresa = $tenant->name; // O nome do teu cliente (o autónomo)

                    // O Script de Venda Matador
                    $mensagem = "Olá, {$nomeCliente}! Tudo bem? 📅\n\n";
                    $mensagem .= "Aqui é da *{$nomeEmpresa}*. Estou a passar por aqui apenas para confirmar a nossa visita/serviço marcada para amanhã às *{$hora}*.\n\n";

                    if (!empty($agenda->descricao)) {
                        $mensagem .= "🛠️ Serviço: {$agenda->descricao}\n\n";
                    }

                    $mensagem .= "Pode confirmar a sua disponibilidade? Responda com *SIM* ou *NÃO*.\n";
                    $mensagem .= "Obrigado!";

                    // 3. Fogo no buraco! Dispara para o Redis processar o envio via Evolution API
                    SendWhatsAppJob::dispatch($cliente->telefone, $mensagem);

                    Log::info("✅ [Bot] Confirmação disparada para {$nomeCliente} (Tenant: {$tenant->id})");
                }
            });
        }

        $this->info("\n🚀 Operação concluída. O Bot despachou os avisos para o Redis.");
    }
}
