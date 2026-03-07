# 🛒 E-commerce Lite na Vitrine Pública — Spec v1

## Visão Geral
Transformar a vitrine pública do tenant (`/v/{slug}`) em um mini e-commerce: exibir produtos com preço, botão "Solicitar Orçamento" ou "Comprar via PIX". Sem carrinho complexo — foco em conversão rápida.

## Funcionalidades

### Catálogo Público
- Lista de produtos/serviços do tenant (usa `TabelaPreco` existente)
- Filtro por categoria
- Fotos dos produtos (Spatie MediaLibrary)
- Preço visível (configurável por item: mostrar/ocultar)

### Ações do Visitante
1. **Solicitar Orçamento**: Abre form com nome, telefone, e-mail → cria `Lead`
2. **Comprar via PIX** (se habilitado): Gera cobrança PIX instantânea via `AsaasService`
3. **WhatsApp Direto**: Botão "Falar no WhatsApp" com mensagem pré-preenchida

### Painel Admin
- Toggle "Publicar na Vitrine" na `TabelaPreco`
- Configurar preço público vs preço interno
- Dashboard de conversões (Leads gerados via vitrine)

## Schema (novas colunas em `tabela_precos`)
```sql
ALTER TABLE tabela_precos ADD COLUMN publicar_vitrine BOOLEAN DEFAULT false;
ALTER TABLE tabela_precos ADD COLUMN preco_publico DECIMAL(10,2) NULL;
ALTER TABLE tabela_precos ADD COLUMN ordem_exibicao INT DEFAULT 0;
ALTER TABLE tabela_precos ADD COLUMN destaque BOOLEAN DEFAULT false;
```

## Rotas
```
GET  /v/{slug}              → Vitrine com catálogo
GET  /v/{slug}/produto/{id} → Detalhe do produto
POST /v/{slug}/orcamento    → Criar lead com itens selecionados
POST /v/{slug}/pix/{id}     → Gerar cobrança PIX instantânea
```

## Dependências
- `PublicProfileController` (já existe)
- `TabelaPreco` model (já existe)
- `AsaasService` para PIX (já existe)
- `LeadController` para captação (já existe)

## Prioridade
Média. Feature de marketing e vendas.
