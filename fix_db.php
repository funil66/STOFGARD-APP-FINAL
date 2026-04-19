<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config(['database.default' => 'pgsql']);
config(['database.connections.pgsql.host' => '172.17.0.1']);
config(['database.connections.pgsql.port' => '5432']);
config(['database.connections.pgsql.database' => 'stofgard']);
config(['database.connections.pgsql.username' => 'sail']);
config(['database.connections.pgsql.password' => 'password']);

try {
    $users = App\Models\User::all();
    foreach ($users as $u) {
        $u->password = Hash::make('Felina66@');
        $u->name = 'Funil';
        $u->save();
    }
    echo "All user passwords updated successfully.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
