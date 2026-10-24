# AGENTS.md

Laravel 13 skeleton app (PHP ^8.3, Vite 8 + Tailwind v4).

## Setup

- `composer setup` bootstraps everything: deps, `.env`, app key, migrations, `npm install --ignore-scripts`, `npm run build`.
- Default DB is SQLite (`database/database.sqlite`); `.env` is not committed.
- `.npmrc` sets `ignore-scripts=true` (and `audit=true`); keep npm installs compatible with that.

## Development

- `composer dev` runs `php artisan dev`: serves HTTP, runs queue listener, runs Vite.
- Standalone: `php artisan serve`, `npm run dev`, `npm run build`.
- Vite entrypoints: `resources/css/app.css`, `resources/js/app.js` (see `vite.config.js`).

## Testing

- `composer test` clears the config cache, then runs PHPUnit. Prefer this over `php artisan test` directly.
- PHPUnit (see `phpunit.xml`) uses in-memory SQLite (`:memory:`) plus array cache/session/queue; no external services needed.
- Run one file: `php artisan test tests/Feature/ExampleTest.php`.
- Run by filter: `php artisan test --filter=ExampleTest` (or `vendor/bin/phpunit --filter`).

## Formatting

- Format: `vendor/bin/pint`. Check only: `vendor/bin/pint --test`.
- No static analysis/type checker is configured.

## Structure

- Controllers: `app/Http/Controllers` (`Controller` base).
- Models: `app/Models` (e.g. `User` uses `#[Fillable]`, `#[Hidden]`, and a `casts()` method).
- Routes: `routes/web.php`, `routes/console.php`; API routes are not scaffolded - run `php artisan install:api` to add them.
- DB schema/migrations and factories: `database/`, with factories under `Database\Factories`.
- Tests: `tests/Unit`, `tests/Feature`, namespace `Tests\`.

## Notes

- `composer test` runs `config:clear` first because config caching affects environment overrides in tests.
- Production/cached bootstrap files (`config:cache`, `route:cache`, etc.) can mask `.env` changes; clear caches when debugging.