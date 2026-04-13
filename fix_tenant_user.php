<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$centralUser = App\Models\User::where('email', 'maria@stofgard.com.br')->first();
$tenantId = $centralUser->tenant_id;
$t = \App\Models\Tenant::find($tenantId);

if ($t) {
    tenancy()->initialize($t);
    $tenantUser = \DB::table('users')->where('email', 'maria@stofgard.com.br')->first();
    if (!$tenantUser) {
        \DB::table('users')->insert([
            'id' => $centralUser->id,
            'name' => 'Maria',
            'email' => 'maria@stofgard.com.br',
            'password' => \Illuminate\Support\Facades\Hash::make('Mariagard2026@'),
            'role' => 'dono',
            'is_super_admin' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "Tenant DB password updated and user inserted.\n";
    } else {
        \DB::table('users')->where('email', 'maria@stofgard.com.br')->update([
            'password' => \Illuminate\Support\Facades\Hash::make('Mariagard2026@')
        ]);
        echo "Tenant DB password updated.\n";
    }
} else {
    echo "Tenant not found!\n";
}
