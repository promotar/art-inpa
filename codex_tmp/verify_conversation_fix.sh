#!/usr/bin/env bash
set -euo pipefail

cd /var/www/store.z4rank.com/laravel

echo "== clear caches =="
php artisan optimize:clear --no-ansi >/tmp/codex_ai_assistant_clear.out
cat /tmp/codex_ai_assistant_clear.out

echo "== routes =="
php artisan route:list --no-ansi | grep -E 'ai-assistant/(chat|message|messages|conversation)|admin.plugins.ai-assistant' || true

echo "== module markers =="
grep -R 'data-history-url\|data-close-url\|closeConversation\|ai-assistant-panel\[hidden\]' -n modules/ai-assistant | head -30

echo "== public asset markers =="
grep -R 'closeConversation\|ai-assistant-panel\[hidden\]' -n ../public_html/platform/plugins/ai-assistant

echo "== frontend csrf/history flow =="
cookie="/tmp/codex_ai_assistant_cookie.txt"
home="/tmp/codex_ai_assistant_home.html"
rm -f "$cookie" "$home"
curl -s -c "$cookie" -b "$cookie" http://10.10.0.20/ -o "$home"
grep -q 'data-ai-assistant-widget' "$home" && echo "widget=yes"
grep -q 'data-csrf-token' "$home" && echo "csrf_attr=yes"
grep -q 'data-history-url' "$home" && echo "history_attr=yes"
grep -q 'data-close-url' "$home" && echo "close_attr=yes"
token=$(sed -n 's/.*data-csrf-token="\([^"]*\)".*/\1/p' "$home" | head -1)
if [ -z "$token" ]; then
  echo "missing_csrf_token"
  exit 1
fi

post_response=$(curl -s -b "$cookie" -c "$cookie" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $token" \
  -X POST http://10.10.0.20/ai-assistant/message \
  --data '{"message":"Reply with OK only."}')
echo "$post_response" | grep -q '"ok":true' && echo "post_ok=yes"

history_response=$(curl -s -b "$cookie" -c "$cookie" \
  -H "Accept: application/json" \
  http://10.10.0.20/ai-assistant/messages)
echo "$history_response" | grep -q '"messages"' && echo "history_ok=yes"
echo "$history_response" | grep -q 'Reply with OK only' && echo "history_persisted=yes"

close_response=$(curl -s -b "$cookie" -c "$cookie" \
  -H "Accept: application/json" \
  -H "X-CSRF-TOKEN: $token" \
  -X DELETE http://10.10.0.20/ai-assistant/conversation)
echo "$close_response" | grep -q '"ok":true' && echo "close_ok=yes"

after_close=$(curl -s -b "$cookie" -c "$cookie" \
  -H "Accept: application/json" \
  http://10.10.0.20/ai-assistant/messages)
echo "$after_close" | grep -q '"messages":\[\]' && echo "close_deleted=yes"

echo "== tests =="
php artisan test --no-ansi
