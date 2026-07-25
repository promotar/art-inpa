#!/usr/bin/env bash
set -euo pipefail

cookie="/tmp/codex_ai_router_cookie.txt"
home="/tmp/codex_ai_router_home.html"
response="/tmp/codex_ai_router_response.json"
rm -f "$cookie" "$home" "$response"

curl -s -c "$cookie" -b "$cookie" http://10.10.0.20/ -o "$home"
token=$(sed -n 's/.*data-csrf-token="\([^"]*\)".*/\1/p' "$home" | head -1)

if [ -z "$token" ]; then
  echo "missing_csrf"
  exit 1
fi

curl -s -o "$response" -w "ai_message_http=%{http_code}\n" \
  -b "$cookie" -c "$cookie" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $token" \
  -X POST http://10.10.0.20/ai/message \
  --data '{"message":"اكتب كود Laravel controller"}'

cat "$response"
