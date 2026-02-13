<?php

namespace Database\Seeders;

use App\Models\TabelaPreco;
use Illuminate\Database\Seeder;

class TabelaPrecosSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa tabela antes de popular (MySQL requer desativar FK checks)
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        TabelaPreco::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $itens = [
            // --- SOFÁS RETRÁTEIS ---
            ['nome' => 'Sofá Retrátil 2 Lugares (Pequeno/Até 2.10m)', 'higi' => 220.00, 'imper' => 380.00],
            ['nome' => 'Sofá Retrátil 2 Lugares (Padrão/2.30m)', 'higi' => 250.00, 'imper' => 450.00],
            ['nome' => 'Sofá Retrátil 3 Lugares (Até 2.50m)', 'higi' => 280.00, 'imper' => 500.00],
            ['nome' => 'Sofá Retrátil 3 Lugares (Grande/2.90m)', 'higi' => 320.00, 'imper' => 550.00],
            ['nome' => 'Sofá Retrátil 4 Lugares / Big (3m+)', 'higi' => 380.00, 'imper' => 650.00],

            // --- SOFÁS DE CANTO (L) ---
            ['nome' => 'Sofá de Canto (Pequeno 4 Lug)', 'higi' => 300.00, 'imper' => 500.00],
            ['nome' => 'Sofá de Canto (Médio 5 Lug)', 'higi' => 380.00, 'imper' => 600.00],
            ['nome' => 'Sofá de Canto (Grande 6+ Lug)', 'higi' => 450.00, 'imper' => 750.00],
            ['nome' => 'Sofá de Canto com Chaise', 'higi' => 400.00, 'imper' => 680.00],
            // --- SOFÁS LIVING / FIXOS ---
            ['nome' => 'Sofá Living 2 Lugares', 'higi' => 180.00, 'imper' => 320.00],
            ['nome' => 'Sofá Living 3 Lugares', 'higi' => 220.00, 'imper' => 380.00],
            ['nome' => 'Sofá Chesterfield (Couro/Tecido)', 'higi' => 300.00, 'imper' => 550.00],
            ['nome' => 'Sofá Cama Casal', 'higi' => 250.00, 'imper' => 420.00],
            // --- POLTRONAS & CADEIRAS ---
            ['nome' => 'Poltrona Simples/Decorativa', 'higi' => 100.00, 'imper' => 200.00],
            ['nome' => 'Poltrona do Papai (Reclinável)', 'higi' => 150.00, 'imper' => 280.00],
            ['nome' => 'Poltrona Berger (Grande)', 'higi' => 180.00, 'imper' => 320.00],
            ['nome' => 'Cadeira de Jantar (Só Assento)', 'higi' => 30.00, 'imper' => 60.00],
            ['nome' => 'Cadeira de Jantar (Assento + Encosto)', 'higi' => 45.00, 'imper' => 80.00],
            ['nome' => 'Cadeira de Jantar (Tecido Total)', 'higi' => 70.00, 'imper' => 120.00],
            ['nome' => 'Cadeira Escritório (Secretária)', 'higi' => 40.00, 'imper' => 0.00],
            ['nome' => 'Cadeira Escritório (Presidente/Gamer)', 'higi' => 80.00, 'imper' => 150.00],
            // --- COLCHÕES ---
            ['nome' => 'Colchão Solteiro', 'higi' => 160.00, 'imper' => 0.00],
            ['nome' => 'Colchão Casal Padrão', 'higi' => 200.00, 'imper' => 0.00],
            ['nome' => 'Colchão Queen Size', 'higi' => 250.00, 'imper' => 0.00],
            ['nome' => 'Colchão King Size', 'higi' => 300.00, 'imper' => 0.00],
            ['nome' => 'Base Box Solteiro', 'higi' => 80.00, 'imper' => 0.00],
            ['nome' => 'Base Box Casal', 'higi' => 100.00, 'imper' => 0.00],
            ['nome' => 'Cabeceira de Cama (Tecido)', 'higi' => 120.00, 'imper' => 200.00],
            // --- VEÍCULOS ---
            ['nome' => 'Carro Passeio (Bancos)', 'higi' => 220.00, 'imper' => 450.00],
            ['nome' => 'Carro Passeio (Completo)', 'higi' => 350.00, 'imper' => 0.00],
            ['nome' => 'SUV / Caminhonete (Bancos)', 'higi' => 280.00, 'imper' => 550.00],
            ['nome' => 'SUV / Caminhonete (Completo)', 'higi' => 450.00, 'imper' => 0.00],
            ['nome' => 'Teto de Veículo', 'higi' => 120.00, 'imper' => 0.00],
            // --- INFANTIL & DIVERSOS ---
            ['nome' => 'Bebê Conforto', 'higi' => 80.00, 'imper' => 150.00],
            ['nome' => 'Cadeirinha de Carro', 'higi' => 100.00, 'imper' => 180.00],
            ['nome' => 'Carrinho de Bebê', 'higi' => 120.00, 'imper' => 200.00],
            ['nome' => 'Puff (Pequeno)', 'higi' => 40.00, 'imper' => 70.00],
            ['nome' => 'Tapete (Por m²)', 'higi' => 40.00, 'imper' => 0.00],
        ];
        foreach ($itens as $item) {
            // Categoria padrão
            $categoria = 'Geral';

            // Cria Higienização (mapeia para o esquema atual)
            if ($item['higi'] > 0) {
                $data = [
                    'tipo_servico' => 'higienizacao',
                    'categoria' => $categoria,
                    'nome_item' => $item['nome'],
                    'unidade_medida' => 'unidade',
                    'preco_vista' => $item['higi'],
                    'preco_prazo' => 0.00,
                    'ativo' => true,
                ];
                if (\Illuminate\Support\Facades\Schema::hasColumn('tabela_precos', 'configuracao_id')) {
                    $data['configuracao_id'] = 1;
                }
                TabelaPreco::create($data);
            }

            // Cria Impermeabilização (mapeia para o esquema atual)
            if ($item['imper'] > 0) {
                $data = [
                    'tipo_servico' => 'impermeabilizacao',
                    'categoria' => $categoria,
                    'nome_item' => $item['nome'],
                    'unidade_medida' => 'unidade',
                    'preco_vista' => $item['imper'],
                    'preco_prazo' => 0.00,
                    'ativo' => true,
                ];
                if (\Illuminate\Support\Facades\Schema::hasColumn('tabela_precos', 'configuracao_id')) {
                    $data['configuracao_id'] = 1;
                }
                TabelaPreco::create($data);
            }
        }
    }
}
