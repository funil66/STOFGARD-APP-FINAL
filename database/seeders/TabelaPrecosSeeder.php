<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TabelaPrecosSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // TABELA HIGIENIZAÇÃO
        $higienizacao = [
            // Cadeiras e Poltronas
            ['categoria' => 'Cadeiras e Poltronas', 'nome_item' => 'Cadeira Simples (Assento)', 'preco_vista' => 35.00, 'preco_prazo' => 40.00],
            ['categoria' => 'Cadeiras e Poltronas', 'nome_item' => 'Cadeira Sala de Jantar (Assento + Encosto)', 'preco_vista' => 50.00, 'preco_prazo' => 55.00],
            ['categoria' => 'Cadeiras e Poltronas', 'nome_item' => 'Banqueta', 'preco_vista' => 40.00, 'preco_prazo' => 45.00],
            ['categoria' => 'Cadeiras e Poltronas', 'nome_item' => 'Poltrona Fixa / Decorativa', 'preco_vista' => 180.00, 'preco_prazo' => 200.00],
            ['categoria' => 'Cadeiras e Poltronas', 'nome_item' => 'Poltrona do Papai / Reclinável', 'preco_vista' => 200.00, 'preco_prazo' => 220.00],
            ['categoria' => 'Cadeiras e Poltronas', 'nome_item' => 'Puff Pequeno/Médio', 'preco_vista' => 40.00, 'preco_prazo' => 50.00],

            // Almofadas
            ['categoria' => 'Almofadas', 'nome_item' => 'Almofada (Qualquer tamanho)', 'preco_vista' => 25.00, 'preco_prazo' => 30.00],

            // Colchões
            ['categoria' => 'Colchões', 'nome_item' => 'Colchão Solteiro', 'preco_vista' => 200.00, 'preco_prazo' => 220.00],
            ['categoria' => 'Colchões', 'nome_item' => 'Colchão Casal Padrão', 'preco_vista' => 260.00, 'preco_prazo' => 290.00],
            ['categoria' => 'Colchões', 'nome_item' => 'Colchão Queen Size', 'preco_vista' => 300.00, 'preco_prazo' => 330.00],
            ['categoria' => 'Colchões', 'nome_item' => 'Colchão King Size', 'preco_vista' => 350.00, 'preco_prazo' => 380.00],

            // Sofás
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 2 Lugares Fixo', 'preco_vista' => 160.00, 'preco_prazo' => 180.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 3 Lugares Fixo', 'preco_vista' => 200.00, 'preco_prazo' => 230.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 2 Lugares Retrátil', 'preco_vista' => 220.00, 'preco_prazo' => 250.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 3 Lugares Retrátil', 'preco_vista' => 280.00, 'preco_prazo' => 310.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá de Canto (5 Lugares)', 'preco_vista' => 350.00, 'preco_prazo' => 390.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá Cama', 'preco_vista' => 250.00, 'preco_prazo' => 280.00],

            // Tapetes e Cortinas (M²)
            ['categoria' => 'Tapetes e Cortinas', 'nome_item' => 'Tapete (Nacional/Importado) - m²', 'preco_vista' => 25.00, 'preco_prazo' => 28.00, 'unidade' => 'm2'],
            ['categoria' => 'Tapetes e Cortinas', 'nome_item' => 'Cortina (Tecido Leve/Pesado) - m²', 'preco_vista' => 25.00, 'preco_prazo' => 28.00, 'unidade' => 'm2'],
            ['categoria' => 'Tapetes e Cortinas', 'nome_item' => 'Persianas - m²', 'preco_vista' => 35.00, 'preco_prazo' => 40.00, 'unidade' => 'm2'],

            // Automotivo
            ['categoria' => 'Estofado Automotivo', 'nome_item' => 'Carro Pequeno (Hatch)', 'preco_vista' => 250.00, 'preco_prazo' => 280.00],
            ['categoria' => 'Estofado Automotivo', 'nome_item' => 'Carro Médio (Sedan)', 'preco_vista' => 280.00, 'preco_prazo' => 310.00],
            ['categoria' => 'Estofado Automotivo', 'nome_item' => 'Carro Grande (SUV / Caminhonete)', 'preco_vista' => 350.00, 'preco_prazo' => 390.00],
            ['categoria' => 'Estofado Automotivo', 'nome_item' => 'Apenas Bancos (Qualquer Carro)', 'preco_vista' => 180.00, 'preco_prazo' => 200.00],
        ];

        // TABELA IMPERMEABILIZAÇÃO
        $impermeabilizacao = [
            // Almofadas
            ['categoria' => 'Almofadas', 'nome_item' => 'Almofada Pequena', 'preco_vista' => 40.00, 'preco_prazo' => 50.00],
            ['categoria' => 'Almofadas', 'nome_item' => 'Almofada Média', 'preco_vista' => 50.00, 'preco_prazo' => 60.00],
            ['categoria' => 'Almofadas', 'nome_item' => 'Almofada Grande', 'preco_vista' => 60.00, 'preco_prazo' => 70.00],

            // Cadeiras
            ['categoria' => 'Cadeiras', 'nome_item' => 'Banqueta de Bar', 'preco_vista' => 45.00, 'preco_prazo' => 55.00],
            ['categoria' => 'Cadeiras', 'nome_item' => 'Cadeira Chinesa / Decorativa', 'preco_vista' => 70.00, 'preco_prazo' => 75.00],
            ['categoria' => 'Cadeiras', 'nome_item' => 'Cadeira Boneca', 'preco_vista' => 90.00, 'preco_prazo' => 110.00],
            ['categoria' => 'Cadeiras', 'nome_item' => 'Cadeira Sala de Jantar (Assento + Encosto)', 'preco_vista' => 50.00, 'preco_prazo' => 60.00],
            ['categoria' => 'Cadeiras', 'nome_item' => 'Cadeira Sala de Jantar (Apenas Assento)', 'preco_vista' => 45.00, 'preco_prazo' => 55.00],
            ['categoria' => 'Cadeiras', 'nome_item' => 'Cadeira Jantar (Assento + Enc. Traseiro)', 'preco_vista' => 60.00, 'preco_prazo' => 70.00],

            // Cabeceiras e Chaises
            ['categoria' => 'Cabeceiras e Chaises', 'nome_item' => 'Cabeceira de Cama Casal', 'preco_vista' => 280.00, 'preco_prazo' => 290.00],
            ['categoria' => 'Cabeceiras e Chaises', 'nome_item' => 'Chaise 2 Lugares (Assento Fixo)', 'preco_vista' => 320.00, 'preco_prazo' => 350.00],
            ['categoria' => 'Cabeceiras e Chaises', 'nome_item' => 'Chaise Long (unidade)', 'preco_vista' => 220.00, 'preco_prazo' => 250.00],

            // Colchões
            ['categoria' => 'Colchões', 'nome_item' => 'Colchão de Solteiro', 'preco_vista' => 280.00, 'preco_prazo' => 320.00],
            ['categoria' => 'Colchões', 'nome_item' => 'Colchão de Casal', 'preco_vista' => 380.00, 'preco_prazo' => 400.00],

            // Conjuntos
            ['categoria' => 'Conjuntos', 'nome_item' => 'Conjunto 3/2 Lug. (Ass./Enc. Solto)', 'preco_vista' => 600.00, 'preco_prazo' => 650.00],
            ['categoria' => 'Conjuntos', 'nome_item' => 'Conjunto 3/2 Lug. (Assento Solto)', 'preco_vista' => 580.00, 'preco_prazo' => 590.00],
            ['categoria' => 'Conjuntos', 'nome_item' => 'Conjunto 3/2 Lug. (Fixo)', 'preco_vista' => 420.00, 'preco_prazo' => 450.00],
            ['categoria' => 'Conjuntos', 'nome_item' => 'Conjunto 3/2 Lug. (Retrátil Fixo)', 'preco_vista' => 580.00, 'preco_prazo' => 590.00],
            ['categoria' => 'Conjuntos', 'nome_item' => 'Conjunto 3/2 Lug. (Retrátil Solto)', 'preco_vista' => 550.00, 'preco_prazo' => 580.00],

            // Cortinas e Tecidos (M²)
            ['categoria' => 'Cortinas e Tecidos', 'nome_item' => 'Cortina, Colcha, Painéis (m²)', 'preco_vista' => 40.00, 'preco_prazo' => 50.00, 'unidade' => 'm2'],

            // Módulos
            ['categoria' => 'Módulos', 'nome_item' => 'Módulo (Ass./Enc. Solto)', 'preco_vista' => 200.00, 'preco_prazo' => 220.00],
            ['categoria' => 'Módulos', 'nome_item' => 'Módulo Fixo', 'preco_vista' => 150.00, 'preco_prazo' => 170.00],
            ['categoria' => 'Módulos', 'nome_item' => 'Módulo Canto (Ass./Enc. Solto)', 'preco_vista' => 190.00, 'preco_prazo' => 200.00],
            ['categoria' => 'Módulos', 'nome_item' => 'Módulo Canto (Assento Solto)', 'preco_vista' => 180.00, 'preco_prazo' => 190.00],
            ['categoria' => 'Módulos', 'nome_item' => 'Módulo Canto Fixo', 'preco_vista' => 170.00, 'preco_prazo' => 180.00],
            ['categoria' => 'Módulos', 'nome_item' => 'Módulo c/ Braço (Ass./Enc. Solto)', 'preco_vista' => 220.00, 'preco_prazo' => 250.00],
            ['categoria' => 'Módulos', 'nome_item' => 'Módulo c/ Braço (Assento Solto)', 'preco_vista' => 190.00, 'preco_prazo' => 210.00],
            ['categoria' => 'Módulos', 'nome_item' => 'Módulo c/ Braço Fixo', 'preco_vista' => 170.00, 'preco_prazo' => 185.00],

            // Poltronas
            ['categoria' => 'Poltronas', 'nome_item' => 'Poltrona Berger (Ass./Enc. Solto)', 'preco_vista' => 180.00, 'preco_prazo' => 190.00],
            ['categoria' => 'Poltronas', 'nome_item' => 'Poltrona Berger (Assento Solto)', 'preco_vista' => 160.00, 'preco_prazo' => 170.00],
            ['categoria' => 'Poltronas', 'nome_item' => 'Poltrona Berger Fixa', 'preco_vista' => 150.00, 'preco_prazo' => 160.00],
            ['categoria' => 'Poltronas', 'nome_item' => 'Poltrona Luiz XV', 'preco_vista' => 70.00, 'preco_prazo' => 90.00],

            // Sofás
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 1,80m', 'preco_vista' => 250.00, 'preco_prazo' => 270.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 2,00m', 'preco_vista' => 280.00, 'preco_prazo' => 290.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 2,90m', 'preco_vista' => 550.00, 'preco_prazo' => 580.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 3 Lug. Retrátil Reclinável (3,50m)', 'preco_vista' => 580.00, 'preco_prazo' => 620.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 3 Lug. Retrátil Reclinável (2,50m)', 'preco_vista' => 520.00, 'preco_prazo' => 560.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 3 Lug. Retrátil Reclinável (2,20m)', 'preco_vista' => 420.00, 'preco_prazo' => 450.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 3 Lug. (Ass./Enc. Solto)', 'preco_vista' => 450.00, 'preco_prazo' => 470.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 3 Lug. (Assento Solto)', 'preco_vista' => 420.00, 'preco_prazo' => 460.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 3 Lug. Fixo', 'preco_vista' => 380.00, 'preco_prazo' => 390.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 2 Lug. (Ass./Enc. Solto)', 'preco_vista' => 350.00, 'preco_prazo' => 370.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 2 Lug. (Assento Solto)', 'preco_vista' => 280.00, 'preco_prazo' => 290.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá 2 Lug. Fixo', 'preco_vista' => 220.00, 'preco_prazo' => 240.00],
            ['categoria' => 'Sofás', 'nome_item' => 'Sofá Cama / Bicama', 'preco_vista' => 320.00, 'preco_prazo' => 340.00],
        ];

        // Inserir Higienização
        foreach ($higienizacao as $item) {
            DB::table('tabela_precos')->insert([
                'tipo_servico' => 'higienizacao',
                'categoria' => $item['categoria'],
                'nome_item' => $item['nome_item'],
                'unidade_medida' => $item['unidade'] ?? 'unidade',
                'preco_vista' => $item['preco_vista'],
                'preco_prazo' => $item['preco_prazo'],
                'ativo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Inserir Impermeabilização
        foreach ($impermeabilizacao as $item) {
            DB::table('tabela_precos')->insert([
                'tipo_servico' => 'impermeabilizacao',
                'categoria' => $item['categoria'],
                'nome_item' => $item['nome_item'],
                'unidade_medida' => $item['unidade'] ?? 'unidade',
                'preco_vista' => $item['preco_vista'],
                'preco_prazo' => $item['preco_prazo'],
                'ativo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('✅ Tabela de preços populada com sucesso!');
        $this->command->info('📊 Higienização: '.count($higienizacao).' itens');
        $this->command->info('📊 Impermeabilização: '.count($impermeabilizacao).' itens');
    }
}
