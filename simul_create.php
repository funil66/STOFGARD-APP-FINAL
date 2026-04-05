<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 'test_y_' . uniqid();
$t = \App\Models\Tenant::create(['id' => $id, 'name' => 'Test Flow Y', 'slug' => 'flow_' . $id]);
\Illuminate\Support\Facades\Cache::put('pending_owner_flow_' . $id, ['email'=>'flowy@f.com','name'=>'Flow Y','password'=>'1']);
echo "Created: $id\n";
