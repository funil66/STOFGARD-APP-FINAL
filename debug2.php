<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$t = App\Models\Tenant::latest()->first();

$job = new App\Jobs\CreateTenantOwnerJob($t);
$job->handle();

echo "Central: " . App\Models\User::where('tenant_id', $t->id)->count() . "\n";
$t->run(function() {
    echo "Tenant: " . App\Models\User::count() . "\n";
});
