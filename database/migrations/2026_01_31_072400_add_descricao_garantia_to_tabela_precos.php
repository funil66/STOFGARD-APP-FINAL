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
        Schema::table('tabela_precos', function (Blueprint $table) {
            $table->text('descricao_tecnica')->nullable()->after('observacoes');
            $table->integer('dias_garantia')->default(7)->after('descricao_tecnica');
        });

        // Seed default values for existing service types
        \DB::table('tabela_precos')
            ->where('tipo_servico', 'higienizacao')
            ->whereNull('descricao_tecnica')
            ->update([
                'descricao_tecnica' => "HIGIENIZAÇÃO\nBiossanitização Profunda: Extração de alta pressão para eliminação de biofilmes, ácaros e bactérias, garantindo assepsia total das fibras e neutralização de odores.",
                'dias_garantia' => 7,
            ]);

        \DB::table('tabela_precos')
            ->where('tipo_servico', 'impermeabilizacao')
            ->whereNull('descricao_tecnica')
            ->update([
                'descricao_tecnica' => "IMPERMEABILIZAÇÃO\nEscudo hidrofóbico invisível que repele líquidos e óleos, preservando a cor e textura original do tecido. Proteção contra manchas e facilitação da limpeza.",
                'dias_garantia' => 365,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tabela_precos', function (Blueprint $table) {
            $table->dropColumn(['descricao_tecnica', 'dias_garantia']);
        });
    }
};
