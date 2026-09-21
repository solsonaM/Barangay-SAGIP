#!/usr/bin/env bash
set -euo pipefail

: "${COMPOSE_ENV_FILE:?Set COMPOSE_ENV_FILE to an isolated restore-test environment file}"
: "${BACKUP_FILE:?Set BACKUP_FILE to a custom-format PostgreSQL dump}"
: "${RESTORE_DATABASE:?Set RESTORE_DATABASE to a disposable database name}"

case "$RESTORE_DATABASE" in
    postgres|template0|template1)
        echo "Refusing to restore into a system database." >&2
        exit 2
        ;;
esac

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.production.yml}"

docker compose --env-file "$COMPOSE_ENV_FILE" -f "$COMPOSE_FILE"     exec -T postgres sh -c 'createdb -U "$POSTGRES_USER" "$1"' sh "$RESTORE_DATABASE"

cleanup() {
    docker compose --env-file "$COMPOSE_ENV_FILE" -f "$COMPOSE_FILE"         exec -T postgres sh -c 'dropdb -U "$POSTGRES_USER" --if-exists "$1"' sh "$RESTORE_DATABASE" >/dev/null 2>&1 || true
}
trap cleanup EXIT

cat "$BACKUP_FILE" | docker compose --env-file "$COMPOSE_ENV_FILE" -f "$COMPOSE_FILE"     exec -T postgres pg_restore --no-owner --clean --if-exists -d "$RESTORE_DATABASE" -

table_count=$(docker compose --env-file "$COMPOSE_ENV_FILE" -f "$COMPOSE_FILE"     exec -T postgres sh -c 'psql -U "$POSTGRES_USER" -d "$1" -tAc "SELECT count(*) FROM pg_catalog.pg_tables WHERE schemaname = '''public''';"'     sh "$RESTORE_DATABASE" | tr -d '[:space:]')

printf '%s\n' "Restore test succeeded; public table count: $table_count"
