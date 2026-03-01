<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('name')->nullable();
            $table->string('slug')->unique()->nullable();
            $table->string('plan')->default('free');
            $table->boolean('is_active')->default(true);
            $table->integer('max_users')->nullable();
            $table->integer('max_orcamentos_mes')->nullable();
            $table->jsonb('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
