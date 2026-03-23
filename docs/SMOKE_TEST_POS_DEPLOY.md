# Smoke test pós-deploy (10 minutos)

Este roteiro valida o fluxo de aquisição e cobrança após deploy, com foco no trilho canônico.

## Pré-requisitos

- APP_URL configurada no .env
- Config cache atualizado
- Filas e scheduler ativos
- LEGACY_PIX_FLOW_ENABLED=false
- LEGACY_PIX_WEBHOOK_ENABLED=false

## 0) Preparação (1 minuto)

Executar na VPS:

```bash
cd /var/www/stofgard
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

## 1) Saúde da aplicação e rotas (2 minutos)

```bash
php artisan route:list | grep -E "registro\.empresa|assinatura\.status|webhooks\.asaas|webhooks\.pix|pagamento\.pix|webhook\.pix"
curl -fsS "${APP_URL}/ping"
```

Resultado esperado:

- rota registro.empresa existe
- rota assinatura.status existe
- webhook canônico api/webhooks/pix/{webhookToken} existe
- /ping retorna {"status":"pong"}

## 2) Legado PIX realmente desligado (1 minuto)

```bash
curl -i "${APP_URL}/pagamento/hash-teste"
curl -i -X POST "${APP_URL}/webhook/pix" -H "Content-Type: application/json" -d '{}'
```

Resultado esperado:

- ambos retornam HTTP 410

## 3) Jornada pública de aquisição (3 minutos)

Acessar no navegador:

- ${APP_URL}/
- clicar em plano START/PRO/ELITE e validar redirecionamento para /registro-empresa?plano=...
- concluir cadastro de teste

Resultado esperado:

- empresa criada
- tela final exibe link de status da assinatura
- quando aplicável, exibe checkout/boleto/pix copia e cola

## 4) Pós-pagamento e webhook (2 minutos)

Validações:

- conferir logs do webhook Asaas e PIX canônico
- confirmar atualização de status_pagamento do tenant

Comandos úteis:

```bash
tail -n 200 storage/logs/laravel.log | grep -E "AsaasWebhook|PixWebhook|RegistroEmpresa"
php artisan tinker --execute="echo \App\Models\Tenant::orderByDesc('created_at')->first(['id','name','plan','status_pagamento','gateway_subscription_id']);"
```

## 5) Critério de aceite

Deploy aprovado quando:

- todos os checks acima passam
- sem erros fatais em storage/logs/laravel.log após cadastro real
- trilho legado permanece desligado
- trilho canônico recebe eventos normalmente
