<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Garante que a chave exista no banco para o Widget não quebrar
        if (! DB::table('configuracoes')->where('chave', 'url_clima')->exists()) {
            DB::table('configuracoes')->insert([
                'grupo' => 'sistema',
                'chave' => 'url_clima',
                'valor' => 'https://wttr.in/Ribeirao+Preto?0QT&lang=pt',
                'tipo'  => 'text',
                'descricao' => 'URL do widget de clima (iframe ou imagem)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void { }
};