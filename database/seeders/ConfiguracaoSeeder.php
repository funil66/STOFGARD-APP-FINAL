<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuracao;

class ConfiguracaoSeeder extends Seeder {
    public function run(): void {
        $payload = [
            'empresa_nome' => 'Stofgard - Higienização e Impermeabilização',
            'empresa_cnpj' => '00.000.000/0001-00',
            'empresa_telefone' => '(16) 99999-9999',
            'empresa_email' => 'contato@stofgard.com.br',
            'desconto_pix' => 10.00, // 10%

            // Taxas de Mercado (Simulação)
            'taxas_parcelamento' => [
                2 => 1.0459, // 4.59%
                3 => 1.0599,
                4 => 1.0750,
                5 => 1.0900,
                6 => 1.1050,
                7 => 1.1200,
                8 => 1.1350,
                9 => 1.1500,
                10 => 1.1650,
                11 => 1.1800,
                12 => 1.1950,
            ],
            // Opções para o Select de Pagamento
            'opcoes_pagamento_personalizado' => [
                'pix' => 'Pix (10% OFF)',
                'dinheiro' => 'Dinheiro (10% OFF)',
                'credito' => 'Cartão de Crédito',
                'boleto' => 'Boleto Bancário',
            ],
            // Cores para o PDF
            'cores_pdf' => [
                'primaria' => '#1e3a8a', // Azul Stofgard
                'secundaria' => '#475569', // Cinza
            ],
            
            'termos_garantia' => '<p>1. Garantia de 1 ano para impermeabilização.</p><p>2. Manchas de urina e tintas não são cobertas.</p>',
        ];

        // Only set fields that actually exist in the current DB schema
        $allowed = [];
        foreach ($payload as $key => $value) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('configuracoes', $key)) {
                $allowed[$key] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
            }
        }

        if (! empty($allowed)) {
            // Ensure required 'grupo' exists for the insert (legacy compatibility)
            if (\Illuminate\Support\Facades\Schema::hasColumn('configuracoes', 'grupo') && ! isset($allowed['grupo'])) {
                $allowed['grupo'] = 'empresa';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('configuracoes', 'chave') && ! isset($allowed['chave'])) {
                $allowed['chave'] = 'padrao';
            }

            Configuracao::updateOrCreate(['id' => 1], $allowed);
        } else {
            // Fallback: ensure at least a single row exists for legacy code
            // Use a safe default for 'grupo' if the column exists
            if (\Illuminate\Support\Facades\Schema::hasColumn('configuracoes', 'grupo')) {
                Configuracao::firstOrCreate(['id' => 1], ['grupo' => 'empresa']);
            } else {
                Configuracao::firstOrCreate(['id' => 1]);
            }
        }
    }
}
