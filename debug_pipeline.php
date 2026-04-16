<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenant = App\Models\Tenant::find('d29e543c-6166-4214-909e-732169f47ea0');
if (!$tenant) { echo "Tenant not found.\n"; exit; }
echo "Tenant found!\n";

try {
    echo "Running seeds...\n";
    (new Stancl\Tenancy\Jobs\SeedDatabase())->handle($tenant);
    echo "-- Seed success\n";
    
    echo "Applying template...\n";
    $job1 = new App\Jobs\ApplyTenantTemplateJob($tenant);
    $job1->handle();
    echo "-- Template success\n";
    
    echo "Creating owner...\n";
    $job2 = new App\Jobs\CreateTenantOwnerJob($tenant);
    $job2->handle();
    echo "-- Owner success\n";
} catch (\Throwable $e) {
    echo "\n[ERROR] " . get_class($e) . "\n" . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
