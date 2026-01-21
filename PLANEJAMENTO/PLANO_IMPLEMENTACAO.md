# 📋 PLANO DE IMPLEMENTAÇÃO - ORÇAMENTO E ALMOXARIFADO
**Data**: 01/01/2026  
**Responsável**: Sistema Stofgard 2026

---

## 🎯 OBJETIVOS PRINCIPAIS

### 1. **Módulo Orçamento - Melhorias**
- ✅ Integrar tabelas de preços (HIGI + IMPER)
- ✅ Separar claramente Higienização vs Impermeabilização
- ✅ Padronizar itens entre Orçamento ↔ OS
- ✅ View/PDF formal e profissional
- ✅ Fluxo: Aprovar → OS → Agenda → Financeiro (automático)
- ✅ Submódulo de configuração de preços

### 2. **Módulo Almoxarifado - Reestruturação**
- ✅ 3 submódulos com navegação por overlay/modal
- ✅ Inventário de equipamentos
- ✅ Estoque visual e interativo (2 produtos)
- ✅ Lista de Desejos (já existe, adaptar)

---

## 📊 ANÁLISE DAS TABELAS DE PREÇOS

### **TABELA HIGIENIZAÇÃO** (37 itens)
```
Seções:
1. Cadeiras e Poltronas (6 itens)
2. Almofadas (1 item)
3. Colchões (4 itens)
4. Sofás (6 itens)
5. Tapetes e Cortinas (3 itens) ← M²
6. Automotivo (4 itens)
```

### **TABELA IMPERMEABILIZAÇÃO** (45 itens)
```
Todos os itens por UNIDADE
Categorias: Almofadas, Cadeiras, Colchões, Sofás, etc.
```

### **Itens que usam M²**:
- ✅ Tapete (Nacional/Importado)
- ✅ Cortina (Tecido)
- ✅ Persianas
- ❌ Resto: POR UNIDADE

---

## 🗄️ ESTRUTURA DE BANCO DE DADOS

### **Nova Tabela: `tabela_precos`**
```sql
- id
- tipo_servico (enum: 'higienizacao', 'impermeabilizacao')
- categoria (string: 'Cadeiras e Poltronas', 'Sofás', etc.)
- nome_item (string)
- unidade_medida (enum: 'unidade', 'm2')
- preco_vista (decimal)
- preco_prazo (decimal)
- ativo (boolean)
- created_at, updated_at
```

### **Alteração: Tabela `orcamentos`**
```sql
ADICIONAR:
- tipo_servico (enum: 'higienizacao', 'impermeabilizacao', 'misto')
  ↳ Identifica se há garantia na conversão para OS
```

### **Alteração: Tabela `orcamento_itens`**
```sql
MODIFICAR:
- tabela_preco_id (FK → tabela_precos) 
- quantidade (decimal: suporta m² fracionados)
- unidade_medida (enum: 'unidade', 'm2')
- MANTER: descricao, valor_unitario, subtotal
```

---

## 🔄 FLUXO DE CONVERSÃO: ORÇAMENTO → OS

### **Quando Orçamento é APROVADO**:
```
1. Criar OrdemServico:
   - tipo_servico = baseado nos itens do orçamento
     * Se TODOS higienização → 'higienizacao'
     * Se TODOS impermeabilização → 'impermeabilizacao' (GARANTIA 1 ANO)
     * Se MISTO → 'impermeabilizacao' (prioriza garantia)
   - itens = cópia dos orcamento_itens
   - Padronização: mesmo formato de descrição

2. Criar Agenda:
   - tipo = 'ordem_servico'
   - ordem_servico_id = OS criada
   - data = data_execucao do orçamento (ou solicitar)

3. Criar TransacaoFinanceira:
   - tipo = 'receita'
   - categoria = 'servico'
   - valor = valor_final do orçamento
   - status = 'pendente'
   - ordem_servico_id = OS criada
   - Se parceiro: criar também comissão (despesa)

4. Atualizar Orçamento:
   - status = 'aprovado'
   - ordem_servico_id = vincular
```

### **Quando Orçamento é RECUSADO**:
```
1. Atualizar status = 'recusado'
2. Agendar Job: Deletar após 7 dias
```

---

## 🎨 VIEW/PDF DO ORÇAMENTO

