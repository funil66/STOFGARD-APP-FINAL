<?php
require_once '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $email = 'allissonsousa.adv@gmail.com';
    $password = 'Swordfish66@';
    
    $user = \App\Models\User::firstOrNew(['email' => $email]);
    $user->name = 'Super Admin Allisson';
    $user->password = \Illuminate\Support\Facades\Hash::make($password);
    $user->is_super_admin = true;
    $user->is_admin = true;
    $user->email_verified_at = now();
    
    // Check if tenant_id exists
    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'tenant_id')) {
        $tenantId = \App\Models\Tenant::first()->id ?? null;
        if(empty($user->tenant_id)) {
            $user->tenant_id = $tenantId;
        }
    }

    $user->save();
    
    echo "Super admin {$email} configured successfully!\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
