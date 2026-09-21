#!/usr/bin/env bash
set -euo pipefail

: "${COMPOSE_ENV_FILE:?Set COMPOSE_ENV_FILE to the deployment environment file; this script does not print or modify it}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.production.yml}"
BACKUP_DIR="${BACKUP_DIR:-./backups}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_FILE="$BACKUP_DIR/barangay-sagip-$TIMESTAMP.dump"

mkdir -p "$BACKUP_DIR"
umask 077

docker compose --env-file "$COMPOSE_ENV_FILE" -f "$COMPOSE_FILE"     exec -T postgres sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom'     > "$BACKUP_FILE"

printf '%s\n' "Backup created: $BACKUP_FILE"