### **Elementos Obrigatórios**:
```
Header:
- Logo Stofgard (centralizada)
- "ORÇAMENTO Nº [ID]"
- CNPJ: 58.794.846/0001-20
- Telefone: (16) 99104-0195
- Responsável Técnico: [Usuario logado]
- Data/Hora Emissão: [now]

Corpo:
- Dados do Cliente
- Tipo de Serviço
- Tabela de Itens:
  * Higienização (se houver)
  * Impermeabilização (se houver)
  * Quantidade | Item | Unid. | Vlr Unit. | Subtotal

Rodapé:
- Valor Total
- Desconto PIX 10%
- Validade: 7 dias
- "O Senhor é meu Pastor e nada me faltará"
```

---

## ⚙️ SUBMÓDULO: CONFIGURAÇÃO DE PREÇOS

### **Localização**: Configurações → Gerenciar Preços

### **Funcionalidades**:
1. **Listar Preços** (tabela com filtros):
   - Filtro por tipo_servico
   - Filtro por categoria
   - Busca por nome_item

2. **Editar Preços**:
   - Inline edit ou modal
   - Atualiza preco_vista e preco_prazo
   - Log de alterações (auditoria)

3. **Adicionar Novo Item**:
   - Formulário completo
   - Validação de campos

4. **Ativar/Desativar Itens**:
   - Soft delete (campo ativo)
   - Inativos não aparecem no orçamento

### **Segurança**:
- Apenas ADMIN (allisson@stofgard.com.br) pode editar

---

## 🏪 ALMOXARIFADO - REESTRUTURAÇÃO

### **Estrutura Atual**:
```
- ProdutoResource (equipamentos/químicos)
- MovimentacaoEstoqueResource (histórico)
- ListaDesejoResource (wishlist)
```

### **Nova Estrutura (conforme RESUMO)**:

#### **Página Principal: AlmoxarifadoPage**
```blade
<div class="grid grid-cols-3 gap-6">
    <!-- Card 1: Inventário -->
    <div @click="abrirModal('inventario')">
        🔧 Inventário de Equipamentos
    </div>
    
    <!-- Card 2: Estoque -->
    <div @click="abrirModal('estoque')">
        🧪 Estoque de Produtos
    </div>
    
    <!-- Card 3: Lista de Desejos -->
    <div @click="abrirModal('lista-desejos')">
        🛒 Lista de Desejos
    </div>
</div>
```

#### **Submódulo 1: INVENTÁRIO**
```
- Cadastro simples de equipamentos
- Campos: nome, categoria, observacoes, foto
- Categorias: Extratora, Pulverizadora, Bucha, Balde, etc.
- Lista com pesquisa
```

#### **Submódulo 2: ESTOQUE (INTERATIVO)**
```
Produtos Fixos:
1. Impermeabilizante
2. Frotador

Para cada produto:
- Volume atual (Litros)
- Histórico de movimentações
- Gráfico de consumo
- Widget visual: 🪣 (1 galão = 20L)
- Alerta crítico: Se volume < 20L → Notificação

Funcionalidades:
- Adicionar volume (entrada)
- Registrar uso (saída)
- Gráfico de linha (últimos 30 dias)
```

#### **Submódulo 3: LISTA DE DESEJOS**
```
- Já implementado
- Manter como está
- Adicionar link "Enviar para Lista" no Inventário
```

---

## 🚧 PLANO DE EXECUÇÃO (Ordem Sequencial)

### **FASE 1: Preparação do Banco de Dados** ⏱️ ~30min
```
1.1. Criar migration: tabela_precos
1.2. Criar migration: alterar orcamentos (add tipo_servico)
1.3. Criar migration: alterar orcamento_itens (add unidade_medida)
1.4. Criar seeder: popular tabela_precos (HIGI + IMPER)
1.5. Executar migrations
```

### **FASE 2: Models e Relacionamentos** ⏱️ ~20min
```
2.1. Criar model TabelaPreco
2.2. Atualizar model Orcamento (add campo, relations)
2.3. Atualizar model OrcamentoItem (add campo)
2.4. Testar relacionamentos
```

### **FASE 3: Resource Configuração de Preços** ⏱️ ~40min
```
3.1. Criar TabelaPrecoResource
3.2. Form com validações
3.3. Table com filtros e busca
3.4. Permissão apenas ADMIN
3.5. Testar CRUD
```

### **FASE 4: Melhorar OrcamentoResource** ⏱️ ~60min
```
4.1. Separar formulário: Higienização | Impermeabilização
4.2. Select de itens → TabelaPreco (filtrado por tipo)
4.3. Calcular automaticamente m² vs unidade
4.4. Adicionar campo data_execucao (para agenda)
4.5. Testar criação de orçamento
```

