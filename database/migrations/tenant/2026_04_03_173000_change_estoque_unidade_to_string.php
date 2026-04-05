<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('estoques', function (Blueprint $table) {
            $table->string('unidade', 50)->default('unidade')->change();
        });
    }

    public function down(): void
    {
        Schema::table('estoques', function (Blueprint $table) {
            // Note: Postgres cannot reliably convert back to enum if values exist
            $table->string('unidade')->change();
        });
    }
};
