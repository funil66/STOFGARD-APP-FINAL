<?php

namespace Database\Seeders;

use App\Services\ConfiguracaoService;
use Illuminate\Database\Seeder;

class ConfiguracaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 🏢 CONFIGURAÇÕES DA EMPRESA
        ConfiguracaoService::set('empresa', 'razao_social', 'Stofgard Limpeza e Impermeabilização LTDA', 'text', 'Razão Social da empresa');
        ConfiguracaoService::set('empresa', 'nome_fantasia', 'Stofgard', 'text', 'Nome Fantasia');
        ConfiguracaoService::set('empresa', 'cnpj', '', 'text', 'CNPJ da empresa (formato: 00.000.000/0001-00)');
        ConfiguracaoService::set('empresa', 'telefone', '(11) 99999-9999', 'text', 'Telefone principal');
        ConfiguracaoService::set('empresa', 'email', 'contato@stofgard.com.br', 'text', 'Email principal');
        ConfiguracaoService::set('empresa', 'endereco', '', 'text', 'Endereço completo');
        ConfiguracaoService::set('empresa', 'cidade', '', 'text', 'Cidade');
        ConfiguracaoService::set('empresa', 'estado', 'SP', 'text', 'Estado (UF)');
        ConfiguracaoService::set('empresa', 'cep', '', 'text', 'CEP');
        ConfiguracaoService::set('empresa', 'logo_url', '', 'file', 'URL da logo da empresa');
        ConfiguracaoService::set('empresa', 'site', '', 'text', 'Site da empresa');

        // 💰 CONFIGURAÇÕES FINANCEIRAS
        ConfiguracaoService::set('financeiro', 'pix_ativo', false, 'boolean', 'Ativar pagamentos via PIX');
        ConfiguracaoService::set('financeiro', 'pix_chave', '', 'text', 'Chave PIX principal');
        ConfiguracaoService::set('financeiro', 'pix_tipo', 'email', 'text', 'Tipo da chave PIX (cpf, cnpj, telefone, email, aleatoria)');
        ConfiguracaoService::set('financeiro', 'banco_nome', '', 'text', 'Nome do banco');
        ConfiguracaoService::set('financeiro', 'banco_agencia', '', 'text', 'Agência');
        ConfiguracaoService::set('financeiro', 'banco_conta', '', 'text', 'Número da conta');
        ConfiguracaoService::set('financeiro', 'gateway_provider', '', 'text', 'Provedor de pagamento (efi, mercadopago, pagseguro)');
        ConfiguracaoService::set('financeiro', 'gateway_client_id', '', 'text', 'Client ID da API de pagamento');
        ConfiguracaoService::set('financeiro', 'gateway_client_secret', '', 'text', 'Client Secret da API de pagamento');
        ConfiguracaoService::set('financeiro', 'gateway_sandbox', true, 'boolean', 'Usar ambiente de testes (sandbox)');
        ConfiguracaoService::set('financeiro', 'dias_vencimento_padrao', 7, 'number', 'Dias para vencimento padrão');
        ConfiguracaoService::set('financeiro', 'juros_atraso', 0, 'number', 'Percentual de juros por dia de atraso');
        ConfiguracaoService::set('financeiro', 'multa_atraso', 0, 'number', 'Percentual de multa por atraso');

        // 📄 CONFIGURAÇÕES DE NOTA FISCAL (NFS-e)
        ConfiguracaoService::set('nfse', 'ativo', false, 'boolean', 'Emissão de NFS-e ativa');
        ConfiguracaoService::set('nfse', 'ambiente', 'homologacao', 'text', 'Ambiente (homologacao ou producao)');
        ConfiguracaoService::set('nfse', 'provider', 'portal_nacional', 'text', 'Provedor (portal_nacional, prefeitura_local)');
        ConfiguracaoService::set('nfse', 'url_homologacao', '', 'text', 'URL da API de homologação');
        ConfiguracaoService::set('nfse', 'url_producao', '', 'text', 'URL da API de produção');
        ConfiguracaoService::set('nfse', 'certificado_path', '', 'file', 'Caminho do certificado digital (.pfx)');
        ConfiguracaoService::set('nfse', 'certificado_senha', '', 'text', 'Senha do certificado digital');
        ConfiguracaoService::set('nfse', 'codigo_municipio', '', 'text', 'Código do município (IBGE)');
        ConfiguracaoService::set('nfse', 'inscricao_municipal', '', 'text', 'Inscrição Municipal');
        ConfiguracaoService::set('nfse', 'aliquota_iss', 5.0, 'number', 'Alíquota de ISS (%)');
        ConfiguracaoService::set('nfse', 'codigo_servico', '', 'text', 'Código do serviço municipal');
        ConfiguracaoService::set('nfse', 'item_lista_servico', '', 'text', 'Item da lista de serviços');
        ConfiguracaoService::set('nfse', 'regime_especial', 1, 'number', 'Regime especial de tributação');
        ConfiguracaoService::set('nfse', 'natureza_operacao', 1, 'number', 'Natureza da operação');

        // ⚙️ CONFIGURAÇÕES DO SISTEMA
        ConfiguracaoService::set('sistema', 'nome_sistema', 'Stofgard 2026', 'text', 'Nome do sistema');
        ConfiguracaoService::set('sistema', 'versao', '1.0.0', 'text', 'Versão do sistema');
        ConfiguracaoService::set('sistema', 'tema', 'light', 'text', 'Tema padrão (light, dark)');
        ConfiguracaoService::set('sistema', 'cor_primaria', '#3b82f6', 'text', 'Cor primária (hex)');
        ConfiguracaoService::set('sistema', 'cor_secundaria', '#10b981', 'text', 'Cor secundária (hex)');
        ConfiguracaoService::set('sistema', 'timezone', 'America/Sao_Paulo', 'text', 'Fuso horário');
        ConfiguracaoService::set('sistema', 'formato_data', 'd/m/Y', 'text', 'Formato de data');
        ConfiguracaoService::set('sistema', 'formato_hora', 'H:i', 'text', 'Formato de hora');
        ConfiguracaoService::set('sistema', 'formato_moeda', 'R$ #,##0.00', 'text', 'Formato de moeda');
        ConfiguracaoService::set('sistema', 'idioma', 'pt_BR', 'text', 'Idioma padrão');
        ConfiguracaoService::set('sistema', 'itens_por_pagina', 25, 'number', 'Itens por página nas listagens');
        ConfiguracaoService::set('sistema', 'manutencao', false, 'boolean', 'Modo de manutenção');
        ConfiguracaoService::set('sistema', 'debug_ativo', false, 'boolean', 'Debug ativo');

        // 🔔 CONFIGURAÇÕES DE NOTIFICAÇÕES
        ConfiguracaoService::set('notificacoes', 'email_ativo', true, 'boolean', 'Notificações por email ativas');
        ConfiguracaoService::set('notificacoes', 'sms_ativo', false, 'boolean', 'Notificações por SMS ativas');
        ConfiguracaoService::set('notificacoes', 'notificar_novo_orcamento', true, 'boolean', 'Notificar quando novo orçamento for criado');
        ConfiguracaoService::set('notificacoes', 'notificar_orcamento_aprovado', true, 'boolean', 'Notificar quando orçamento for aprovado');
        ConfiguracaoService::set('notificacoes', 'notificar_servico_concluido', true, 'boolean', 'Notificar quando serviço for concluído');
        ConfiguracaoService::set('notificacoes', 'notificar_pagamento_recebido', true, 'boolean', 'Notificar quando pagamento for recebido');
        ConfiguracaoService::set('notificacoes', 'notificar_pagamento_atrasado', true, 'boolean', 'Notificar pagamentos em atraso');
        ConfiguracaoService::set('notificacoes', 'dias_alerta_atraso', 3, 'number', 'Dias para alerta de pagamento atrasado');

        $this->command->info('✅ Configurações padrão criadas com sucesso!');
    }
}
