<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordem_servico_items', function (Blueprint $table) {
            if (!Schema::hasColumn('ordem_servico_items', 'servico_tipo')) {
                $table->string('servico_tipo')->nullable()->after('descricao');
            }

            if (!Schema::hasColumn('ordem_servico_items', 'perfil_garantia_id')) {
                $table->unsignedBigInteger('perfil_garantia_id')->nullable()->after('servico_tipo');
                $table->index('perfil_garantia_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordem_servico_items', function (Blueprint $table) {
            if (Schema::hasColumn('ordem_servico_items', 'perfil_garantia_id')) {
                $table->dropIndex(['perfil_garantia_id']);
                $table->dropColumn('perfil_garantia_id');
            }

            if (Schema::hasColumn('ordem_servico_items', 'servico_tipo')) {
                $table->dropColumn('servico_tipo');
            }
        });
    }
};
