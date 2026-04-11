#!/bin/bash
set -euo pipefail

echo "🚁 Iron Code: Iniciando Deploy Tático Zero Downtime no fluxo Docker..."

if ! command -v docker &> /dev/null; then
    echo "❌ Docker não encontrado."
    exit 1
fi

# Verifica o nome do container
CONTAINER_NAME="${CONTAINER_NAME:-autonomia-app}"
if ! docker ps --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    for candidate in stofgard-app-standalone stofgard-app-final-laravel.test-1; do
        if docker ps --format "{{.Names}}" | grep -q "^${candidate}$"; then
            CONTAINER_NAME="$candidate"
            break
        fi
    done
fi

if ! docker ps --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    echo "❌ Container de aplicação não encontrado. Defina CONTAINER_NAME ou suba o compose."
    exit 1
fi

# Entra na pasta do projeto
PROJECT_DIR="${PROJECT_DIR:-$PWD}"
cd "$PROJECT_DIR"

APP_WENT_DOWN=0
cleanup() {
    if [ "$APP_WENT_DOWN" -eq 1 ]; then
        echo "⚠️ Falha detectada. Tentando retirar do modo manutenção..."
        docker exec "$CONTAINER_NAME" php artisan up || true
    fi
}
trap cleanup EXIT

# Coloca em modo manutenção
if docker exec "$CONTAINER_NAME" php artisan down; then
    APP_WENT_DOWN=1
fi

# Puxa o código
git pull --ff-only origin main

# Instala dependências do PHP sem travar o servidor (roda NO CONTAINER)
docker exec "$CONTAINER_NAME" composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
                                              
# Roda as migrations
docker exec "$CONTAINER_NAME" php artisan migrate --force
docker exec "$CONTAINER_NAME" php artisan tenants:migrate --force # Fuzila a migration nos clientes isolados
docker exec "$CONTAINER_NAME" php artisan tenants:seed --class=ProjectBaselineSeeder --force # Preenche listas vitais como tipos de cadastro
                                              
# Limpa e reconstrói o cache
docker exec "$CONTAINER_NAME" php artisan optimize:clear
docker exec "$CONTAINER_NAME" php artisan config:cache
docker exec "$CONTAINER_NAME" php artisan event:cache
docker exec "$CONTAINER_NAME" php artisan route:cache
docker exec "$CONTAINER_NAME" php artisan view:cache

# Reboota os trabalhadores da fila
docker exec "$CONTAINER_NAME" php artisan queue:restart

# Volta pro jogo
docker exec "$CONTAINER_NAME" php artisan up
APP_WENT_DOWN=0
