<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            if (!Schema::hasColumn('categorias', 'cor')) {
                $table->string('cor')->nullable()->after('tipo');
            }
            if (!Schema::hasColumn('categorias', 'icone')) {
                $table->string('icone')->nullable()->after('cor');
            }
            if (!Schema::hasColumn('categorias', 'descricao')) {
                $table->text('descricao')->nullable()->after('icone');
            }
            if (!Schema::hasColumn('categorias', 'ordem')) {
                $table->integer('ordem')->default(0)->after('sistema');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn(['cor', 'icone', 'descricao', 'ordem']);
        });
    }
};
