#!/bin/bash

# Iron Code - Protocolo de Correção de Permissões e Limpeza
# Requer SUDO para execução

# 1. Verifica se está rodando como SUDO
if [ "$EUID" -ne 0 ]; then
  echo "❌ ERRO: Este script precisa de poder total."
  echo "👉 Rode com: sudo ./fix_project.sh"
  exit
fi

echo "💀 Iron Code: Iniciando Protocolo de Resgate..."
echo "----------------------------------------------"

# 2. Correção Brutal de Permissões (Para Ambiente de Dev)
echo "🔧 1. Forçando permissões 777 em pastas críticas (Dev Mode)..."
chmod -R 777 storage
chmod -R 777 bootstrap/cache
chmod -R 777 public

# Ajusta o dono dos arquivos para o usuário real (não root) para você poder editar depois
REAL_USER=$SUDO_USER
if [ -z "$REAL_USER" ]; then REAL_USER=$(whoami); fi
echo "👤 2. Devolvendo propriedade dos arquivos para: $REAL_USER"
chown -R $REAL_USER:www-data .

# 3. A GRANDE DEMOLIÇÃO (Removendo arquivos duplicados/inglês que causam conflito)
echo "🧹 3. Deletando arquivos conflitantes (Resources em Inglês)..."

# Arrays de arquivos para deletar
FILES_TO_DELETE=(
    "app/Filament/Resources/StockResource.php"
    "app/Filament/Resources/InventoryItemResource.php"
    "app/Filament/Resources/FinancialTransactionResource.php"
    "app/Filament/Resources/BudgetResource.php"
    "app/Filament/Resources/ClientResource.php"
    "app/Filament/Resources/ServiceOrderResource.php"
    "app/Filament/Resources/InvoiceResource.php"
    "app/Filament/Resources/EventResource.php"
    "app/Filament/Resources/WishlistItemResource.php"
    "app/Models/Stock.php"
    "app/Models/FinancialTransaction.php"
    "app/Models/Budget.php"
    "app/Models/Client.php"
    "app/Models/Invoice.php"
)

# Arrays de pastas para deletar
DIRS_TO_DELETE=(
    "app/Filament/Resources/StockResource"
    "app/Filament/Resources/InventoryItemResource"
    "app/Filament/Resources/FinancialTransactionResource"
    "app/Filament/Resources/BudgetResource"
    "app/Filament/Resources/ClientResource"
    "app/Filament/Resources/ServiceOrderResource"
    "app/Filament/Resources/InvoiceResource"
    "app/Filament/Resources/EventResource"
    "app/Filament/Resources/WishlistItemResource"
)

for file in "${FILES_TO_DELETE[@]}"; do
    if [ -f "$file" ]; then
        rm -f "$file"
        echo "   -> Deletado: $file"
    fi
done

for dir in "${DIRS_TO_DELETE[@]}"; do
    if [ -d "$dir" ]; then
        rm -rf "$dir"
        echo "   -> Deletada Pasta: $dir"
    fi
done

# 4. Limpeza de Cache via Docker
echo "🚀 4. Exectando limpeza interna no Laravel..."
# Usamos o usuário do container para garantir que o cache seja criado corretamente
docker compose exec -T -u laravel laravel.test php artisan view:clear
docker compose exec -T -u laravel laravel.test php artisan config:clear
docker compose exec -T -u laravel laravel.test php artisan route:clear
docker compose exec -T -u laravel laravel.test php artisan filament:optimize-clear

echo "----------------------------------------------"
echo "✅ Protocolo Finalizado. O sistema deve estar limpo."