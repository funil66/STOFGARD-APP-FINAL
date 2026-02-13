<?php

namespace Database\Seeders;

use App\Models\TabelaPreco;
use Illuminate\Database\Seeder;

class TabelaPrecosCompletaSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa a tabela para não duplicar, ignorando constraints de chave estrangeira
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        TabelaPreco::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

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
            ['nome' => 'Cadeira de Jantar (Tecido Total/Poltroninha)', 'higi' => 70.00, 'imper' => 120.00],
            ['nome' => 'Cadeira Escritório (Secretária)', 'higi' => 40.00, 'imper' => 0.00],
            ['nome' => 'Cadeira Escritório (Presidente/Gamer)', 'higi' => 80.00, 'imper' => 150.00],
            ['nome' => 'Banqueta Alta (Tecido)', 'higi' => 30.00, 'imper' => 50.00],

            // --- COLCHÕES (Higiene Profunda) ---
            ['nome' => 'Colchão Solteiro', 'higi' => 160.00, 'imper' => 0.00],
            ['nome' => 'Colchão Viúva', 'higi' => 180.00, 'imper' => 0.00],
            ['nome' => 'Colchão Casal Padrão', 'higi' => 200.00, 'imper' => 0.00],
            ['nome' => 'Colchão Queen Size', 'higi' => 250.00, 'imper' => 0.00],
            ['nome' => 'Colchão King Size', 'higi' => 300.00, 'imper' => 0.00],
            ['nome' => 'Base Box Solteiro', 'higi' => 80.00, 'imper' => 0.00],
            ['nome' => 'Base Box Casal', 'higi' => 100.00, 'imper' => 0.00],
            ['nome' => 'Cabeceira de Cama (Tecido)', 'higi' => 120.00, 'imper' => 200.00],

            // --- VEÍCULOS ---
            ['nome' => 'Carro Passeio (Bancos)', 'higi' => 220.00, 'imper' => 450.00],
            ['nome' => 'Carro Passeio (Completo: Teto/Carpete)', 'higi' => 350.00, 'imper' => 0.00],
            ['nome' => 'SUV / Caminhonete (Bancos)', 'higi' => 280.00, 'imper' => 550.00],
            ['nome' => 'SUV / Caminhonete (Completo)', 'higi' => 450.00, 'imper' => 0.00],
            ['nome' => 'Teto de Veículo (Isolado)', 'higi' => 120.00, 'imper' => 0.00],
            ['nome' => 'Carpete de Veículo (Isolado)', 'higi' => 150.00, 'imper' => 0.00],

            // --- INFANTIL & DIVERSOS ---
            ['nome' => 'Bebê Conforto', 'higi' => 80.00, 'imper' => 150.00],
            ['nome' => 'Cadeirinha de Carro', 'higi' => 100.00, 'imper' => 180.00],
            ['nome' => 'Carrinho de Bebê (Simples)', 'higi' => 120.00, 'imper' => 200.00],
            ['nome' => 'Carrinho de Bebê (Travel System/Grande)', 'higi' => 180.00, 'imper' => 280.00],
            ['nome' => 'Puff Quadrado/Redondo (Pequeno)', 'higi' => 40.00, 'imper' => 70.00],
            ['nome' => 'Puff Baú (Grande)', 'higi' => 80.00, 'imper' => 140.00],
            ['nome' => 'Urso de Pelúcia (Pequeno)', 'higi' => 30.00, 'imper' => 0.00],
            ['nome' => 'Urso de Pelúcia (Grande)', 'higi' => 80.00, 'imper' => 0.00],
            ['nome' => 'Tapete (Por m²)', 'higi' => 40.00, 'imper' => 0.00],
        ];

        foreach ($itens as $item) {
            // Determinar categoria automaticamente
            $categoria = $this->determinarCategoria($item['nome']);

            // Cria Higienização
            if ($item['higi'] > 0) {
                TabelaPreco::create([
                    'configuracao_id' => 1,
                    'nome_item' => $item['nome'],
                    'preco_vista' => $item['higi'],
                    'preco_prazo' => $item['higi'] * 1.1, // Padrão 10% a mais
                    'tipo_servico' => 'higienizacao',
                    'categoria' => $categoria,
                    'unidade_medida' => 'unidade',
                    'ativo' => true,
                ]);
            }
            // Cria Impermeabilização
            if ($item['imper'] > 0) {
                TabelaPreco::create([
                    'configuracao_id' => 1,
                    'nome_item' => $item['nome'],
                    'preco_vista' => $item['imper'],
                    'preco_prazo' => $item['imper'] * 1.1, // Padrão 10% a mais
                    'tipo_servico' => 'impermeabilizacao',
                    'categoria' => $categoria,
                    'unidade_medida' => 'unidade',
                    'ativo' => true,
                ]);
            }
        }
    }

    private function determinarCategoria(string $nome): string
    {
        $nome = strtolower($nome);

        if (str_contains($nome, 'cadeira') || str_contains($nome, 'banqueta')) {
            return 'Cadeiras e Banquetas';
        }
        if (str_contains($nome, 'poltrona') || str_contains($nome, 'puff')) {
            return 'Poltronas e Puffs';
        }
        if (str_contains($nome, 'sofá') || str_contains($nome, 'sofa')) {
            return 'Sofás';
        }
        if (str_contains($nome, 'colchão') || str_contains($nome, 'colchao') || str_contains($nome, 'cama') || str_contains($nome, 'cabeceira')) {
            return 'Colchões e Camas';
        }
        if (str_contains($nome, 'carro') || str_contains($nome, 'suv') || str_contains($nome, 'caminhão') || str_contains($nome, 'veículo')) {
            return 'Automotivo';
        }
        if (str_contains($nome, 'bebê') || str_contains($nome, 'bebe') || str_contains($nome, 'carrinho') || str_contains($nome, 'urso')) {
            return 'Bebê e Criança';
        }
        if (str_contains($nome, 'tapete')) {
            return 'Tapetes';
        }

        return 'Diversos';
    }
}
