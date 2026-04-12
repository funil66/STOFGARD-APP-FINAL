<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pdf_generations') && Schema::hasColumn('pdf_generations', 'orcamento_id')) {
            if (DB::getDriverName() === 'sqlite') {
                return;
            }

            if (DB::getDriverName() === 'pgsql') {
                 DB::statement('ALTER TABLE pdf_generations ALTER COLUMN orcamento_id DROP NOT NULL');
            } else {
                 Schema::table('pdf_generations', function (Blueprint $table) {
                     $table->unsignedBigInteger('orcamento_id')->nullable()->change();
                 });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pdf_generations') && Schema::hasColumn('pdf_generations', 'orcamento_id')) {
            if (DB::getDriverName() === 'sqlite') {
                return;
            }

            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE pdf_generations ALTER COLUMN orcamento_id SET NOT NULL');
            } else {
                 Schema::table('pdf_generations', function (Blueprint $table) {
                     $table->unsignedBigInteger('orcamento_id')->nullable(false)->change();
                 });
            }
        }
    }
};
