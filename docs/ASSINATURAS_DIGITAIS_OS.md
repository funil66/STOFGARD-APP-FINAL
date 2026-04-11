# Assinaturas Digitais em Ordem de Serviço (Cliente + Técnico)

## Objetivo

Este documento descreve o fluxo completo de assinatura digital de Ordem de Serviço (OS), incluindo:

- assinatura do cliente/responsável;
- assinatura do técnico (usuário autenticado no painel);
- geração de PDF assinado;
- persistência de metadados legais (IP, user-agent, timestamp, hash);
- visualização no Filament.

## Escopo funcional implementado

### 1) Assinatura do Cliente

- Ação de tabela no Filament: `Assinar`.
- Captura via `SignaturePad`.
- Persistência da assinatura e metadados legais.
- Regeneração do PDF da OS com assinatura renderizada no bloco “ASSINATURA DO CLIENTE”.

### 2) Assinatura do Técnico (usuário logado)

- Nova ação de tabela no Filament: `Assinar Téc.`.
- Captura via `SignaturePad`.
- Vínculo explícito ao usuário autenticado (`user_id`, `user_name`).
- Persistência da assinatura e metadados legais em colunas próprias do técnico.
- Renderização no PDF no bloco “ASSINATURA DO TÉCNICO”.

## Arquivos principais

- `app/Actions/FinalizeAssinaturaAction.php`
- `app/Models/OrdemServico.php`
- `app/Filament/Resources/OrdemServicoResource.php`
- `resources/views/pdf/os.blade.php`
- `database/migrations/tenant/2026_04_10_230500_add_missing_signature_columns_to_ordens_servico_table.php`
- `database/migrations/tenant/2026_04_10_231000_add_tecnico_signature_columns_to_ordens_servico_table.php`

## Modelo de dados

### Colunas do cliente

- `assinatura` (imagem base64/data-uri)
- `assinatura_metadata` (json)
- `assinatura_pdf_hash` (sha256 do PDF final)
- `assinado_em` (timestamp)
- `assinatura_ip`
- `assinatura_user_agent`
- `assinatura_timestamp`
- `assinatura_hash`

### Colunas do técnico

- `assinatura_tecnico` (imagem base64/data-uri)
- `assinatura_tecnico_metadata` (json)
- `assinatura_tecnico_pdf_hash` (sha256 do PDF final)
- `assinado_tecnico_em` (timestamp)
- `assinatura_tecnico_ip`
- `assinatura_tecnico_user_agent`
- `assinatura_tecnico_timestamp`
- `assinatura_tecnico_hash`
- `assinatura_tecnico_user_id`
- `assinatura_tecnico_user_name`

## Fluxo técnico (resumo)

1. Usuário aciona assinatura no Filament (`Assinar` ou `Assinar Téc.`).
2. `FinalizeAssinaturaAction` resolve o contexto do assinante (`cliente`/`tecnico`).
3. Assinatura é persistida no campo correspondente.
4. PDF final da OS é gerado (`pdf.os`) com Browsershot configurado via `PdfService`.
5. Hash SHA-256 do PDF é calculado.
6. Metadados legais são coletados e salvos nas colunas do contexto.
7. Assinaturas passam a aparecer no PDF e na seção de detalhes da OS no Filament.

## Requisitos de migração (tenancy)

As migrations são tenant-scoped e devem rodar por tenant:

```bash
php artisan tenants:migrate --path=database/migrations/tenant/2026_04_10_230500_add_missing_signature_columns_to_ordens_servico_table.php --force
php artisan tenants:migrate --path=database/migrations/tenant/2026_04_10_231000_add_tecnico_signature_columns_to_ordens_servico_table.php --force
```

Quando em ambiente containerizado, executar dentro do container Laravel (ex.: `docker exec ... php artisan ...`).

## Operação pós-deploy

Após atualização de blade/action/resource:

```bash
php artisan view:clear
# se aplicável em Octane/FrankenPHP:
# reiniciar container/processo
```

## Problemas conhecidos resolvidos

### Erro: coluna `assinatura` inexistente

- Causa: schema do tenant sem colunas de assinatura.
- Correção: migration defensiva `2026_04_10_230500_*` com `Schema::hasColumn`.

### Erro de view PDF inexistente

- Causa: referência a view incorreta.
- Correção: uso da view `pdf.os` no fluxo de finalização.

### Erro Puppeteer (`EACCES /root/.config/puppeteer`)

- Causa: diretório sem permissão de escrita no runtime.
- Correção: padronização da configuração do Browsershot via `PdfService::configureBrowsershotPublic`.

## Critérios de auditoria atendidos

- Rastreabilidade de autoria por assinante.
- Integridade documental por hash do PDF final.
- Registro de contexto de sessão/requisição.
- Evidência visual das assinaturas no documento final.

## Sugestões de evolução

- Política de ordem de assinatura (ex.: cliente antes do técnico, ou vice-versa).
- Bloqueio de reassinatura após ambos assinarem.
- Trilhas de auditoria com versionamento de PDFs assinados.
- Validação criptográfica externa do hash em endpoint dedicado.
