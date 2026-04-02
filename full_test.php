<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate Filament form data mutation
$data = [
    'name' => 'Visual Test',
    'owner_name' => 'Visual Owner',
    'owner_email' => 'visual_' . time() . '@test.com',
    'owner_password' => 'secret1234',
    'slug' => 'visual' . time(),
    'plan' => 'pro'
];

$page = new \App\Filament\SuperAdmin\Resources\TenantResource\Pages\CreateTenant();
$mutateMethod = new ReflectionMethod($page, 'mutateFormDataBeforeCreate');
$mutateMethod->setAccessible(true);
$mutatedData = $mutateMethod->invoke($page, $data);

// Create the tenant record exactly like Filament does
$tenant = \App\Models\Tenant::create($mutatedData);
echo "Created tenant {$tenant->id}\n";
echo "Pending Owner:\n";
print_r($tenant->getAttribute('pending_owner'));

echo "\nProcessing Jobs...\n";
\Illuminate\Support\Facades\Artisan::call('queue:work', ['--stop-when-empty' => true]);

echo "\nCentral Users:\n";
$centralUsers = \App\Models\User::where('tenant_id', $tenant->id)->get(['email', 'tenant_id']);
foreach($centralUsers as $u) echo "+ {$u->email} (T-ID: {$u->tenant_id})\n";

echo "\nTenant Users:\n";
$tenant->run(function() {
    $users = \App\Models\User::all(['email', 'tenant_id']);
    foreach($users as $u) echo "+ {$u->email}\n";
});
