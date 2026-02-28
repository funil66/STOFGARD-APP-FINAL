<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adiciona colunas de controle de acesso do Super Admin.
     *
     * is_super_admin: TRUE apenas para membros da equipe técnica Stofgard.
     *   Ativa acesso ao painel /super-admin (SuperAdminPanelProvider).
     *
     * last_login_at: Timestamp do último login para auditoria de tenants.
     *   Exibido no TenantResource para identificar usuários inativos.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('is_admin');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_super_admin', 'last_login_at']);
        });
    }
};
