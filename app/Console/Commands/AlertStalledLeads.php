<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Orcamento;
use App\Models\User;
use Filament\Notifications\Notification;

class AlertStalledLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:alert-stalled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alerta sobre leads parados na etapa de Proposta Enviada há mais de 3 dias';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Define o limite (3 dias atrás)
        $limitDate = now()->subDays(3);

        // Busca orçamentos em "proposta_enviada" que não foram modificados recentemente
        $stalledLeads = Orcamento::where('etapa_funil', 'proposta_enviada')
            ->where('updated_at', '<', $limitDate)
            ->get();

        if ($stalledLeads->isEmpty()) {
            $this->info('Nenhum lead estagnado encontrado.');
            return;
        }

        $count = $stalledLeads->count();
        $this->info("Encontrados {$count} leads estagnados.");

        // Busca usuários admins para notificar
        $admins = User::where('is_admin', true)->get();

        foreach ($stalledLeads as $lead) {
            $days = $lead->updated_at->diffInDays(now());

            // Envia notificação via Filament Database Notifications
            Notification::make()
                ->title('⚠️ Lead Estagnado')
                ->body("O orçamento #{$lead->numero} de {$lead->cliente->nome} está parado há {$days} dias. Faça um follow-up!")
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('ver')
                        ->label('Ver Orçamento')
                        ->url(route('filament.admin.resources.orcamentos.edit', $lead))
                        ->button(),
                    \Filament\Notifications\Actions\Action::make('whatsapp')
                        ->label('Cobrar no Zap')
                        ->url(app(\App\Services\WhatsAppService::class)->getFollowUpLink($lead))
                        ->openUrlInNewTab(),
                ])
                ->sendToDatabase($admins);

            $this->info("Alerta enviado para o lead #{$lead->numero}");
        }

        $this->info('Processo concluído.');
    }
}
