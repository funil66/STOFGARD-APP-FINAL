# Documentação Técnica: Sincronização e Persistência de Inquilinos (Tenants)

Este documento descreve as correções implementadas para resolver a falha na criação automática de usuários administradores e a persistência de dados estruturados durante o processo de onboarding de novos tenants.

## Problemas Identificados

1.  **Perda de Atributos Virtuais**: O Stancl Tenancy, por padrão, ignora atributos enviados no array `data` que não correspondam a colunas reais no banco de dados, a menos que a coluna `data` (JSON) esteja explicitamente registrada para gerenciamento.
2.  **Falha na Sincronização (CreateTenantOwnerJob)**: O job de sincronização falhava porque os dados do proprietário (`pending_owner`) não eram persistidos no banco de dados central antes da execução do worker de fila.
3.  **Contexto de Conexão**: O worker de fila ocasionalmente perdia o contexto da conexão `central` ao tentar criar o usuário no painel Super Admin enquanto operava dentro do escopo de um inquilino.

## Mudanças Implementadas

### 1. Modelo `Tenant` (`app/Models/Tenant.php`)
- **Registro da Coluna `data`**: Adicionada a coluna `data` ao método `getCustomColumns()`. Isso garante que o Stancl Tenancy persista qualquer atributo extra (como `pending_owner`) dentro da coluna JSON do banco de dados.
- **Atributo Virtual**: Implementado `setPendingOwnerAttribute` para mapear os dados do formulário Filament diretamente para a estrutura de persistência.

### 2. Job de Sincronização (`app/Jobs/CreateTenantOwnerJob.php`)
- **Uso Explicito de Conexão**: O job agora utiliza `User::on(config('tenancy.central_connection'))->create(...)` para garantir que o registro do dono no painel Super Admin ocorra sempre no banco de dados mestre.
- **Logs de Auditoria**: Adicionados logs detalhados para rastrear o sucesso/falha da criação tanto no banco central quanto no banco do inquilino.
- **Robustez**: Implementada verificação de existência prévia para evitar erros de duplicidade em caso de reprocessamento da fila.

### 3. Pipeline de Criação (`app/Filament/SuperAdmin/Resources/TenantResource/Pages/CreateTenant.php`)
- **Injeção de Dados**: Ajustada a lógica `mutateFormDataBeforeCreate` para garantir que os dados do dono sejam passados corretamente para o modelo `Tenant` no momento da persistência inicial.

### 4. Configuração de Rede (`config/tenancy.php`)
- **Domínios Centrais**: Atualizada a lista de `central_domains` para incluir `app.autonomia.app.br`, permitindo que o sistema identifique corretamente as requisições de administração.

## Como Validar o Processo

1.  Acesse o painel **Super Admin**.
2.  Crie um novo **Inquilino (Tenant)**.
3.  Preencha as informações do **Dono do Projeto** (Nome, E-mail e Senha).
4.  Após a conclusão, suba o nível de log (`tail -f storage/logs/worker.log`) para confirmar a execução do `CreateTenantOwnerJob`.
5.  O usuário deverá aparecer imediatamente na lista global de usuários do Super Admin e ser capaz de acessar o painel do inquilino.

---
**Nota**: Certifique-se de que o worker de fila (`php artisan queue:work`) esteja em execução e tenha sido reiniciado após estas modificações para carregar as novas definições de classes.

## Correções de Conexão com o Banco de Dados (Filament Error 500)

### 1. Conexões Chumbadas ("Hardcoded") Removidas
- **Problema Identificado**: O painel do Super Admin (Filament) retornava **Erro HTTP 500** imediatamente ao tentar processar o Login via POST. Isso se originava devido a modelos que faziam carregamento proativo de métricas na interface (como badges e dados do dashboard) possuindo o atributo `protected $connection = 'pgsql';` fixado em seus respectivos arquivos. Em ambientes em que a aplicação se conecta a um banco MySQL, a tentativa simultânea de acessar um driver PostgreSQL causava uma exceção crítica fatal `PDOException: connection to server at "mysql" port 3306 failed: received invalid response to SSL negotiation`.
- **Modelos Afetados e Corrigidos**:
  - `app/Models/TicketSuporte.php`
  - `app/Models/GlobalAnnouncement.php`
- **Solução Aplicada**: Comentamos as linhas que forçavam a conexão `pgsql`.  Esses modelos agora herdam corretamente a conexão padrão dinâmica do banco de dados principal do `.env` ou `config/database.php` (normalmente MySQL no ambiente de produção do Autonomia), permitindo o completo funcionamento do fluxo de autenticação e navegação do Super Admin sem falhas de driver.

## Correções de Conexão com o Banco de Dados (Filament Error 500)

### 1. Conexões Chumbadas Removidas
- **Problema**: O painel Super Admin retornava Erro HTTP 500 no login via POST. Isso se dava devido a modelos (`app/Models/TicketSuporte.php` e `app/Models/GlobalAnnouncement.php`) possuírem o atributo `protected $connection = 'pgsql';` fixado em seus arquivos. Ao conectar no MySQL do Autonomia, o Filament chamava essas queries proativas para calcular notificações e badges, resultando num erro fatal `PDOException` (visto como um erro SSL na negociação para o driver postgres).
- **Solução**: Comentamos as linhas que forçavam `pgsql` localmente e em produção. O Filament agora obedece a configuração universal default baseada no ambiente.
