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
        Schema::table('nota_fiscals', function (Blueprint $table) {
            $table->string('provedor_referencia_id')->nullable()->after('numero_nf')->comment('ID retornado pela API do provedor (ex: FocusNFe)');
            $table->string('status_sefaz')->nullable()->after('status')->comment('Status exato retornado pela SEFAZ/Prefeitura');
            $table->text('erros_processamento')->nullable()->after('status_sefaz');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nota_fiscals', function (Blueprint $table) {
            $table->dropColumn(['provedor_referencia_id', 'status_sefaz', 'erros_processamento']);
        });
    }
};
