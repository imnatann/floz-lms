# FLOZ Web App

This directory contains the main FLOZ web application.

## Stack

- Laravel 12
- PHP 8.2+
- Vue 3 + Inertia.js
- PostgreSQL
- Redis
- Vite

## What Lives Here

- `app/Http/Controllers/Platform` - central platform controllers for super admin features
- `app/Http/Controllers/Tenant` - tenant-scoped school features
- `app/Models/Central` - platform data like tenants and subscriptions
- `app/Models/Tenant` - school data like users, students, teachers, grades, and schedules
- `app/Services` - business logic such as tenant provisioning and report card generation
- `resources/js/Pages/Platform` - platform admin pages
- `resources/js/Pages/Tenant` - tenant pages for students, grades, schedules, announcements, report cards, and more
- `routes/web.php` - Inertia/web routes
- `routes/api.php` - mobile API routes

## Local Setup

Run these commands from `src`:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan tenants:migrate
```

## Local Development

Typical local workflow:

```bash
php artisan serve
php artisan queue:listen
php artisan reverb:start
npm run dev
```

Or use the root-level Docker files:

- `docker-compose.dev.yml` for bind-mounted development
- `docker-compose.yml` for production-style images

## Security and Tenancy Notes

- Platform routes are separated from tenant routes.
- Tenant context is resolved from subdomains on web requests and `X-Tenant-Slug` on mobile API requests.
- Central database defaults should come from `TENANCY_CENTRAL_DATABASE`.
- Do not deploy with example passwords, localhost URLs, or placeholder secrets.

## Verification

Useful commands from `src`:

```bash
php artisan test
npm run build
```
