<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$t = App\Models\Tenant::create([
    'name' => 'Teste Pipeline',
    'slug' => 'testepipe'.time(),
    'is_active' => true,
    'pending_owner' => [
        'name' => 'Owner Pipe',
        'email' => 'pipe'.time().'@example.com',
        'password' => '123456789'
    ]
]);

echo "Tenant created: " . $t->id . "\n";
Artisan::call('queue:work --stop-when-empty');
echo "Queue finished.\n";
