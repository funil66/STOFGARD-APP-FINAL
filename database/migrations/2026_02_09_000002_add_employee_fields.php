<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona campos para funcionários na tabela cadastros.
     * Permite tipo 'funcionario' com cargo, salário, data de admissão, e configurações de pró-labore.
     */
    public function up(): void
    {
        Schema::table('cadastros', function (Blueprint $table) {
            // Campos específicos para funcionários
            $table->string('cargo')->nullable()->after('tipo');
            $table->decimal('salario_base', 12, 2)->nullable()->after('cargo');
            $table->date('data_admissao')->nullable()->after('salario_base');
            $table->date('data_demissao')->nullable()->after('data_admissao');
            $table->boolean('is_socio')->default(false)->after('data_demissao');
            $table->decimal('percentual_prolabore', 5, 2)->nullable()->after('is_socio');
        });

        // Adicionar funcionario_id às ordens de serviço para atribuição de técnico
        Schema::table('ordens_servico', function (Blueprint $table) {
            if (! Schema::hasColumn('ordens_servico', 'funcionario_id')) {
                $table->foreignId('funcionario_id')
                    ->nullable()
                    ->after('vendedor_id')
                    ->constrained('cadastros')
                    ->nullOnDelete();
            }
        });

        // Adicionar funcionario_id às agendas para atribuição de responsável
        Schema::table('agendas', function (Blueprint $table) {
            if (! Schema::hasColumn('agendas', 'funcionario_id')) {
                $table->foreignId('funcionario_id')
                    ->nullable()
                    ->after('orcamento_id')
                    ->constrained('cadastros')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            if (Schema::hasColumn('agendas', 'funcionario_id')) {
                $table->dropConstrainedForeignId('funcionario_id');
            }
        });

        Schema::table('ordens_servico', function (Blueprint $table) {
            if (Schema::hasColumn('ordens_servico', 'funcionario_id')) {
                $table->dropConstrainedForeignId('funcionario_id');
            }
        });

        Schema::table('cadastros', function (Blueprint $table) {
            $table->dropColumn([
                'cargo',
                'salario_base',
                'data_admissao',
                'data_demissao',
                'is_socio',
                'percentual_prolabore',
            ]);
        });
    }
};
