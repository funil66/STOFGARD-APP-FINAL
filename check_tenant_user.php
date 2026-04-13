<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$centralUser = App\Models\User::where('email', 'maria@stofgard.com.br')->first();
$t = \App\Models\Tenant::find($centralUser->tenant_id);
tenancy()->initialize($t);
$tu = \DB::table('users')->where('email', 'maria@stofgard.com.br')->first();
echo "Tenant User ID = {$tu->id}, Updated_at = {$tu->updated_at}\n";
