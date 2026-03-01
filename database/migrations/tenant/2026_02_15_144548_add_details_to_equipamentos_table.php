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
        Schema::table('equipamentos', function (Blueprint $table) {
            $table->string('numero_serie')->nullable()->after('codigo_patrimonio');
            $table->string('marca')->nullable()->after('numero_serie');
            $table->string('modelo')->nullable()->after('marca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipamentos', function (Blueprint $table) {
            $table->dropColumn(['numero_serie', 'marca', 'modelo']);
        });
    }
};
