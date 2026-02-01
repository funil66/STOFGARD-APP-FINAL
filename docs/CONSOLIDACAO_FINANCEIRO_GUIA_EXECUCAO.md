# 🚀 Guia de Execução - Consolidação do Sistema Financeiro

**Data:** 01/02/2026  
**Autor:** Auditoria Técnica STOFGARD  
**Status:** ⚠️ **CRÍTICO - REQUER BACKUP ANTES DE EXECUTAR**

---

## ⚠️ PRÉ-REQUISITOS OBRIGATÓRIOS

### 1. **BACKUP COMPLETO DO BANCO DE DADOS**

```bash
# MySQL/MariaDB
mysqldump -u root -p stofgard_db > backup_pre_consolidacao_$(date +%Y%m%d_%H%M%S).sql

# PostgreSQL
pg_dump stofgard_db > backup_pre_consolidacao_$(date +%Y%m%d_%H%M%S).sql
```

### 2. **Verificar Estado Atual**

```sql
-- Verificar quantos registros existem
SELECT COUNT(*) as total_financeiros FROM financeiros;
SELECT COUNT(*) as total_transacoes FROM transacoes_financeiras;

-- Verificar se há dados em transacoes_financeiras
SELECT COUNT(*) FROM transacoes_financeiras WHERE deleted_at IS NULL;
```

**⚠️ SE `transacoes_financeiras` tiver dados (> 0), NÃO pule a Fase 2.6**

### 3. **Ambiente de Teste**

Recomendado: Execute **PRIMEIRO** em ambiente de desenvolvimento/staging com cópia dos dados de produção.

---

## 📋 ARQUIVOS CRIADOS/MODIFICADOS

### Migrations Criadas (executar em ordem)
1. ✅ `DISABLED_2026_01_30_191600_create_financeiros_real_table.php` - Desabilitada
2. ✅ `2026_02_01_062306_add_composite_indexes_to_financeiros_table.php`
3. ✅ `2026_02_01_062327_create_financeiro_audit_view.php`
4. ✅ `2026_02_01_062355_convert_cadastro_id_to_integer_in_financeiros.php` ⚠️ **CRÍTICA**
5. ✅ `2026_02_01_062500_consolidate_categoria_to_categoria_id_in_financeiros.php`

### Models Atualizados
- ✅ `app/Models/Financeiro.php` - Removida lógica legada, adicionado relationship `cadastro()`

### Resources Atualizados
- ✅ `app/Filament/Resources/FinanceiroResource.php` - Substituído options() por relationship

---

## 🎯 FASES DE EXECUÇÃO

### **FASE 1 - Estabilização (Seguro, Não Quebra Nada)** ✅

#### Passo 1.1: Rodar Migrations Seguras

```bash
cd "/home/funil/Área de trabalho/STOFGARD/APP STOFGARD 2026"

# Executar migrations de índices e auditoria
php artisan migrate --path=database/migrations/2026_02_01_062306_add_composite_indexes_to_financeiros_table.php
php artisan migrate --path=database/migrations/2026_02_01_062327_create_financeiro_audit_view.php
```

#### Passo 1.2: Verificar View de Auditoria

```sql
SELECT * FROM financeiro_audit;
```

**Resultado esperado:**
```
+-------------------------+------------------+-----------+-------+----------------+--------------+---------------------+
| tabela                  | total_registros  | pendentes | pagos | total_entradas | total_saidas | ultimo_registro     |
+-------------------------+------------------+-----------+-------+----------------+--------------+---------------------+
| financeiros             | 150              | 45        | 105   | 125000.00      | 35000.00     | 2026-01-31 18:30:00 |
| transacoes_financeiras  | 0                | 0         | 0     | 0.00           | 0.00         | NULL                |
+-------------------------+------------------+-----------+-------+----------------+--------------+---------------------+
```

---

### **FASE 2 - Consolidação (CRÍTICA - Requer Backup)** ⚠️

#### Passo 2.1: Verificar Integridade Antes da Conversão

```sql
-- Verificar formatos de cadastro_id existentes
SELECT 
    CASE 
        WHEN cadastro_id LIKE 'cliente_%' THEN 'string_cliente'
        WHEN cadastro_id LIKE 'parceiro_%' THEN 'string_parceiro'
        WHEN cadastro_id REGEXP '^[0-9]+$' THEN 'numerico'
        ELSE 'outro'
    END as formato,
    COUNT(*) as quantidade
FROM financeiros
WHERE cadastro_id IS NOT NULL
GROUP BY formato;
```

#### Passo 2.2: Executar Conversão de cadastro_id ⚠️

```bash
php artisan migrate --path=database/migrations/2026_02_01_062355_convert_cadastro_id_to_integer_in_financeiros.php
```

