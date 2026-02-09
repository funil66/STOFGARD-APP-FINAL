<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class AuxiliaryListsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Unidades de Medida (Estoque)
        $unidades = [
            'unidade' => 'Unidade (un)',
            'litros' => 'Litros (L)',
            'caixa' => 'Caixa (cx)',
            'metro' => 'Metro (m)',
            'kg' => 'Quilograma (kg)',
            'pacote' => 'Pacote (pct)',
        ];

        foreach ($unidades as $slug => $nome) {
            Categoria::firstOrCreate(
                ['slug' => $slug, 'tipo' => 'estoque_unidade'],
                ['nome' => $nome, 'ativo' => true, 'cor' => 'gray']
            );
        }

        // 2. Tipos de Cadastro
        $tiposCadastro = [
            'cliente' => 'Cliente Final',
            'loja' => 'Loja (Ponto Fixo)',
            'vendedor' => 'Vendedor (Interno)',
            'parceiro' => 'Parceiro de Negócios',
        ];

        foreach ($tiposCadastro as $slug => $nome) {
            Categoria::firstOrCreate(
                ['slug' => $slug, 'tipo' => 'cadastro_tipo'],
                ['nome' => $nome, 'ativo' => true, 'cor' => 'info']
            );
        }

        // 3. Tipos de Serviço
        $tiposServico = [
            'higienizacao' => 'Higienização',
            'impermeabilizacao' => 'Impermeabilização',
            'combo' => 'Combo (Higi + Imper)',
            'outro' => 'Outro/Personalizado',
        ];

        foreach ($tiposServico as $slug => $nome) {
            Categoria::firstOrCreate(
                ['slug' => $slug, 'tipo' => 'servico_tipo'],
                ['nome' => $nome, 'ativo' => true, 'cor' => 'primary']
            );
        }
    }
}
