#!/bin/bash
# ============================================
# AUTONOMIA ILIMITADA — Commit das Correções
# ============================================
# Execute com: bash commit_correcoes.sh
# Deve ser rodado na raiz do projeto
# ============================================

set -e

PROJECT_DIR="/home/funil/dev/autonomia"

if [ ! -f "artisan" ]; then
    echo "❌ ERRO: Este script deve ser rodado na raiz do projeto Laravel."
    echo "   Execute: cd $PROJECT_DIR && bash commit_correcoes.sh"
    exit 1
fi

echo "========================================"
echo "  AUTONOMIA ILIMITADA — Git Commit"
echo "========================================"
echo ""

# Configurar identidade git (necessário após trocar de máquina/container)
echo "📋 Configurando identidade Git..."
git config user.email "allissonsousa.adv@gmail.com"
git config user.name "Allisson Sousa"
echo "  ✅ Identidade configurada"

# Limpar arquivos temporários restantes
echo ""
echo "🧹 Limpando arquivos temporários..."

# Arquivo swap do nano
if [ -f "..env.swp" ]; then
    rm -f "..env.swp"
    echo "  🗑️  ..env.swp removido"
fi

# debug2.php (já arquivado)
if [ -f "debug2.php" ]; then
    mv debug2.php _archive/legacy-scripts/
    echo "  📦 debug2.php → _archive/"
fi

# stofgard-worker.conf legado
if [ -f "stofgard-worker.conf" ]; then
    mv stofgard-worker.conf _archive/legacy-scripts/
    echo "  📦 stofgard-worker.conf → _archive/"
fi

# correcoes_criticas.sh (cumpriu seu papel)
if [ -f "correcoes_criticas.sh" ]; then
    mv correcoes_criticas.sh _archive/
    echo "  📦 correcoes_criticas.sh → _archive/"
fi

echo ""
echo "📝 Stageing das alterações..."
git add -A
echo "  ✅ Staged"

echo ""
echo "📊 Resumo das alterações:"
git diff --cached --stat

echo ""
echo "🚀 Fazendo commit..."
git commit -m "security+cleanup: credenciais rotacionadas, scripts arquivados, supervisord e redis corrigidos

=== SEGURANÇA ===
- .env: credenciais atualizadas (DB_DATABASE=autonomia, DB_USERNAME=Funil)
- .env: MYSQL_ROOT_PASSWORD e REDIS_PASSWORD definidos
- .env.prod e .env.production removidos do tracking Git
- .gitignore: .env.production e _archive/ adicionados
- docker-compose: credenciais MySQL via env vars (sem hardcode)
- docker-compose: Redis com autenticação via REDIS_PASSWORD
- supervisord: PHP-FPM no lugar de php artisan serve como root
- PdfService: SSL verify_peer=true em producao/staging

=== CÓDIGO MORTO REMOVIDO ===
- PixService.php: deprecated (throws RuntimeException)
- PixGatewayService.php: deprecated (throws RuntimeException)
- PixWebhookController.php legado: 410 Gone stub
- routes/web.php: rotas legadas de webhook PIX removidas

=== QUALIDADE ===
- GatewayService: guard para gateways não implementados (EfiPay/MercadoPago)
- PixMasterService: DDDs consolidados via PixKeyValidatorService::isDddValido()
- PixKeyValidatorService: novo método público isDddValido()

=== LIMPEZA ===
- 89+ scripts de fix/patch arquivados em _archive/legacy-scripts/
- debug2.php, stofgard-worker.conf movidos para _archive/
- ..env.swp (nano swap) removido
- Arquivos orfaos da raiz removidos (out.html, orcamento.blade.php, etc)"

echo ""
echo "========================================"
echo "✅ COMMIT CONCLUÍDO!"
echo "========================================"
echo ""

# Push
echo "🚀 Enviando para o repositório remoto..."
git push
echo "  ✅ Push concluído!"

echo ""
echo "========================================"
echo "✅ TUDO CONCLUÍDO!"
echo "========================================"
echo ""
echo "⚠️  AINDA PENDENTE (manual):"
echo "   1. php artisan key:generate --force  (rotacionar APP_KEY)"
echo "   2. Pinnar versão do browserless/chrome no docker-compose.yml"
echo "   3. Renomear StofgardSystem → AutonomiaSistema (branding)"
echo ""
