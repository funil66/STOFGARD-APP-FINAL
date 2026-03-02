# 📖 Manual de Operação AUTONOMIA ILIMITADA

Manual completo para operação do sistema de gestão AUTONOMIA ILIMITADA.

---

## 📑 Índice

1. [Fluxo de Vendas](#-fluxo-de-vendas)
2. [Módulo Financeiro](#-módulo-financeiro)
3. [Ordens de Serviço](#-ordens-de-serviço)
4. [Agenda e Google Calendar](#-agenda-e-google-calendar)
5. [PIX Automático](#-pix-automático)
6. [Troubleshooting](#-troubleshooting)

---

## 🛒 Fluxo de Vendas

### 1. Captação de Lead

#### Via Site (Automático)
1. Cliente acessa `/solicitar-orcamento`
2. Preenche: Nome, Celular, Serviço, Cidade
3. Sistema cria automaticamente:
   - Cadastro do cliente (ou vincula se já existir)
   - Orçamento com status `rascunho` e etapa `novo`

#### Manual (Painel Admin)
1. Acesse **Orçamentos → Novo**
2. Selecione ou crie o cliente
3. Preencha os dados do orçamento
4. Salve como rascunho

### 2. Elaboração do Orçamento

1. Acesse o orçamento criado
2. Adicione itens da **Tabela de Preços**
3. Configure:
   - **Desconto do Prestador**: Valor fixo em R$
   - **PIX no PDF**: Marque para incluir QR Code
   - **Desconto PIX**: Aplica % configurado globalmente
4. Selecione **Vendedor** e **Loja** (para comissões)

### 3. Envio ao Cliente

1. Clique no ícone **PDF** (verde) na listagem
2. Baixe ou visualize o PDF
3. Opções de compartilhamento:
   - **WhatsApp**: Usa link assinado público
   - **Email**: Anexe o PDF gerado

### 4. Aprovação e Geração de OS

1. Na listagem, clique no ícone **Aprovar** (✓)
2. Preencha no modal:
   - Data do serviço
   - Horário início/fim
   - Local do serviço
3. O sistema automaticamente:
   - Muda status para `aprovado`
   - Cria **Ordem de Serviço**
   - Cria **Agenda** vinculada
   - Gera **Lançamento Financeiro** (se configurado)

---

## 💰 Módulo Financeiro

### Tipos de Transação

| Tipo | Descrição | Cor |
|------|-----------|-----|
| ↓ Entrada | Receitas (vendas, recebimentos) | Verde |
| ↑ Saída | Despesas (compras, pagamentos) | Vermelho |

### Status

| Status | Significado | Ação |
|--------|-------------|------|
| ⏳ Pendente | Aguardando pagamento | - |
| ✓ Pago | Quitado | Confirme com data de pagamento |
| ! Atrasado | Vencido e não pago | Contate cliente |
| ✗ Cancelado | Transação cancelada | - |

### Lançar Despesa

1. Acesse **Financeiro → Transações → Nova**
2. Selecione **Tipo: Saída**
3. Preencha:
   - Cliente/Fornecedor
   - Descrição
   - Valor
   - Categoria
   - Data de vencimento
4. Salve

### Dar Baixa em Pagamento

1. Na listagem, clique no registro
2. Edite o status para **Pago**
3. Preencha:
   - Data do pagamento
   - Valor pago
   - Forma de pagamento
4. Salve

### Comissões

Comissões são geradas automaticamente quando:
- Orçamento é aprovado e tem Vendedor/Loja vinculados
- Cadastro do Vendedor/Loja tem `comissao_percentual` configurado

Para pagar comissão:
1. Filtre por **Comissões Pendentes**
2. Abra o registro
3. Marque **Comissão Paga**
4. Informe data do pagamento

### Filtros Rápidos

- **Hoje / Esta Semana / Este Mês**: Atalhos de período
- **Tipo**: Entradas ou Saídas
- **Status**: Pendentes, Pagos, Atrasados
- **Categoria**: Por categoria financeira

---

## 📋 Ordens de Serviço

### Ciclo de Vida

```
Aberta → Agendada → Em Execução → Concluída
              ↓
          Cancelada
```

### Criar OS Manual

1. Acesse **Ordens de Serviço → Nova**
2. Preencha:
   - Cliente
   - Loja / Vendedor
   - Tipo de serviço
   - Itens do serviço
3. Salve

### Concluir OS

1. Abra a OS
2. Mude status para **Concluída**
3. Se desejar, gere o PDF para assinatura do cliente
4. Anexe fotos do serviço (opcional)

### Campos Personalizados

Use **Detalhes Adicionais (Personalizado)** para:
- Tecido do sofá
- Cor
- Quantidade de peças
- Observações específicas

---

## 📅 Agenda e Google Calendar

### Configuração Inicial

1. Acesse **Configurações → Google Calendar**
2. Clique em **Conectar Google**
3. Autorize o acesso à sua conta Google
4. Selecione o calendário padrão

### Sincronização Automática

Quando uma OS é criada:
1. Evento é criado no Google Calendar
2. Título: `[OS-XXX] Nome do Cliente`
3. Descrição: Detalhes do serviço
4. Local: Endereço do cliente

### Editar Agendamento

1. Acesse **Agenda** no painel
2. Edite data/hora
3. Salvamento sincroniza com Google

---

## 💳 PIX Automático

### Configuração de Chaves

1. Acesse **Configurações → PIX**
2. Adicione suas chaves:
   - Tipo (CPF, CNPJ, Email, Celular, Aleatória)
   - Chave
   - Titular
3. Configure **Desconto à Vista** (%)

### Gerar PIX no Orçamento

1. Edite o orçamento
2. Marque **Gerar QR Code PIX**
3. Selecione a chave desejada
4. (Opcional) Marque **Aplicar Desconto PIX**
5. Salve e gere o PDF

### Verificar Pagamento

O sistema suporta webhook automático (EFI/Gerencianet):
- Pagamento confirmado atualiza status
- Notificação é enviada

---

## 🔧 Troubleshooting

### O PDF não gerou

**Sintomas**: Erro ao clicar em PDF, página em branco

**Soluções**:
1. Verifique se o Chrome/Chromium está instalado:
   ```bash
   which chromium || which google-chrome
   ```
2. Verifique variáveis de ambiente:
   ```
   CHROME_PATH=/usr/bin/chromium
   ```
3. Teste geração manual:
   ```bash
   php artisan tinker
   > app(\App\Http\Controllers\OrcamentoPdfController::class)->gerarPdf(\App\Models\Orcamento::first())
   ```

### Agenda do Google não sincronizou

**Sintomas**: Evento não aparece no Google Calendar

**Soluções**:
1. Verifique conexão:
   - Acesse **Configurações → Google Calendar**
   - Se desconectado, reconecte
2. Verifique permissões:
   - Acesso ao calendário deve estar autorizado
3. Verifique logs:
   ```bash
   tail -f storage/logs/laravel.log | grep -i google
   ```

### PIX não está gerando QR Code

**Sintomas**: PDF sem QR Code ou erro

**Soluções**:
1. Verifique se chave está selecionada no orçamento
2. Verifique configuração das chaves em **Configurações → PIX**
3. Para PIX via EFI (cobrança com webhook):
   - Verifique credenciais `EFI_CLIENT_ID` / `EFI_CLIENT_SECRET`
   - Verifique certificado `.pem`
   - Teste em sandbox primeiro

### Erro ao acessar arquivos/downloads

**Sintomas**: 404 ou 403 ao baixar arquivo

**Soluções**:
1. Arquivo pode ter sido excluído
2. Extensão não permitida (ver lista em `FileDownloadController`)
3. Verifique permissões do storage:
   ```bash
   chmod -R 775 storage
   chown -R www-data:www-data storage
   ```

### Sistema lento

**Sintomas**: Páginas demoram para carregar

**Soluções**:
1. Otimize cache:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
2. Verifique filas em background:
   ```bash
   php artisan queue:work
   ```
3. Limpe cache antigo:
   ```bash
   php artisan cache:clear
   ```

### Backup não executou

**Sintomas**: Backup não aparece no disco

**Soluções**:
1. Verifique agendamento:
   ```bash
   php artisan schedule:list
   ```
2. Execute manualmente:
   ```bash
   php artisan backup:run
   ```
3. Verifique espaço em disco
4. Verifique configuração do disco de destino em `config/backup.php`

---

## 📞 Suporte Técnico

Para problemas não listados:

1. Colete informações:
   - Mensagem de erro exata
   - Passos para reproduzir
   - Logs relevantes (`storage/logs/laravel.log`)

2. Execute diagnóstico:
   ```bash
   php artisan iron:check
   ```

3. Contate o desenvolvedor com:
   - Screenshot do erro
   - Resultado do `iron:check`
   - Trecho relevante dos logs

---

**Versão do Manual:** 1.0.0  
**Última atualização:** Fevereiro 2026
