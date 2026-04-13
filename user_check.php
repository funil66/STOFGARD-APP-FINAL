<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$centralUser = App\Models\User::where('email', 'maria@stofgard.com.br')->first();
$tenantId = $centralUser->tenant_id;
$t = \App\Models\Tenant::find($tenantId);
tenancy()->initialize($t);

$tenantUser = \App\Models\User::where('email', 'maria@stofgard.com.br')->first();

echo "Central: is_admin=" . var_export($centralUser->is_admin, true) . ", role=" . $centralUser->role . "\n";
echo "Tenant: is_admin=" . var_export($tenantUser->is_admin, true) . ", role=" . $tenantUser->role . "\n";

