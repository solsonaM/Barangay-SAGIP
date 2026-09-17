# Production container deployment

Barangay SAGIP's production baseline is Nginx -> Laravel PHP-FPM, with PostgreSQL and the FastAPI classification service on a private Docker network.

## First deployment

1. Build the images:
   `docker compose -f docker-compose.production.yml build`
2. Create the deployment `.env` beside `docker-compose.production.yml` using `docker-compose.production.env.example` as a template. Generate long random values for `POSTGRES_PASSWORD` and `TOKENIZATION_SERVICE_KEY`.
3. Copy the real Laravel environment file to `barangay-sagip-web/.env.production`. Keep it outside Git. Set `APP_ENV=production`, `APP_DEBUG=false`, the real `APP_KEY`, PostgreSQL credentials, and the internal ML service key.
4. Start the stack:
   `docker compose --env-file .env -f docker-compose.production.yml up -d`
5. Run migrations from the application container:
   `docker compose --env-file .env -f docker-compose.production.yml exec app php artisan migrate --force`
6. Cache the production configuration/routes/views:
   `docker compose --env-file .env -f docker-compose.production.yml exec app php artisan optimize`
7. Verify the Laravel health endpoint through Nginx and verify the ML container health before opening external traffic.

Do not publish PostgreSQL or the ML service ports. Put TLS termination and the public DNS name in front of Nginx (for example, a managed load balancer or reverse proxy).

## Backup

Run a logical PostgreSQL backup from the deployment host or a trusted backup runner. Example:

`docker compose --env-file .env -f docker-compose.production.yml exec -T postgres pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom > barangay-sagip-$(date +%Y%m%d-%H%M%S).dump`

Store backups outside the application host, encrypt them, apply a retention policy, and periodically perform a restore test. A backup that has never been restored is not a verified recovery plan.

## Restore test

Restore into a separate PostgreSQL database/environment, never directly over the live production database. Example with a temporary database/container:

`pg_restore --clean --if-exists --no-owner -d <test_database> <backup.dump>`

Then run application smoke tests and confirm row counts for critical tables before considering the backup valid.

## Rollback

Keep the previously deployed application image/tag available. For an application-only rollback, deploy the previous known-good image and run only migrations that are compatible with that version. Prefer expand/contract database migrations so rolling back application code does not require destructive schema reversal.
