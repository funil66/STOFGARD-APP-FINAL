<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Usa o sistema de notificações nativo do Laravel (DatabaseNotification).
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@stofgard.com')->first();

        if ($admin) {
            // Notificação de boas-vindas usando o sistema nativo do Laravel
            $admin->notify(new \Illuminate\Notifications\AnonymousNotifiable());

            // Notificações de exemplo usando notifyNow diretamente na tabela
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'App\Notifications\SistemaNotification',
                    'notifiable_type' => \App\Models\User::class,
                    'notifiable_id' => $admin->id,
                    'data' => json_encode([
                        'type' => 'success',
                        'title' => 'Sistema iniciado com sucesso!',
                        'message' => 'O sistema STOFGARD foi configurado e está pronto para uso.',
                        'module' => 'sistema',
                    ]),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'App\Notifications\SistemaNotification',
                    'notifiable_type' => \App\Models\User::class,
                    'notifiable_id' => $admin->id,
                    'data' => json_encode([
                        'type' => 'info',
                        'title' => 'Bem-vindo ao STOFGARD 2026',
                        'message' => 'Configure os módulos e comece a gerenciar seus clientes e ordens de serviço.',
                        'module' => 'dashboard',
                    ]),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
