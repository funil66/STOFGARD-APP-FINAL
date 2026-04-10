<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pdf_generations') || !Schema::hasColumn('pdf_generations', 'orcamento_id')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE pdf_generations ALTER COLUMN orcamento_id DROP NOT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('pdf_generations') || !Schema::hasColumn('pdf_generations', 'orcamento_id')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE pdf_generations ALTER COLUMN orcamento_id SET NOT NULL');
    }
};
