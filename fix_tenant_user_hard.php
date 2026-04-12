<?php
require_once '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = \App\Models\Tenant::find('62b4015d-cf0c-4867-a548-6be3bd44197e');
if($t) {
    $t->run(function() {
        if(!\App\Models\User::where('email', 'maria@stofgard.com.br')->exists()) {
            \App\Models\User::create([
                'name' => 'MARIA DE JESUS SILVA',
                'email' => 'maria@stofgard.com.br',
                'password' => \Illuminate\Support\Facades\Hash::make('Mariagard2026@'),
                'is_admin' => true,
                'is_super_admin' => false,
                'tenant_id' => '62b4015d-cf0c-4867-a548-6be3bd44197e',
                'email_verified_at' => now(),
                'role' => 'dono'
            ]);
            echo "Tenant owner Maria successfully seeded inside tenant DB!\n";
        } else {
            echo "Maria already exists in tenant DB!\n";
        }
    });
}
