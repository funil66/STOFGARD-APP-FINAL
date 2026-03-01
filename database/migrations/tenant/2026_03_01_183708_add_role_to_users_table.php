<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adiciona a coluna `role` à tabela `users` do tenant.
     * Roles: dono, funcionario, secretaria (padrão: dono para o 1º usuário)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['dono', 'funcionario', 'secretaria'])
                ->default('dono')
                ->after('email')
                ->comment('Papel do usuário dentro do tenant');

            $table->boolean('acesso_financeiro')
                ->default(true)
                ->after('role')
                ->comment('Se false, oculta o módulo financeiro para este usuário');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'acesso_financeiro']);
        });
    }
};
