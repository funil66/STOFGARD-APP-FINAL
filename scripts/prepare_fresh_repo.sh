#!/usr/bin/env bash
# scripts/prepare_fresh_repo.sh
# Safe "fresh repo" preparation script.
# Default: dry-run (no deletions, no git rename, no push).
# Use: ./scripts/prepare_fresh_repo.sh [--apply] [--remote <url>] [--branch <name>] [--yes]
#
# What it automates:
#  - Backup file detection (dry-run) and optional deletion (--apply)
#  - Duplicate files summary generation (writes /tmp/duplicate_files_summary.txt)
#  - Optional running of the project's duplicate cleanup script (interactive)
#  - `composer dump-autoload --optimize` (runs automatically if composer present)
#  - `php artisan optimize:clear` (runs automatically if php + artisan present)
#  - Git "fresh start": rename `.git` -> `.git_old_<timestamp>`, git init, commit, add remote, push --force (only with --apply)

set -euo pipefail

SCRIPT_NAME="$(basename "$0")"
APPLY=0
YES=0
REMOTE_URL=""
BRANCH="main"

show_help() {
  cat <<EOF
Usage: $SCRIPT_NAME [--apply] [--remote <url>] [--branch <name>] [--yes] [--help]

Options:
  --apply           Actually perform destructive actions (delete files, rename .git, push). Without this the script is a dry-run.
  --remote <url>    Remote URL to add as 'origin' during fresh git start. If not given you'll be prompted.
  --branch <name>   Branch name to push (default: 'main').
  --yes             Assume "yes" to interactive confirmations.
  --help            Show this help message.
EOF
}

# Simple yes/no prompt (returns 0 if yes)
confirm() {
  if [[ $YES -eq 1 ]]; then
    return 0
  fi
  local prompt="${1:-Are you sure?}"
  while true; do
    read -r -p "$prompt [y/N]: " ans
    case "$ans" in
      [Yy]|[Yy][Ee][Ss]) return 0 ;;
      [Nn]|[Nn][Oo]|"") return 1 ;;
      *) echo "Please answer y or n." ;;
    esac
  done
}

# Parse args
while [[ $# -gt 0 ]]; do
  case "$1" in
    --apply) APPLY=1; shift ;;
    --remote) REMOTE_URL="${2:-}"; shift 2 ;;
    --branch) BRANCH="${2:-$BRANCH}"; shift 2 ;;
    --yes) YES=1; shift ;;
    -h|--help) show_help; exit 0 ;;
    *) echo "Unknown arg: $1"; show_help; exit 2 ;;
  esac
done

echo "=== Prepare Fresh Repo (dry-run: $([[ $APPLY -eq 0 ]] && echo yes || echo no)) ==="
echo "Branch: $BRANCH"
[[ -n "$REMOTE_URL" ]] && echo "Remote: $REMOTE_URL"

# Safety: ensure we're in project root (has composer.json or artisan or .git)
if [[ ! -f composer.json && ! -f artisan && ! -d .git ]]; then
  echo "Warning: this directory doesn't look like the project root (no composer.json, no artisan, no .git)."
  if ! confirm "Continue anyway?"; then
    echo "Aborted."
    exit 1
  fi
fi

###############################
# 1) BACKUP FILE DETECTION
###############################
echo
echo "-> (1) Searching for backup files (common patterns: *.bak *.backup *~ *.orig *.old)..."
# Exclude vendor and .git and public/build and node_modules
readarray -t BACKUP_FILES < <(find . -type f \
  \( -name '*.bak' -o -name '*.backup' -o -name '*~' -o -name '*.orig' -o -name '*.old' \) \
  ! -path "./vendor/*" ! -path "./.git/*" ! -path "./public/build/*" ! -path "./node_modules/*" -print)

