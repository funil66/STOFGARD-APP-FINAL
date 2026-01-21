<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure any existing view is removed before (re)creating it. Some DB engines
        // or migration flows can leave views behind, which would cause a CREATE VIEW
        // statement to fail on repeat runs.
        DB::statement('DROP VIEW IF EXISTS cadastros_view');

        // Create a read-only view that unifies clientes and parceiros for listing
        DB::statement(<<<SQL
CREATE VIEW cadastros_view AS
SELECT
  ('cliente-' || id) AS id,
  id AS model_id,
  'cliente' AS model,
  nome,
  'cliente' AS tipo,
  telefone,
  celular,
  cidade,
  created_at
FROM clientes
UNION ALL
SELECT
  ('parceiro-' || id) AS id,
  id AS model_id,
  'parceiro' AS model,
  nome,
  tipo,
  telefone,
  celular,
  cidade,
  created_at
FROM parceiros;
SQL
        );
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS cadastros_view');
    }
};