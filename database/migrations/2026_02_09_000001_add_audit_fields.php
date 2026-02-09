<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adiciona campos de auditoria (created_by, updated_by) às tabelas principais.
     * Registra quem criou e quem editou cada registro.
     */
    public function up(): void
    {
        $tables = [
            'orcamentos',
            'ordens_servico',
            'financeiros',
            'agendas',
            'estoques',
            'cadastros',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    if (!Schema::hasColumn($table, 'created_by')) {
                        $blueprint->foreignId('created_by')
                            ->nullable()
                            ->constrained('users')
                            ->nullOnDelete();
                    }

                    if (!Schema::hasColumn($table, 'updated_by')) {
                        $blueprint->foreignId('updated_by')
                            ->nullable()
                            ->constrained('users')
                            ->nullOnDelete();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'orcamentos',
            'ordens_servico',
            'financeiros',
            'agendas',
            'estoques',
            'cadastros',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    if (Schema::hasColumn($table, 'created_by')) {
                        $blueprint->dropConstrainedForeignId('created_by');
                    }
                    if (Schema::hasColumn($table, 'updated_by')) {
                        $blueprint->dropConstrainedForeignId('updated_by');
                    }
                });
            }
        }
    }
};
