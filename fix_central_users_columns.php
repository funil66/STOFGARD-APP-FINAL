<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (!Schema::connection('mysql')->hasColumn('users', 'role')) {
    Schema::connection('mysql')->table('users', function (Blueprint $table) {
        $table->string('role')->nullable();
        $table->boolean('acesso_financeiro')->default(false);
        $table->unsignedBigInteger('local_estoque_id')->nullable();
        $table->unsignedBigInteger('cadastro_id')->nullable();
        $table->boolean('is_cliente')->default(false);
    });
    echo "Columns added to central users table.\n";
} else {
    echo "Columns already exist.\n";
}
