# 🔄 Módulo de Recorrência Mensal — Spec v1

## Visão Geral
Automatizar cobranças recorrentes (mensalidades, aluguéis, manutenção periódica) gerando `Financeiro` + cobrança PIX automaticamente no início de cada ciclo.

## Tabela: `recorrencias`
```sql
CREATE TABLE recorrencias (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    cadastro_id BIGINT NOT NULL,          -- Cliente
    descricao VARCHAR(255) NOT NULL,       -- "Manutenção mensal"
    valor DECIMAL(10,2) NOT NULL,
    dia_vencimento TINYINT DEFAULT 5,      -- Dia do mês (1-28)
    frequencia ENUM('mensal','bimestral','trimestral','semestral','anual') DEFAULT 'mensal',
    status ENUM('ativa','pausada','cancelada') DEFAULT 'ativa',
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,                    -- NULL = infinita
    ultima_geracao DATE NULL,              -- Controle de duplicidade
    gerar_cobranca_pix BOOLEAN DEFAULT false,
    categoria_id BIGINT NULL,
    observacoes TEXT NULL,
    timestamps
);
```

## Fluxo
1. **Scheduler** (`app/Console/Kernel.php`): Job `GerarRecorrenciasJob` roda diariamente
2. Para cada recorrência ativa cujo `dia_vencimento` = hoje (ou passado sem `ultima_geracao` neste ciclo):
   - Cria `Financeiro` com `tipo=entrada`, `status=pendente`
   - Se `gerar_cobranca_pix=true`, dispara `GerarCobrancaPixJob`
   - Atualiza `ultima_geracao`
3. **Admin**: CRUD completo no `RecorrenciaResource` (Filament)
4. **Portal**: Cliente vê cobranças geradas normalmente no portal

## Dependências
- `GerarCobrancaPixJob` (já existe)
- `AsaasService` para cobranças automáticas (já existe)
- Scheduler do Laravel (já configurado via Horizon)

## Prioridade
Média-alta. Automação direta de faturamento.
