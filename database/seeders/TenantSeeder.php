<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder: TenantSeeder
 *
 * Cria o tenant padrão (necessário para ambientes de desenvolvimento e CI).
 * Em produção, este seeder deve ser rodado 1x antes de qualquer migration.
 *
 * USO:
 *   php artisan db:seed --class=TenantSeeder
 *
 * O tenant "default" (id=1, slug="default") é o tenant que receberá
 * todos os dados existentes via backfill da migration 150100.
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Tenant padrão — dados legados
        $default = Tenant::updateOrCreate(
            ['slug' => 'default'],
            [
                'id' => 1,
                'name' => 'Autonomia Ilimitada (Padrão)',
                'plan' => 'pro',
                'is_active' => true,
                'settings' => [
                    'timezone' => 'America/Sao_Paulo',
                    'currency' => 'BRL',
                    'locale' => 'pt_BR',
                ],
                'max_users' => 999,
                'max_orcamentos_mes' => 999,
            ]
        );

        // Atualizar a sequência no PostgreSQL
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement("SELECT setval(pg_get_serial_sequence('tenants', 'id'), coalesce(max(id), 1), true) FROM tenants;");
        }

        $this->command->info("✅ Tenant padrão criado: {$default->name} (ID: {$default->id})");

        // Associar todos os admins existentes ao tenant padrão
        $updated = User::where('is_admin', true)
            ->whereNull('tenant_id')
            ->update(['tenant_id' => $default->id]);

        $this->command->info("👤 {$updated} usuário(s) admin associados ao tenant padrão.");
    }
}
