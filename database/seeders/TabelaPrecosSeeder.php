<?php

namespace Database\Seeders;

use App\Models\TabelaPreco;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TabelaPrecosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ---------------------------------------------------------
        // PROCEDIMENTO DE LIMPEZA SEGURA (MySQL)
        // ---------------------------------------------------------
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        TabelaPreco::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $itens = [
            // ==============================================================================
            // 1. SOFÁS RETRÁTEIS (GRANULARIDADE POR MEDIDA)
            // ==============================================================================
            ['cat' => 'Sofá Retrátil', 'nome' => 'Sofá Retrátil 2 Lugares (Até 2.00m)', 'higi' => 200.00, 'imper' => 380.00],
            ['cat' => 'Sofá Retrátil', 'nome' => 'Sofá Retrátil 2 Lugares (2.10m a 2.30m)', 'higi' => 240.00, 'imper' => 420.00],
            ['cat' => 'Sofá Retrátil', 'nome' => 'Sofá Retrátil 3 Lugares (2.40m a 2.50m)', 'higi' => 280.00, 'imper' => 480.00],
            ['cat' => 'Sofá Retrátil', 'nome' => 'Sofá Retrátil 3 Lugares (2.60m a 2.80m)', 'higi' => 320.00, 'imper' => 520.00],
            ['cat' => 'Sofá Retrátil', 'nome' => 'Sofá Retrátil 4 Lugares (2.90m a 3.10m)', 'higi' => 380.00, 'imper' => 600.00],
            ['cat' => 'Sofá Retrátil', 'nome' => 'Sofá Retrátil 4 Lugares (3.20m a 3.50m)', 'higi' => 420.00, 'imper' => 680.00],
            ['cat' => 'Sofá Retrátil', 'nome' => 'Sofá Retrátil Gigante (3.60m a 4.00m)', 'higi' => 500.00, 'imper' => 800.00],
            ['cat' => 'Sofá Retrátil', 'nome' => 'Sofá Retrátil Big Brother (Acima 4m)', 'higi' => 600.00, 'imper' => 950.00],

            // ==============================================================================
            // 2. SOFÁS RETRÁTEIS PREMIUM (PILLOW TOP / TECIDOS NATURAIS)
            // ==============================================================================
            ['cat' => 'Sofá Premium', 'nome' => 'Sofá Retrátil c/ Pillow Top (2 Lug)', 'higi' => 280.00, 'imper' => 450.00],
            ['cat' => 'Sofá Premium', 'nome' => 'Sofá Retrátil c/ Pillow Top (3 Lug)', 'higi' => 350.00, 'imper' => 580.00],
            ['cat' => 'Sofá Premium', 'nome' => 'Sofá Retrátil c/ Pillow Top (4 Lug)', 'higi' => 450.00, 'imper' => 750.00],
            ['cat' => 'Sofá Premium', 'nome' => 'Sofá em Linho/Algodão Cru (Taxa Extra)', 'higi' => 80.00, 'imper' => 50.00],

            // ==============================================================================
            // 3. SOFÁS LIVING / FIXOS
            // ==============================================================================
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Living 2 Lugares (Sem almofadas)', 'higi' => 180.00, 'imper' => 320.00],
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Living 3 Lugares (Sem almofadas)', 'higi' => 220.00, 'imper' => 380.00],
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Living 4 Lugares (Sem almofadas)', 'higi' => 280.00, 'imper' => 450.00],
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Living 2 Lug (Almofadas Soltas)', 'higi' => 200.00, 'imper' => 350.00],
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Living 3 Lug (Almofadas Soltas)', 'higi' => 250.00, 'imper' => 420.00],
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Estilo Retro (Pés Palito 2 Lug)', 'higi' => 190.00, 'imper' => 330.00],
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Estilo Retro (Pés Palito 3 Lug)', 'higi' => 230.00, 'imper' => 400.00],
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Chesterfield 2 Lug (Capitonê)', 'higi' => 300.00, 'imper' => 500.00],
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Chesterfield 3 Lug (Capitonê)', 'higi' => 380.00, 'imper' => 600.00],
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Chesterfield 4 Lug (Capitonê)', 'higi' => 450.00, 'imper' => 750.00],
            ['cat' => 'Sofá Living', 'nome' => 'Sofá Curvo / Orgânico (Por Módulo)', 'higi' => 150.00, 'imper' => 250.00],

            // ==============================================================================
            // 4. SOFÁS DE CANTO (L) E MODULARES
            // ==============================================================================
            ['cat' => 'Sofá Canto', 'nome' => 'Sofá em L Pequeno (3 a 4 Lug)', 'higi' => 300.00, 'imper' => 500.00],
            ['cat' => 'Sofá Canto', 'nome' => 'Sofá em L Médio (5 Lug)', 'higi' => 380.00, 'imper' => 600.00],
            ['cat' => 'Sofá Canto', 'nome' => 'Sofá em L Grande (6 Lug)', 'higi' => 450.00, 'imper' => 700.00],
            ['cat' => 'Sofá Canto', 'nome' => 'Sofá em L Gigante (7+ Lug)', 'higi' => 550.00, 'imper' => 850.00],
            ['cat' => 'Sofá Canto', 'nome' => 'Sofá em U (8 a 10 Lugares)', 'higi' => 650.00, 'imper' => 1100.00],
            ['cat' => 'Sofá Canto', 'nome' => 'Módulo de Canto (Cunha)', 'higi' => 120.00, 'imper' => 200.00],
            ['cat' => 'Sofá Canto', 'nome' => 'Chaise Fixa Acoplada', 'higi' => 150.00, 'imper' => 250.00],
            ['cat' => 'Sofá Canto', 'nome' => 'Sofá Ilha (Bilateral)', 'higi' => 400.00, 'imper' => 700.00],

            // ==============================================================================
            // 5. SOFÁS CAMA / DIVÃS
            // ==============================================================================
            ['cat' => 'Sofá Cama', 'nome' => 'Sofá Cama Solteiro (Futon)', 'higi' => 180.00, 'imper' => 300.00],
            ['cat' => 'Sofá Cama', 'nome' => 'Sofá Cama Casal (Tradicional)', 'higi' => 250.00, 'imper' => 450.00],
            ['cat' => 'Sofá Cama', 'nome' => 'Sofá Cama 3 Lugares (King)', 'higi' => 300.00, 'imper' => 500.00],
            ['cat' => 'Sofá Cama', 'nome' => 'Recamier Clássico (Sem encosto)', 'higi' => 120.00, 'imper' => 200.00],
            ['cat' => 'Sofá Cama', 'nome' => 'Recamier Baú (Pé de Cama)', 'higi' => 100.00, 'imper' => 180.00],
            ['cat' => 'Sofá Cama', 'nome' => 'Divã (Psicanálise/Consultório)', 'higi' => 180.00, 'imper' => 320.00],
            ['cat' => 'Sofá Cama', 'nome' => 'Chaise Longue Avulsa (Le Corbusier)', 'higi' => 200.00, 'imper' => 350.00],

            // ==============================================================================
            // 6. POLTRONAS (AQUI ESTÁ A VARIEDADE)
            // ==============================================================================
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Decorativa Pequena', 'higi' => 80.00, 'imper' => 150.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Decorativa Grande', 'higi' => 100.00, 'imper' => 180.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona do Papai (Manual)', 'higi' => 150.00, 'imper' => 280.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona do Papai (Elétrica)', 'higi' => 180.00, 'imper' => 320.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Berger (Tradicional)', 'higi' => 180.00, 'imper' => 300.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Opala', 'higi' => 90.00, 'imper' => 160.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Pétala', 'higi' => 100.00, 'imper' => 180.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Costela (c/ Puff)', 'higi' => 150.00, 'imper' => 250.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Charles Eames (Couro)', 'higi' => 200.00, 'imper' => 350.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Barcelona (Capitonê)', 'higi' => 150.00, 'imper' => 280.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Egg (Design)', 'higi' => 130.00, 'imper' => 220.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Swan', 'higi' => 90.00, 'imper' => 160.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Tulipa', 'higi' => 80.00, 'imper' => 150.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Mole (Sérgio Rodrigues)', 'higi' => 250.00, 'imper' => 400.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Amamentação Simples', 'higi' => 130.00, 'imper' => 220.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Amamentação c/ Puff', 'higi' => 160.00, 'imper' => 280.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Giratória Escritório', 'higi' => 100.00, 'imper' => 180.00],
            ['cat' => 'Poltronas', 'nome' => 'Poltrona Recepção (Quadrada)', 'higi' => 90.00, 'imper' => 160.00],

            // ==============================================================================
            // 7. CADEIRAS DE JANTAR E BANQUETAS
            // ==============================================================================
            ['cat' => 'Cadeiras', 'nome' => 'Cadeira Assento (Apenas)', 'higi' => 35.00, 'imper' => 60.00],
            ['cat' => 'Cadeiras', 'nome' => 'Cadeira Assento + Encosto Peq', 'higi' => 45.00, 'imper' => 80.00],
            ['cat' => 'Cadeiras', 'nome' => 'Cadeira Assento + Encosto Grd', 'higi' => 55.00, 'imper' => 100.00],
            ['cat' => 'Cadeiras', 'nome' => 'Cadeira Revestimento Total (Sueda)', 'higi' => 70.00, 'imper' => 120.00],
            ['cat' => 'Cadeiras', 'nome' => 'Cadeira Revestimento Total (Linho)', 'higi' => 80.00, 'imper' => 140.00],
            ['cat' => 'Cadeiras', 'nome' => 'Cadeira Com Braços (Poltroninha)', 'higi' => 85.00, 'imper' => 150.00],
            ['cat' => 'Cadeiras', 'nome' => 'Cadeira Medalhão (Clássica)', 'higi' => 75.00, 'imper' => 130.00],
            ['cat' => 'Cadeiras', 'nome' => 'Cadeira Capitonê (Botões)', 'higi' => 90.00, 'imper' => 160.00],
            ['cat' => 'Cadeiras', 'nome' => 'Cadeira Eiffel (Botone)', 'higi' => 50.00, 'imper' => 90.00],
            ['cat' => 'Cadeiras', 'nome' => 'Banqueta Alta (Assento)', 'higi' => 30.00, 'imper' => 50.00],
            ['cat' => 'Cadeiras', 'nome' => 'Banqueta Alta (Com Encosto)', 'higi' => 50.00, 'imper' => 90.00],
            ['cat' => 'Cadeiras', 'nome' => 'Banqueta de Bar (Estofada)', 'higi' => 40.00, 'imper' => 70.00],

            // ==============================================================================
            // 8. CADEIRAS DE ESCRITÓRIO E CORPORATIVO
            // ==============================================================================
            ['cat' => 'Escritório', 'nome' => 'Cadeira Secretária (Simples)', 'higi' => 40.00, 'imper' => 0.00],
            ['cat' => 'Escritório', 'nome' => 'Cadeira Executiva (Média)', 'higi' => 55.00, 'imper' => 0.00],
            ['cat' => 'Escritório', 'nome' => 'Cadeira Diretor (Alta)', 'higi' => 70.00, 'imper' => 0.00],
            ['cat' => 'Escritório', 'nome' => 'Cadeira Presidente (Espaldar Alto)', 'higi' => 90.00, 'imper' => 0.00],
            ['cat' => 'Escritório', 'nome' => 'Cadeira Gamer (Tecido/Mesh)', 'higi' => 90.00, 'imper' => 150.00],
            ['cat' => 'Escritório', 'nome' => 'Cadeira Gamer (Couro Sintético)', 'higi' => 80.00, 'imper' => 0.00],
            ['cat' => 'Escritório', 'nome' => 'Cadeira Herman Miller (Tela)', 'higi' => 120.00, 'imper' => 0.00],
            ['cat' => 'Escritório', 'nome' => 'Longarina de Espera (Por Lugar)', 'higi' => 30.00, 'imper' => 0.00],
            ['cat' => 'Escritório', 'nome' => 'Cadeira de Auditório/Cinema', 'higi' => 35.00, 'imper' => 0.00],
            ['cat' => 'Escritório', 'nome' => 'Cadeira de Igreja (Empilhável)', 'higi' => 25.00, 'imper' => 45.00],

            // ==============================================================================
            // 9. COLCHÕES (DIMENSÕES EXATAS)
            // ==============================================================================
            ['cat' => 'Colchões', 'nome' => 'Colchão Berço Americano (1,30x0,70)', 'higi' => 80.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Colchão Mini Cama', 'higi' => 90.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Colchão Solteiro (0,78x1,88)', 'higi' => 140.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Colchão Solteiro Padrão (0,88x1,88)', 'higi' => 150.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Colchão Solteirão / Viúva (1,28x1,88)', 'higi' => 180.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Colchão Casal Padrão (1,38x1,88)', 'higi' => 200.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Colchão Queen Size (1,58x1,98)', 'higi' => 250.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Colchão King Size (1,93x2,03)', 'higi' => 300.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Colchão Super King (Sob Medida)', 'higi' => 350.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Colchão Redondo', 'higi' => 300.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Colchonete de Ginástica', 'higi' => 40.00, 'imper' => 0.00],
            ['cat' => 'Colchões', 'nome' => 'Adicional Urina/Mancha (Por Lado)', 'higi' => 50.00, 'imper' => 0.00],

            // ==============================================================================
            // 10. BASES E CABECEIRAS
            // ==============================================================================
            ['cat' => 'Cama', 'nome' => 'Base Box Solteiro', 'higi' => 80.00, 'imper' => 0.00],
            ['cat' => 'Cama', 'nome' => 'Base Box Casal', 'higi' => 100.00, 'imper' => 0.00],
            ['cat' => 'Cama', 'nome' => 'Base Box Queen', 'higi' => 120.00, 'imper' => 0.00],
            ['cat' => 'Cama', 'nome' => 'Base Box King', 'higi' => 140.00, 'imper' => 0.00],
            ['cat' => 'Cama', 'nome' => 'Cama Auxiliar (Gaveta)', 'higi' => 60.00, 'imper' => 0.00],
            ['cat' => 'Cama', 'nome' => 'Cabeceira Solteiro (Tecido Liso)', 'higi' => 90.00, 'imper' => 150.00],
            ['cat' => 'Cama', 'nome' => 'Cabeceira Solteiro (Capitonê)', 'higi' => 120.00, 'imper' => 200.00],
            ['cat' => 'Cama', 'nome' => 'Cabeceira Casal (Tecido Liso)', 'higi' => 120.00, 'imper' => 200.00],
            ['cat' => 'Cama', 'nome' => 'Cabeceira Casal (Capitonê)', 'higi' => 150.00, 'imper' => 250.00],
            ['cat' => 'Cama', 'nome' => 'Cabeceira Queen (Tecido Liso)', 'higi' => 140.00, 'imper' => 240.00],
            ['cat' => 'Cama', 'nome' => 'Cabeceira Queen (Capitonê)', 'higi' => 180.00, 'imper' => 300.00],
            ['cat' => 'Cama', 'nome' => 'Cabeceira King (Tecido Liso)', 'higi' => 160.00, 'imper' => 280.00],
            ['cat' => 'Cama', 'nome' => 'Cabeceira King (Capitonê)', 'higi' => 220.00, 'imper' => 380.00],
            ['cat' => 'Cama', 'nome' => 'Painel de Parede Estofado (m²)', 'higi' => 60.00, 'imper' => 100.00],

            // ==============================================================================
            // 11. PUFFS E BANCOS
            // ==============================================================================
            ['cat' => 'Puffs', 'nome' => 'Puff Quadrado (35x35)', 'higi' => 35.00, 'imper' => 60.00],
            ['cat' => 'Puffs', 'nome' => 'Puff Redondo Peq', 'higi' => 40.00, 'imper' => 70.00],
            ['cat' => 'Puffs', 'nome' => 'Puff Redondo Grande', 'higi' => 60.00, 'imper' => 100.00],
            ['cat' => 'Puffs', 'nome' => 'Puff Baú (Solteiro)', 'higi' => 70.00, 'imper' => 120.00],
            ['cat' => 'Puffs', 'nome' => 'Puff Baú (Casal)', 'higi' => 100.00, 'imper' => 180.00],
            ['cat' => 'Puffs', 'nome' => 'Puff Pera / Gigante', 'higi' => 90.00, 'imper' => 150.00],
            ['cat' => 'Puffs', 'nome' => 'Puff Decorativo (Crochê/Corda)', 'higi' => 50.00, 'imper' => 0.00],
            ['cat' => 'Puffs', 'nome' => 'Banco de Penteadeira', 'higi' => 50.00, 'imper' => 90.00],
            ['cat' => 'Puffs', 'nome' => 'Banco de Piano', 'higi' => 50.00, 'imper' => 90.00],
            ['cat' => 'Puffs', 'nome' => 'Apoio de Pés (Footstool)', 'higi' => 30.00, 'imper' => 50.00],

            // ==============================================================================
            // 12. TAPETES (RESIDENCIAL E COMERCIAL)
            // ==============================================================================
            ['cat' => 'Tapetes', 'nome' => 'Tapete Sintético Pelo Curto (m²)', 'higi' => 30.00, 'imper' => 0.00],
            ['cat' => 'Tapetes', 'nome' => 'Tapete Sintético Pelo Alto (m²)', 'higi' => 40.00, 'imper' => 0.00],
            ['cat' => 'Tapetes', 'nome' => 'Tapete Shaggy (Fios Longos) (m²)', 'higi' => 45.00, 'imper' => 0.00],
            ['cat' => 'Tapetes', 'nome' => 'Tapete Sisal / Corda (m²)', 'higi' => 50.00, 'imper' => 0.00],
            ['cat' => 'Tapetes', 'nome' => 'Tapete Algodão / Lã (m²)', 'higi' => 55.00, 'imper' => 0.00],
            ['cat' => 'Tapetes', 'nome' => 'Tapete Persa / Oriental (m²)', 'higi' => 80.00, 'imper' => 0.00],
            ['cat' => 'Tapetes', 'nome' => 'Tapete de Couro (m²)', 'higi' => 60.00, 'imper' => 0.00],
            ['cat' => 'Tapetes', 'nome' => 'Carpete Fixo Residencial (m²)', 'higi' => 25.00, 'imper' => 0.00],
            ['cat' => 'Tapetes', 'nome' => 'Carpete em Placas (Escritório) (m²)', 'higi' => 20.00, 'imper' => 0.00],
            ['cat' => 'Tapetes', 'nome' => 'Passadeira (Metro Linear)', 'higi' => 25.00, 'imper' => 0.00],

            // ==============================================================================
            // 13. CORTINAS E PERSIANAS
            // ==============================================================================
            ['cat' => 'Cortinas', 'nome' => 'Cortina Voil / Tergal (m²)', 'higi' => 20.00, 'imper' => 0.00],
            ['cat' => 'Cortinas', 'nome' => 'Cortina Linho / Seda (m²)', 'higi' => 35.00, 'imper' => 0.00],
            ['cat' => 'Cortinas', 'nome' => 'Cortina Veludo / Pesada (m²)', 'higi' => 40.00, 'imper' => 0.00],
            ['cat' => 'Cortinas', 'nome' => 'Cortina Blackout (Tecido) (m²)', 'higi' => 30.00, 'imper' => 0.00],
            ['cat' => 'Cortinas', 'nome' => 'Cortina Blackout (Emborrachado) (m²)', 'higi' => 40.00, 'imper' => 0.00],
            ['cat' => 'Cortinas', 'nome' => 'Persiana Rolo (Screen/Solar) (m²)', 'higi' => 45.00, 'imper' => 0.00],
            ['cat' => 'Cortinas', 'nome' => 'Persiana Romana (m²)', 'higi' => 50.00, 'imper' => 0.00],
            ['cat' => 'Cortinas', 'nome' => 'Persiana Vertical (Tecido) (m²)', 'higi' => 35.00, 'imper' => 0.00],
            ['cat' => 'Cortinas', 'nome' => 'Bandô (Metro Linear)', 'higi' => 30.00, 'imper' => 0.00],

            // ==============================================================================
            // 14. INFANTIL (HOME - SEM CARROS)
            // ==============================================================================
            ['cat' => 'Infantil', 'nome' => 'Carrinho de Bebê (Simples)', 'higi' => 100.00, 'imper' => 180.00],
            ['cat' => 'Infantil', 'nome' => 'Carrinho de Bebê (Travel System)', 'higi' => 150.00, 'imper' => 250.00],
            ['cat' => 'Infantil', 'nome' => 'Carrinho de Gêmeos', 'higi' => 200.00, 'imper' => 350.00],
            ['cat' => 'Infantil', 'nome' => 'Bebê Conforto (Estofado)', 'higi' => 80.00, 'imper' => 140.00],
            ['cat' => 'Infantil', 'nome' => 'Cadeirinha de Alimentação (Estofado)', 'higi' => 60.00, 'imper' => 0.00],
            ['cat' => 'Infantil', 'nome' => 'Cadeirinha Auto (Limpeza avulsa)', 'higi' => 90.00, 'imper' => 160.00],
            ['cat' => 'Infantil', 'nome' => 'Berço Desmontável (Camping)', 'higi' => 120.00, 'imper' => 0.00],
            ['cat' => 'Infantil', 'nome' => 'Ninho Redutor de Berço', 'higi' => 50.00, 'imper' => 0.00],
            ['cat' => 'Infantil', 'nome' => 'Almofada de Amamentação', 'higi' => 40.00, 'imper' => 0.00],
            ['cat' => 'Infantil', 'nome' => 'Tapete de Atividades (EVA/Tecido)', 'higi' => 50.00, 'imper' => 0.00],

            // ==============================================================================
            // 15. PELÚCIAS E BRINQUEDOS
            // ==============================================================================
            ['cat' => 'Pelúcias', 'nome' => 'Urso de Pelúcia P (até 30cm)', 'higi' => 25.00, 'imper' => 0.00],
            ['cat' => 'Pelúcias', 'nome' => 'Urso de Pelúcia M (30-50cm)', 'higi' => 40.00, 'imper' => 0.00],
            ['cat' => 'Pelúcias', 'nome' => 'Urso de Pelúcia G (50-80cm)', 'higi' => 60.00, 'imper' => 0.00],
            ['cat' => 'Pelúcias', 'nome' => 'Urso de Pelúcia GG (80cm-1m)', 'higi' => 90.00, 'imper' => 0.00],
            ['cat' => 'Pelúcias', 'nome' => 'Urso de Pelúcia Gigante (>1m)', 'higi' => 150.00, 'imper' => 0.00],
            ['cat' => 'Pelúcias', 'nome' => 'Boneca de Pano', 'higi' => 25.00, 'imper' => 0.00],

            // ==============================================================================
            // 16. ACESSÓRIOS E DECORAÇÃO
            // ==============================================================================
            ['cat' => 'Acessórios', 'nome' => 'Almofada Decorativa P (40x40)', 'higi' => 15.00, 'imper' => 30.00],
            ['cat' => 'Acessórios', 'nome' => 'Almofada Decorativa M (50x50)', 'higi' => 20.00, 'imper' => 35.00],
            ['cat' => 'Acessórios', 'nome' => 'Almofada Grande / Encosto', 'higi' => 30.00, 'imper' => 50.00],
            ['cat' => 'Acessórios', 'nome' => 'Rolinho de Cama / Yoga', 'higi' => 25.00, 'imper' => 45.00],
            ['cat' => 'Acessórios', 'nome' => 'Futon Turco (Chão)', 'higi' => 60.00, 'imper' => 100.00],
            ['cat' => 'Acessórios', 'nome' => 'Manta de Sofá (Lavagem)', 'higi' => 35.00, 'imper' => 0.00],

            // ==============================================================================
            // 17. PETS (CAMA E ACESSÓRIOS)
            // ==============================================================================
            ['cat' => 'Pet', 'nome' => 'Caminha Pet P', 'higi' => 40.00, 'imper' => 0.00],
            ['cat' => 'Pet', 'nome' => 'Caminha Pet M', 'higi' => 55.00, 'imper' => 0.00],
            ['cat' => 'Pet', 'nome' => 'Caminha Pet G', 'higi' => 70.00, 'imper' => 0.00],
            ['cat' => 'Pet', 'nome' => 'Caminha Pet GG/Colchão', 'higi' => 90.00, 'imper' => 0.00],
            ['cat' => 'Pet', 'nome' => 'Toca / Iglu de Tecido', 'higi' => 50.00, 'imper' => 0.00],
            ['cat' => 'Pet', 'nome' => 'Arranhador com Pelúcia (Torre)', 'higi' => 80.00, 'imper' => 0.00],

            // ==============================================================================
            // 18. COURO (HIDRATAÇÃO) - SERVIÇO ESPECÍFICO
            // ==============================================================================
            ['cat' => 'Couro', 'nome' => 'Sofá Couro 2 Lug (Limp + Hidratação)', 'higi' => 250.00, 'imper' => 0.00],
            ['cat' => 'Couro', 'nome' => 'Sofá Couro 3 Lug (Limp + Hidratação)', 'higi' => 320.00, 'imper' => 0.00],
            ['cat' => 'Couro', 'nome' => 'Sofá Couro 4 Lug (Limp + Hidratação)', 'higi' => 400.00, 'imper' => 0.00],
            ['cat' => 'Couro', 'nome' => 'Poltrona Couro (Limp + Hidratação)', 'higi' => 120.00, 'imper' => 0.00],
            ['cat' => 'Couro', 'nome' => 'Cadeira Couro (Limp + Hidratação)', 'higi' => 60.00, 'imper' => 0.00],
            ['cat' => 'Couro', 'nome' => 'Puff Couro (Limp + Hidratação)', 'higi' => 50.00, 'imper' => 0.00],

            // ==============================================================================
            // 19. ADICIONAIS TÉCNICOS (CRUCIAIS PARA ORÇAMENTO)
            // ==============================================================================
            ['cat' => 'Adicionais', 'nome' => 'Tratamento de Urina (Enzima)', 'higi' => 50.00, 'imper' => 0.00],
            ['cat' => 'Adicionais', 'nome' => 'Tratamento Anti-Mofo', 'higi' => 60.00, 'imper' => 0.00],
            ['cat' => 'Adicionais', 'nome' => 'Remoção de Pelos Excessivos', 'higi' => 40.00, 'imper' => 0.00],
            ['cat' => 'Adicionais', 'nome' => 'Secagem Turbo (Ventilador)', 'higi' => 70.00, 'imper' => 0.00],
            ['cat' => 'Adicionais', 'nome' => 'Taxa de Deslocamento (Zona Rural)', 'higi' => 50.00, 'imper' => 0.00],
        ];

        foreach ($itens as $item) {
            $categoria = $item['cat'] ?? 'Geral';

            // Lógica de Unidade de Medida
            $unidade = 'unidade';
            $nomeLower = mb_strtolower($item['nome']);
            if (str_contains($nomeLower, 'm2'))
                $unidade = 'm2';
            elseif (str_contains($nomeLower, 'metro'))
                $unidade = 'metro';
            elseif (str_contains($nomeLower, 'assento'))
                $unidade = 'assento';
            elseif (str_contains($nomeLower, 'lugar'))
                $unidade = 'unidade'; // Explicito

            // 1. Cria HIGIENIZAÇÃO
            if ($item['higi'] > 0) {
                $dataHigi = [
                    'tipo_servico' => 'higienizacao',
                    'categoria' => $categoria,
                    'nome_item' => $item['nome'],
                    'unidade_medida' => $unidade,
                    'preco_vista' => $item['higi'],
                    'preco_prazo' => 0.00,
                    'ativo' => true,
                ];
                if (Schema::hasColumn('tabela_precos', 'configuracao_id')) {
                    $dataHigi['configuracao_id'] = 1;
                }
                TabelaPreco::create($dataHigi);
            }

            // 2. Cria IMPERMEABILIZAÇÃO
            if ($item['imper'] > 0) {
                $dataImper = [
                    'tipo_servico' => 'impermeabilizacao',
                    'categoria' => $categoria,
                    'nome_item' => $item['nome'],
                    'unidade_medida' => $unidade,
                    'preco_vista' => $item['imper'],
                    'preco_prazo' => 0.00,
                    'ativo' => true,
                ];
                if (Schema::hasColumn('tabela_precos', 'configuracao_id')) {
                    $dataImper['configuracao_id'] = 1;
                }
                TabelaPreco::create($dataImper);
            }
        }
    }
}
