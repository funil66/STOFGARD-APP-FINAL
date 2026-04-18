<?php

namespace App\Listeners;

use Laravel\Horizon\Events\LongWaitDetected;

class HorizonLongWaitDetectedListener
{
    /**
     * Handle the event.
     */
    public function handle(LongWaitDetected $event): void
    {
        // Se a fila engasgar (baseado no threshold de config/horizon.php waits), alerta no Sentry
        if (app()->bound('sentry')) {
            \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($event): void {
                $scope->setTag('horizon.queue', $event->queue);
                $scope->setTag('horizon.connection', $event->connection);
                $scope->setExtra('wait_seconds', $event->seconds);

                // Dispara um evento para o Sentry de forma proativa
                \Sentry\captureMessage(
                    "CRÍTICO - Fila Horizon Engasgada: A fila [{$event->queue}] na conexão [{$event->connection}] está aguardando há mais de {$event->seconds} segundos."
                );
            });
        }
    }
}
