#!/bin/bash

# Parar o script em caso de erro
set -e

echo "🚀 Iniciando Deploy do Stofgard..."

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
docker compose -f docker-compose.prod.yml up -d --build

# Ajuste de permissões preventivo no host (executado como root)
echo "🔐 Ajustando permissões no host..."
chmod -R 777 public storage bootstrap/cache
chown -R 1000:1000 .

# 4. Instalar dependências e rodar migrações
echo "📦 Instalando dependências e rodando migrações..."
# Executamos como root no container para garantir que o filament:upgrade possa sobrescrever arquivos se necessário
docker compose -f docker-compose.prod.yml exec -T app composer install --no-dev --optimize-autoloader
docker compose -f docker-compose.prod.yml exec -T app php artisan key:generate
docker compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec -T app php artisan tenants:migrate --force
docker compose -f docker-compose.prod.yml exec -T app php artisan tenants:seed --class=ProjectBaselineSeeder --force
docker compose -f docker-compose.prod.yml exec -T app php artisan storage:link
docker compose -f docker-compose.prod.yml exec -T app php artisan optimize:clear
docker compose -f docker-compose.prod.yml exec -T app php artisan optimize

# Add npm install and build for assets
docker compose -f docker-compose.prod.yml exec -T app npm install --allow-root
docker compose -f docker-compose.prod.yml exec -T app npm run build

# Garantia final de ownership para o usuário www (1000)
chown -R 1000:1000 .

echo "✅ Deploy concluído com sucesso!"
echo "🌍 Acesse sua aplicação no navegador."
