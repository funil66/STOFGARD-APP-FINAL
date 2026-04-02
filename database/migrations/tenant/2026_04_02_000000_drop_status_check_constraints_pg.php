<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dropping specific ENUM-generated check constraints from Postgres
        // Since Laravel's enum() creates check constraints in PostgreSQL like "table_column_check",
        // and we altered them to string/varchar later, those old constraints remain and block inserts.
        
        $constraintsToDrop = [
            'orcamentos' => ['orcamentos_status_check'],
            'financeiros' => ['financeiros_status_check', 'financeiros_tipo_check'],
            'ordem_servicos' => ['ordem_servicos_status_check'],
            'cadastros' => ['cadastros_tipo_check', 'cadastros_status_check'],
            'tarefas' => ['tarefas_status_check', 'tarefas_prioridade_check'],
        ];

        foreach ($constraintsToDrop as $table => $constraints) {
            if (Schema::hasTable($table)) {
                foreach ($constraints as $constraint) {
                    try {
                        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
                    } catch (\Exception $e) {
                        // Constraint might not exist on all environments, ignore if failing
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No safe generic way to recreate them accurately without knowing previous states.
    }
};
