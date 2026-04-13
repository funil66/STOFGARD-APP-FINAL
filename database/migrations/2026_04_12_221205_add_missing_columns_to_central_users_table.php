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
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->nullable();
                $table->boolean('acesso_financeiro')->default(false);
                $table->unsignedBigInteger('local_estoque_id')->nullable();
                $table->unsignedBigInteger('cadastro_id')->nullable();
                $table->boolean('is_cliente')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'acesso_financeiro',
                'local_estoque_id',
                'cadastro_id',
                'is_cliente'
            ]);
        });
    }
};
