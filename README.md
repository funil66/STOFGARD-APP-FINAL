# Autonomia Ilimitada - SaaS / CRM Management

## Visão Geral
Autonomia Ilimitada é um sistema ERP/CRM Multi-Tenant focado em prestadores de serviços. A plataforma oferece módulos completos e integrados para a gestão inteligente do negócio, incluindo **Orçamentos**, **Ordens de Serviço**, **Financeiro Recorrente** e **Automação de WhatsApp**.

## Stack Tecnológica
O ecossistema é construído com as tecnologias mais robustas do mercado:
- **Backend:** Laravel 12, PHP 8.4
- **Frontend/Painel:** Filament v3, Livewire 3, TailwindCSS
- **Banco de Dados:** MySQL
- **Filas e Cache:** Redis (Filas e Horizon)
- **Infraestrutura:** Docker

## Arquitetura de Infraestrutura (Docker)
O ambiente da aplicação é conteinerizado, garantindo isolamento e alta performance. A arquitetura é dividida em 3 containers principais:
- **`app`:** Servidor web responsável por rodar o Nginx e PHP-FPM, atendendo as requisições HTTP e renderizando o painel Filament.
- **`worker`:** Container dedicado ao processamento de filas assíncronas com o Laravel Horizon. Lida com tarefas pesadas como a geração da fila de PDFs e envio de webhooks.
- **`scheduler`:** Container responsável exclusivamente por executar os cronjobs agendados do sistema.

## Integrações Principais
- **EvolutionAPI:** Automação de envio de mensagens e notificações instantâneas via WhatsApp.
- **Asaas:** Gateway de pagamentos para a gestão completa de Recorrência e PIX.
- **OpenWeather:** Obtenção de dados e previsões climáticas em tempo real.

## Comandos Úteis de Manutenção

**Rodar Migrations de Tenant:**
```bash
php artisan tenants:migrate
```

**Limpar Cache de Forma Segura (dentro do container `app`):**
```bash
php artisan optimize:clear
```

**Verificar Logs do Sentry:**
Acesse o dashboard da sua organização no **Sentry** para acompanhar a observabilidade em tempo real, monitorar erros e exceções não tratadas tanto nos workers quanto na interface web.
