<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('financeiros', function (Blueprint $table) {
            if (!Schema::hasColumn('financeiros', 'assinatura_recibo')) {
                $table->text('assinatura_recibo')->nullable()->comment('Assinatura do Recibo');
                $table->string('assinatura_recibo_path')->nullable();
                $table->json('assinatura_recibo_metadata')->nullable();
                $table->string('assinatura_recibo_pdf_hash')->nullable();
                $table->string('assinatura_recibo_ip')->nullable();
                $table->string('assinatura_recibo_user_agent')->nullable();
                $table->timestamp('assinatura_recibo_timestamp')->nullable();
                $table->string('assinatura_recibo_hash')->nullable();
                $table->string('recibo_selo')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financeiros', function (Blueprint $table) {
            if (Schema::hasColumn('financeiros', 'assinatura_recibo')) {
                $table->dropColumn([
                    'assinatura_recibo',
                    'assinatura_recibo_path',
                    'assinatura_recibo_metadata',
                    'assinatura_recibo_pdf_hash',
                    'assinatura_recibo_ip',
                    'assinatura_recibo_user_agent',
                    'assinatura_recibo_timestamp',
                    'assinatura_recibo_hash',
                    'recibo_selo',
                ]);
            }
        });
    }
};
