#!/usr/bin/env bash
set -euo pipefail

# scripts/cleanup_duplicates.sh
# dry-run: list duplicate groups (from /tmp/duplicate_files_summary.txt)
# --apply: delete suggested low-risk duplicates (config below)

SUMMARY_FILE=/tmp/duplicate_files_summary.txt

if [[ ! -f $SUMMARY_FILE ]]; then
  echo "Resumo de duplicatas não encontrado em $SUMMARY_FILE"
  echo "Execute a varredura anterior (o script 'find' com md5) ou re-run the find command used by the CI agent."
  exit 1
fi

echo "=== DUPLICATE FILE GROUPS ==="
cat $SUMMARY_FILE

if [[ ${1:-} == "--apply" ]]; then
  echo "Aplicando remoções sugeridas (modo --apply)"

  # Low-risk removals (customize as needed):
  # - backups (.bak, .backup)
  # - planning copies that duplicate public assets (PLANEJAMENTO/*logo.png, PLANEJAMENTO/*FOTO*)
  files_to_remove=(
    "phpunit.xml.dist.bak"
    "PLANEJAMENTO/nova logo.png"
    "PLANEJAMENTO/FOTO DASHBOARD.png"
  )

  for f in "${files_to_remove[@]}"; do
    if [[ -f $f ]]; then
      echo "Removendo $f"
      git rm -f "$f" || rm -f "$f"
    else
      echo "Arquivo $f não existe, pulando"
    fi
  done

  echo "Commitando remoções (branch atual: $(git rev-parse --abbrev-ref HEAD))"
  git commit -m "chore: cleanup duplicates (low-risk)" || true
  echo "Feito. Verifique as mudanças e abra um PR.";
else
  echo "Dry-run completo. Para aplicar as remoções sugeridas, execute:"
  echo "  scripts/cleanup_duplicates.sh --apply"
fi
