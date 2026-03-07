<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orcamento_items', function (Blueprint $table) {
            $table->string('opcao', 1)->default('A')->after('orcamento_id');
        });
    }

    public function down(): void
    {
        Schema::table('orcamento_items', function (Blueprint $table) {
            $table->dropColumn('opcao');
        });
    }
};
