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
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::unprepared('
                CREATE OR REPLACE FUNCTION prevent_audit_modification()
                RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION \'Auditoria é imutável: updates ou deletes não são permitidos.\';
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER enforce_audit_immutability
                BEFORE UPDATE OR DELETE ON audits
                FOR EACH ROW EXECUTE FUNCTION prevent_audit_modification();
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::unprepared('
                DROP TRIGGER IF EXISTS enforce_audit_immutability ON audits;
                DROP FUNCTION IF EXISTS prevent_audit_modification();
            ');
        }
    }
};