### **FASE 5: View/PDF do Orçamento** ⏱️ ~50min
```
5.1. Criar blade: orcamento-pdf.blade.php
5.2. Adicionar logo, CNPJ, dados formais
5.3. Separar seções HIGI e IMPER
5.4. Adicionar rodapé com validade
5.5. Action "Gerar PDF" (dompdf/snappy)
5.6. Testar impressão
```

### **FASE 6: Fluxo de Aprovação** ⏱️ ~70min
```
6.1. Action "Aprovar Orçamento":
   - Criar OrdemServico
   - Criar Agenda
   - Criar TransacaoFinanceira
   - Atualizar status orçamento

6.2. Action "Recusar Orçamento":
   - Atualizar status
   - Agendar Job exclusão (7 dias)

6.3. Job: DeleteRecusedOrcamentos (scheduled)
6.4. Testar conversão completa
```

### **FASE 7: Almoxarifado - Inventário** ⏱️ ~40min
```
7.1. Criar migration: equipamentos
7.2. Criar model Equipamento
7.3. Criar EquipamentoResource (simples)
7.4. Testar CRUD
```

### **FASE 8: Almoxarifado - Estoque Interativo** ⏱️ ~90min
```
8.1. Criar Page: EstoquePage (Livewire)
8.2. Criar componente: ProdutoEstoqueCard
8.3. Implementar widget galões (20L = 🪣)
8.4. Gráfico ApexCharts/ChartJS
8.5. Sistema de alertas (< 20L)
8.6. Histórico de movimentações
8.7. Testar entrada/saída
```

### **FASE 9: Integração Almoxarifado** ⏱️ ~30min
```
9.1. Criar AlmoxarifadoPage (3 cards)
9.2. Modal/Overlay para cada submódulo
9.3. Navegação entre seções
9.4. Link dashboard → Almoxarifado
9.5. Testar navegação
```

### **FASE 10: Testes Integrados** ⏱️ ~60min
```
10.1. Criar orçamento completo (HIGI + IMPER)
10.2. Aprovar → Verificar OS criada
10.3. Verificar Agenda criada
10.4. Verificar Financeiro registrado
10.5. Testar edição de preços
10.6. Testar Almoxarifado completo
10.7. Corrigir bugs encontrados
```

---

## ⚠️ PONTOS CRÍTICOS DE ATENÇÃO

### 🔴 **Não Quebrar**:
1. ✅ Orçamentos existentes (compatibilidade)
2. ✅ OS existentes (manter estrutura)
3. ✅ Relacionamentos Cliente/Parceiro
4. ✅ Google Calendar sync

### 🟡 **Migração de Dados**:
- Orçamentos antigos: adicionar tipo_servico = 'misto'
- OrcamentoItens antigos: unidade_medida = 'unidade'

### 🟢 **Backup Antes**:
```bash
php artisan backup:run
# ou
cp database/database.sqlite database/database_backup_$(date +%Y%m%d).sqlite
```

---

## 📝 VALIDAÇÕES NECESSÁRIAS

### **Orçamento**:
- [ ] Pelo menos 1 item
- [ ] Tipo serviço definido
- [ ] Valor > 0
- [ ] Cliente associado

### **Conversão OS**:
- [ ] Status = 'pendente' (não converter 2x)
- [ ] Data execução válida
- [ ] Itens padronizados corretos

### **Preços**:
- [ ] Valores > 0
- [ ] Preço prazo >= preço vista
- [ ] Nome item único por tipo

---

## 🎯 RESULTADO ESPERADO

### **Orçamento**:
✅ Itens integrados com tabelas reais  
✅ Separação clara HIGI/IMPER  
✅ PDF profissional  
✅ Conversão automática OS+Agenda+Financeiro  
✅ Gerenciamento de preços fácil  

### **Almoxarifado**:
✅ 3 submódulos organizados  
✅ Estoque visual e interativo  
✅ Alertas de escassez  
✅ Navegação intuitiva  

---

## 📅 CRONOGRAMA ESTIMADO

**Total**: ~7-8 horas de desenvolvimento  
**Período sugerido**: 2-3 dias (com testes)

**Início**: 01/01/2026  
**Conclusão prevista**: 03/01/2026

---

## ✅ CHECKLIST FINAL

- [ ] Migrations executadas
- [ ] Seeds populados
- [ ] Resources testados
- [ ] PDF gerado corretamente
- [ ] Conversão OS funcionando
- [ ] Almoxarifado navegável
- [ ] Estoque interativo
- [ ] Sem erros no console
- [ ] Performance OK
- [ ] Backup realizado

---

**Status**: 📋 PLANEJAMENTO COMPLETO - PRONTO PARA EXECUÇÃO
