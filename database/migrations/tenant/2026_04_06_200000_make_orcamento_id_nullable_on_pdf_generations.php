<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pdf_generations') || !Schema::hasColumn('pdf_generations', 'orcamento_id')) {
            return;
        }

        Schema::table('pdf_generations', function (Blueprint $table) {
            $table->unsignedBigInteger('orcamento_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pdf_generations') || !Schema::hasColumn('pdf_generations', 'orcamento_id')) {
            return;
        }

        Schema::table('pdf_generations', function (Blueprint $table) {
            $table->unsignedBigInteger('orcamento_id')->nullable(false)->change();
        });
    }
};
