<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$data = [
    'name' => 'Teste Tenant User',
    'slug' => 'testetestuser1',
    'is_active' => true,
    'status_pagamento' => 'trial',
    'data' => [
        'pending_owner' => [
            'name' => 'Admin Teste',
            'email' => 'admintesteuser@example.com',
            'password' => 'password123'
        ]
    ]
];

$tenant = App\Models\Tenant::create($data);
echo "Created tenant " . $tenant->id . "\n";
var_dump($tenant->data);
var_dump($tenant->pending_owner);
