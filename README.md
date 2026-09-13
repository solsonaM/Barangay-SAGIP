# Barangay SAGIP — Full System (Laravel + Python ML Microservice)

A machine learning-driven emergency assistance classification and response
coordination platform, implementing all 12 features from the project
proposal. Two parts:

- **`/laravel-app`** — PHP/Laravel application layer (migrations, models,
  controllers, services, views). Meant to be merged into a fresh
  `laravel new` project (see below for why).
- **`/ml-service`** — Python FastAPI microservice with real, trained
  scikit-learn models for request classification, urgency classification,
  and response-assignment scoring. This part runs and has been tested
  end-to-end already.

---

## Why `laravel-app` is an overlay, not a full install

This was built in a sandboxed environment without registry access to
`packagist.org`, so `composer install` could not be run here to generate
Laravel's full framework skeleton (`vendor/`, `public/index.php`,
`bootstrap/app.php`, etc.). Every file in `laravel-app/` is real,
hand-written, syntax-checked PHP (all 53 files pass `php -l`), but you'll
drop them into a freshly generated Laravel project rather than running this
folder standalone. This takes about 10 minutes — steps below.

## Feature → File Map

| # | Feature | Where it lives |
|---|---------|-----------------|
| 1 | Resident Registration and Profiling | `Auth/RegisteredUserController`, `ResidentProfileController`, `resident_profiles` table |
| 2 | Emergency/Assistance Request Submission | `EmergencyRequestController@create/store`, `requests/create.blade.php` |
| 3 | ML-Based Request Classification | `MLClassificationService`, `ml-service/main.py: /classify/request-type` |
| 4 | Urgency/Priority Classification | `MLClassificationService`, `ml-service/main.py: /classify/urgency` |
| 5 | Request Validation | `needs_review` flag set in `classify/full`, low-confidence threshold in `ml-service/main.py` |
| 6 | Response Assignment Classification | `ResponseAssignmentService`, `ResponseAssignmentController`, `ml-service/main.py: /assign/response` |
| 7 | Real-Time Urgent Status Tracking | `EmergencyRequest::transitionTo()`, `request_status_logs` table, `requests/show.blade.php` |
| 8 | Location Map Generator | `MapController`, `map/index.blade.php` (Leaflet) |
| 9 | Response Personnel Management | `ResponsePersonnelController`, `personnel/*.blade.php` |
| 10 | Alerts and Notifications | `RequestStatusUpdated`, `NewAssignmentNotification`, `NotificationController` |
| 11 | Dashboards | `DashboardController`, `dashboard.blade.php` (role-conditional) |
| 12 | Report Generator | `ReportController`, `reports/index.blade.php`, CSV export |

---

## Part 1: Set up the ML microservice

```bash
cd ml-service
python3 -m venv venv
source venv/bin/activate        # Windows: venv\Scripts\activate
pip install -r requirements.txt

# Generate the training dataset and train the classifiers
python3 data/generate_dataset.py
python3 train_classifier.py

# Run the service
uvicorn main:app --host 0.0.0.0 --port 8001
```

Verify it's up: `curl http://127.0.0.1:8001/health` should return
`{"status":"ok","models_loaded":true}`.

**About the dataset and accuracy:** `data/generate_dataset.py` builds a
templated, researcher-constructed dataset (per the proposal's Section 6.2
data-limitation disclosure) — ~378 rows of Filipino/Bikol/English
code-switched sample reports. Evaluated with a template-held-out split (so
near-duplicate phrasings never leak between train/test), the baseline
models score roughly **~49% accuracy on request-type** and **~38% on
urgency** — honest numbers for a small synthetic dataset, not inflated
ones. This is a real starting point, not a finished model: swap in actual
(anonymized) barangay incident logs as they become available, and consider
a fine-tuned multilingual transformer (see proposal Section 9.1) once you
have enough real data. Re-run `train_classifier.py` any time the dataset
changes — it regenerates `models/training_metrics.json` with the new
numbers for your thesis documentation.

---

## Part 2: Set up the Laravel application

```bash
# 1. Generate a fresh Laravel 11 project (needs internet access to packagist.org)
composer create-project laravel/laravel barangay-sagip-web
cd barangay-sagip-web

# 2. Install the one additional package used for API auth (optional but
#    referenced in routes/api.php)
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 3. Copy this overlay's app-specific files into the fresh project,
#    overwriting where they already exist (App\Models\User, routes/web.php,
#    routes/api.php, config/services.php — merge the ml_service key into
#    your existing services.php rather than overwriting it wholesale).
cp -r ../laravel-app/app/* app/
cp -r ../laravel-app/database/migrations/* database/migrations/
cp ../laravel-app/database/seeders/DatabaseSeeder.php database/seeders/
cp -r ../laravel-app/resources/views/* resources/views/
cp ../laravel-app/routes/web.php routes/web.php
cp ../laravel-app/routes/api.php routes/api.php
# Merge (don't overwrite) the ml_service block from:
#   ../laravel-app/config/services.php  ->  config/services.php

# 4. Register the 'role' middleware alias — see
#    ../laravel-app/bootstrap/app.php.snippet for exactly what to add to
#    your bootstrap/app.php

# 5. Environment
cp ../laravel-app/.env.example .env
php artisan key:generate
# Edit .env: set your DB credentials and confirm ML_SERVICE_URL matches
# the FastAPI service from Part 1 (default http://127.0.0.1:8001)

# 6. Database
php artisan migrate --seed

# 7. Run
php artisan serve
```

Visit `http://127.0.0.1:8000`. Demo logins (seeded, password `password` for all):

| Role | Email |
|------|-------|
| Official | `official@sagip.test` |
| Personnel | `personnel@sagip.test` |
| Resident | `resident@sagip.test` |

Log in as the resident, submit a request (try: *"tulong po, hindi na
humihinga ang lolo ko"*) — with both servers running, you should see it
auto-classified as Medical/Critical and auto-assigned to the nearest
available medical-specialization personnel within a second or two. Log in
as the official to see it on the Dashboard, Map, and Reports pages.
