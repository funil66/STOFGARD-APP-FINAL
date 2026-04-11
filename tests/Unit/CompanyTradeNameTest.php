<?php

namespace Tests\Unit;

use App\Models\Configuracao;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTradeNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_trade_name_prefers_more_descriptive_non_generic_value(): void
    {
        Setting::set('empresa_nome', 'STOFGARD', 'geral', 'string');
        Setting::set('nome_sistema', 'STOFGARD', 'geral', 'string');

        Configuracao::create([
            'grupo' => 'empresa',
            'chave' => 'padrao',
            'empresa_nome' => 'STOFGARD HIGIENIZAÇÃO E IMPERMEABILIZAÇÃO',
            'empresa_cnpj' => '58.794.846/0001-20',
            'empresa_telefone' => '(16) 99753-9698',
            'empresa_email' => 'contato@stofgard.com.br',
        ]);

        $this->assertSame('STOFGARD HIGIENIZAÇÃO E IMPERMEABILIZAÇÃO', company_trade_name());
    }

    public function test_company_pdf_identity_prefers_fresh_settings_over_generic_configuracao(): void
    {
        Setting::set('empresa_nome', 'STOFGARD HIGIENIZAÇÃO E IMPERMEABILIZAÇÃO', 'geral', 'string');
        Setting::set('empresa_cnpj', '58.794.846/0001-20', 'geral', 'string');
        Setting::set('empresa_telefone', '(16) 99753-9698', 'geral', 'string');
        Setting::set('empresa_email', 'contato@stofgard.com.br', 'geral', 'string');
        Setting::set('empresa_logo', 'logos/stofgard.png', 'geral', 'string');

        Configuracao::create([
            'grupo' => 'empresa',
            'chave' => 'padrao',
            'empresa_nome' => 'STOFGARD',
            'empresa_cnpj' => '00.000.000/0001-00',
            'empresa_telefone' => '(00) 00000-0000',
            'empresa_email' => 'contato@minhaempresa.com.br',
            'empresa_logo' => 'logos/default.png',
        ]);

        $identity = company_pdf_identity();

        $this->assertSame('STOFGARD HIGIENIZAÇÃO E IMPERMEABILIZAÇÃO', $identity['empresa_nome']);
        $this->assertSame('58.794.846/0001-20', $identity['empresa_cnpj']);
        $this->assertSame('(16) 99753-9698', $identity['empresa_telefone']);
        $this->assertSame('contato@stofgard.com.br', $identity['empresa_email']);
        $this->assertSame('logos/stofgard.png', $identity['empresa_logo']);
    }
}
