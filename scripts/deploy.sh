#!/bin/bash

set -euo pipefail

echo "🚀 Iniciando Deploy do Autonomia..."

COMPOSE_FILE=""

if [ -n "${DEPLOY_COMPOSE_FILE:-}" ]; then
    if [ ! -f "$DEPLOY_COMPOSE_FILE" ]; then
        echo "❌ DEPLOY_COMPOSE_FILE informado, mas arquivo não existe: $DEPLOY_COMPOSE_FILE"
        exit 1
    fi
    COMPOSE_FILE="$DEPLOY_COMPOSE_FILE"
else
    for candidate in docker-compose.prod.yml docker-compose.standalone.yml docker-compose.yml compose.yaml; do
        if [ -f "$candidate" ]; then
            COMPOSE_FILE="$candidate"
            break
        fi
    done
fi

if [ -z "$COMPOSE_FILE" ]; then
    echo "❌ Nenhum arquivo docker compose encontrado."
    exit 1
fi

echo "📦 Usando compose: $COMPOSE_FILE"

ENV_FILE="${DEPLOY_ENV_FILE:-}"
if [ -z "$ENV_FILE" ]; then
    if [ "$COMPOSE_FILE" = "docker-compose.standalone.yml" ] && [ -f .env.standalone ]; then
        ENV_FILE=".env.standalone"
    elif [ -f .env ]; then
        ENV_FILE=".env"
    fi
fi

COMPOSE_ARGS=(-f "$COMPOSE_FILE")
if [ -n "$ENV_FILE" ]; then
    if [ ! -f "$ENV_FILE" ]; then
        echo "❌ Arquivo de ambiente não encontrado: $ENV_FILE"
        exit 1
    fi
    COMPOSE_ARGS+=(--env-file "$ENV_FILE")
    echo "🧾 Usando env file: $ENV_FILE"
fi

# 1. Verificar Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker não encontrado. Por favor instale o Docker primeiro."
    exit 1
fi

echo "✅ Docker encontrado."

# 2. Configurar arquivo de ambiente quando ausente
if [ "$COMPOSE_FILE" = "docker-compose.standalone.yml" ]; then
    if [ ! -f .env.standalone ]; then
        echo "⚠️ .env.standalone não encontrado. Criando a partir de .env.standalone.example..."
        if [ -f .env.standalone.example ]; then
            cp .env.standalone.example .env.standalone
        else
            echo "❌ .env.standalone.example não encontrado. Ajuste DEPLOY_ENV_FILE manualmente."
            exit 1
        fi
    fi
else
    if [ ! -f .env ]; then
        echo "⚠️Arquivo .env não encontrado. Criando a partir de .env.prod..."
        if [ -f .env.prod ]; then
            cp .env.prod .env
        else
            echo "❌ Arquivo .env.prod não encontrado! Usando .env.example como fallback..."
            cp .env.example .env
        fi
        echo "❗ Por favor, verifique o arquivo .env com as credenciais de produção."
    fi
fi

# 3. Build e Up dos Containers
echo "🐳 Subindo containers..."
docker compose "${COMPOSE_ARGS[@]}" up -d --build

APP_SERVICE=""
for service in app laravel.test autonomia-app; do
    if docker compose "${COMPOSE_ARGS[@]}" config --services | grep -qx "$service"; then
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
docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" composer install --no-dev --optimize-autoloader --no-interaction

# Não rotacionar APP_KEY em todo deploy (evita invalidar sessões/tokens)
if ! docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" sh -lc "[ -n \"\${APP_KEY:-}\" ] || ([ -f .env ] && grep -Eq '^APP_KEY=base64:' .env) || ([ -f /var/www/.env ] && grep -Eq '^APP_KEY=base64:' /var/www/.env) || ([ -f /var/www/html/.env ] && grep -Eq '^APP_KEY=base64:' /var/www/html/.env)"; then
    echo "🔑 APP_KEY ausente. Gerando chave..."
    docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" php artisan key:generate --force
fi

docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" php artisan migrate --force
docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" php artisan tenants:migrate --force
docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" php artisan tenants:seed --class=ProjectBaselineSeeder --force
docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" php artisan storage:link || true
docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" php artisan optimize:clear
docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" php artisan optimize
docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" php artisan queue:restart || true

# Build de assets no container
if docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" sh -lc "command -v npm >/dev/null 2>&1 && [ -f package.json ]"; then
    if docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" sh -lc "[ -f package-lock.json ]"; then
        docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" npm ci --allow-root
    else
        docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" npm install --allow-root
    fi
    docker compose "${COMPOSE_ARGS[@]}" exec -T "$APP_SERVICE" npm run build
else
    echo "ℹ️ npm/package.json não encontrado no serviço $APP_SERVICE. Pulando build de assets."
fi

# Garantia final de ownership para o usuário www (1000)
chown -R 1000:1000 storage bootstrap/cache public || true

echo "✅ Deploy concluído com sucesso!"
echo "🌍 Acesse sua aplicação no navegador."
