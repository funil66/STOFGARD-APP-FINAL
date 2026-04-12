<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['orcamentos', 'ordens_servico', 'agendas'];
        
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'cadastro_id')) {
                if (DB::getDriverName() === 'mysql') {
                    // Limpar strings vazias no mysql
                    try {
                        DB::statement("UPDATE {$table} SET cadastro_id = NULL WHERE cadastro_id = ''");
                        DB::statement("ALTER TABLE {$table} MODIFY COLUMN cadastro_id BIGINT UNSIGNED");
                    } catch (\Exception $e) {
                         // ignore
                    }
                } elseif (DB::getDriverName() === 'pgsql') {
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN cadastro_id TYPE BIGINT USING NULLIF(cadastro_id, '')::bigint");
                }
            }
        }
    }

    public function down(): void
    {
        // Reversão omitida para simplificar
    }
};
