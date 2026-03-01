#!/bin/bash
set -e

echo "🚁 Iron Code: Iniciando Deploy Tático Zero Downtime..."

# Entra na pasta do projeto
cd /var/www/stofgard # AJUSTE ESTE CAMINHO PARA O CAMINHO REAL DA SUA VPS

# Coloca em modo manutenção mas permite ignorar se o cara tiver um secret
php artisan down || true

# Puxa o código
git pull origin main

# Instala dependências do PHP sem travar o servidor
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Roda as migrations (Isso vai rodar no banco tenant central. Precisamos rodar nos tenants também)
php artisan migrate --force
php artisan tenants:migrate --force # Fuzila a migration em todos os bancos de clientes isolados

# Limpa e reconstrói o cache
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Reboota os trabalhadores da fila (PDF, WhatsApp)
php artisan queue:restart

# Volta pro jogo
php artisan up

echo "✅ Missão Cumprida: Sistema atualizado e rodando!"
