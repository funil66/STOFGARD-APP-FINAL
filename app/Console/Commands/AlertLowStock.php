<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Estoque;
use App\Models\User;
use Filament\Notifications\Notification;

class AlertLowStock extends Command
{
    protected $signature = 'estoque:alert-low';

    protected $description = 'Verifica níveis de estoque e alerta admins se estiverem baixos';

    public function handle()
    {
        $items = Estoque::all()->filter(fn($item) => $item->isAbaixoDoMinimo());

        if ($items->isEmpty()) {
            $this->info('Estoque OK.');
            return;
        }

        $count = $items->count();
        $this->info("Encontrados {$count} itens com estoque baixo.");

        $admins = User::where('is_admin', true)->get();

        foreach ($items as $item) {
            Notification::make()
                ->title('⚠️ Estoque Baixo')
                ->body("O item '{$item->item}' está com apenas {$item->quantidade} {$item->unidade}. (Mínimo: {$item->minimo_alerta})")
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('comprar')
                        ->label('Ver Estoque')
                        ->url(route('filament.admin.resources.estoques.index'))
                        ->button(),
                ])
                ->sendToDatabase($admins);

            $this->info("Alerta enviado para: {$item->item}");
        }

        $this->info('Verificação concluída.');
    }
}
