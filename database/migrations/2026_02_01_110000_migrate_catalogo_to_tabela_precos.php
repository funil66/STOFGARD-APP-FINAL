<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;
use App\Models\TabelaPreco;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrar dados de catalogo_servicos_v2 (JSON) para tabela_precos
        $catalogoSetting = Setting::where('key', 'catalogo_servicos_v2')->first();
        
        if ($catalogoSetting && !empty($catalogoSetting->value)) {
            $catalogoItems = json_decode($catalogoSetting->value, true);
            
            if (is_array($catalogoItems)) {
                foreach ($catalogoItems as $item) {
                    $nomeItem = $item['nome'] ?? '';
                    
                    if (empty($nomeItem)) continue;
                    
                    // Categorizar itens automaticamente baseado no nome
                    $categoria = $this->determinarCategoria($nomeItem);
                    
                    // Criar entradas para Higienização
                    if (($item['preco_higi'] ?? 0) > 0) {
                        TabelaPreco::firstOrCreate(
                            [
                                'nome_item' => $nomeItem,
                                'tipo_servico' => 'higienizacao',
                            ],
                            [
                                'categoria' => $categoria,
                                'unidade_medida' => $item['unidade'] === 'm2' ? 'm2' : 'unidade',
                                'preco_vista' => $item['preco_higi'],
                                'preco_prazo' => $item['preco_higi'] * 1.1, // 10% a mais no prazo
                                'ativo' => true,
                                'observacoes' => 'Migrado do catálogo JSON',
                            ]
                        );
                    }
                    
                    // Criar entradas para Impermeabilização
                    if (($item['preco_imper'] ?? 0) > 0) {
                        TabelaPreco::firstOrCreate(
                            [
                                'nome_item' => $nomeItem,
                                'tipo_servico' => 'impermeabilizacao',
                            ],
                            [
                                'categoria' => $categoria,
                                'unidade_medida' => $item['unidade'] === 'm2' ? 'm2' : 'unidade',
                                'preco_vista' => $item['preco_imper'],
                                'preco_prazo' => $item['preco_imper'] * 1.1, // 10% a mais no prazo
                                'ativo' => true,
                                'observacoes' => 'Migrado do catálogo JSON',
                            ]
                        );
                    }
                }
                
                echo "Migração concluída: " . count($catalogoItems) . " itens processados\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove itens migrados baseado na observação
        TabelaPreco::where('observacoes', 'Migrado do catálogo JSON')->delete();
    }

    private function determinarCategoria(string $nome): string
    {
        $nome = strtolower($nome);
        
        if (str_contains($nome, 'cadeira')) return 'Cadeiras e Banquetas';
        if (str_contains($nome, 'poltrona') || str_contains($nome, 'puff')) return 'Poltronas e Puffs';
        if (str_contains($nome, 'sofá') || str_contains($nome, 'sofa')) return 'Sofás';
        if (str_contains($nome, 'colchão') || str_contains($nome, 'colchao') || str_contains($nome, 'cama') || str_contains($nome, 'cabeceira')) return 'Colchões e Camas';
        if (str_contains($nome, 'carro') || str_contains($nome, 'suv') || str_contains($nome, 'caminhão') || str_contains($nome, 'teto veicular')) return 'Automotivo';
        if (str_contains($nome, 'bebê') || str_contains($nome, 'bebe') || str_contains($nome, 'carrinho') || str_contains($nome, 'urso')) return 'Bebê e Criança';
        if (str_contains($nome, 'tapete') || str_contains($nome, 'cortina') || str_contains($nome, 'persiana')) return 'Tapetes e Cortinas';
        
        return 'Diversos';
    }
};