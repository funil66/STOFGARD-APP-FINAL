<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@stofgard.com')->first();

        if ($admin) {
            // Notificações de exemplo
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'success',
                'title' => 'Sistema iniciado com sucesso!',
                'message' => 'O sistema STOFGARD foi configurado e está pronto para uso.',
                'module' => 'sistema',
                'icon' => 'heroicon-o-check-circle',
            ]);

            Notification::create([
                'user_id' => $admin->id,
                'type' => 'info',
                'title' => 'Bem-vindo ao STOFGARD 2026',
                'message' => 'Configure os módulos e comece a gerenciar seus clientes e ordens de serviço.',
                'module' => 'dashboard',
                'icon' => 'heroicon-o-information-circle',
            ]);

            Notification::create([
                'user_id' => $admin->id,
                'type' => 'warning',
                'title' => 'Módulos em desenvolvimento',
                'message' => 'Alguns módulos ainda estão sendo implementados. Fique atento às atualizações!',
                'module' => 'sistema',
                'icon' => 'heroicon-o-exclamation-triangle',
            ]);
        }
    }
}
