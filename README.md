<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Docker Run

Use the bundled Docker setup to run the app with Nginx and MySQL:

```bash
docker compose up --build
```

Then open `http://localhost:8000` in your browser.

API base URL: `http://localhost:8000/api/v1`

## API Docs (Swagger)

Base Swagger UI and OpenAPI spec are available at:

- UI: `http://localhost:8000/docs`
- Spec: `http://localhost:8000/docs/openapi.yaml`

You can use this as the starter contract for future API feature testing.

## Patient Registration Module

Implemented scope:

- Registrasi pasien
- Verifikasi NIK/identitas
- Manajemen riwayat kesehatan
- Manajemen riwayat vaksinasi

Identifier format (human-readable):

- `patients.patient_id` -> `PAT-YYYYMMDD-000001` (seed) / `PAT-<ULID>` (API create)
- `faskes.faskes_id` -> `FSK-YYYYMMDD-000001`
- `schedules.schedule_id` -> `SCH-YYYYMMDD-000001`
- `health_histories.health_history_id` -> `HLT-YYYYMMDD-000001`
- `vaccination_histories.vaccination_history_id` -> `VAC-YYYYMMDD-000001`

Main endpoints:

> Lookup route parameter menggunakan ID eksternal (contoh: `PAT-...`, `HLT-...`, `VAC-...`) bukan numeric `id` internal.

- `POST /api/v1/patients`
- `GET /api/v1/patients`
- `GET /api/v1/patients/{patientId}`
- `POST /api/v1/patients/verify-identity`
- `GET /api/v1/patients/{patientId}/health-histories`
- `POST /api/v1/patients/{patientId}/health-histories`
- `PUT /api/v1/patients/{patientId}/health-histories/{historyId}`
- `DELETE /api/v1/patients/{patientId}/health-histories/{historyId}`
- `GET /api/v1/patients/{patientId}/vaccination-histories`
- `POST /api/v1/patients/{patientId}/vaccination-histories`
- `PUT /api/v1/patients/{patientId}/vaccination-histories/{historyId}`
- `DELETE /api/v1/patients/{patientId}/vaccination-histories/{historyId}`

## Large Scale Dummy Data (10.000+)

Seeder already prepares large-scale simulation data for:

- `patients` (default 10,000 rows)
- `faskes` (default 10,000 rows)
- `schedules` (default 10,000 rows)
- `health_histories` (random 1-3 rows per patient)
- `vaccination_histories` (random 1-4 rows per patient)

Run migrations and seed:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Optional override counts:

```bash
docker compose exec app sh -lc "SEED_PATIENT_COUNT=10000 SEED_FASKES_COUNT=10000 SEED_SCHEDULE_COUNT=10000 php artisan db:seed"
```

This setup uses chunked inserts and indexed columns to keep resource usage efficient during normal load and still support high-volume lookup scenarios.

## Spike Test Setup (Lonjakan User)

Project ini sudah disiapkan dengan skenario load test berbasis `k6` di file:

- `loadtest/k6-spike.js`
- `loadtest/export-k6-summary-csv.ps1`

### 1) Start service dan siapkan data

```bash
docker compose up -d
docker compose exec app php artisan migrate:fresh --seed
```

### 2) Opsional: naikkan rate limit API saat stress test

Karena endpoint API memakai middleware `throttle:api`, default limit adalah `120` request/menit/IP.

Untuk pengujian lonjakan, naikkan di `.env`:

```dotenv
API_RATE_LIMIT_PER_MINUTE=50000
```

Lalu restart app container:

```bash
docker compose restart app
```

### 3) Jalankan smoke test (cek script & konektivitas)

```powershell
docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=smoke grafana/k6 run /scripts/loadtest/k6-spike.js
```

### 4) Jalankan spike test utama

```powershell
docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=spike grafana/k6 run /scripts/loadtest/k6-spike.js
```

### 5) Jalankan profile burst 10k (simulasi lonjakan besar)

```powershell
docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=burst10k -e BURST10K_TARGET=10000 -e BURST10K_HOLD=120s grafana/k6 run /scripts/loadtest/k6-spike.js
```

Catatan: jalankan di mesin yang cukup kuat. Jika ingin trial cepat, gunakan target + durasi pendek berikut:

```powershell
docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=burst10k -e BURST10K_TARGET=200 -e BURST10K_HOLD=5s -e BURST_RAMP_1=3s -e BURST_RAMP_2=3s -e BURST_RAMP_3=3s -e BURST_RAMP_DOWN_1=3s -e BURST_RAMP_DOWN_2=3s -e REQUEST_TIMEOUT=10s grafana/k6 run /scripts/loadtest/k6-spike.js
```

### 6) Simpan hasil test ke JSON

```powershell
New-Item -ItemType Directory -Force -Path .\loadtest\results | Out-Null
docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=burst10k -e BURST10K_TARGET=10000 -e BURST10K_HOLD=120s grafana/k6 run --summary-export=/scripts/loadtest/results/summary.json --out json=/scripts/loadtest/results/metrics.json /scripts/loadtest/k6-spike.js
```

### 7) Konversi summary JSON ke CSV

```powershell
powershell -ExecutionPolicy Bypass -File .\loadtest\export-k6-summary-csv.ps1 -InputPath .\loadtest\results\summary.json -OutputPath .\loadtest\results\summary.csv
```

### 8) Pantau bottleneck container

```bash
docker stats faskes_app faskes_nginx faskes_db
```

### Catatan hasil

- Fokus metrik: `http_req_duration` (p95), `http_req_failed`, `http_429_total`.
- Jika `429` tinggi, berarti limiter aktif (bukan selalu bug performa aplikasi).
- File hasil utama: `loadtest/results/summary.json`, `loadtest/results/metrics.json`, `loadtest/results/summary.csv`.
- Untuk simulasi 10.000+ concurrent user yang realistis, pertimbangkan distributed load generator (lebih dari 1 mesin).

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
