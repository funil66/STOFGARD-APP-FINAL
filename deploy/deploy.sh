#!/bin/bash

set -euo pipefail

echo "🚀 Iniciando Deploy do Stofgard..."

COMPOSE_FILE=""
for candidate in docker-compose.prod.yml docker-compose.standalone.yml compose.yaml docker-compose.yml; do
    if [ -f "$candidate" ]; then
        COMPOSE_FILE="$candidate"
        break
    fi
done

if [ -z "$COMPOSE_FILE" ]; then
    echo "❌ Nenhum arquivo docker compose encontrado."
    exit 1
fi

echo "📦 Usando compose: $COMPOSE_FILE"

# 1. Verificar Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker não encontrado. Por favor instale o Docker primeiro."
    exit 1
fi

echo "✅ Docker encontrado."

# 2. Configurar .env
if [ ! -f .env ]; then
    echo "⚠️Arquivo .env não encontrado. Criando a partir de .env.prod..."
    if [ -f .env.prod ]; then
        cp .env.prod .env
    else
        echo "❌ Arquivo .env.prod não encontrado! Usando .env.example como fallback..."
        cp .env.example .env
    fi
    echo "❗ Por favor, verifique o arquivo .env com as credenciais de produção."
    # Não vamos pausar se for automatizado, mas deixamos o aviso
fi

# 3. Build e Up dos Containers
echo "🐳 Subindo containers..."
docker compose -f "$COMPOSE_FILE" up -d --build

APP_SERVICE=""
for service in app laravel.test; do
    if docker compose -f "$COMPOSE_FILE" config --services | grep -qx "$service"; then
        APP_SERVICE="$service"
        break
    fi
done

if [ -z "$APP_SERVICE" ]; then
    echo "❌ Serviço da aplicação não encontrado (esperado: app ou laravel.test)."
    exit 1
fi

echo "🧩 Serviço da aplicação: $APP_SERVICE"

# Ajuste de permissões preventivo no host
echo "🔐 Ajustando permissões no host..."
mkdir -p storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache public || true
chown -R 1000:1000 storage bootstrap/cache public || true

# 4. Instalar dependências e rodar migrações
echo "📦 Instalando dependências e rodando migrações..."
# Executamos no serviço detectado para evitar divergência entre ambientes
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" composer install --no-dev --optimize-autoloader --no-interaction

# Não rotacionar APP_KEY em todo deploy (evita invalidar sessões/tokens)
if ! docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php -r "exit((bool) env('APP_KEY') ? 0 : 1);"; then
    echo "🔑 APP_KEY ausente. Gerando chave..."
    docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan key:generate --force
fi

docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan migrate --force
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan tenants:migrate --force
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan tenants:seed --class=ProjectBaselineSeeder --force
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan storage:link || true
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan optimize:clear
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan optimize
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan queue:restart || true

# Build de assets no container
if [ -f package-lock.json ]; then
    docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" npm ci --allow-root
else
    docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" npm install --allow-root
fi
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" npm run build

# Garantia final de ownership para o usuário www (1000)
chown -R 1000:1000 storage bootstrap/cache public || true

echo "✅ Deploy concluído com sucesso!"
echo "🌍 Acesse sua aplicação no navegador."
