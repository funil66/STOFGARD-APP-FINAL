<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tokens de acesso por magic link para o cliente final.
     * Não usa senha — apenas link temporário enviado via WhatsApp.
     */
    public function up(): void
    {
        Schema::create('cliente_acessos', function (Blueprint $table) {
            $table->id();

            // Cliente que receberá o link
            $table->foreignId('cadastro_id')->constrained('cadastros')->cascadeOnDelete();

            // Token único do magic link
            $table->string('token', 64)->unique();

            // Validade: expira em X horas após envio
            $table->timestamp('expires_at');

            // Rastreabilidade
            $table->timestamp('used_at')->nullable()->comment('Quando foi acessado pela 1ª vez');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            // Contexto do link (para analytics e segurança)
            $table->string('motivo')->default('portal')
                ->comment('portal | orcamento | os | nota_fiscal');
            $table->unsignedBigInteger('resource_id')->nullable()
                ->comment('ID do recurso relacionado (orcamento_id, os_id, etc.)');

            $table->timestamps();

            $table->index(['token', 'expires_at']);
            $table->index(['cadastro_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_acessos');
    }
};
