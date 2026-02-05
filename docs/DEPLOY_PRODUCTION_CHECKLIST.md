# 🚀 STOFGARD - Checklist de Deploy em Produção

**Data:** 5 de fevereiro de 2026  
**Versão:** 1.0  
**Ambiente:** Produção

---

## ✅ Correções Implementadas

### 1. Configuração de Ambiente

- [x] **env() para config()**: Todas as chamadas `env()` fora de `config/` foram movidas para arquivos de configuração
  - `UserSeeder.php` agora usa `config('app.admin_email')` e `config('app.admin_password')`
  - `PdfService.php` agora usa `config('app.node_binary')` e `config('app.npm_binary')`
  - Criado `config/pix.php` para futuras implementações PIX

- [x] **.gitignore atualizado**: Adicionados patterns críticos
  ```
  *.sql
  public/hot
  phpunit.xml
  public/debug-*.html
  public/test-*.html
  DEV_CREDENTIALS.md
  storage/serve.pid
  ```

### 2. Segurança

- [x] **php.ini atualizado**:
  ```ini
  display_errors = Off
  expose_php = Off
  log_errors = On
  ```

- [x] **Session cookies seguros**: `config/session.php` agora força `secure = true` em produção

- [x] **Browsershot seguro**: Removido `--disable-web-security` do Chrome

- [x] **Arquivos de teste/debug removidos**:
  - `public/hot`
  - `public/debug-widget.html`
  - `public/test-*.html` (5 arquivos)
  - `backup_20260201.sql`
  - `storage/serve.pid`

### 3. Limpeza de Código

- [x] **Models inexistentes**: Removido `InventarioObserver.php` e método `arquivos()` de `Cadastro.php`

- [x] **Console.log**: Removidos 7 logs de debug do widget de clima (mantido apenas log de erro)

### 4. Docker/Compose

- [x] **compose.prod.yaml criado**: Configuração limpa para produção
  - Xdebug desabilitado
  - Porta Vite 5173 removida
  - Porta Redis não exposta ao host
  - `restart: unless-stopped` adicionado
  - Health checks para Laravel e Redis

---

## 🔴 CRÍTICO - Ações Manuais Obrigatórias Antes do Deploy

### 1. Variáveis de Ambiente (.env)

**Arquivo `.env` no servidor de produção DEVE ter:**

```bash
APP_NAME="STOFGARD"
APP_ENV=production
APP_KEY=base64:... # Use: php artisan key:generate
APP_DEBUG=false
APP_URL=https://seu-dominio.com.br

# Admin (usado pelo seeder)
FILAMENT_ADMIN_EMAIL=allisson@stofgard.com.br
FILAMENT_ADMIN_PASSWORD=SenhaForteAqui123!

# PDF Generation
NODE_BINARY=/usr/bin/node
NPM_BINARY=/usr/bin/npm

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stofgard_prod
DB_USERNAME=stofgard_user
DB_PASSWORD=SenhaSeguraDoMySQL

# Session
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true

# Cache/Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.seuservidor.com
MAIL_PORT=587
MAIL_USERNAME=naoresponda@stofgard.com.br
MAIL_PASSWORD=SenhaDoEmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=naoresponda@stofgard.com.br
MAIL_FROM_NAME="${APP_NAME}"

# OpenWeather
OPENWEATHER_API_KEY=sua_chave_aqui
OPENWEATHER_DEFAULT_CITY="Ribeirao Preto"

# Browsershot
BROWSERSHOT_CHROME_PATH=/usr/bin/google-chrome-stable
BROWSERSHOT_NODE_PATH=/usr/bin/node
BROWSERSHOT_NPM_PATH=/usr/bin/npm

# PIX (quando implementar)
PIX_CHAVE=
PIX_CLIENT_ID=
PIX_CLIENT_SECRET=
PIX_CERTIFICATE_PATH=
```

### 2. Rotação de Senhas

**TODAS as senhas hardcoded em `UserSeeder.php` DEVEM ser trocadas:**

```php
// Linha 17: senha 'admin' - OK para dev@local
// Linha 23: senha vem de .env - CONFIGURAR NO SERVIDOR
// Linhas 28, 33, 38: senha 'Stofgard2026' - TROCAR URGENTE
```

**Ação:**
1. Editar `database/seeders/UserSeeder.php`
2. Trocar `'Stofgard2026'` por senhas fortes individuais ou variáveis de ambiente
3. Após deploy inicial, forçar troca de senha no primeiro login

### 3. Build de Produção

```bash
# No servidor, após fazer pull
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
```

### 4. Migrations e Seed

```bash
php artisan migrate --force
php artisan db:seed --class=UserSeeder --force
php artisan db:seed --class=ConfiguracaoSeeder --force
# Adicionar outros seeders necessários
```

### 5. Permissões

```bash
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
```

### 6. Docker Compose

**Para usar em produção:**

```bash
# Usar o arquivo de produção
docker compose -f compose.prod.yaml up -d

# Ou renomear para compose.yaml depois de fazer backup do original
mv compose.yaml compose.dev.yaml
mv compose.prod.yaml compose.yaml
docker compose up -d
```

### 7. SSL/HTTPS

**Nginx deve ter:**
- Certificado SSL válido (Let's Encrypt recomendado)
- Redirect HTTP → HTTPS
- Headers de segurança:
  ```nginx
  add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
  add_header X-Frame-Options "SAMEORIGIN" always;
  add_header X-Content-Type-Options "nosniff" always;
  add_header X-XSS-Protection "1; mode=block" always;
  ```

---

## 🟡 RECOMENDADO

### 1. Squash de Migrations

As 119 migrações atuais devem ser consolidadas em ~10-15 migrações limpas após o deploy inicial bem-sucedido.

### 2. Monitoramento

Implementar:
- Laravel Telescope (dev only)
- Sentry ou Bugsnag para erros em produção
- New Relic ou similar para performance

### 3. Backup Automático

```bash
# Cron diário para backup do banco
0 3 * * * /usr/bin/docker exec laravel.test php artisan backup:run >> /var/log/backup.log 2>&1
```

### 4. Queue Worker

Se usar filas, adicionar supervisor/systemd:

```bash
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

---

## 🧪 Checklist Pós-Deploy

- [ ] Site carrega em HTTPS
- [ ] Login admin funciona
- [ ] Widgets do dashboard aparecem (incluindo clima)
- [ ] Criar cadastro de cliente/parceiro funciona
- [ ] Criar ordem de serviço funciona
- [ ] Gerar orçamento e PDF funciona
- [ ] Financeiro registra entradas/saídas
- [ ] Relatórios carregam
- [ ] Session persiste após fechar navegador
- [ ] Logs em `storage/logs/` não mostram erros críticos

---

## 📞 Suporte

Em caso de problemas:
1. Verificar logs: `docker compose logs -f laravel.test`
2. Verificar Laravel logs: `storage/logs/laravel.log`
3. Verificar Nginx logs: `/var/log/nginx/error.log`
4. Verificar PHP logs: `/var/log/php_errors.log`

---

## 🔐 Segurança Contínua

**Após deploy, DELETAR do repositório (se ainda não foi feito):**
- `DEV_CREDENTIALS.md` (se commitado)
- Qualquer `.sql` backup
- `phpunit.xml` local

**Rotação de APP_KEY:**
- Nunca commitar `.env` com APP_KEY
- Em caso de vazamento, gerar nova chave imediatamente com `php artisan key:generate`

---

**Última atualização:** 5 de fevereiro de 2026, 17:30
