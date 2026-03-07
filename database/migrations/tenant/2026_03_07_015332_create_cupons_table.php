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
        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_indicador_id')->nullable()->constrained('cadastros')->nullOnDelete();
            $table->string('codigo')->unique();
            $table->decimal('desconto_percentual', 5, 2)->default(10.00);
            $table->boolean('usado')->default(false);
            $table->timestamp('data_expiracao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupons');
    }
};
