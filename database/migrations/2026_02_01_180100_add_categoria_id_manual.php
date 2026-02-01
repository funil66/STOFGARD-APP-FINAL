<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Adicionar coluna categoria_id diretamente via SQL, mas só se a tabela existir
        if (Schema::hasTable('financeiros')) {
            if (! Schema::hasColumn('financeiros', 'categoria_id')) {
                DB::statement('ALTER TABLE financeiros ADD COLUMN categoria_id INTEGER');
            }

            DB::statement('CREATE INDEX IF NOT EXISTS idx_financeiros_categoria_id ON financeiros(categoria_id)');
        } else {
            if (app()->runningInConsole()) {
                echo "⚠ Tabela 'financeiros' não existe — pulando add_categoria_id_manual\n";
            }
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE financeiros DROP COLUMN categoria_id');
    }
};