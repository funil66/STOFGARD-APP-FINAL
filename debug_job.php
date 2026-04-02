<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$t = App\Models\Tenant::latest()->first();

echo "Raw data: " . var_export($t->data, true) . "\n";
echo "Pending Owner: " . var_export($t->pending_owner, true) . "\n";
