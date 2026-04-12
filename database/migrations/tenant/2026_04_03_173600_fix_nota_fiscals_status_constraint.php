<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            try {
                DB::statement('ALTER TABLE nota_fiscals DROP CONSTRAINT nota_fiscals_status_check');
            } catch (\Exception $e) {
                // Ignore if constraint does not exist
            }
        }
    }

    public function down(): void
    {
        // Nothing here.
    }
};
