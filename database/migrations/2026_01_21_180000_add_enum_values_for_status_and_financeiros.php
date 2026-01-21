<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make this migration safe to run on multiple drivers. ALTER MODIFY syntax
        // used in MySQL is not supported in SQLite (in-memory tests). Only run the
        // statements when we are on MySQL.
        if (DB::getDriverName() === 'mysql') {
            // Add 'pendente' to ordens_servico.status
            DB::statement("ALTER TABLE ordens_servico MODIFY COLUMN `status` ENUM('aberta','em_andamento','aguardando_pecas','concluida','cancelada','pendente') NOT NULL DEFAULT 'aberta'");

            // Add 'receita' to financeiros.tipo
            DB::statement("ALTER TABLE financeiros MODIFY COLUMN `tipo` ENUM('entrada','saida','receita') NOT NULL DEFAULT 'entrada'");
        } else {
            // SQLite and other drivers: no-op. Tests that use sqlite don't require
            // enum expansion; running no-op keeps the test suite stable.
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Revert ordens_servico.status to original set (without 'pendente')
            DB::statement("ALTER TABLE ordens_servico MODIFY COLUMN `status` ENUM('aberta','em_andamento','aguardando_pecas','concluida','cancelada') NOT NULL DEFAULT 'aberta'");

            // Revert financeiros.tipo to original set
            DB::statement("ALTER TABLE financeiros MODIFY COLUMN `tipo` ENUM('entrada','saida') NOT NULL DEFAULT 'entrada'");
        }
    }
};
