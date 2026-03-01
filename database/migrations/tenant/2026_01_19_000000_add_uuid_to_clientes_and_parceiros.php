<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add uuid columns (nullable initially for sqlite compatibility)
        if (! Schema::hasColumn('clientes', 'uuid')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id')->index();
            });
        }

        if (! Schema::hasColumn('parceiros', 'uuid')) {
            Schema::table('parceiros', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id')->index();
            });
        }

        // Backfill existing records
        DB::table('clientes')->whereNull('uuid')->get()->each(function ($row) {
            DB::table('clientes')->where('id', $row->id)->update(['uuid' => Str::uuid()->toString()]);
        });

        DB::table('parceiros')->whereNull('uuid')->get()->each(function ($row) {
            DB::table('parceiros')->where('id', $row->id)->update(['uuid' => Str::uuid()->toString()]);
        });

        // For databases that support altering column nullability, set not null
        try {
            Schema::table('clientes', function (Blueprint $table) {
                $table->uuid('uuid')->nullable(false)->change();
                $table->unique('uuid');
            });
        } catch (\Throwable $e) {
            // SQLite/change() not supported: leave as is but add unique index if possible
            try {
                DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS clientes_uuid_unique ON clientes (uuid)');
            } catch (\Throwable $_) {
                // best-effort
            }
        }

        try {
            Schema::table('parceiros', function (Blueprint $table) {
                $table->uuid('uuid')->nullable(false)->change();
                $table->unique('uuid');
            });
        } catch (\Throwable $e) {
            try {
                DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS parceiros_uuid_unique ON parceiros (uuid)');
            } catch (\Throwable $_) {
                // best-effort
            }
        }
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'uuid')) {
                $table->dropIndex(['uuid']);
                $table->dropColumn('uuid');
            }
        });

        Schema::table('parceiros', function (Blueprint $table) {
            if (Schema::hasColumn('parceiros', 'uuid')) {
                $table->dropIndex(['uuid']);
                $table->dropColumn('uuid');
            }
        });
    }
};
