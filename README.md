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

## API Docs (Swagger)

Base Swagger UI and OpenAPI spec are available at:

- UI: `http://localhost:8000/docs`
- Spec: `http://localhost:8000/docs/openapi.yaml`

You can use this as the starter contract for future API feature testing.

## Load Testing (Queue & Booking)

This project includes a K6-based spike testing suite specifically designed to test the Queue and Booking module's performance under heavy load. The load test simulates high concurrency, checking for race conditions, queue numbering consistency, and database limits.

**How to run the tests via Docker:**

1. **Smoke Test** (Quick check):
   ```bash
   docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=smoke grafana/k6 run /scripts/loadtest/queue/k6-spike.js
   ```

2. **Spike Test** (Standard spike):
   ```bash
   docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=spike grafana/k6 run /scripts/loadtest/queue/k6-spike.js
   ```

3. **Burst 10k Test** (Massive 10,000 user concurrency):
   ```bash
   docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=burst10k -e BURST10K_TARGET=10000 -e BURST10K_HOLD=120s grafana/k6 run /scripts/loadtest/queue/k6-spike.js
   ```

*(Note: If you run into timeouts like `request timeout`, ensure your application is running properly and Docker can access your host via `host.docker.internal`, or change it to your machine's local IP. Also increase `API_RATE_LIMIT_PER_MINUTE` in `.env` to avoid throttle limits).*

For full details and how to export metrics to CSV, see the [Loadtest Queue README](loadtest/queue/README.md).

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