**O que acontece nesta migration:**
1. ✅ Cria coluna temporária `cadastro_id_new` (integer)
2. ✅ Migra dados de `cliente_id` legado
3. ✅ Converte strings "cliente_123" → ID do Cadastro
4. ✅ Remove coluna antiga e renomeia nova
5. ✅ Cria FK para tabela `cadastros`
6. ✅ Remove colunas legadas `cliente_id` e `parceiro_id`

**Saída esperada:**
```
✓ Coluna temporária cadastro_id_new criada
✓ Migrados 50 registros de cliente_id
✓ Convertidos 100 registros com cadastro_id string
⚠ 2 registros não puderam ser convertidos (ficarão NULL)

📊 RESUMO DA MIGRAÇÃO:
   Total de registros: 152
   Migrados com sucesso: 150
   Pendentes (NULL): 2

✓ Coluna cadastro_id substituída por integer
✓ Foreign key criada para tabela cadastros
✓ Colunas legadas cliente_id e parceiro_id removidas

✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!
```

#### Passo 2.3: Verificar Resultados

```sql
-- Verificar integridade referencial
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN cadastro_id IS NOT NULL THEN 1 ELSE 0 END) as com_cadastro,
    SUM(CASE WHEN cadastro_id IS NULL THEN 1 ELSE 0 END) as sem_cadastro
FROM financeiros;

-- Listar registros sem cadastro (se houver)
SELECT id, descricao, valor, data 
FROM financeiros 
WHERE cadastro_id IS NULL
LIMIT 10;
```

#### Passo 2.4: Consolidar Categorias

```bash
php artisan migrate --path=database/migrations/2026_02_01_062500_consolidate_categoria_to_categoria_id_in_financeiros.php
```

**Saída esperada:**
```
📋 Encontradas 12 categorias únicas como string
  ✓ Criada categoria: Serviço de Limpeza
  ✓ Criada categoria: Material de Construção
  ✓ Criada categoria: Comissão Vendedor
  ...
✅ 12 novas categorias criadas
✅ 150 registros financeiros atualizados com categoria_id
✅ Coluna categoria (string) removida

🎉 CONSOLIDAÇÃO DE CATEGORIAS CONCLUÍDA!
```

#### Passo 2.5: Validação Final

```sql
-- Verificar se todas as FKs estão corretas
SELECT 
    f.id,
    f.cadastro_id,
    c.nome as nome_cadastro,
    c.tipo as tipo_cadastro,
    f.categoria_id,
    cat.nome as nome_categoria
FROM financeiros f
LEFT JOIN cadastros c ON f.cadastro_id = c.id
LEFT JOIN categorias cat ON f.categoria_id = cat.id
LIMIT 10;
```

---

### **FASE 2.6 - Arquivar TransacaoFinanceira (OPCIONAL)** 🔄

**⚠️ Execute apenas se `transacoes_financeiras` tiver dados**

#### Criar Migration de Arquivamento

```bash
php artisan make:migration archive_transacoes_financeiras_to_legacy
```

**Conteúdo da migration:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Migrar dados para financeiros
            $transacoes = DB::table('transacoes_financeiras')
                ->whereNull('deleted_at')
                ->get();

            foreach ($transacoes as $t) {
                DB::table('financeiros')->insert([
                    'cadastro_id' => $t->cadastro_id,
                    'tipo' => $t->tipo === 'receita' ? 'entrada' : 'saida',
                    'descricao' => $t->descricao ?? 'Migrado de transacoes_financeiras',
                    'valor' => $t->valor_total ?? 0,
                    'status' => $t->status ?? 'pendente',
                    'categoria_id' => $t->categoria_id,
                    'data' => $t->data_vencimento ?? $t->created_at,
                    'data_vencimento' => $t->data_vencimento,
                    'data_pagamento' => $t->data_pagamento,
                    'created_at' => $t->created_at,
                    'updated_at' => $t->updated_at,
                ]);
            }

            // Renomear tabela
            Schema::rename('transacoes_financeiras', 'transacoes_financeiras_legacy_backup');
        });
    }

    public function down(): void
    {
        Schema::rename('transacoes_financeiras_legacy_backup', 'transacoes_financeiras');
    }
};
```

---

## 🧪 TESTES PÓS-MIGRAÇÃO

### 1. **Teste no Painel Filament**

1. Acesse: `/admin/financeiros`
2. Clique em "Novo" → Verifique se campo "Cliente/Fornecedor" mostra cadastros
3. Edite um registro existente → Verifique se o nome do cadastro aparece corretamente
4. Verifique filtros e busca

### 2. **Teste de Performance**

```sql
-- Query antes (lenta)
SELECT * FROM financeiros WHERE cadastro_id LIKE 'cliente_%' LIMIT 100;

-- Query depois (rápida com índice)
SELECT f.*, c.nome 
FROM financeiros f
INNER JOIN cadastros c ON f.cadastro_id = c.id
WHERE f.status = 'pendente' AND f.tipo = 'entrada'
LIMIT 100;

