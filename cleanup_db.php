<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dbs = DB::table("pg_database")->where('datname', 'like', 'tenant%')->pluck('datname');
foreach($dbs as $dbName) { 
    $id = str_replace("tenant", "", $dbName); 
    if (!App\Models\Tenant::withTrashed()->find($id)) { 
        echo "Dropping orphan DB: $dbName\n"; 
        try { DB::statement("DROP DATABASE \"$dbName\""); } catch (Exception $e) { echo $e->getMessage()."\n"; } 
    } 
}
