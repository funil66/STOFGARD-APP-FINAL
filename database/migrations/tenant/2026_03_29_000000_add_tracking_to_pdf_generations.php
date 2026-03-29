<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdf_generations', function (Blueprint $table) {
            if (!Schema::hasColumn('pdf_generations', 'tipo')) {
                $table->string('tipo')->nullable()->after('url');
            }
            if (!Schema::hasColumn('pdf_generations', 'modelo_id')) {
                $table->string('modelo_id')->nullable()->after('tipo');
            }
            if (!Schema::hasColumn('pdf_generations', 'status')) {
                $table->string('status')->default('processing')->after('modelo_id');
            }
            if (!Schema::hasColumn('pdf_generations', 'file_path')) {
                $table->string('file_path')->nullable()->after('status');
            }
            if (!Schema::hasColumn('pdf_generations', 'error_message')) {
                $table->text('error_message')->nullable()->after('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pdf_generations', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'modelo_id', 'status', 'file_path', 'error_message']);
        });
    }
};
