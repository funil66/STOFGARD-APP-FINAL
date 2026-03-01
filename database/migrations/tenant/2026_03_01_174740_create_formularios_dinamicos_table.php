<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('formularios_dinamicos', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // "Anamnese Estética", "Vistoria Ar-Condicionado"
            $table->string('tipo_servico')->nullable(); // Vincula a tipo de serviço
            $table->json('campos'); // Schema dos campos (Filament Builder format)
            $table->boolean('ativo')->default(true);
            $table->text('descricao')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formularios_dinamicos');
    }
};
