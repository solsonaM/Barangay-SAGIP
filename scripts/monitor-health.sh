#!/usr/bin/env bash
set -euo pipefail

LARAVEL_URL="${LARAVEL_URL:-http://127.0.0.1:8080/up}"
TOKENIZATION_URL="${TOKENIZATION_URL:-http://127.0.0.1:8001/health}"
ALERT_WEBHOOK_URL="${MONITOR_ALERT_WEBHOOK_URL:-}"

failures=()

check_url() {
    local name="$1"
    local url="$2"
    if ! curl --fail --silent --show-error --max-time 10 "$url" >/dev/null; then
        failures+=( "$name: $url" )
    fi
}

check_url "Laravel health" "$LARAVEL_URL"
check_url "Tokenization service health" "$TOKENIZATION_URL"

if (("${#failures[@]}" > 0)); then
    message="Barangay SAGIP health check failed: $(IFS='; '; echo "${failures[*]}")"
    printf '%s\n' "$message" >&2

    if [[ -n "$ALERT_WEBHOOK_URL" ]]; then
        payload=$(printf '{"text":"%s"}' "$(printf '%s' "$message" | sed 's/"/\\\"/g')")
        curl --fail --silent --show-error --max-time 10             -H 'Content-Type: application/json'             -d "$payload"             "$ALERT_WEBHOOK_URL" >/dev/null
    fi

    exit 1
fi

printf '%s\n' "Barangay SAGIP health checks passed."
