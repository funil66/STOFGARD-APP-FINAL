<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$centralUser = App\Models\User::where('email', 'maria@stofgard.com.br')->first();
if ($centralUser) {
    $centralUser->password = \Illuminate\Support\Facades\Hash::make('Mariagard2026@');
    $centralUser->is_admin = true;
    $centralUser->is_super_admin = true; // Giving super admin just in case they are trying the central panel
    $centralUser->role = 'dono';
    $centralUser->save();
    echo "Updated central user password to Mariagard2026@\n";
    
    $tenantId = $centralUser->tenant_id;
    $t = \App\Models\Tenant::find($tenantId);

    if ($t) {
        tenancy()->initialize($t);
        // DB bypass to avoid global scopes
        $tenantUser = \DB::table('users')->where('email', 'maria@stofgard.com.br')->first();
        if ($tenantUser) {
            \DB::table('users')->where('email', 'maria@stofgard.com.br')->update([
                'password' => \Illuminate\Support\Facades\Hash::make('Mariagard2026@'),
                'is_admin' => true,
                'is_super_admin' => true,
                'role' => 'dono',
                'updated_at' => now()
            ]);
            echo "Updated tenant user password to Mariagard2026@\n";
        }
    }
}