-- Verificar uso de índice
EXPLAIN SELECT * FROM financeiros WHERE cadastro_id = 1 AND status = 'pendente' AND tipo = 'entrada';
```

### 3. **Teste de Integridade Referencial**

```sql
-- Deve retornar 0 (nenhum órfão)
SELECT COUNT(*) as orfaos
FROM financeiros f
LEFT JOIN cadastros c ON f.cadastro_id = c.id
WHERE f.cadastro_id IS NOT NULL AND c.id IS NULL;
```

---

## 🔄 ROLLBACK (Em Caso de Emergência)

### Opção 1: Rollback de Migrations

```bash
# Reverter última migration
php artisan migrate:rollback --step=1

# Reverter todas as migrations da consolidação
php artisan migrate:rollback --step=5
```

⚠️ **ATENÇÃO:** O rollback da migration de conversão NÃO recupera os dados originais exatamente como eram.

### Opção 2: Restaurar Backup

```bash
# MySQL/MariaDB
mysql -u root -p stofgard_db < backup_pre_consolidacao_YYYYMMDD_HHMMSS.sql

# PostgreSQL
psql stofgard_db < backup_pre_consolidacao_YYYYMMDD_HHMMSS.sql
```

---

## 📊 MÉTRICAS DE SUCESSO

### ✅ Checklist de Validação

- [ ] View `financeiro_audit` criada e retorna dados
- [ ] Coluna `cadastro_id` é integer (não string)
- [ ] FK `financeiros.cadastro_id` → `cadastros.id` existe
- [ ] Coluna `categoria` (string) removida
- [ ] Coluna `categoria_id` (FK) existe
- [ ] Colunas `cliente_id` e `parceiro_id` removidas
- [ ] Migration `DISABLED_*_create_financeiros_real_table.php` não executa
- [ ] Painel Filament funciona sem erros
- [ ] Cadastros aparecem corretamente no select
- [ ] Nenhum registro órfão (sem cadastro válido)

### 📈 Ganhos Esperados

| Métrica | Antes | Depois | Melhoria |
|---------|-------|--------|----------|
| **Queries de busca** | ~150ms | ~20ms | 🚀 **7.5x mais rápido** |
| **Integridade** | ❌ Sem FK | ✅ Com FK | Previne dados órfãos |
| **Código duplicado** | 3 sistemas | 1 sistema | 📉 **-66% complexidade** |
| **Manutenção** | Difícil | Simples | 👍 Código limpo |

---

## 🚨 PROBLEMAS CONHECIDOS & SOLUÇÕES

### Problema 1: "Duplicate column name 'cadastro_id'"

**Causa:** Migration já rodou parcialmente  
**Solução:**
```sql
-- Verificar estrutura da tabela
DESCRIBE financeiros;

-- Se cadastro_id já for integer, pule a migration 2026_02_01_062355
```

### Problema 2: "Cannot add foreign key constraint"

**Causa:** Existem valores em `cadastro_id` que não existem em `cadastros.id`  
**Solução:**
```sql
-- Encontrar registros órfãos
SELECT f.* 
FROM financeiros f
LEFT JOIN cadastros c ON f.cadastro_id = c.id
WHERE f.cadastro_id IS NOT NULL AND c.id IS NULL;

-- Corrigir manualmente ou setar NULL
UPDATE financeiros SET cadastro_id = NULL WHERE id IN (1, 2, 3);
```

### Problema 3: Migration trava por muito tempo

**Causa:** Tabela `financeiros` muito grande (> 100k registros)  
**Solução:**
```sql
-- Processar em lotes menores
-- Editar migration e adicionar:
DB::table('financeiros')->chunk(1000, function($rows) {
    // processar lote
});
```

---

## 📞 SUPORTE

**Em caso de problemas críticos:**

1. ❌ **NÃO execute `migrate:fresh`** em produção
2. 📸 Tire screenshots dos erros
3. 📋 Copie logs: `storage/logs/laravel.log`
4. 💾 Mantenha o backup seguro
5. 🔄 Use rollback se necessário

---

## 📅 PRÓXIMOS PASSOS (Pós-Consolidação)

### Fase 3 - Refatoração de Cadastros (Opcional)
- [ ] Criar `ClienteResource` especializado
- [ ] Criar `LojaResource` especializado
- [ ] Criar `ParceiroResource` especializado

### Fase 4 - Limpeza Final
- [ ] Remover `TransacaoFinanceiraResource.php`
- [ ] Remover Model `TransacaoFinanceira.php`
- [ ] Remover migrations duplicadas de categorias
- [ ] Deletar pasta `database/migrations/archived/` (se seguro)

### Fase 5 - Testes Automatizados
- [ ] Criar teste: `FinanceiroModelTest.php`
- [ ] Criar teste: `FinanceiroResourceTest.php`
- [ ] Criar teste: `CadastroIntegrationTest.php`

---

**✅ Documento Gerado em:** 01/02/2026  
**🔒 Confidencial:** Uso Interno STOFGARD
