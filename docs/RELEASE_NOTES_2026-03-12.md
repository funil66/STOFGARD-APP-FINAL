# Release Notes — 2026-03-12

## Versão
- Branch: `main`
- Commit: `1af16c1`
- Tipo: Hardening + Refactor + Novos Recursos (Fases 1–8)

## Resumo Executivo
Esta entrega consolida a estabilização da base multi-tenant, remove legado obsoleto, corrige pontos críticos de segurança e adiciona recursos de crescimento operacional (NPS, contratos recorrentes, SLA, checklist com foto, KPIs e alerta de estoque). O ciclo foi finalizado com validação completa de testes e build.

## Principais Entregas

### 1) Segurança, configuração e hardening
- Remoção de credenciais hardcoded e ajustes de configuração sensível.
- Rate limit aplicado em webhooks e endpoints públicos críticos.
- Registro/correção de provider de tenancy e ajustes de boot multi-tenant.
- Endpoints e artefatos de debug não essenciais removidos.

### 2) Limpeza de legado e consolidação de domínio
- Migração estrutural de fluxos Cliente/Parceiro para Cadastro unificado.
- Remoção de arquivos órfãos, recursos duplicados e políticas/eventos sem uso.
- Redução de superfície técnica e simplificação de manutenção.

### 3) Criação de tenant (empresa)
- Fluxo SuperAdmin de criação de tenant habilitado.
- Fluxo self-service de registro de empresa implementado via Livewire.
- Pipeline de seed e defaults de tenant normalizados.

### 4) Melhorias de UX e performance
- Correções de N+1 em recursos Filament prioritários.
- Ajustes no portal do cliente e proteção de acesso por middleware dedicado.
- Correções de mapeamento/relacionamentos em recursos operacionais.

### 5) Novos recursos (Fase 6)
- NPS/Avaliação:
  - Modelo, migração, recurso Filament, tela pública por token.
- Contratos recorrentes:
  - Modelo, migração, recurso Filament e job de processamento automático.
- SLA:
  - Campos de SLA na OS, comando de verificação e agendamento periódico.
- Checklist de serviço:
  - Checklist em OS com suporte a evidência de foto.
- Dashboard de KPIs:
  - Widget de conversão, tempo médio, receita e ticket.
- Alerta de estoque baixo:
  - Widget com produtos abaixo do mínimo.

## Alterações de dados (migrations novas)
- `database/migrations/tenant/2026_03_12_120000_create_avaliacoes_table.php`
- `database/migrations/tenant/2026_03_12_120100_create_contratos_servico_table.php`
- `database/migrations/tenant/2026_03_12_120200_add_checklist_sla_contrato_to_ordens_servico.php`

## Rotinas agendadas adicionadas/ajustadas
- Processamento de contratos recorrentes.
- Verificação de SLA em intervalos regulares.

## Validação final
- Testes: `php artisan test` → 35 passed (80 assertions)
- Rotas: `php artisan route:list` → OK
- Build frontend: `npm run build` → OK

## Impacto operacional
- Menor risco de incidente por configuração insegura.
- Maior consistência de dados no domínio Cadastro.
- Novas capacidades para retenção, qualidade e previsibilidade operacional.
- Base mais limpa para evolução de funcionalidades SaaS multi-tenant.

## Observações de rollout
1. Executar migrations em todos os tenants.
2. Garantir queue worker ativo para jobs recorrentes.
3. Confirmar scheduler ativo (`schedule:run`) em produção.
4. Validar permissões de acesso aos novos recursos Filament por perfil.

## Itens potencialmente breaking (atenção)
- Remoção de estruturas legadas Cliente/Parceiro em pontos antigos de integração interna.
- Remoção de recursos duplicados e arquivos obsoletos pode afetar customizações locais não versionadas.

## Recomendação pós-release (D+1)
- Monitorar logs de jobs de contratos recorrentes e SLA.
- Acompanhar adoção da avaliação pública NPS.
- Revisar métricas dos novos widgets para calibrar metas de operação.
