<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insere a configuração padrão do Clima se não existir
        if (! DB::table('configuracoes')->where('grupo', 'sistema')->where('chave', 'url_clima')->exists()) {
            DB::table('configuracoes')->insert([
                'grupo' => 'sistema',
                'chave' => 'url_clima',
                'valor' => 'https://wttr.in/Ribeirao+Preto?0QT&lang=pt', // URL limpa para embed
                'tipo'  => 'text',
                'descricao' => 'URL do widget de clima (iframe ou imagem)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Não removemos configurações em rollback para evitar perda de dados do usuário
    }
};