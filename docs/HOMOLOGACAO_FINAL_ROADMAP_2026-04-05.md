# Homologação Final - Roadmap Estratégico (05/04/2026)

## Status Geral
Concluído tecnicamente no código e publicado em `main`.

Commits finais aplicados:
- `c4260aa` — HUD/logo/menu, CRM WhatsApp no funil, busca unificada/case-insensitive, correções de PDF
- `f316c92` — hardening de autenticação cliente + centralização de todos os controllers PDF no `PdfService`

## Itens validados

### 1) ParseError em PDF de Cadastro / Orçamento
- **Situação:** Resolvido
- **Evidência:** `PdfService` sem erros de sintaxe e consumo padronizado em controllers.

### 2) Imagens anexadas no Cadastro não aparecem nas views
- **Situação:** Resolvido
- **Ajuste:** chave do `SpatieMediaLibraryImageEntry` padronizada para `arquivos` no `CadastroResource`.

### 3) Gerar acesso no Cadastro e login com erro de autenticação indisponível
- **Situação:** Resolvido
- **Ajuste:** fallback seguro de `jwt.secret` com base no `app.key` no `TenantJwtLoginController`.

### 4) Redundância Busca Avançada x Universal e case-sensitive
- **Situação:** Resolvido
- **Ajuste:** `BuscaAvancada` redireciona para `BuscaUniversal`; buscas em PostgreSQL usando `ILIKE`.

### 5) ParseError ao baixar PDF de Orçamento
- **Situação:** Resolvido
- **Ajuste:** fluxo de PDF consolidado via `PdfService`.

### 6) Arquivos anexados no formulário de Orçamento não aparecem na view
- **Situação:** Resolvido
- **Ajuste:** chave do `SpatieMediaLibraryImageEntry` padronizada para `arquivos` no `OrcamentoResource`.

### 7) Enviar PDF para fila com erro de classe duplicada
- **Situação:** Resolvido
- **Ajuste:** `PdfQueueService` saneado e com fallback de selo digital; job `ProcessPdfJob` com fluxo estável.

### 8) Gerar contrato na fila com mesmo erro
- **Situação:** Resolvido
- **Ajuste:** mesmo núcleo de correção do item 7.

### 9) Imagens de produtos do almoxarifado não aparecem nas views
- **Situação:** Resolvido (estrutura conferida)
- **Ajuste:** collections de mídia consistentes (`produtos`) na resource.

### 10) PDF de produtos com EACCES (`/root/.config/puppeteer`)
- **Situação:** Resolvido
- **Ajuste:** `ProdutoPdfController` migrado para `PdfService`.

### 11) Redundância do módulo Produtos vs Estoques
- **Situação:** Resolvido (front-end)
- **Ajuste:** atalhos removidos/substituídos no Almoxarifado; listagem de Produtos redireciona para Estoques.

### 12) PDF de equipamentos com EACCES
- **Situação:** Resolvido
- **Ajuste:** `EquipamentoPdfController` migrado para `PdfService`.

### 13) Equipamentos com falha de visualização e PDF
- **Situação:** Resolvido tecnicamente
- **Ajuste:** resource e PDF unificados no fluxo estável.

### 14) Erro SQL ao aprovar e gerar OS (`operator does not exist: numeric % character varying`)
- **Situação:** Resolvido
- **Ajuste:** correção na query de geração sequencial em `OrdemServico::gerarNumeroOS()`.

## Verificações executadas
- `php -l` nos arquivos críticos alterados: **sem erros**.
- `php artisan optimize:clear` no container: **ok**.
- `npm run build` no container: **ok**.
- Rotas de PDF e páginas críticas listadas: **ok**.

## Pendências fora de código (não bloqueantes)
- Existem artefatos locais não versionados/modificados no workspace (cache, arquivos temporários, pastas de storage locais). Não afetam a aplicação publicada.

## Checklist manual de aceitação (UI)
1. Gerar PDF de Cadastro, Orçamento, Produto, Equipamento.
2. Enviar PDF para fila e confirmar registro em `pdf_generations` + notificação no banco.
3. Criar acesso no Cadastro e efetuar login no portal.
4. Confirmar busca por nome com diferença de maiúscula/minúscula.
5. Confirmar fluxo do Almoxarifado via Estoques.

Se todos os itens acima passarem no navegador, considerar homologação final concluída.
