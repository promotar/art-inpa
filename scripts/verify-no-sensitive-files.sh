#!/usr/bin/env bash
set -euo pipefail

failed=0

while IFS= read -r tracked_path; do
    case "$tracked_path" in
        .env|.env.*|*/.env|*/.env.*|docker/secrets/*|secrets/*|*/secrets/*|auth.json|*/auth.json|id_rsa|*/id_rsa|*.pem|*.key|*.p12|*.pfx|*.sql|*/.backups/*|storage/app/*|storage/framework/views/*|art-inpa-main.zip)
            if [[ "$tracked_path" != ".env.example" ]]; then
        printf 'Sensitive or runtime path is tracked: %s\n' "$tracked_path" >&2
                failed=1
            fi
            ;;
    esac
done < <(git ls-files)

content_matches="$({
    git grep -IlE '^APP_KEY=base64:[A-Za-z0-9+/=]{20,}|^(DB|MYSQL|MARIADB|REDIS|MAIL)_PASSWORD=(")?[^"$<{[:space:]][^[:space:]]*|(^|[^A-Za-z0-9])sk-(proj-)?[A-Za-z0-9_-]{20,}|-----BEGIN ([A-Z0-9 ]+ )?PRIVATE KEY-----' -- ':!.env.example' || true
} | sort -u)"

if [[ -n "$content_matches" ]]; then
    while IFS= read -r matched_path; do
        printf 'Potential credential content is tracked in: %s\n' "$matched_path" >&2
    done <<< "$content_matches"
    failed=1
fi

if [[ "$failed" -ne 0 ]]; then
    printf 'Refusing to package or publish tracked credentials.\n' >&2
    exit 1
fi

printf 'Tracked-file credential check passed.\n'
