#!/bin/bash
set -e
cd /home/funil/dev/autonomia

echo "=== Configurando Git ==="
git config user.email "allissonsousa.adv@gmail.com"
git config user.name "Allisson Sousa"

echo ""
echo "=== Deletando stubs deprecated ==="
rm -f app/Services/PixService.php
rm -f app/Services/Pagamento/PixGatewayService.php
echo "  ✅ PixService.php deletado"
echo "  ✅ PixGatewayService.php deletado"

echo ""
echo "=== Staging ==="
git add -A
git diff --cached --stat

echo ""
echo "=== Commit ==="
git commit -m "security+cleanup: fix webhook forgery bypass, delete deprecated PIX stubs

SECURITY (critical):
- AsaasWebhookController: token agora é OBRIGATÓRIO — se ASAAS_WEBHOOK_TOKEN
  estiver vazio no .env, webhooks são rejeitados com 503 (antes passavam)
- AsaasWebhookController: comparação via hash_equals() (timing-safe)
- PixWebhookController: removido fallback que fazia scan de TODOS os tenants
  ativos (vetor de DoS e timing attack)

CLEANUP:
- DELETADO app/Services/PixService.php (stub deprecated, 22 linhas)
- DELETADO app/Services/Pagamento/PixGatewayService.php (stub deprecated, 22 linhas)
- PagamentoController: PixService → GatewayService
- RenderOrcamentoHtml: PixService → PixMasterService (método real gerarQrCode)
- GerarCobrancaPixJob: PixGatewayService → GatewayService"

echo ""
echo "=== Push ==="
git push

echo ""
echo "========================================="
echo "✅ DONE"
echo ""
echo "Verificação:"
echo "  grep -r 'PixService' app/ --include='*.php' | grep -v PixMasterService | grep -v PixKeyValidator"
echo "  grep -r 'PixGatewayService' app/ --include='*.php'"
echo "  (ambos devem retornar zero linhas)"
echo "========================================="