if [[ ${#BACKUP_FILES[@]} -eq 0 ]]; then
  echo "  No backup files found."
else
  echo "  Found ${#BACKUP_FILES[@]} backup files (sample up to 20):"
  for i in "${!BACKUP_FILES[@]}"; do
    [[ $i -ge 20 ]] && break
    echo "    ${BACKUP_FILES[$i]}"
  done
  echo "  Full list saved to /tmp/prepare_fresh_repo_backup_files.txt"
  printf "%s\n" "${BACKUP_FILES[@]}" > /tmp/prepare_fresh_repo_backup_files.txt

  if [[ $APPLY -eq 1 ]]; then
    if confirm "Delete these ${#BACKUP_FILES[@]} files now?"; then
      echo "  Deleting..."
      for f in "${BACKUP_FILES[@]}"; do
        # Prefer removing via git if file is tracked and git exists
        if git rev-parse --is-inside-work-tree >/dev/null 2>&1 && git ls-files --error-unmatch "$f" >/dev/null 2>&1; then
          git rm -f --ignore-unmatch "$f" || rm -f "$f"
        else
          rm -f "$f"
        fi
      done
      echo "  Deletion complete."
    else
      echo "  Skipping backup deletion (user declined)."
    fi
  else
    echo "  Dry-run: no files were deleted. Rerun with --apply to delete."
  fi
fi

###############################
# 2) DUPLICATE SUMMARY
###############################
echo
echo "-> (2) Generating duplicate files summary..."
SUMMARY_FILE="/tmp/duplicate_files_summary.txt"
TMP_MD5="/tmp/prepare_fresh_repo_md5s.txt"

if ! command -v md5sum >/dev/null 2>&1; then
  echo "  Error: md5sum not found. Install coreutils or ensure md5sum is on PATH."
else
  echo "  Scanning files (this may take a while)..."
  # Exclude vendor, .git, public/build, node_modules, and the summary itself
  find . -type f \
    ! -path "./vendor/*" ! -path "./.git/*" ! -path "./public/build/*" ! -path "./node_modules/*" \
    -not -path "$SUMMARY_FILE" -print0 \
    | xargs -0 md5sum 2>/dev/null | sort > "$TMP_MD5" || true

  # Build grouped summary (hash -> file list) and write groups with >1 files to SUMMARY_FILE
  awk '{
    h=$1; sub($1 FS, ""); f=$0;
    a[h]=(h in a)? a[h] RS f : f
  }
  END {
    for (h in a) {
      n=split(a[h], arr, /\n/);
      if (n>1) {
        print "=== MD5: " h " ===";
        for (i=1;i<=n;i++) print arr[i];
        print ""
      }
    }
  }' "$TMP_MD5" > "$SUMMARY_FILE" || true

  if [[ ! -s "$SUMMARY_FILE" ]]; then
    echo "  No duplicates found. ($SUMMARY_FILE is empty)"
  else
    echo "  Duplicate summary written to $SUMMARY_FILE"
    echo "  Sample:"
    sed -n '1,40p' "$SUMMARY_FILE"
    echo "  (use 'cat $SUMMARY_FILE' to inspect the full summary)"
  fi
fi

# Offer to run the repository's cleanup script (scripts/cleanup_duplicates.sh)
if [[ -f scripts/cleanup_duplicates.sh ]]; then
  echo
  if [[ $APPLY -eq 1 ]]; then
    echo "  Found existing cleanup script: scripts/cleanup_duplicates.sh"
    if confirm "Run 'scripts/cleanup_duplicates.sh --apply' now?"; then
      echo "  Running cleanup script in --apply mode..."
      scripts/cleanup_duplicates.sh --apply
      echo "  Duplicate cleanup script finished."
    else
      echo "  Skipping cleanup script run."
    fi
  else
    echo "  Existing cleanup script found at scripts/cleanup_duplicates.sh"
    echo "  Dry-run: to actually run it, re-run this script with --apply and confirm."
  fi
fi

###############################
# 3) COMPOSER DUMP / ARTISAN OPTIMIZE CLEAR
###############################
echo
echo "-> (3) Running maintenance commands (non-destructive)"

if command -v composer >/dev/null 2>&1; then
  echo "  Running: composer dump-autoload --optimize"
  if composer dump-autoload --optimize; then
    echo "  composer dump-autoload succeeded."
  else
    echo "  composer dump-autoload failed (non-fatal)."
  fi
else
  echo "  composer not found; skipping composer dump-autoload."
fi

if command -v php >/dev/null 2>&1 && [[ -f artisan ]]; then
  echo "  Running: php artisan optimize:clear"
  if php artisan optimize:clear; then
    echo "  php artisan optimize:clear succeeded."
  else
    echo "  php artisan optimize:clear failed (non-fatal)."
  fi
else
  echo "  php or artisan not found; skipping artisan optimize:clear."
fi

###############################
# 4) GIT FRESH START
###############################
echo
echo "-> (4) Git fresh start (rename .git -> .git_old_YYYYMMDD_HHMMSS, git init, commit, add remote, push --force)"
if [[ -d .git ]]; then
  TS="$(date +%Y%m%d_%H%M%S)"
  GIT_OLD_NAME=".git_old_${TS}"
  echo "  Current repo detected (.git exists). Proposed backup: $GIT_OLD_NAME"

  # Check for uncommitted changes
  if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    if [[ -n "$(git status --porcelain)" ]]; then
      echo "  Warning: There are uncommitted changes in the working tree."
      if ! confirm "Continue with fresh start despite uncommitted changes? (these changes will remain in the working directory but the old git DB will be renamed)"; then
        echo "  Aborted by user due to uncommitted changes."
        exit 1
      fi
    fi
  fi

  if [[ $APPLY -eq 1 ]]; then
    if confirm "Rename .git -> $GIT_OLD_NAME now?"; then
      echo "  Renaming .git -> $GIT_OLD_NAME"
      mv .git "$GIT_OLD_NAME"
      echo "  Old repo preserved at $GIT_OLD_NAME"
    else
      echo "  Skipping rename of .git (user declined)."
    fi
  else
    echo "  Dry-run: would run: mv .git $GIT_OLD_NAME"
  fi

  echo "  Initializing new git repository..."
  if [[ $APPLY -eq 1 ]]; then
    git init
    git add -A
    git commit -m "chore: repo fresh start ($(date -u +"%Y-%m-%d %H:%M:%SZ"))" || true
  else
    echo "  Dry-run: would run: git init; git add -A; git commit -m 'chore: repo fresh start ...'"
  fi

  # Remote handling
  if [[ -z "$REMOTE_URL" ]]; then
    read -r -p "Enter remote URL to add as 'origin' (leave blank to skip): " user_remote
    REMOTE_URL="$user_remote"
  fi

  if [[ -n "$REMOTE_URL" ]]; then
    echo "  Remote to add: $REMOTE_URL"
    if [[ $APPLY -eq 1 ]]; then
      git remote add origin "$REMOTE_URL" || git remote set-url origin "$REMOTE_URL"
      echo "  Remote set to origin -> $REMOTE_URL"
      if confirm "Push branch '$BRANCH' to origin (--force)?"; then
        echo "  Pushing (force) to origin $BRANCH..."
        git push -u origin "$BRANCH" --force
        echo "  Push complete."
      else
        echo "  Skipping push (user declined)."
      fi
    else
      echo "  Dry-run: would run: git remote add origin $REMOTE_URL; git push -u origin $BRANCH --force"
    fi
  else
    echo "  No remote provided; skipping remote add/push step."
  fi

else
  echo "  No .git folder found; performing new init (dry-run outputs shown)."
  if [[ $APPLY -eq 1 ]]; then
    git init
    git add -A
    git commit -m "chore: repo fresh start ($(date -u +"%Y-%m-%d %H:%M:%SZ"))" || true
    if [[ -n "$REMOTE_URL" ]]; then
      git remote add origin "$REMOTE_URL" || git remote set-url origin "$REMOTE_URL"
      if confirm "Push branch '$BRANCH' to origin (--force)?"; then
        git push -u origin "$BRANCH" --force
      else
        echo "  Skipping push (user declined)."
      fi
    fi
  else
    echo "  Dry-run: would run git init; git add -A; git commit; and optionally add remote/push."
  fi
fi

###############################
# Final instructions & summary
###############################
echo
echo "=== DONE (script completed) ==="
if [[ $APPLY -eq 1 ]]; then
  echo "Actions were performed where you confirmed them."
  echo "IMPORTANT: The previous git DB (if present) was moved to a timestamped folder (.git_old_<ts>)."
  echo "Inspect it if you need to recover tags/branches/refs."
  echo
fi
