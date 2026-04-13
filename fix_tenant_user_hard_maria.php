<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$centralUser = App\Models\User::where('email', 'maria@stofgard.com.br')->first();
$tenantId = $centralUser->tenant_id;
$t = \App\Models\Tenant::find($tenantId);

if($t) {
    tenancy()->initialize($t);
    $tenantUser = \App\Models\User::where('email', 'maria@stofgard.com.br')->first();
    if(!$tenantUser) {
        \DB::table('users')->insert([
            'name' => 'MARIA DE JESUS SILVA',
            'email' => 'maria@stofgard.com.br',
            'password' => \Illuminate\Support\Facades\Hash::make('Mudar123!'),
            'is_admin' => true,
            'is_super_admin' => false,
            // 'tenant_id' => $tenantId, // REMOVED
            'email_verified_at' => now(),
            'role' => 'dono',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "Tenant owner Maria successfully seeded inside tenant DB ($tenantId) with new password!\n";
    } else {
        \DB::table('users')->where('email', 'maria@stofgard.com.br')->update([
            'password' => \Illuminate\Support\Facades\Hash::make('Mudar123!'),
            'is_admin' => true,
            'is_super_admin' => false,
            'role' => 'dono',
            'updated_at' => now()
        ]);
        echo "Maria already exists in tenant DB, updated password to Mudar123!\n";
    }

    $centralUser->password = \Illuminate\Support\Facades\Hash::make('Mudar123!');
    $centralUser->save();
} else {
    echo "Tenant NOT FOUND.\n";
}
