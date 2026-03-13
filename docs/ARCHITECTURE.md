# Architecture

## Overview

This repository is an API-only Laravel 12 backend for the NutriSport technical test.

It serves two main consumers:

- the public storefront / customer frontend
- the BackOffice frontend

The backend is multi-site on the storefront side:

- France
- Italy
- Belgium

The API prefix is versioned under `/v1`.

## System Model

The system is organized around a few stable business areas:

- catalog
- cart
- customers
- orders / checkout
- BackOffice
- feeds
- reporting

The storefront side is site-aware.

That affects:

- visible products
- prices
- customer accounts
- carts
- orders
- bank-transfer details

The BackOffice side is global rather than site-scoped.

That affects:

- agent authentication
- recent orders
- product creation
- realtime order notifications

## Request Flow

Public and customer endpoints use host-based site resolution.

The normal request model is:

1. the request reaches Laravel on a site-specific host
2. the current site is resolved
3. the endpoint runs inside that site context

The current default host shape is:

- `fr.api.nutri-core.com`
- `it.api.nutri-core.com`
- `be.api.nutri-core.com`

An optional `X-Site-Code` fallback exists for local or constrained environments, but host-based resolution is the primary contract.

## Authentication Model

There are two separate auth surfaces.

### Customer auth

- site-scoped
- JWT access token + rotating refresh token
- registration, login, refresh, logout

### BackOffice auth

- global
- JWT access token + rotating refresh token
- permission-gated access to protected resources

BackOffice authorization is intentionally narrow:

- `can_view_recent_orders`
- `can_create_products`

Agent `ID=1` bypasses permission checks.

## Data Model Rules

Several rules shape the whole backend:

- product stock is shared across all sites
- product prices are site-specific
- customers are not shared between sites
- BackOffice agents are global
- money is stored internally as integer cents
- money is exposed publicly as fixed-scale decimal strings

Examples of public money fields:

- `price_amount`
- `unit_price_amount`
- `line_total_amount`
- `delivery_amount`
- `total_amount`

## Code Organization

The repository keeps Laravel HTTP concerns in standard framework locations and groups business logic by feature under `app/Domain`.

### HTTP layer

- `app/Http/Controllers/Api`
- `app/Http/Requests/Api`
- `app/Http/Resources/Api`
- `app/Http/Middleware`

Controllers stay thin and delegate use-case orchestration to domain actions and queries.

### Domain layer

- `app/Domain/Catalog`
- `app/Domain/Cart`
- `app/Domain/Orders`
- `app/Domain/Customers`
- `app/Domain/Backoffice`
- `app/Domain/Feeds`
- `app/Domain/Reporting`
- `app/Domain/Shared`

`app/Domain/Shared` is reserved for cross-feature concerns such as site context and common support services.

## Delivery And Side Effects

The backend uses three delivery styles beyond normal HTTP responses.

### Queued order side effects

Successful checkout dispatches an order-placed event.

That event currently drives:

- customer order email
- administrator order email

Those email listeners are queued.

### Realtime BackOffice notifications

Successful checkout also triggers a BackOffice broadcast event.

This is:

- broadcast-only
- private-channel based
- permission-gated
- suitable for Reverb-backed realtime delivery

### Scheduled reporting

The backend registers a daily midnight reporting job.

That job sends a plain-text French `J-1` administrator report containing:

- most sold product
- least sold product
- highest-turnover product
- lowest-turnover product
- turnover by site

## Runtime Dependencies

The main runtime expectations are:

- MySQL for persistence
- Redis for cache and queue runtime
- mail configuration for email delivery
- optional Reverb for live BackOffice notifications

The app can still run without live Reverb; realtime notifications then remain inactive at the websocket layer.

## Implemented Feature Areas

The current backend includes:

- public catalog and product detail
- cache-backed cart
- customer auth
- customer profile and order history
- checkout and bank-transfer details
- BackOffice auth, recent orders, product creation, and realtime notifications
- public JSON/XML feeds
- daily reporting mail

## Related Docs

- [docs/CATALOG_API.md](docs/CATALOG_API.md)
- [docs/CART_API.md](docs/CART_API.md)
- [docs/CUSTOMER_AUTH_API.md](docs/CUSTOMER_AUTH_API.md)
- [docs/CUSTOMER_ACCOUNT_API.md](docs/CUSTOMER_ACCOUNT_API.md)
- [docs/CHECKOUT_API.md](docs/CHECKOUT_API.md)
- [docs/BACKOFFICE_API.md](docs/BACKOFFICE_API.md)
- [docs/FEEDS_API.md](docs/FEEDS_API.md)
- [docs/REPORTING.md](docs/REPORTING.md)
