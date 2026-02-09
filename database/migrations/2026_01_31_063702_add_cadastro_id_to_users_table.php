<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cadastro_id')->nullable()->constrained('cadastros')->nullOnDelete();
            $table->boolean('is_cliente')->default(false); // Flag para diferenciar login
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cadastro_id']);
            $table->dropColumn(['cadastro_id', 'is_cliente']);
        });
    }
};
