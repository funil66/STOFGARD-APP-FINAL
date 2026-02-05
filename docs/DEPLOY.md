# 🚀 Guia de Deploy - STOFGARD

Documentação completa para deploy em VPS (Contabo + Ubuntu + Nginx).

---

## 📋 Pré-requisitos

- VPS com Ubuntu 22.04+ LTS
- Acesso SSH com sudo
- Domínio configurado (DNS apontando para o IP)
- Git configurado

---

## 1️⃣ Script de Provisionamento

Execute como root ou com sudo:

```bash
#!/bin/bash
# provision-server.sh
# Provisionamento completo para STOFGARD

set -e

echo "🔧 Atualizando sistema..."
apt update && apt upgrade -y

echo "🐘 Instalando PHP 8.4 e extensões..."
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-common \
    php8.4-mysql php8.4-sqlite3 php8.4-pgsql \
    php8.4-zip php8.4-gd php8.4-mbstring php8.4-curl \
    php8.4-xml php8.4-bcmath php8.4-intl php8.4-readline \
    php8.4-redis php8.4-imagick

echo "🌐 Instalando Nginx..."
apt install -y nginx

echo "📦 Instalando Node.js 20 LTS..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

echo "🎭 Instalando Chromium para PDF..."
apt install -y chromium-browser

echo "🔄 Instalando Supervisor..."
apt install -y supervisor

echo "📂 Instalando Redis..."
apt install -y redis-server
systemctl enable redis-server

echo "🔐 Instalando Certbot (SSL)..."
apt install -y certbot python3-certbot-nginx

echo "📦 Instalando Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "✅ Provisionamento concluído!"
echo "Versões instaladas:"
php -v | head -1
node -v
npm -v
nginx -v
```

Salve como `provision-server.sh` e execute:

```bash
chmod +x provision-server.sh
sudo ./provision-server.sh
```

---

## 2️⃣ Configuração do Nginx

Crie o arquivo `/etc/nginx/sites-available/stofgard`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name seudominio.com.br www.seudominio.com.br;

    # Redireciona HTTP para HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name seudominio.com.br www.seudominio.com.br;

    root /var/www/stofgard/public;
    index index.php index.html;

    # SSL (configurado pelo Certbot)
    ssl_certificate /etc/letsencrypt/live/seudominio.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seudominio.com.br/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # Logs
    access_log /var/log/nginx/stofgard_access.log;
    error_log /var/log/nginx/stofgard_error.log;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # Headers de Segurança
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Upload máximo (para anexos)
    client_max_body_size 100M;

    # Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Bloqueia acesso a arquivos ocultos
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache de assets estáticos
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

Ative a configuração:

```bash
sudo ln -s /etc/nginx/sites-available/stofgard /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## 3️⃣ Configuração do SSL (Let's Encrypt)

```bash
sudo certbot --nginx -d seudominio.com.br -d www.seudominio.com.br
```

---

## 4️⃣ Configuração do Supervisor

Crie `/etc/supervisor/conf.d/stofgard-worker.conf`:

```ini
[program:stofgard-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/stofgard/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/stofgard/storage/logs/worker.log
stopwaitsecs=3600

[program:stofgard-schedule]
command=/bin/bash -c "while true; do php /var/www/stofgard/artisan schedule:run --verbose --no-interaction; sleep 60; done"
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/stofgard/storage/logs/schedule.log
```

Aplique:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## 5️⃣ Deploy do Código

```bash
# Clonar repositório
cd /var/www
sudo git clone https://github.com/seu-usuario/stofgard.git stofgard
cd stofgard

# Permissões
sudo chown -R www-data:www-data /var/www/stofgard
sudo chmod -R 775 storage bootstrap/cache

# Instalar dependências
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci
sudo -u www-data npm run build

# Configuração
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate

# Editar .env com suas configurações
sudo nano .env

# Banco de dados
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force

# Cache
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan icons:cache
```

---

## 6️⃣ Script de Deploy Automático

Crie `/var/www/stofgard/deploy.sh`:

```bash
#!/bin/bash
# deploy.sh - Script de deploy automatizado

set -e

cd /var/www/stofgard

echo "🔄 Entrando em manutenção..."
php artisan down

echo "📥 Baixando código..."
git pull origin main

echo "📦 Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader

echo "📦 Instalando dependências JS..."
npm ci
npm run build

echo "🗄️ Executando migrations..."
php artisan migrate --force

echo "🔄 Limpando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

echo "🔄 Reiniciando workers..."
php artisan queue:restart
sudo supervisorctl restart stofgard-worker:*

echo "✅ Saindo de manutenção..."
php artisan up

echo "🎉 Deploy concluído!"
```

```bash
chmod +x deploy.sh
```

---

## 7️⃣ Crontab

Adicione ao crontab do www-data:

```bash
sudo crontab -u www-data -e
```

```cron
# STOFGARD - Scheduler Laravel
* * * * * cd /var/www/stofgard && php artisan schedule:run >> /dev/null 2>&1

# Renovação SSL (mensal)
0 0 1 * * certbot renew --quiet
```

---

## 8️⃣ Verificação Final

```bash
# Testar ambiente
cd /var/www/stofgard
php artisan iron:check

# Testar backup
php artisan backup:run --only-db

# Testar filas
php artisan queue:work --once

# Verificar logs
tail -f storage/logs/laravel.log
```

---

## 🔒 Checklist de Segurança

- [ ] Firewall configurado (UFW)
- [ ] SSH com chave (sem senha)
- [ ] Fail2ban instalado
- [ ] APP_DEBUG=false
- [ ] .env protegido (chmod 600)
- [ ] Backups automáticos funcionando
- [ ] Monitoramento de uptime (UptimeRobot, etc.)

---

## 📊 Monitoramento Recomendado

1. **Laravel Pulse** - Métricas em tempo real
2. **Sentry** - Erros e exceções
3. **UptimeRobot** - Disponibilidade
4. **Netdata** - Recursos do servidor

---

**Última atualização:** Fevereiro 2026
