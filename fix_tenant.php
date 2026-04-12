<?php
require_once '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = \App\Models\Tenant::find('62b4015d-cf0c-4867-a548-6be3bd44197e');
if($t) {
    echo "Found tenant: " . $t->id . "\n";
    $job = new \App\Jobs\CreateTenantOwnerJob($t);
    $job->handle();
    echo "Tenant user created.\n";
} else {
    echo "Tenant not found.\n";
}
