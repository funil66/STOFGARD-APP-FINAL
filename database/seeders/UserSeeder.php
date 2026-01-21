<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Allisson Gonçalves de Sousa',
                'email' => 'allisson@stofgard.com.br',
                'password' => Hash::make('Swordfish'),
                'is_admin' => true,
            ],
            [
                'name' => 'Maria de Jesus Silva',
                'email' => 'maria@stofgard.com.br',
                'password' => Hash::make('Stofgard2026'),
                'is_admin' => false,
            ],
            [
                'name' => 'Jaelsa Maria Silva',
                'email' => 'jaelsa@stofgard.com.br',
                'password' => Hash::make('Stofgard2026'),
                'is_admin' => false,
            ],
            [
                'name' => 'Raelcia Maria Silva',
                'email' => 'raelcia@stofgard.com.br',
                'password' => Hash::make('Stofgard2026'),
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
