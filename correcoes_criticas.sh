#!/bin/bash
# ============================================
# AUTONOMIA ILIMITADA — Script de Correções Críticas
# ============================================
# Execute com: bash correcoes_criticas.sh
# Deve ser rodado na raiz do projeto
# ============================================

set -e

PROJECT_DIR="/home/funil/dev/autonomia"

# Verifica que está no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ ERRO: Este script deve ser rodado na raiz do projeto Laravel."
    echo "   Execute: cd $PROJECT_DIR && bash correcoes_criticas.sh"
    exit 1
fi

echo "========================================"
echo "  AUTONOMIA ILIMITADA — Correções Críticas"
echo "========================================"
echo ""

# ── FASE 1: Credenciais no Git ──────────────
echo "🔒 FASE 1 — Removendo credenciais do Git..."
git rm --cached .env.prod .env.production 2>/dev/null && echo "  ✅ Removidos do tracking" || echo "  ℹ️  Arquivos já não estavam tracked"

# ── FASE 2: Scripts soltos ──────────────────
echo ""
echo "🧹 FASE 2 — Movendo scripts soltos para _archive/..."
mkdir -p _archive/legacy-scripts

MOVED=0
for f in \
    fix_*.php fix2.php patch_*.php create_*.php check_*.php \
    debug_*.php cleanup*.php simul_*.php user_check.php inject_tracer.php \
    full_test*.php blade_check.php edit_redis*.php delete_test_host.php \
    get_html.php run_pipeline.php update_capabilities.php update_pdfservice.php \
    remote_seal.php script_fix_col.php debug_job.php debug_pipeline.php \
    debug_tenant_auth.php simul_debug_queue.php patch_all_pdfs.php \
    patch_test*.php patch_mig.php patch_migration*.php patch_real.php \
    patch_registro.php patch_seal_*.php patch_session.php \
    patch_sqlite_migration*.php patch_tbody.php \
    fix_pdf.sh fix-docker*.sh patch_garantia_block*.sh run_cmd.sh \
    fix_central_login.patch fix_job.patch fix_lead.patch; do
    if [ -f "$f" ]; then
        mv "$f" _archive/legacy-scripts/
        echo "  📦 $f"
        MOVED=$((MOVED + 1))
    fi
done

if [ $MOVED -eq 0 ]; then
    echo "  ℹ️  Nenhum script solto encontrado (já movidos anteriormente)"
else
    echo "  ✅ $MOVED arquivos movidos para _archive/legacy-scripts/"
fi

# ── Arquivos órfãos ─────────────────────────
echo ""
echo "🗑️  Removendo arquivos órfãos..."
REMOVED=0
for f in orcamento.blade.php out.html out_orc.blade last_dusk.txt "data[pending_owner]" "tenant-"; do
    if [ -f "$f" ]; then
        rm -f "$f"
        echo "  🗑️  $f"
        REMOVED=$((REMOVED + 1))
    fi
done
[ $REMOVED -eq 0 ] && echo "  ℹ️  Nenhum arquivo órfão encontrado" || echo "  ✅ $REMOVED arquivos removidos"

# ── Backups de nginx ────────────────────────
echo ""
echo "🗑️  Removendo backups de nginx..."
NGINX_BAKS=$(find deploy/nginx/conf.d/ -name "*.bak*" 2>/dev/null | wc -l)
if [ "$NGINX_BAKS" -gt 0 ]; then
    find deploy/nginx/conf.d/ -name "*.bak*" -delete
    echo "  ✅ $NGINX_BAKS backups de nginx removidos"
else
    echo "  ℹ️  Nenhum backup de nginx encontrado"
fi

# ── Commit ──────────────────────────────────
echo ""
echo "📝 Verificando alterações para commit..."
if git diff --quiet && git diff --cached --quiet && [ -z "$(git ls-files --others --exclude-standard | grep -v _archive)" ]; then
    echo "  ℹ️  Nada a commitar além das mudanças já staged"
fi

git add -A
git commit -m "security+cleanup: remove credentials, archive legacy scripts, fix supervisord/redis/ssl

Security:
- .env.prod e .env.production removidos do Git tracking
- .env.production adicionado ao .gitignore
- docker-compose: credenciais MySQL via env vars (sem hardcode)
- docker-compose: Redis com autenticação via REDIS_PASSWORD
- supervisord: php artisan serve (root) substituído por PHP-FPM
- PdfService: SSL verify_peer=true em produção

Código morto:
- PixService deprecated (stub → RuntimeException)
- PixGatewayService deprecated (stub → RuntimeException)
- PixWebhookController legado → 410 Gone
- routes/web.php: rotas de webhook legado removidas

Qualidade:
- GatewayService: guard para gateways não implementados (EfiPay/MercadoPago)
- PixMasterService: DDDs consolidados em PixKeyValidatorService::isDddValido()
- _archive/legacy-scripts: 55+ scripts de fix/patch arquivados" 2>/dev/null || echo "  ℹ️  Commit não necessário (sem alterações pendentes)"

echo ""
echo "========================================"
echo "✅ SCRIPT CONCLUÍDO!"
echo "========================================"
echo ""
echo "⚠️  AÇÕES MANUAIS RESTANTES (não automatizáveis):"
echo ""
echo "  1. ROTACIONAR CREDENCIAIS EM PRODUÇÃO (URGENTE):"
echo "     As credenciais abaixo foram expostas no histórico do Git:"
echo "     - APP_KEY (rodar: php artisan key:generate)"
echo "     - Senha MySQL: 'Swordfish'"
echo "     - Senhas de usuário: 'Swordfish66@', 'Mudar123!'"
echo "     - API Key OpenWeather: 0267c3aedd170c6d4813d125d65b3bb3"
echo ""
echo "  2. CONFIGURAR NO .env DE PRODUÇÃO:"
echo "     MYSQL_ROOT_PASSWORD=<senha forte>"
echo "     REDIS_PASSWORD=<senha forte>"
echo ""
echo "  3. VERIFICAR:"
echo "     docker-compose config"
echo "     php artisan route:list | grep webhook"
echo ""
