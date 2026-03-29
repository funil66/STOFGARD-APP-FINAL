# Deploy Standalone Docker (sistema.stofgard.com.br)

## Objetivo
Subir uma instância autônoma do aplicativo, em modo single-tenant, sem super-admin e sem billing SaaS de tenants.

## 1) Preparação
1. Copie o arquivo de ambiente:
   - `cp .env.standalone.example .env.standalone`
2. Preencha obrigatoriamente:
   - `APP_KEY` (gerar com `php artisan key:generate --show` em container temporário)
   - `JWT_SECRET`
   - `DB_PASSWORD`
   - `FILAMENT_ADMIN_EMAIL` e `FILAMENT_ADMIN_PASSWORD`
3. Confirme:
   - `APP_STANDALONE_MODE=true`
   - `DISABLE_SUPER_ADMIN_PANEL=true`
   - `DISABLE_PUBLIC_COMPANY_REGISTRATION=true`
   - `DISABLE_SAAS_BILLING=true`
   - `DISABLE_TENANT_PROVISIONING=true`
   - `REGISTER_TENANT_DOMAIN_ROUTES=false`

## 2) Subida da stack
```bash
docker compose -f docker-compose.standalone.yml --env-file .env.standalone up -d --build
```

Verificação inicial:
```bash
docker compose -f docker-compose.standalone.yml --env-file .env.standalone ps
docker compose -f docker-compose.standalone.yml --env-file .env.standalone logs -f app nginx
```

## 3) Banco e seed tenant único
```bash
docker compose -f docker-compose.standalone.yml --env-file .env.standalone exec app php artisan migrate --force
docker compose -f docker-compose.standalone.yml --env-file .env.standalone exec app php artisan db:seed --class=TenantSeeder --force
```

## 4) Otimização de runtime
```bash
docker compose -f docker-compose.standalone.yml --env-file .env.standalone exec app php artisan config:cache
docker compose -f docker-compose.standalone.yml --env-file .env.standalone exec app php artisan route:cache
docker compose -f docker-compose.standalone.yml --env-file .env.standalone exec app php artisan view:cache
```

## 5) Smoke tests
```bash
curl -fsS https://sistema.stofgard.com.br/up
curl -i -X POST https://sistema.stofgard.com.br/ping

docker compose -f docker-compose.standalone.yml --env-file .env.standalone exec app php artisan route:list | grep -E 'super-admin|registro.empresa|webhooks.asaas'
```

Resultado esperado do grep: vazio.

## 5.1) Cloudflare Tunnel (serviço do host)
Mapeie `sistema.stofgard.com.br` para `http://localhost:8010` no arquivo de ingress do host (`/etc/cloudflared/config.yml`) e reinicie o serviço:

```bash
sudo systemctl restart cloudflared
sudo systemctl status cloudflared --no-pager
```

## 6) Rollback rápido
```bash
docker compose -f docker-compose.standalone.yml --env-file .env.standalone down
```

Para rollback completo com limpeza de dados locais:
```bash
docker compose -f docker-compose.standalone.yml --env-file .env.standalone down -v
```
