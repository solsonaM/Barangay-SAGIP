# Barangay SAGIP

**Barangay SAGIP: A Machine Learning-Driven Emergency Assistance Classification and Response Coordination Platform for Smart Barangay Services.**

Barangay SAGIP is a Laravel web application for resident assistance requests, emergency response coordination, personnel management, maps, notifications, dashboards, and reports. It uses a private Python FastAPI service for request classification, urgency classification, and response-assignment scoring.

## Current architecture

```text
Browser
   |
   v
Nginx --> Laravel PHP-FPM --> PostgreSQL
             |
             +----------> Private FastAPI service
```

The production Docker stack is defined in `docker-compose.production.yml` and contains:

- Laravel PHP-FPM 8.4 application
- Nginx web server
- Python 3.12 FastAPI tokenization/classification service
- PostgreSQL 17
- Private Docker backend network
- Persistent PostgreSQL and Laravel storage volumes
- Queue worker for database-backed Laravel jobs

Frontend assets are built into the production Docker images. The production Nginx container does not depend on an ignored `public/build` directory on the deployment host.

## Feature map

| # | Feature | Main implementation |
|---|---|---|
| 1 | Resident Registration and Profiling | Authentication, `ResidentProfileController`, `resident_profiles` |
| 2 | Emergency/Assistance Request Submission | `EmergencyRequestController`, request views |
| 3 | ML-Based Request Classification | `TokenizationClassificationService`, FastAPI `/classify/request-type` |
| 4 | Urgency/Priority Classification | FastAPI `/classify/urgency` |
| 5 | Request Validation | Confidence threshold and `needs_review` handling |
| 6 | Response Assignment Classification | `ResponseAssignmentService`, FastAPI `/assign/response` |
| 7 | Urgent Status Tracking | Status transitions and request status logs |
| 8 | Location Map Generator | `MapController`, Leaflet map |
| 9 | Response Personnel Management | `ResponsePersonnelController` and personnel views |
| 10 | Alerts and Notifications | Laravel notifications and notification controller |
| 11 | Dashboards | Role-specific dashboard views |
| 12 | Report Generator | `ReportController` and CSV reporting |

## Important classification note

The current repository implementation is a **deterministic tokenization and keyword/phrase matching service**, not a trained statistical model. It provides classification labels, heuristic confidence, review flags, and response-assignment scoring.

Do not describe the current deployed implementation as a trained scikit-learn model. A trained model should only be claimed after an actual training pipeline, held-out evaluation, model artifact/versioning, and documented metrics have been added.

## Local development with Laravel Herd

Laravel Herd is the recommended local Laravel environment for this project. Docker is reserved for production-style builds and deployment validation; you do not need to run the production Docker stack for normal Laravel development.

### Laravel application

Open the project in Herd or place/link the project directory in a Herd-managed path. Herd will provide the local PHP runtime and `.test` site for the Laravel application.

From the Laravel project directory:

```bash
cd barangay-sagip-web
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

Use the `.test` URL shown by Herd to open the application. Do not assume a fixed `localhost:8000` URL when using Herd.

For a production-like frontend build instead of Vite's development server, use:

```bash
npm run build
```

### Python service

Run the FastAPI service separately from Laravel Herd:

```bash
cd tokenization-service
python -m venv .venv

# Windows
.venv\Scripts\activate

pip install -r requirements.txt
uvicorn main:app --host 127.0.0.1 --port 8001
```

The Laravel application's `.env` should point to the local FastAPI service:

```env
TOKENIZATION_SERVICE_URL=http://127.0.0.1:8001
TOKENIZATION_SERVICE_TIMEOUT=5
TOKENIZATION_SERVICE_KEY=your-local-service-key
```

The `TOKENIZATION_SERVICE_KEY` must match the key configured for the FastAPI service. Keep the real value in the local `.env` files and never commit it.

### Recommended Herd workflow

Use two terminals during development:

**Terminal 1 — Laravel/Vite**

```bash
cd barangay-sagip-web
npm run dev
```

**Terminal 2 — FastAPI ML service**

```bash
cd tokenization-service
.venv\Scripts\activate
uvicorn main:app --host 127.0.0.1 --port 8001
```

Herd handles the Laravel/PHP web server. Vite handles frontend asset hot-reloading, while the FastAPI process provides the private classification API locally.

## Production deployment

Production templates are provided for the application and Docker Compose configuration. Real secrets must remain outside Git.

1. Copy `docker-compose.production.env.example` to `.env` beside `docker-compose.production.yml`.
2. Generate strong unique values for `POSTGRES_PASSWORD` and `TOKENIZATION_SERVICE_KEY`.
3. Copy `barangay-sagip-web/.env.production.example` to `barangay-sagip-web/.env.production` on the deployment host.
4. Set `APP_ENV=production`, `APP_DEBUG=false`, the HTTPS `APP_URL`, PostgreSQL credentials, mail settings, and the internal ML service key.
5. Keep PostgreSQL and the ML service unexposed to the public internet.
6. Build and start the stack:

```bash
docker compose -f docker-compose.production.yml build
docker compose -f docker-compose.production.yml up -d
```

7. Run the Laravel production setup:

```bash
docker compose -f docker-compose.production.yml exec app php artisan migrate --force
docker compose -f docker-compose.production.yml exec app php artisan optimize
```

8. Put TLS/DNS in front of Nginx using the selected hosting provider, load balancer, or reverse proxy.
9. Verify the Laravel `/up` health endpoint and the internal FastAPI `/health` endpoint.

See `docker/README.md` for backup, restore testing, and rollback procedures.

## CI/CD

GitHub Actions validates:

- Composer dependency installation and security audit
- Laravel migrations and automated tests
- PHP syntax
- npm security audit and frontend build
- Python dependency audit
- Python compilation/imports and service tests
- production Docker Compose configuration
- production Docker image builds
- repository secret scanning with Gitleaks

The CI workflow is `.github/workflows/ci.yml`.

## Security principles

- Laravel login routes are rate-limited.
- Resident request authorization is enforced server-side.
- Personnel location updates are restricted to the authenticated personnel account.
- Manual assignment validates personnel availability, workload, request status, and duplicate assignments.
- Assignment operations use transactions and row locking where concurrent updates could otherwise corrupt workload state.
- The FastAPI service requires `X-Service-Key` for protected endpoints.
- Service failures fail safely into review handling rather than silently accepting an unavailable classification service.
- Production secrets and `.env` files are excluded from Git.
- PostgreSQL and the ML service are kept on the private Docker network.

## Production readiness sequence

Before a real public deployment, complete the remaining operational work:

1. Build and validate the production images in CI.
2. Deploy to a staging environment that matches production.
3. Configure HTTPS, DNS, firewall/WAF, and secrets management.
4. Configure production PostgreSQL backups and perform a real restore test.
5. Verify the Laravel storage volume and decide the serving strategy for any resident-uploaded files.
6. Confirm queued notifications/jobs are processed by the dedicated queue worker.
7. Add application monitoring, centralized logs, metrics, and alerts.
8. Run load, authorization, and end-to-end tests in staging.
9. Freeze and tag the exact release commit.
10. Deploy with a tested rollback path.

Never use the demo credentials or placeholder secrets in a production environment.
