<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Make this migration safe to run on multiple drivers.
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: ALTER MODIFY COLUMN for ENUM expansion
            DB::statement("ALTER TABLE ordens_servico MODIFY COLUMN `status` ENUM('aberta','em_andamento','aguardando_pecas','concluida','cancelada','pendente') NOT NULL DEFAULT 'aberta'");

            if (Schema::hasTable('financeiros')) {
                DB::statement("ALTER TABLE financeiros MODIFY COLUMN `tipo` ENUM('entrada','saida','receita') NOT NULL DEFAULT 'entrada'");
            }
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: ENUMs are managed via CHECK constraints.
            // Add 'pendente' to the allowed status values via constraint.
            // First drop existing check if it exists, then re-add with new values.
            DB::statement("ALTER TABLE ordens_servico DROP CONSTRAINT IF EXISTS ordens_servico_status_check");
            DB::statement("ALTER TABLE ordens_servico ADD CONSTRAINT ordens_servico_status_check CHECK (status IN ('aberta','em_andamento','aguardando_pecas','concluida','cancelada','pendente'))");

            if (Schema::hasTable('financeiros')) {
                DB::statement("ALTER TABLE financeiros DROP CONSTRAINT IF EXISTS financeiros_tipo_check");
                DB::statement("ALTER TABLE financeiros ADD CONSTRAINT financeiros_tipo_check CHECK (tipo IN ('entrada','saida','receita'))");
            }
        }
        // SQLite: no-op — tests use sqlite and don't require enum expansion.
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE ordens_servico MODIFY COLUMN `status` ENUM('aberta','em_andamento','aguardando_pecas','concluida','cancelada') NOT NULL DEFAULT 'aberta'");
            DB::statement("ALTER TABLE financeiros MODIFY COLUMN `tipo` ENUM('entrada','saida') NOT NULL DEFAULT 'entrada'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE ordens_servico DROP CONSTRAINT IF EXISTS ordens_servico_status_check");
            DB::statement("ALTER TABLE ordens_servico ADD CONSTRAINT ordens_servico_status_check CHECK (status IN ('aberta','em_andamento','aguardando_pecas','concluida','cancelada'))");

            DB::statement("ALTER TABLE financeiros DROP CONSTRAINT IF EXISTS financeiros_tipo_check");
            DB::statement("ALTER TABLE financeiros ADD CONSTRAINT financeiros_tipo_check CHECK (tipo IN ('entrada','saida'))");
        }
    }
};
