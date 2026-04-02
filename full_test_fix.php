<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = \App\Models\Tenant::latest()->first();

echo "\nTenant ID: {$tenant->id}\n";
echo "\nCentral Users:\n";
$centralUsers = \App\Models\User::where('tenant_id', $tenant->id)->get(['email', 'tenant_id']);
foreach($centralUsers as $u) echo "+ {$u->email} (T-ID: {$u->tenant_id})\n";

echo "\nTenant Users:\n";
$tenant->run(function() {
    $users = \App\Models\User::all(['email']); // Remove tenant_id since tenant DB doesn't have it
    foreach($users as $u) echo "+ {$u->email}\n";
});
