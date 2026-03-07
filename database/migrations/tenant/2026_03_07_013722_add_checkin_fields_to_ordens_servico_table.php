<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->string('checkin_latitude')->nullable()->after('assinatura_ip');
            $table->string('checkin_longitude')->nullable()->after('checkin_latitude');
            $table->timestamp('checkin_at')->nullable()->after('checkin_longitude');
            $table->string('checkin_ip')->nullable()->after('checkin_at');
        });
    }

    public function down()
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn(['checkin_latitude', 'checkin_longitude', 'checkin_at', 'checkin_ip']);
        });
    }
};
