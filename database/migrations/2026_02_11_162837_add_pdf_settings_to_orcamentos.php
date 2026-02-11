<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            // #2a: Toggles for PDF sections
            $table->boolean('pdf_mostrar_comissoes')->default(true);
            $table->boolean('pdf_mostrar_parcelamento')->default(true);

            // #2b: Per-orçamento aliquota overrides (null = use Settings defaults)
            $table->decimal('pdf_desconto_pix_percentual', 5, 2)->nullable();
            $table->json('pdf_parcelamento_custom')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn([
                'pdf_mostrar_comissoes',
                'pdf_mostrar_parcelamento',
                'pdf_desconto_pix_percentual',
                'pdf_parcelamento_custom',
            ]);
        });
    }
};
