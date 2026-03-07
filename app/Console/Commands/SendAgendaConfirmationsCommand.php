<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppJob;
use App\Models\Agenda;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAgendaConfirmationsCommand extends Command
{
    protected $signature = 'agendas:enviar-confirmacoes';
    protected $description = 'Envia mensagens via WhatsApp solicitando confirmação para agendamentos de amanhã (Apenas Pro/Elite)';

    public function handle()
    {
        $this->info('Iniciando envio de confirmações de agendamento...');

        $tenants = Tenant::whereIn('plano', ['pro', 'elite'])->get();

        foreach ($tenants as $tenant) {
            /** @var \App\Models\Tenant $tenant */
            tenancy()->initialize($tenant);

            $this->info("Processando Tenant {$tenant->getTenantKey()}...");

            $amanha = now()->addDays(1)->format('Y-m-d');

            // Busca os agendamentos marcados para amanhã que ainda não foram confirmados/cancelados
            $agendas = Agenda::whereDate('data_hora_inicio', $amanha)
                ->whereNotIn('status', ['cancelado', 'concluido'])
                ->get();

            foreach ($agendas as $agenda) {
                // Pular se já enviou lembrete (temos a flag lembrete_enviado na Agenda)
                if ($agenda->lembrete_enviado) {
                    continue;
                }

                $cadastro = $agenda->cadastro;
                if (!$cadastro || !$cadastro->celular) {
                    continue; // Sem número para enviar
                }

                $hora = $agenda->data_hora_inicio->format('H:i');
                $nome = explode(' ', $cadastro->nome_fantasia ?? $cadastro->razao_social ?? 'Cliente')[0];

                $mensagem = "Olá {$nome}! Tudo bem?\n\n";
                $mensagem .= "Passando para confirmar o seu agendamento conosco para *amanhã às {$hora}*.\n\n";
                $mensagem .= "📍 *Local/Serviço:* {$agenda->titulo}\n";
                if ($agenda->endereco_completo) {
                    $mensagem .= "📌 *Endereço:* {$agenda->endereco_completo}\n";
                }
                $mensagem .= "\nPor favor, responda com *SIM* para confirmar ou *NÃO* para reagendar/cancelar, para que possamos organizar nossa equipe. 😊";

                $instancia = $tenant->whatsapp_instance ?? 'default';

                SendWhatsAppJob::dispatch($cadastro->celular, $mensagem, $instancia);

                $agenda->update(['lembrete_enviado' => true]);

                $this->info("Mensagem disparada para Agenda #{$agenda->id} do Tenant {$tenant->getTenantKey()}");
            }

            tenancy()->end();
        }

        $this->info('Processo finalizado.');
    }
}
