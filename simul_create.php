<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$slug = 'tenant' . time();
$data = [
    'name' => 'Fake Store',
    'slug' => $slug,
    'is_active' => true,
    'status_pagamento' => 'trial',
    'trial_termina_em' => now()->addDays(14),
    'os_criadas_mes_atual' => 0,
    'pending_owner' => [
        'name' => 'Owner Fake',
        'email' => "fakeowner_{$slug}@example.com",
        'password' => 'fake12345'
    ]
];
$t = App\Models\Tenant::create($data);
echo "Simulating queue...\n";
\Illuminate\Support\Facades\Artisan::call('queue:work', ['--stop-when-empty' => true]);
echo "Tenant DB Users: \n";
$t->run(function() {
    echo App\Models\User::count() . "\n";
});
echo "Central DB Users for tenant: \n";
echo App\Models\User::where('tenant_id', $t->id)->count() . "\n";
