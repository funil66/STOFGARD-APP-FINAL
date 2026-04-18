# Autonomia SaaS

Bem-vindo ao repositório do **Autonomia SaaS**. Este documento serve como guia central (Onboarding) para desenvolvedores. Todo o histórico detalhado e decisões arquiteturais mais densas podem ser encontrados na pasta `docs/`.

## 🛠 Stack Tecnológica Exata

- **Framework Principal:** Laravel 12
- **Painel Administrativo:** Filament PHP v3
- **Linguagem:** PHP 8.4
- **Filas e Jobs:** Horizon
- **Websockets:** Laravel Reverb
- **Banco de Dados:** MySQL
- **Cache / Sessão:** Redis

## 💻 Setup do Ambiente Local

Existem duas abordagens principais para rodar o Autonomia usando Docker Sail (ou via standalone):

1. **Standalone (Recomendado para Dev Local Rápido):**
   Utiliza o arquivo `docker-compose.standalone.yml`.
   ```bash
   cp .env.standalone.example .env
   # Preencha as variáveis no .env
   docker-compose -f docker-compose.standalone.yml up -d
   ```

2. **Full Stack (Com Traefik e Múltiplos Serviços):**
   Utiliza o `docker-compose.yml` e o `traefik-compose.yml` (geralmente usado no ambiente de staging/produção).
   ```bash
   docker-compose up -d
   ```

**Passos Iniciais:**
```bash
composer install
npm install
npm run dev
php artisan migrate --seed
php artisan storage:link
```

## 🏢 Arquitetura Multi-Tenant

O sistema é construído sobre o pacote **Stancl/Tenancy**.
- **Modelo de Banco de Dados:** Banco único (Single Database) com separação lógica via `tenant_id` em todas as tabelas sensíveis de inquilinos.
- **Isolamento de Contexto:** O Filament é configurado para utilizar o `TenantContext`. Painel Super Admin (raiz) vs Painel do Inquilino.
- **Armazenamento (LGPD):** Os arquivos de uploads e PII dos clientes de cada tenant são isolados na pasta `storage/tenant{id}/`, sendo estritamente proibido versionar estes diretórios (já adicionado no `.gitignore`).

## 🔌 Integrações Externas Ativas

A plataforma Autonomia depende de vários serviços de terceiros para seu funcionamento pleno:

1. **Asaas (Pagamentos e Cobranças):**
   Utilizado para gestão de assinaturas (Super Admin cobrando Tenants) e pagamentos finais (Tenants cobrando clientes). Possui integração para Pix e Boletos.
2. **OpenWeather:**
   API para consulta de dados climáticos utilizada no dashboard.
3. **Google Calendar:**
   Integração para sincronizar a agenda e agendamentos de serviços.
4. **Evolution API (WhatsApp):**
   Responsável pelo disparo automático de mensagens (lembretes, envio de orçamentos e recibos).

---

> *Para uma visão mais aprofundada sobre cada feature, consulte os relatórios técnicos em `docs/`.*
