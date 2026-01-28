<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\Cadastro;
use App\Models\Setting;

class OrcamentoSeeder extends Seeder
{
    public function run(): void
    {
        // Pega clientes existentes
        $clientes = Cadastro::where('tipo', 'cliente')->get();

        // Pega o catálogo das configurações (decodificando JSON)
        $catalogoRaw = Setting::get('catalogo_servicos_v2');
        $catalogo = is_string($catalogoRaw) ? json_decode($catalogoRaw, true) : $catalogoRaw;

        if ($clientes->isEmpty() || empty($catalogo)) {
            $this->command->info('Sem clientes ou catálogo para gerar orçamentos.');
            return;
        }

        // Gera 10 orçamentos aleatórios
        for ($i = 0; $i < 10; $i++) {
            $cliente = $clientes->random();

            // Cria o cabeçalho
            $numeroGerado = Orcamento::gerarNumeroOrcamento();

            $payload = [
                // Novos campos (compatibilidade com legacy)
                'numero' => $numeroGerado,
                'numero_orcamento' => $numeroGerado,
                'cadastro_id' => $cliente->id,
                'data_orcamento' => now(),
                'data_validade' => now()->addDays(15),
                'status' => fake()->randomElement(['rascunho', 'enviado']),
                'tipo_servico' => 'misto',
                'descricao_servico' => 'Serviços diversos (gerado automaticamente)',
                'observacoes' => 'Orçamento gerado automaticamente pelo sistema.',
                'criado_por' => 'SE',
                'valor_total' => 0,
            ];

            // Criamos com os campos mínimos obrigatórios do schema legacy
            $orcamento = Orcamento::create($payload);

            $total = 0;
            // Adiciona 2 a 4 itens aleatórios
            $itensQtd = rand(2, 4);

            for ($j = 0; $j < $itensQtd; $j++) {
                $itemCatalogo = $catalogo[array_rand($catalogo)];
                $tipoServico = fake()->randomElement(['higienizacao', 'impermeabilizacao']);

                // Pega o preço baseado no tipo
                $preco = ($tipoServico == 'higienizacao')
                    ? ($itemCatalogo['preco_higi'] ?? 0)
                    : ($itemCatalogo['preco_imper'] ?? 0);

                // Se o preço for 0 (ex: imper em item que não tem imper), troca para higienização
                if ($preco <= 0) {
                    $tipoServico = 'higienizacao';
                    $preco = $itemCatalogo['preco_higi'] ?? 100;
                }

                $qtd = rand(1, 2);
                $subtotal = $preco * $qtd;

                OrcamentoItem::create([
                    'orcamento_id' => $orcamento->id,
                    'item_nome' => $itemCatalogo['nome'],
                    'servico_tipo' => $tipoServico,
                    'unidade' => $itemCatalogo['unidade'] ?? 'un',
                    'quantidade' => $qtd,
                    'valor_unitario' => $preco,
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            // Atualiza o total do orçamento pai
            $orcamento->update(['valor_total' => $total]);
        }

        $this->command->info('Orçamentos gerados: 10');
    }
}
