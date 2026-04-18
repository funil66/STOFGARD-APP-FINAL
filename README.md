# Autonomia Ilimitada - SaaS / CRM Management

## Visão Geral
O Autonomia Ilimitada é um sistema ERP/CRM Multi-Tenant focado em prestadores de serviços. A plataforma oferece módulos completos e integrados para a gestão do negócio, incluindo **Orçamentos**, **Ordens de Serviço**, **Financeiro Recorrente** e **Automação de WhatsApp**.

## Stack Tecnológica
- **Backend:** Laravel 12, PHP 8.4
- **Frontend/Painel:** Filament v3, Livewire 3, TailwindCSS
- **Banco de Dados:** MySQL
- **Filas e Cache:** Redis (Filas e Horizon)
- **Infraestrutura:** Docker

## Arquitetura de Infraestrutura (Docker)
O ambiente da aplicação é conteinerizado utilizando Docker, garantindo isolamento e performance. A arquitetura principal é dividida em 3 containers principais:
- **`app`:** Servidor web responsável por rodar o Nginx e PHP-FPM, atendendo as requisições HTTP e renderizando o painel Filament.
- **`worker`:** Container dedicado ao processamento de filas em background com o Laravel Horizon. É responsável por lidar com tarefas pesadas e assíncronas, como a fila de geração de PDFs e envio de webhooks.
- **`scheduler`:** Container responsável exclusivamente por executar os cronjobs agendados do sistema.

## Integrações Principais
O ecossistema do Autonomia Ilimitada conta com as seguintes integrações externas:
- **EvolutionAPI:** Automação de envio de mensagens e notificações via WhatsApp.
- **Asaas:** Gateway de pagamentos para gestão de cobranças, PIX e assinaturas com Recorrência.
- **OpenWeather:** Consulta de dados e previsões climáticas em tempo real.

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
Acesse o dashboard da sua organização no **Sentry** para acompanhar os logs, monitorar erros e garantir a observabilidade de exceções não tratadas tanto nos webhooks quanto na interface web.
