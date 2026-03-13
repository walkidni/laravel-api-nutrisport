# NutriSport Laravel API

API-first Laravel backend for the NutriSport technical test.

This repository implements the backend side of a multi-site e-commerce API for France, Italy, and Belgium. The current codebase includes:

- public catalog and product details
- cart stored in cache
- customer registration, login, refresh, logout
- customer profile and order history
- checkout with bank transfer only
- customer and administrator order emails
- BackOffice auth, recent orders, product creation, and realtime order notifications
- public JSON/XML product feeds
- daily reporting mail for `J-1`

There is no web UI in this repository. Everything is exposed as JSON/XML HTTP endpoints under `/v1`.

## Requirements

- PHP `^8.2`
- Composer
- MySQL
- Redis
- a mail transport if you want real email delivery
- optional: Laravel Reverb if you want live BackOffice notifications instead of inert/log broadcasting

## Clone And Start

### 1. Install dependencies

```bash
composer install
```

### 2. Create your local env file

```bash
cp .env.example .env
```

### 3. Generate the Laravel app key

```bash
php artisan key:generate
```

### 4. Generate the JWT secret

This project uses `tymon/jwt-auth`. A cloned repo will not work correctly for auth until the JWT secret exists.

```bash
php artisan jwt:secret
```

### 5. Create the databases

At minimum you need:

- one local database for the app, for example `nutrisport`
- one local database for tests, for example `nutrisport_test`

Update `.env` if your local MySQL credentials differ from the defaults.

### 6. Run migrations and seed the sites table

```bash
php artisan migrate --seed
```

Important:

- `migrate` alone is not enough for local usage
- the `SiteSeeder` populates the `sites` table from `config/sites.php`
- site resolution will fail if the `sites` table is empty

## Site Resolution

Most public endpoints are site-aware. The backend resolves the current site from the request host.

Default site hosts:

- `fr.api.nutri-core.com`
- `it.api.nutri-core.com`
- `be.api.nutri-core.com`

### Option A: use local hosts file entries

Add entries like these to your local hosts file:

```txt
127.0.0.1 fr.api.nutri-core.com
127.0.0.1 it.api.nutri-core.com
127.0.0.1 be.api.nutri-core.com
```

Then start the app:

```bash
php artisan serve
```

And call it with the site host:

```bash
curl http://fr.api.nutri-core.com:8000/v1/products
```

### Option B: use the fallback site header for local testing

If you do not want local host entries, enable the fallback in `.env`:

```env
SITE_HEADER_FALLBACK_ENABLED=true
```

Then call the API through one shared host and pass the site code:

```bash
curl \
  -H 'X-Site-Code: fr' \
  http://127.0.0.1:8000/v1/products
```

This is only a fallback. The primary runtime model is still host-based site resolution.

## Background Processes

### Queue worker

Checkout dispatches order side effects. Customer and administrator order emails are handled by queued listeners.

Run a worker if you want those async side effects to execute locally:

```bash
php artisan queue:work
```

If you keep the default local `MAIL_MAILER=log`, emails will be written to logs rather than sent.

### Scheduler

The daily reporting email is registered in Laravel's scheduler and runs at midnight.

For local testing of scheduled tasks:

```bash
php artisan schedule:work
```

### Reverb

BackOffice realtime order notifications are broadcast through Laravel broadcasting. If you want live realtime delivery locally, configure Reverb and run:

```bash
php artisan reverb:start
```

If you leave `BROADCAST_CONNECTION=log`, the realtime feature stays inert from a websocket perspective.

## First Requests To Try

### Public catalog

```bash
curl http://fr.api.nutri-core.com:8000/v1/products
```

### Public feeds index

```bash
curl http://fr.api.nutri-core.com:8000/v1/feeds
```

### JSON feed

```bash
curl http://fr.api.nutri-core.com:8000/v1/feeds/json
```

### XML feed

```bash
curl http://fr.api.nutri-core.com:8000/v1/feeds/xml
```

### Customer registration

```bash
curl \
  -X POST \
  -H 'Content-Type: application/json' \
  -d '{"email":"alice@example.com","password":"password"}' \
  http://fr.api.nutri-core.com:8000/v1/auth/register
```

## Bootstrapping Data For Local Testing

The default seeding is intentionally minimal.

What gets seeded:

- sites only

What does not get seeded automatically:

- products
- customers
- BackOffice agents

### Create a BackOffice agent

There is no BackOffice agent-management endpoint yet. For local testing, create agents through Tinker or direct SQL.

Example with Tinker:

```bash
php artisan tinker
```

```php
App\Domain\Backoffice\Models\BackofficeAgent::factory()->create([
    'email' => 'admin@example.com',
    'can_view_recent_orders' => true,
    'can_create_products' => true,
]);
```

Notes:

- the factory sets the password to `password`
- if the `backoffice_agents` table is empty, the first created agent will usually get `id = 1`
- agent `id = 1` bypasses BackOffice authorization checks by design

### Create products

You can create products:

- through the BackOffice `POST /v1/backoffice/products` endpoint once you have an authenticated agent
- or directly through factories / Tinker if you just need local data quickly

## Running Tests

Run the full test suite:

```bash
php artisan test
```

Run a focused subset:

```bash
php artisan test tests/Feature/Reporting tests/Feature/Api/Backoffice
```

Important:

- this project uses a shared MySQL test database
- do not run MySQL-backed `RefreshDatabase` test files in parallel terminals
- if you want to run multiple test files, pass them in one single `php artisan test ...` command

## API Surface

Main public/customer routes:

- `POST /v1/auth/register`
- `POST /v1/auth/login`
- `POST /v1/auth/refresh`
- `POST /v1/auth/logout`
- `GET /v1/me`
- `PATCH /v1/me`
- `PUT /v1/me/password`
- `GET /v1/me/orders`
- `GET /v1/me/orders/{order}`
- `GET /v1/cart`
- `POST /v1/cart/items`
- `PATCH /v1/cart/items/{product}`
- `DELETE /v1/cart/items/{product}`
- `GET /v1/products`
- `GET /v1/products/{product}`
- `GET /v1/bank-transfer-details`
- `POST /v1/checkout`
- `GET /v1/feeds`
- `GET /v1/feeds/{format}`

BackOffice routes:

- `POST /v1/backoffice/auth/login`
- `POST /v1/backoffice/auth/refresh`
- `POST /v1/backoffice/auth/logout`
- `GET /v1/backoffice/orders`
- `POST /v1/backoffice/products`

## Project Docs

The local docs folder contains the implementation notes for each slice:

- [docs/INDEX.md](docs/INDEX.md)
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- [docs/CATALOG_API.md](docs/CATALOG_API.md)
- [docs/CART_API.md](docs/CART_API.md)
- [docs/CUSTOMER_AUTH_API.md](docs/CUSTOMER_AUTH_API.md)
- [docs/CUSTOMER_ACCOUNT_API.md](docs/CUSTOMER_ACCOUNT_API.md)
- [docs/CHECKOUT_API.md](docs/CHECKOUT_API.md)
- [docs/BACKOFFICE_API.md](docs/BACKOFFICE_API.md)
- [docs/FEEDS_API.md](docs/FEEDS_API.md)
- [docs/REPORTING.md](docs/REPORTING.md)

## Practical Notes

- public and customer endpoints are site-aware
- users are not shared across sites
- stock is shared globally across sites
- prices are site-specific
- money is stored internally in integer cents and exposed publicly as fixed-scale decimal strings
- daily reporting uses the Laravel app timezone from `APP_TIMEZONE`
