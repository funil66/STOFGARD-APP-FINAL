<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Cria a tabela central de tenants.
 *
 * Esta é a tabela-mãe do sistema multi-tenant.
 * Cada tenant = uma empresa/cliente do SaaS.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            $table->string('name');                               // "Stofgard São Paulo"
            $table->string('slug')->unique();                     // "sp" → sp.stofgard.com.br
            $table->string('domain')->nullable()->unique();       // Domínio customizado (opcional)

            $table->enum('plan', ['free', 'starter', 'pro', 'enterprise'])->default('starter');

            $table->boolean('is_active')->default(true);

            $table->jsonb('settings')->nullable();                // Configs específicas do tenant
            $table->unsignedInteger('max_users')->default(10);
            $table->unsignedInteger('max_orcamentos_mes')->default(100);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
