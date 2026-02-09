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
        Schema::table('cadastros', function (Blueprint $table) {
            $table->string('especialidade')->nullable()->after('tipo')
                ->comment('Qualificação do parceiro: Arquiteto, Advogado, etc');
        });
    }

    public function down(): void
    {
        Schema::table('cadastros', function (Blueprint $table) {
            $table->dropColumn('especialidade');
        });
    }
};
