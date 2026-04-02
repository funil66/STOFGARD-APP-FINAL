<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$data = [
    'name' => 'Teste Browser',
    'slug' => 'testebrowser2',
    'plan' => 'pro',
    'pending_owner' => [
        'name' => 'Browser',
        'email' => 'browser@teste.com',
        'password' => '12345678'
    ]
];
$tenant = new App\Models\Tenant;
$tenant->fill($data);
$tenant->save();

var_dump($tenant->pending_owner);
