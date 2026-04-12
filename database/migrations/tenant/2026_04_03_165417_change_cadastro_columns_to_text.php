<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cadastros', function (Blueprint $table) {
            $indexes = Schema::getIndexes('cadastros');
            $indexNames = array_column($indexes, 'name');

            if (in_array('cadastros_documento_unique', $indexNames)) {
                $table->dropUnique('cadastros_documento_unique');
            }

            if (in_array('cadastros_email_unique', $indexNames)) {
                $table->dropUnique('cadastros_email_unique');
            }
        });

        Schema::table('cadastros', function (Blueprint $table) {
            $table->text('email')->nullable()->change();
            $table->text('telefone')->nullable()->change();
            $table->text('celular')->nullable()->change();
            $table->text('documento')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cadastros', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->change();
            $table->string('telefone', 255)->nullable()->change();
            $table->string('celular', 255)->nullable()->change();
            $table->string('documento', 255)->nullable()->change();
        });
    }
};
