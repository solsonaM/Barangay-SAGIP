# Laravel Queue Worker

The production queue worker consumes Laravel database-queue jobs.

It runs:

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=90 --max-time=3600
```

The worker uses the same production environment file as the Laravel application so it connects to the same PostgreSQL database and queue configuration.
