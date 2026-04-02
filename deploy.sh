#!/bin/bash
set -e

echo "🚁 Iron Code: Iniciando Deploy Tático Zero Downtime no fluxo Docker..."

# Verifica o nome do container
CONTAINER_NAME="autonomia-app"
if ! docker ps --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    if docker ps --format "{{.Names}}" | grep -q "^stofgard-app-standalone$"; then
        CONTAINER_NAME="stofgard-app-standalone"
    else
        echo "⚠️  Aviso: Container não encontrado! Verifique se seu docker-compose está rodando."
    fi
fi

# Entra na pasta do projeto
cd /root/STOFGARD-APP-FINAL-1 # Ajustado para o caminho real da sua VPS

# Coloca em modo manutenção
docker exec ${CONTAINER_NAME} php artisan down || true

# Puxa o código
git pull origin main

# Instala dependências do PHP sem travar o servidor (roda NO CONTAINER)
docker exec ${CONTAINER_NAME} composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
                                              
# Roda as migrations
docker exec ${CONTAINER_NAME} php artisan migrate --force
docker exec ${CONTAINER_NAME} php artisan tenants:migrate --force # Fuzila a migration nos clientes isolados
docker exec ${CONTAINER_NAME} php artisan tenants:seed --class=ProjectBaselineSeeder --force # Preenche listas vitais como tipos de cadastro
                                              
# Limpa e reconstrói o cache
docker exec ${CONTAINER_NAME} php artisan optimize:clear
docker exec ${CONTAINER_NAME} php artisan config:cache
docker exec ${CONTAINER_NAME} php artisan event:cache
docker exec ${CONTAINER_NAME} php artisan route:cache
docker exec ${CONTAINER_NAME} php artisan view:cache

# Reboota os trabalhadores da fila
docker exec ${CONTAINER_NAME} php artisan queue:restart

# Volta pro jogo
docker exec ${CONTAINER_NAME} php artisan up
