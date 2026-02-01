<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Adicionar coluna categoria_id diretamente via SQL
        DB::statement('ALTER TABLE financeiros ADD COLUMN categoria_id INTEGER');
        DB::statement('CREATE INDEX idx_financeiros_categoria_id ON financeiros(categoria_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE financeiros DROP COLUMN categoria_id');
    }
};