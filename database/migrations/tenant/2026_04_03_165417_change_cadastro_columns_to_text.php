<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Se usar schema->table(dropIndex), ele lança erro se não existir mesmo com try/catch em algumas versões de Laravel/MySQL
        // O ideal é usar DB::statement com ignore error, ou apenas fazer o DB modify com query direta
        
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE cadastros MODIFY COLUMN email TEXT NULL');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE cadastros MODIFY COLUMN telefone TEXT NULL');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE cadastros MODIFY COLUMN celular TEXT NULL');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE cadastros MODIFY COLUMN documento TEXT NULL');
    }

    public function down(): void
    {
        Schema::table('cadastros', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->change();
            $table->string('telefone', 255)->nullable()->change();
            $table->string('celular', 255)->nullable()->change();
            $table->string('documento', 255)->nullable()->change();
        });
    }
};
