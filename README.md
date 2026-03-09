# NutriSport Laravel API

Laravel backend for the NutriSport technical test.

Project requirements are documented in [docs/PROJECT.md](docs/PROJECT.md).

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Notes

- This repository is intended to expose an API.
- The current project specification lives in `docs/PROJECT.md`.
