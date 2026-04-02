<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::create([
    "name" => "Test",
    "email" => "t2@example.com",
    "password" => bcrypt("12345")
]);
$u->delete();
echo "Deleted successfully no media library crash!\n";
