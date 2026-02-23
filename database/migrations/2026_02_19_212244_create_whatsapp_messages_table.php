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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();

            // Chave Estrangeira — vincula ao cadastro (cliente)
            $table->foreignId('cadastro_id')->nullable()->constrained('cadastros')->nullOnDelete();

            // Dados da API da Evolution
            $table->string('remote_message_id')->unique()->nullable(); // ID único da mensagem no WhatsApp
            $table->string('remote_jid'); // Número real com sufixo (ex: 5511999999999@s.whatsapp.net)

            // Conteúdo
            $table->text('body')->nullable(); // Texto da mensagem
            $table->string('type')->default('text'); // text, image, audio, video, document
            $table->string('direction'); // 'in' = recebida do cliente, 'out' = enviada pelo sistema
            $table->string('status')->default('sent'); // sent, delivered, read, failed

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
