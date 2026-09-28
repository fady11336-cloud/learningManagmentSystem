<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# Project: Learning Management System (LMS) API

Laravel 13 / PHP `^8.3` API for an LMS. Backend-only: the domain is defined by models + migrations
(roles, users, courses, categories, certificates, lessons, enrollments, progresses, assignments,
submissions, quizzes, questions, attempts, notifications). The app is **early scaffolding** —
there are **no controllers, no API routes, and no feature tests yet**. Only `Controller.php` base
class and a bare `routes/web.php` exist.

## Environment & storage

- DB is **SQLite** (`.env`: `DB_CONNECTION=sqlite`). Tests run against `:memory:` sqlite.
- `SESSION_DRIVER`, `QUEUE_CONNECTION`, and `CACHE_STORE` are all `database` — a fresh dev DB needs
  the sessions/jobs/cache tables from the default migrations before the app works (`composer setup`
  handles this).
- `.npmrc` sets `ignore-scripts=true` and `audit=true`; npm installs will not run lifecycle scripts.
- No CI and no Docker configs exist in this repo.

## Commands

- Bootstrap a fresh dev environment: `composer setup` (composer install, copy `.env`, `key:generate`,
  `migrate --force`, `npm install --ignore-scripts`, `npm run build`).
- Run tests: `composer test` (runs `artisan config:clear` then `artisan test`). Focus a single test with
  standard PHPUnit filters, e.g. `php artisan test --filter=SomeTest`.
- Format code: `vendor/bin/pint`.
- Frontend assets: `npm run build` / `npm run dev` (Vite + Tailwind 4, `resources/css|js`).

## Gotchas / non-obvious facts

- **Quizzes table is typo'd as `quizes`** (migration `2026_08_27_235339_create_quizes_table.php` → table
  `quizes`). `app/Models/Quiz.php` does **not** set `protected $table`, so Eloquent will look for
  `quizzes` and fail on any query. Any code touching `Quiz` must set the table name (or rename the
  table in a new migration). `Course::quizes()` returns `hasMany(Quiz::class)`.
- **Model conventions are inconsistent**: `User` uses modern `#[\Fillable([...])]` / `#[\Hidden([...])]`
  PHP attributes; every other model uses classic `protected $fillable = [...]`. Match the style of the
  specific model being edited.
- **JWT auth is installed and configured but NOT wired**: `tymon/jwt-auth` is in `composer.json`,
  `config/jwt.php` is published, and `JWT_SECRET` is set in `.env` — but `config/auth.php` only defines
  the `web` guard (no `api`/`jwt` guard). Adding JWT-protected API routes requires wiring the guard first.
- **`routes/api.php` is not registered**: `bootstrap/app.php` `withRouting()` only wires `web` and
  `commands` (plus the `/up` health route), and `routes/api.php` is empty anyway. It must be added to
  `withRouting()` before API routes work. `shouldRenderJsonWhen(...api/*...)` is already configured.
