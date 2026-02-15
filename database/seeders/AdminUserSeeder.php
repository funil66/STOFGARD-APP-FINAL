<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Allisson Gonçalves de Sousa',
                'email' => 'allisson@stofgard.com.br',
                'password' => Hash::make('admin'),
                'is_admin' => true,
            ],
            [
                'name' => 'Maria de Jesus Silva',
                'email' => 'maria@stofgard.com.br',
                'password' => Hash::make('admin'),
                'is_admin' => true,
            ],
        ];

        foreach ($admins as $adminData) {
            User::updateOrCreate(
                ['email' => $adminData['email']],
                $adminData
            );
        }
    }
}
