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
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->json('config_prazo_garantia')->nullable()->after('pdf_layout');
        });

        // Seed default warranty configurations
        DB::table('configuracoes')->update([
            'config_prazo_garantia' => json_encode([
                [
                    'tipo_servico' => 'higienizacao',
                    'dias' => 90,
                    'descricao' => 'Garantia contra manchas, odores e sujeiras reaparecendo'
                ],
                [
                    'tipo_servico' => 'impermeabilizacao',
                    'dias' => 365,
                    'descricao' => 'Garantia contra infiltrações e vazamentos'
                ],
                [
                    'tipo_servico' => 'combo',
                    'dias' => 365,
                    'descricao' => 'Garantia total higienização + impermeabilização'
                ],
            ])
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->dropColumn('config_prazo_garantia');
        });
    }
};
