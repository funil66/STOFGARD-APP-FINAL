<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$centralUser = App\Models\User::where('email', 'maria@stofgard.com.br')->first();
$t = \App\Models\Tenant::find($centralUser->tenant_id);

if ($t) {
    tenancy()->initialize($t);
    \DB::table('users')->where('email', 'maria@stofgard.com.br')->update([
        'is_admin' => 1
    ]);
    echo "Tenant user updated to is_admin=1.\n";
} else {
    echo "Tenant not found.\n";
}
