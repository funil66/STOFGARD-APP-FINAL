<?php

namespace Database\Seeders;

use App\Models\Configuracao;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ConfigSeed extends Seeder
{
    /**
     * Seeder das configurações personalizadas do sistema STOFGARD
     * Preserva todas as configurações definidas pelo usuário
     */
    public function run(): void
    {
        $this->command->info('🔧 Populando configurações do sistema...');

        // Configurações da tabela 'configuracoes'
        $this->seedConfiguracoes();
        
        // Settings da tabela 'settings'
        $this->seedSettings();
        
        $this->command->info('✅ Configurações populadas com sucesso!');
    }

    private function seedConfiguracoes(): void
    {
        $configuracoes = [
            [
                'chave' => 'padrao',
                'valor' => 'https://wttr.in/Ribeirao+Preto?0QT&lang=pt',
            ],
        ];

        foreach ($configuracoes as $config) {
            Configuracao::updateOrCreate(
                ['chave' => $config['chave']],
                $config
            );
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            // Informações da Empresa
            [
                'key' => 'nome_sistema',
                'value' => 'STOFGARD',
            ],
            [
                'key' => 'empresa_nome',
                'value' => 'STOFGARD HIGIENIZAÇÃO E IMPERMEABILIZAÇÃO',
            ],
            [
                'key' => 'empresa_logo',
                'value' => 'logos/01KGD171X1E39NDQV340FGHDKT.png',
            ],
            [
                'key' => 'empresa_cnpj',
                'value' => '58.794.846/0001-20',
            ],
            [
                'key' => 'empresa_telefone',
                'value' => '(16) 99753-9698',
            ],
            [
                'key' => 'empresa_email',
                'value' => 'contato@stofgard.com.br',
            ],
            [
                'key' => 'empresa_endereco',
                'value' => 'Rua Escritor José Mauro de Vasconcelos - nº 155 - Planalto Verde - CEP 14056-150 - Ribeirão Preto - SP',
            ],
            
            // Configurações do Dashboard
            [
                'key' => 'dashboard_frase',
                'value' => 'BORA TRABALHAR!',
            ],
            [
                'key' => 'dashboard_mostrar_clima',
                'value' => '1',
            ],
            [
                'key' => 'url_clima',
                'value' => 'https://www.tempo.com/munique.htm',
            ],
            
            // Catálogo de Serviços V2 (JSON) - OBSOLETO: Migrado para tabela tabela_precos
            [
                'key' => 'catalogo_servicos_v2',
                'value' => '[]', // Vazio - dados migrados para tabela_precos
            ],
            
            // Configurações do Sistema
            [
                'key' => 'sistema_debug',
                'value' => '0',
            ],
            [
                'key' => 'sistema_timezone',
                'value' => '',
            ],
            [
                'key' => 'admin_emails',
                'value' => '[]',
            ],
            
            // Tipos de Serviço do Sistema
            [
                'key' => 'system_service_types',
                'value' => '[{"slug":"higienizacao","label":"Higienização","color":"info","icon":"heroicon-o-sparkles"},{"slug":"impermeabilizacao","label":"Impermeabilização","color":"warning","icon":"heroicon-o-shield-check"},{"slug":"combo","label":"Combo (Higi + Imper)","color":"success","icon":"heroicon-o-squares-plus"},{"slug":"outro","label":"Outro/Personalizado","color":"gray","icon":"heroicon-o-cog"}]',
            ],
            
            // Configurações do PDF
            [
                'key' => 'pdf_color_primary',
                'value' => '#290f94',
            ],
            [
                'key' => 'pdf_color_secondary',
                'value' => '#08785e',
            ],
            [
                'key' => 'pdf_color_text',
                'value' => '#287a1a',
            ],
            [
                'key' => 'pdf_mostrar_fotos_global',
                'value' => '1',
            ],
            [
                'key' => 'pdf_include_pix_global',
                'value' => '1',
            ],
            [
                'key' => 'pdf_aplicar_desconto_global',
                'value' => '1',
            ],
            
            // Layout do PDF (JSON completo)
            [
                'key' => 'pdf_layout',
                'value' => '[{"type":"header","data":{"show_logo":true,"show_dates":true,"alignment":"left"}},{"type":"dados_cliente","data":{"titulo":"DADOS DO CLIENTE","show_email":true,"show_phone":true,"show_address":true}},{"type":"tabela_itens","data":{"titulo":"ITENS DO ORÇAMENTO","show_category_colors":true}},{"type":"container_duplo","data":{"coluna_esquerda":"totais","coluna_direita":"pix"}},{"type":"texto_livre","data":{"conteudo":"<ul><li>Orçamento válido por 7 dias.</li><li>Pagamento 50% na aprovação e 50% na entrega.</li></ul>"}},{"type":"galeria_fotos","data":{"titulo":"REGISTROS FOTOGRÁFICOS","columns":"2","show_legend":true}},{"type":"rodape_padrao","data":{"texto_legal":"Este documento não é fiscal."}}]',
            ],
            
            // Configurações Financeiras
            [
                'key' => 'financeiro_pix_keys',
                'value' => '[{"chave":"+5516981017879","titular":"ALLISSON GONÇALVES DE SOUSA","tipo":"telefone","codigo_pais":"55","validada":true},{"chave":"01809430224","titular":"RAELCIA MARIA SILVA","tipo":"cpf","codigo_pais":"55","validada":true},{"chave":"allissonsousa.adv@gmail.com","titular":"ALLISSON SOUSA","tipo":"email","codigo_pais":"","validada":true}]',
            ],
            [
                'key' => 'financeiro_desconto_avista',
                'value' => '3',
            ],
            [
                'key' => 'financeiro_parcelamento',
                'value' => '[{"parcelas":"1","taxa":0},{"parcelas":"2","taxa":"2"},{"parcelas":"3","taxa":"3"},{"parcelas":"4","taxa":"4"},{"parcelas":"5","taxa":"5"},{"parcelas":"6","taxa":"6"}]',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('   💾 ' . count($settings) . ' configurações de settings criadas/atualizadas');
    }
}
