# Production container deployment

Barangay SAGIP's production baseline is Nginx -> Laravel PHP-FPM, with PostgreSQL and the FastAPI classification service on a private Docker network. The application and queue worker run as non-root users, and production Compose enables `no-new-privileges` isolation.

## Release gates

A release is deployable only after all of these are green:

1. Laravel migrations and tests against PostgreSQL 17.
2. Python service tests and dependency audit.
3. Composer and npm security audits.
4. Repository secret scanning with Gitleaks.
5. CodeQL analysis for PHP, JavaScript/TypeScript, and Python.
6. Production Compose configuration validation and image builds.
7. Container vulnerability scans for all four application images.

Do not bypass a failed security or database test to ship a release. Fix the finding or document a time-bounded exception outside the application repository.

## First deployment

1. Create the deployment `.env` beside `docker-compose.production.yml` using `docker-compose.production.env.example` as a template. Generate long random values for `POSTGRES_PASSWORD` and `TOKENIZATION_SERVICE_KEY`.
2. Copy the real Laravel environment file to `barangay-sagip-web/.env.production`. Keep it outside Git. Set `APP_ENV=production`, `APP_DEBUG=false`, the real `APP_KEY`, PostgreSQL credentials, and the internal tokenization service key.
3. Validate the Compose configuration:
   `docker compose --env-file .env -f docker-compose.production.yml config`
4. Build the images:
   `docker compose --env-file .env -f docker-compose.production.yml build`
5. Start the stack:
   `docker compose --env-file .env -f docker-compose.production.yml up -d`
6. Run migrations from the application container:
   `docker compose --env-file .env -f docker-compose.production.yml exec app php artisan migrate --force`
7. Cache the production configuration/routes/views:
   `docker compose --env-file .env -f docker-compose.production.yml exec app php artisan optimize`
8. Verify the Laravel health endpoint through Nginx and verify the tokenization service health before opening external traffic.
9. Confirm the queue worker is running and process at least one non-critical queued notification in staging before production traffic is enabled.

Do not publish PostgreSQL or the tokenization service ports. Put TLS termination, DNS, WAF/rate limiting, and the public DNS name in front of Nginx (for example, a managed load balancer or reverse proxy). Enable HSTS only at the HTTPS edge after the hostname is confirmed to be HTTPS-only.

## Release procedure

1. Freeze the exact Git commit being released.
2. Build application images from that commit in CI.
3. Retain the exact image digests used by staging.
4. Deploy the same image digests to production. Do not rebuild from source on the production host.
5. Run the database migration as a separate release step.
6. Run `php artisan optimize` after the migration.
7. Smoke-test `/up`, authentication, one resident request flow, one personnel workflow, and one queued notification.
8. Watch error rate, queue depth, database health, response latency, and application logs during the release window.
9. If the application is unhealthy, roll back the application images to the previous known-good digest. Do not automatically reverse database migrations.

## Backup

Run a logical PostgreSQL backup from the deployment host or a trusted backup runner. The database container supplies its own `POSTGRES_USER` and `POSTGRES_DB` environment variables:

`docker compose --env-file .env -f docker-compose.production.yml exec -T postgres sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom' > barangay-sagip-$(date +%Y%m%d-%H%M%S).dump`

Store backups outside the application host, encrypt them, apply a retention policy, and periodically perform a restore test. A backup that has never been restored is not a verified recovery plan.

Recommended minimum recovery targets for an initial deployment:

- Daily full logical backup.
- At least 14 days of retention.
- At least one backup stored outside the primary host/provider.
- Monthly restore verification in a separate environment.

## Restore test

Restore into a separate PostgreSQL database/environment, never directly over the live production database. Example with a temporary database/container:

`pg_restore --clean --if-exists --no-owner -d <test_database> <backup.dump>`

Then run application smoke tests and confirm row counts for critical tables before considering the backup valid.

## Rollback

Keep the previously deployed application image digest available. For an application-only rollback, deploy the previous known-good image and run only migrations that are compatible with that version. Prefer expand/contract database migrations so rolling back application code does not require destructive schema reversal.

If a migration is destructive or irreversible, it must be preceded by a verified backup and an explicit recovery procedure. Never make destructive production schema changes as an incidental part of an application rollback.

## Upload storage

The current stack persists Laravel storage in a Docker volume. This is acceptable for a single-host staging deployment, but production user uploads should move to durable object storage before scaling the application across multiple hosts or containers. The storage migration is intentionally left as an infrastructure deployment task because the provider and bucket are not defined in the repository.

## Observability

Before opening public traffic, configure external monitoring for:

- `/up` availability.
- HTTP 5xx rate.
- Request latency.
- PostgreSQL availability and disk usage.
- Queue depth and failed jobs.
- FastAPI health and request failures.
- Container restarts.
- Host CPU, memory, and disk usage.

The repository does not assume a paid monitoring provider. Sentry, OpenTelemetry-compatible telemetry, or an equivalent managed stack can be connected at deployment time.
