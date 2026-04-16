<?php

namespace Database\Seeders;

use App\Models\TabelaPreco;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TabelaPrecosSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        TabelaPreco::truncate();
        Schema::enableForeignKeyConstraints();

        $dadosParaInserir = [];

        // ==============================================================================
        // 1. GERADOR DE SOFÁS (REFINADO)
        // ==============================================================================
        $tamanhosSofa = range(1.2, 4.0, 0.1); // De 10cm em 10cm
        $complexidadeSofa = [
            'Liso Padrão' => 1.0,
            'c/ Almofadas Soltas' => 1.2,
            'Retrátil' => 1.3,
            'Retrátil + Almofadas Soltas' => 1.5,
            'Pillow Top Premium' => 1.7,
            'Couro/Sintético (Hidratação)' => 1.4
        ];

        foreach ($tamanhosSofa as $t) {
            foreach ($complexidadeSofa as $nomeMod => $fator) {
                $medida = number_format($t, 2, ',', '');
                $dadosParaInserir[] = $this->gerarItem('Sofás', "Sofá {$medida}m - {$nomeMod}", ($t * 146.25 * $fator), ($t * 85 * $fator));
            }
        }

        // ==============================================================================
        // 2. FÁBRICA DE CADEIRAS E BANQUETAS
        // ==============================================================================
        $tiposCadeira = [
            'Cadeira Jantar' => 1.0,
            'Cadeira Medalhão' => 1.3,
            'Cadeira Capitonê' => 1.5,
            'Banqueta Alta' => 0.8,
            'Poltroninha de Aproximação' => 1.8
        ];

        $variacoesCadeira = [
            'Apenas Assento' => 0.6,
            'Assento + Encosto' => 1.0,
            'Revestimento Total' => 1.4,
            'Palha/Rattan (Somente Higi)' => 0.7
        ];

        foreach ($tiposCadeira as $tipo => $fatorTipo) {
            foreach ($variacoesCadeira as $var => $fatorVar) {
                $baseImper = 85.00; // Valor base por cadeira
                $baseHigi = 50.00;
                $dadosParaInserir[] = $this->gerarItem('Cadeiras', "{$tipo} ({$var})", ($baseImper * $fatorTipo * $fatorVar), ($baseHigi * $fatorTipo * $fatorVar));
            }
        }

        // ==============================================================================
        // 3. CHAISES, DIVÃS E RECAMIERS (O "SETOR PREMIUM")
        // ==============================================================================
        $chaises = [
            'Chaise Longue Simples' => ['imper' => 250, 'higi' => 150],
            'Chaise Longue Double (Dupla)' => ['imper' => 450, 'higi' => 280],
            'Divã de Consultório' => ['imper' => 320, 'higi' => 180],
            'Recamier Baú' => ['imper' => 180, 'higi' => 120],
            'Recamier Clássico Capitonê' => ['imper' => 280, 'higi' => 180],
            'Chaise Acoplada em Sofá (Módulo)' => ['imper' => 220, 'higi' => 140]
        ];

        foreach ($chaises as $nome => $precos) {
            $dadosParaInserir[] = $this->gerarItem('Chaises e Divãs', $nome, $precos['imper'], $precos['higi']);
        }

        // ==============================================================================
        // 4. POLTRONAS (A VARIEDADE QUE VOCÊ QUERIA)
        // ==============================================================================
        $poltronas = [
            'Poltrona Decorativa Peq' => 1.0,
            'Poltrona Decorativa Grd' => 1.3,
            'Poltrona do Papai (Manual)' => 1.8,
            'Poltrona do Papai (Elétrica)' => 2.1,
            'Poltrona Egg/Swan' => 1.5,
            'Poltrona Costela (c/ Puff)' => 2.0,
            'Poltrona Charles Eames (Couro)' => 2.5,
            'Poltrona Amamentação' => 1.4
        ];

        foreach ($poltronas as $nome => $fator) {
            $dadosParaInserir[] = $this->gerarItem('Poltronas', $nome, (150 * $fator), (90 * $fator));
        }

        // ==============================================================================
        // 5. COLCHÕES, PUFFS E TAPETES
        // ==============================================================================
        $outros = [
            ['cat' => 'Colchões', 'nome' => 'Colchão Solteiro', 'imper' => 250, 'higi' => 150],
            ['cat' => 'Colchões', 'nome' => 'Colchão Casal', 'imper' => 320, 'higi' => 200],
            ['cat' => 'Colchões', 'nome' => 'Colchão Queen', 'imper' => 380, 'higi' => 250],
            ['cat' => 'Colchões', 'nome' => 'Colchão King', 'imper' => 450, 'higi' => 300],
            ['cat' => 'Tapetes', 'nome' => 'Tapete Sintético (m2)', 'imper' => 80, 'higi' => 50],
            ['cat' => 'Tapetes', 'nome' => 'Tapete Pelo Alto/Shaggy (m2)', 'imper' => 100, 'higi' => 50],
            ['cat' => 'Puffs', 'nome' => 'Puff Pera/Gigante', 'imper' => 180, 'higi' => 100],
            ['cat' => 'Puffs', 'nome' => 'Puff Quadrado/Redondo Peq', 'imper' => 70, 'higi' => 40],
        ];

        foreach ($outros as $o) {
            $dadosParaInserir[] = $this->gerarItem($o['cat'], $o['nome'], $o['imper'], $o['higi']);
        }

        // --- BÔNUS: ACESSÓRIOS ---
        $dadosParaInserir[] = $this->gerarItem('Acessórios', 'Almofada Decorativa (Até 50x50)', 35, 20);
        $dadosParaInserir[] = $this->gerarItem('Acessórios', 'Almofada Encosto Grande', 55, 30);

        // Flattening e Bulk Insert
        $finalData = [];
        foreach ($dadosParaInserir as $subArray) {
            foreach ($subArray as $item) {
                $finalData[] = $item;
            }
        }

        foreach (array_chunk($finalData, 200) as $chunk) {
            TabelaPreco::insert($chunk);
        }
    }

    private function gerarItem($cat, $nome, $precoImper, $precoHigi)
    {
        $itens = [];
        if ($precoHigi > 0) {
            $itens[] = [
                'tipo_servico' => 'higienizacao',
                'categoria' => $cat,
                'nome_item' => $nome,
                'unidade_medida' => str_contains($nome, 'm2') ? 'm2' : 'unidade',
                'preco_vista' => round($precoHigi, 2),
                'preco_prazo' => round($precoHigi * 1.1, 2),
                'ativo' => true, 'configuracao_id' => 1, 'created_at' => now(), 'updated_at' => now()
            ];
        }
        if ($precoImper > 0) {
            $itens[] = [
                'tipo_servico' => 'impermeabilizacao',
                'categoria' => $cat,
                'nome_item' => $nome,
                'unidade_medida' => str_contains($nome, 'm2') ? 'm2' : 'unidade',
                'preco_vista' => round($precoImper, 2),
                'preco_prazo' => round($precoImper * 1.1, 2),
                'ativo' => true, 'configuracao_id' => 1, 'created_at' => now(), 'updated_at' => now()
            ];
        }
        return $itens;
    }
}