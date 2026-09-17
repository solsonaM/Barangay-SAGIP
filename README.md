# Barangay SAGIP

**Barangay SAGIP: A Tokenization-based Emergency Assistance Classification and Response Coordination Platform for Smart Barangay Services.**

Barangay SAGIP is a Laravel web application for resident assistance requests, emergency response coordination, personnel management, maps, notifications, dashboards, and reports. It uses a private Python FastAPI service for tokenization-based request classification, urgency classification, and response-assignment scoring.

## Classification approach

The current repository does **not** use machine learning or a trained statistical model.

Barangay SAGIP uses a **tokenization-based classification approach for emergency and assistance request processing**. The FastAPI service tokenizes and normalizes request text, then applies deterministic keyword and phrase matching rules to classify request type and urgency and to support response-assignment scoring.

The service can return classification labels, heuristic confidence values, review flags, and response-assignment scores. These outputs are rule-based and should not be described as predictions from a trained ML model.

A future trained model should only be described as part of the system after an actual training pipeline, held-out evaluation, model artifact/versioning, and documented performance metrics have been implemented.

## Architecture

```text
Browser
   |
   v
Herd / Nginx --> Laravel PHP --> MySQL (local) / PostgreSQL (production)
                    |
                    +----------> FastAPI tokenization/classification service
                    |
                    +----------> Database-backed queue --> Queue worker
```

Production uses Docker Compose, PHP-FPM 8.4, Nginx, Python 3.12, and PostgreSQL 17. Normal Windows development uses Laravel Herd, MySQL 8.4, Python 3.12, Vite, and the local FastAPI service.

## Feature map

| # | Feature | Main implementation |
|---|---|---|
| 1 | Resident Registration and Profiling | Authentication, `ResidentProfileController`, `resident_profiles` |
| 2 | Emergency/Assistance Request Submission | `EmergencyRequestController`, request views |
| 3 | Tokenization-Based Request Classification | `TokenizationClassificationService`, FastAPI `/classify/request-type` |
| 4 | Urgency/Priority Classification | FastAPI `/classify/urgency` |
| 5 | Request Validation | Confidence threshold and `needs_review` handling |
| 6 | Response Assignment Classification | `ResponseAssignmentService`, FastAPI `/assign/response` |
| 7 | Urgent Status Tracking | Status transitions and request status logs |
| 8 | Location Map Generator | `MapController`, Leaflet map |
| 9 | Response Personnel Management | `ResponsePersonnelController` and personnel views |
| 10 | Alerts and Notifications | Laravel notifications and notification controller |
| 11 | Dashboards | Role-specific dashboard views |
| 12 | Report Generator | `ReportController` and CSV reporting |

## Local development on Windows

Laravel Herd is the recommended local Laravel environment. Docker is reserved for production-style builds and deployment validation.

### Prerequisites

Install:

- Laravel Herd with PHP 8.4
- Composer
- Node.js and npm
- MySQL Server 8.4
- Python 3.12
- Git

### 1. Clone the repository

```powershell
git clone https://github.com/solsonaM/Barangay-SAGIP.git
cd Barangay-SAGIP
```

### 2. Configure MySQL

Create the local database and application user. Example:

```sql
CREATE DATABASE barangay_sagip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'barangay_sagip'@'localhost' IDENTIFIED BY 'sagip-local-password';
CREATE USER 'barangay_sagip'@'127.0.0.1' IDENTIFIED BY 'sagip-local-password';
GRANT ALL PRIVILEGES ON barangay_sagip.* TO 'barangay_sagip'@'localhost';
GRANT ALL PRIVILEGES ON barangay_sagip.* TO 'barangay_sagip'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Use your own local password if preferred and put the same value in `.env`. Never commit local secrets.

### 3. Configure Laravel

```powershell
cd barangay-sagip-web
composer install
copy .env.example .env
php artisan key:generate
```

Set the relevant `.env` values:

```env
APP_NAME="Barangay SAGIP"
APP_ENV=local
APP_DEBUG=true
APP_URL=https://barangay-sagip.test
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barangay_sagip
DB_USERNAME=barangay_sagip
DB_PASSWORD=sagip-local-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=log
MAIL_MAILER=log

TOKENIZATION_SERVICE_URL=http://127.0.0.1:8001
TOKENIZATION_SERVICE_TIMEOUT=5
TOKENIZATION_SERVICE_KEY=sagip-local-service-key
```

Run:

```powershell
php artisan migrate --seed
```

The seeder provides these local-only demo accounts:

| Role | Email | Password |
|---|---|---|
| Official | `official@sagip.test` | `password` |
| Personnel | `personnel@sagip.test` | `password` |
| Resident | `resident@sagip.test` | `password` |

### 4. Configure Herd

From `barangay-sagip-web`:

```powershell
herd link barangay-sagip
```

Open:

```text
https://barangay-sagip.test
```

The resident login is `/login`. Barangay staff use `/admin/login`.

**Use the HTTPS Herd URL.** Browser geolocation requires a secure context, so opening the application over plain HTTP will prevent resident GPS detection.

Do not run `php artisan serve` when using Herd.

### 5. Install and run Vite

```powershell
npm install
npm run dev
```

Keep Vite running during development. Use `npm run build` for a production-like frontend build.

### 6. Set up the FastAPI tokenization/classification service

Open another PowerShell terminal:

```powershell
cd tokenization-service
py -3.12 -m venv .venv
.\.venv\Scripts\Activate.ps1
python --version
python -m pip install --upgrade pip
pip install -r requirements.txt
$env:TOKENIZATION_SERVICE_KEY="sagip-local-service-key"
uvicorn main:app --host 127.0.0.1 --port 8001
```

Verify the service at `http://127.0.0.1:8001/health`. API documentation is at `http://127.0.0.1:8001/docs`.

