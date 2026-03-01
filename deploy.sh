#!/bin/bash
set -e

echo "🚁 Iron Code: Iniciando Deploy Tático Zero Downtime no fluxo Docker..."

# Entra na pasta do projeto
cd /var/www/stofgard # AJUSTE ESTE CAMINHO PARA O CAMINHO REAL DA SUA VPS

# Coloca em modo manutenção
docker exec stofgard-laravel.test-1 php artisan down || true

# Puxa o código
git pull origin main

# Instala dependências do PHP sem travar o servidor (roda NO CONTAINER)
docker exec stofgard-laravel.test-1 composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Roda as migrations
docker exec stofgard-laravel.test-1 php artisan migrate --force
docker exec stofgard-laravel.test-1 php artisan tenants:migrate --force # Fuzila a migration nos clientes isolados

# Limpa e reconstrói o cache
docker exec stofgard-laravel.test-1 php artisan optimize:clear
docker exec stofgard-laravel.test-1 php artisan config:cache
docker exec stofgard-laravel.test-1 php artisan event:cache
docker exec stofgard-laravel.test-1 php artisan route:cache
docker exec stofgard-laravel.test-1 php artisan view:cache

# Reboota os trabalhadores da fila
docker exec stofgard-laravel.test-1 php artisan queue:restart

# Volta pro jogo
docker exec stofgard-laravel.test-1 php artisan up

echo "✅ Missão Cumprida: Sistema atualizado e rodando!"
