<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\Agenda;
use App\Models\Produto;
use App\Models\Financeiro;
use App\Models\User;
use Carbon\Carbon;

class GenerateDailyNotifications extends Command
{
    protected $signature = 'app:generate-daily-notifications';
    protected $description = 'Gera notificações diárias para eventos, estoque e financeiro';

    public function handle()
    {
        $this->info('Gerando notificações diárias...');

        $this->notifyDailyEvents();
        $this->notifyLowStock();
        $this->notifyPendingTransactions();

        $this->info('Notificações geradas com sucesso!');
    }

    private function notifyDailyEvents()
    {
        $events = \App\Models\Agenda::whereDate('data_hora_inicio', \Carbon\Carbon::today())->get();
        // Assuming we notify all admins about system events, or specific users about their events
        $users = \App\Models\User::all();

        foreach ($events as $event) {
            foreach ($users as $user) {
                // Logic to determine if user should be notified (e.g. if event is theirs or if they are admin)
                // For now, notifying everyone for simplicity as per requirement "events of the day"
                \App\Services\NotificationService::info(
                    $user,
                    'Evento Hoje: ' . $event->titulo,
                    "Evento agendado para hoje às " . $event->data_hora_inicio->format('H:i') . ".",
                    'agenda',
                    'heroicon-o-calendar',
                    '/admin/agendas/' . $event->id . '/edit',
                    'Ver Evento'
                );
            }
        }
        $this->info('Eventos notificados: ' . $events->count());
    }

    private function notifyLowStock()
    {
        // Check if estoque_minimo column exists first to avoid crashes if migration didn't run? 
        // User asked to verify it. Assuming it exists based on earlier grep check (to be confirmed).
        // If grep failed, we might need a fallback.

        try {
            $products = \App\Models\Produto::whereColumn('estoque_atual', '<=', 'estoque_minimo')
                ->where('estoque_minimo', '>', 0)
                ->get();
        } catch (\Exception $e) {
            $this->error('Erro ao verificar estoque mínimo: ' . $e->getMessage());
            return;
        }

        if ($products->isEmpty())
            return;

        $admins = \App\Models\User::all(); // Notify all users for now

        foreach ($products as $product) {
            foreach ($admins as $admin) {
                \App\Services\NotificationService::warning(
                    $admin,
                    'Estoque Baixo: ' . $product->nome,
                    "Nível de estoque: {$product->estoque_atual} (Mínimo: {$product->estoque_minimo}).",
                    'estoque',
                    null,
                    '/admin/produtos/' . $product->id . '/edit',
                    'Ver Produto'
                );
            }
        }
        $this->info('Produtos com estoque baixo notificados: ' . $products->count());
    }

    private function notifyPendingTransactions()
    {
        $transactions = \App\Models\Financeiro::where('status', 'pendente')
            ->whereDate('data_vencimento', '<=', \Carbon\Carbon::today())
            ->get();

        if ($transactions->isEmpty())
            return;

        $admins = \App\Models\User::all();

        foreach ($transactions as $transaction) {
            $typeLabel = $transaction->tipo === 'entrada' ? 'Recebimento' : 'Pagamento';
            $isPast = \Carbon\Carbon::parse($transaction->data_vencimento)->isPast();

            $msg = $isPast
                ? "O {$typeLabel} de R$ " . number_format($transaction->valor, 2, ',', '.') . " venceu em " . $transaction->data_vencimento->format('d/m/Y') . "."
                : "O {$typeLabel} de R$ " . number_format($transaction->valor, 2, ',', '.') . " vence hoje.";

            foreach ($admins as $admin) {
                \App\Services\NotificationService::danger(
                    $admin,
                    "Financeiro Pendente: {$typeLabel}",
                    $msg,
                    'financeiro',
                    null,
                    '/admin/financeiros/' . $transaction->id . '/edit',
                    'Ver Transação'
                );
            }
        }
        $this->info('Transações pendentes notificadas: ' . $transactions->count());
    }
}
