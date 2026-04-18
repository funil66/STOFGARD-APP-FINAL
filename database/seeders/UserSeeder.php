<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed development users in local/testing environments
        if (! app()->environment('local', 'testing')) {
            $this->command?->info('⚠️  UserSeeder skipped in non-local environment.');
            return;
        }

        $users = [
            [
                'name' => 'Admin Dev',
                'email' => 'admin@dev.local',
                'password' => Hash::make('admin'),
                'is_admin' => true,
            ],
            [
                'name' => 'Allisson Gonçalves de Sousa',
                'email' => 'allisson@autonomia.com.br',
                'password' => Hash::make('admin'),
                'is_admin' => true,
            ],
            [
                'name' => 'Maria de Jesus Silva',
                'email' => 'maria@autonomia.com.br',
                'password' => Hash::make('admin'),
                'is_admin' => true,
            ],
            [
                'name' => 'Jaelsa Maria Silva',
                'email' => 'jaelsa@autonomia.com.br',
                'password' => Hash::make('Autonomia Ilimitada2026'),
                'is_admin' => false,
            ],
            [
                'name' => 'Raelcia Maria Silva',
                'email' => 'raelcia@autonomia.com.br',
                'password' => Hash::make('Autonomia Ilimitada2026'),
                'is_admin' => false,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
