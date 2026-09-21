# PostgreSQL backup and restore-test procedure

## Backup

Run `scripts/postgres-backup.sh` from an operator workstation or controlled host with Docker Compose access. Set `COMPOSE_ENV_FILE` to the deployment environment file without printing or committing it.

The script creates a PostgreSQL custom-format logical dump with restrictive local file permissions. Store backups outside the application repository, keep an off-host copy, apply an agreed retention policy, and encrypt backups at rest where required by the organization's security policy.

## Restore verification

Run `scripts/postgres-restore-test.sh` only against an isolated/disposable PostgreSQL environment. Set `COMPOSE_ENV_FILE`, `BACKUP_FILE`, and a disposable `RESTORE_DATABASE`. The script creates the database, restores the dump, checks that the public schema is readable, and removes the disposable database on exit.

A restore test must never target the live application database. Schedule periodic restore verification and record the date, backup identifier, result, and operator.

## Monitoring

Run `scripts/monitor-health.sh` from a monitoring host or scheduler. It checks Laravel `/up` and the tokenization service `/health`. Set `MONITOR_ALERT_WEBHOOK_URL` outside the repository when an alert destination is available. No alert-provider dependency is required.
