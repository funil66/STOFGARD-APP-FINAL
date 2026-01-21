#!/usr/bin/env bash
set -euo pipefail

# Programmatic login helper (local use only)
# Usage: BASE_URL=http://127.0.0.1:8000 ./scripts/login-with-csrf.sh
BASE_URL=${BASE_URL:-http://127.0.0.1:8000}
EMAIL="allisson@stofgard.com"
PASSWORD="Swordfish"

COOKIES=$(mktemp)
trap 'rm -f "$COOKIES"' EXIT

# Fetch login page (sets XSRF cookie)
curl -c "$COOKIES" -sS -L "$BASE_URL/admin/login" -o /dev/null

RAW=$(grep -i XSRF-TOKEN "$COOKIES" | awk '{print $7}' || true)
if [ -z "$RAW" ]; then
  echo "XSRF cookie not found" >&2; exit 1
fi

# URL-decode the cookie value
XSRF=$(python3 -c "import urllib.parse,sys;print(urllib.parse.unquote(sys.argv[1]))" "$RAW")

# Ensure test user exists (local-only route)
curl -b "$COOKIES" -sS -X GET "$BASE_URL/debug/ensure-admin-user" || true

# Perform login
echo "POSTing login for $EMAIL to $BASE_URL/admin/login"
curl -b "$COOKIES" -i -sS -H "X-XSRF-TOKEN: $XSRF" -d "email=${EMAIL}&password=${PASSWORD}" -X POST "$BASE_URL/admin/login"

# Show session debug after attempt
curl -b "$COOKIES" -sS -X GET "$BASE_URL/debug/session"

exit 0
