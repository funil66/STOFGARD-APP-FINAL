<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$t = App\Models\Tenant::latest()->first();
echo "Tenant ID: {$t->id}\n";
$c = App\Models\User::where('tenant_id', $t->id)->count();
echo "Central Users for Tenant: {$c}\n";

$t->run(function() {
    $u = App\Models\User::count();
    echo "Tenant Users in tenant DB: {$u}\n";
});
