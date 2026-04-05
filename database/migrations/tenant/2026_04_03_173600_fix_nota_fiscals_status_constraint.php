<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Só rodar se não for SQLite para não quebrar os testes. SQLite não suporta essa sintaxe ALTER TABLE DROP CONSTRAINT.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE nota_fiscals DROP CONSTRAINT IF EXISTS nota_fiscals_status_check');
        }
    }

    public function down(): void
    {
        // Nothing here.
    }
};
