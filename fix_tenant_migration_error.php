<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$centralUser = App\Models\User::where('email', 'maria@stofgard.com.br')->first();
$t = \App\Models\Tenant::find($centralUser->tenant_id);
if ($t) {
    tenancy()->initialize($t);
    $indexes = \DB::select("SHOW INDEX FROM cadastros WHERE Column_name = 'documento'");
    print_r($indexes);
}