### 7. Start the Laravel queue worker

Open another PowerShell terminal:

```powershell
cd barangay-sagip-web
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```

Keep the worker running. It processes database-backed Laravel jobs, including queued in-app notifications.

### 8. One-command local startup

The repository includes:

- `Start-SAGIP.ps1` to start the local development environment.
- `Stop-SAGIP.ps1` to stop the local development services.

The startup script expects Herd to be running, MySQL 8.4 to use the Windows service name `MySQL84`, and `tokenization-service\.venv` to exist with Python 3.12.

If PowerShell blocks the scripts on first use:

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
Unblock-File .\Start-SAGIP.ps1
Unblock-File .\Stop-SAGIP.ps1
```

From the repository root:

```powershell
.\Start-SAGIP.ps1
```

This checks/starts MySQL, starts Vite, starts the FastAPI service with the local service key, starts the Laravel queue worker, and opens the Laravel application over HTTPS. The three service terminals must remain open while developing.

To stop the development services:

```powershell
.\Stop-SAGIP.ps1
```

The shutdown script stops Vite, FastAPI, and the Laravel queue worker. It intentionally leaves MySQL and Herd running.

### Local stack summary

```text
Herd                    -> Laravel web application
MySQL 8.4               -> Local database
Vite                    -> Frontend development server
FastAPI :8001           -> Tokenization/classification service
Laravel queue worker    -> Queued jobs and notifications

Application             -> https://barangay-sagip.test
Staff login             -> https://barangay-sagip.test/admin/login
FastAPI health          -> http://127.0.0.1:8001/health
```

Redis, WebSockets/Reverb/Pusher, SMTP, and the production Docker stack are not required for this normal local setup. The local configuration uses database-backed sessions/cache/queue, log broadcasting, and the log mail driver.

## Production deployment

Production templates are provided through Docker Compose. Real secrets must remain outside Git.

1. Copy `docker-compose.production.env.example` to `.env` beside `docker-compose.production.yml`.
2. Generate strong unique values for `POSTGRES_PASSWORD` and `TOKENIZATION_SERVICE_KEY`.
3. Copy `barangay-sagip-web/.env.production.example` to `barangay-sagip-web/.env.production` on the deployment host.
4. Set `APP_ENV=production`, `APP_DEBUG=false`, the HTTPS `APP_URL`, PostgreSQL credentials, mail settings, and the internal tokenization service key.
5. Keep PostgreSQL and the FastAPI tokenization/classification service unexposed to the public internet.
6. Build and start:

```bash
docker compose -f docker-compose.production.yml build
docker compose -f docker-compose.production.yml up -d
```

7. Run:

```bash
docker compose -f docker-compose.production.yml exec app php artisan migrate --force
docker compose -f docker-compose.production.yml exec app php artisan optimize
```

8. Configure TLS/DNS in front of Nginx.
9. Verify the Laravel `/up` endpoint and internal FastAPI `/health` endpoint.

See `docker/README.md` for backup, restore testing, and rollback procedures.

## CI/CD

GitHub Actions validates Composer dependency installation and security audit, Laravel migrations and tests, PHP syntax, npm audit and frontend build, Python dependency audit and service tests, production Docker Compose configuration, production image builds, and repository secret scanning with Gitleaks.

The workflow is `.github/workflows/ci.yml`.

## Security principles

- Laravel login routes are rate-limited.
- Resident request authorization is enforced server-side.
- Personnel location updates are restricted to the authenticated personnel account.
- Manual assignment validates personnel availability, workload, request status, and duplicate assignments.
- Assignment operations use transactions and row locking where concurrent updates could otherwise corrupt workload state.
- Protected FastAPI endpoints require `X-Service-Key`.
- Service failures fail safely into review handling rather than silently accepting an unavailable classification service.
- Production secrets and `.env` files are excluded from Git.
- PostgreSQL and the FastAPI service are kept on the private Docker network.

## Production readiness sequence

Before a real public deployment:

1. Build and validate production images in CI.
2. Deploy to a production-like staging environment.
3. Configure HTTPS, DNS, firewall/WAF, and secrets management.
4. Configure PostgreSQL backups and perform a real restore test.
5. Verify Laravel storage and the serving strategy for resident-uploaded files.
6. Confirm queued jobs are processed by the dedicated queue worker.
7. Add application monitoring, centralized logs, metrics, and alerts.
8. Run load, authorization, and end-to-end tests in staging.
9. Freeze and tag the exact release commit.
10. Deploy with a tested rollback path.

Never use the demo credentials or placeholder secrets in production.
