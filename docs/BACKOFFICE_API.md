# BackOffice API

## Scope

This note documents the implemented first BackOffice slice.

The current BackOffice surface includes:

- agent auth
- recent-orders listing
- product creation
- realtime order notifications over the private `backoffice.orders` channel

BackOffice agents are global:

- they are not scoped to a storefront site
- they authenticate with the dedicated `backoffice` JWT guard
- authorization uses narrow capability flags, not RBAC
- agent `ID=1` bypasses permission checks

## Auth endpoints

### `POST /v1/backoffice/auth/login`

Authenticates a BackOffice agent with email and password.

Request body:

- `email`
- `password`

Success response fields:

- `access_token`
- `refresh_token`

Notes:

- invalid credentials return validation-style `422`
- access JWTs are short-lived
- refresh-token rotation is used to maintain the session
- overall BackOffice session lifetime is capped at 8 hours from login
- this implementation uses short-lived access JWTs plus rotating refresh tokens rather than a single long-lived JWT covering the entire 8-hour window

### `POST /v1/backoffice/auth/refresh`

Rotates the current refresh token and returns a new auth pair.

Request body:

- `refresh_token`

Success response fields:

- `access_token`
- `refresh_token`

Notes:

- the old refresh token becomes unusable after a successful refresh
- invalid, consumed, or expired refresh tokens return validation-style `422`
- refresh cannot extend the session past the 8-hour absolute session cap

### `POST /v1/backoffice/auth/logout`

Logs out the current BackOffice session by consuming the submitted refresh token.

Request body:

- `refresh_token`

Response:

- `204`

Notes:

- logout is current-session oriented
- the operation is idempotent for an already consumed refresh token

## Protected endpoints

### `GET /v1/backoffice/orders`

Returns the recent BackOffice order list across all sites.

Auth:

- requires BackOffice access JWT

Query parameters:

- optional `page`
- optional `per_page`
  - default is `20`
  - maximum is `100`

Response fields per order:

- `id`
- `customer_name`
- `total_amount`
- `status`
- `remaining_amount`

Behavior:

- newest first
- all storefront sites combined
- limited to the last 5 days only
- paginated with standard Laravel `links` and `meta`

Authorization:

- requires `can_view_recent_orders`
- agent `ID=1` bypasses the permission check

Notes:

- `total_amount` and `remaining_amount` are decimal strings
- `remaining_amount` currently equals `total_amount`
- partial-payment tracking is not part of the current API surface

### `POST /v1/backoffice/products`

Creates a product on top of the existing global product plus site-price model.

Auth:

- requires BackOffice access JWT

Request body:

- `name`
- `initial_stock`
- `site_prices`

`site_prices[*]` fields:

- `site_code`
- `price`

Success response fields:

- `id`
- `name`
- `stock`
- `site_prices`

`site_prices[*]` response fields:

- `site_code`
- `price_amount`

Behavior:

- creates one global product row
- seeds the global stock from `initial_stock`
- creates one site-price row per submitted site entry
- the product is effectively visible only on sites that receive a price entry

Authorization:

- requires `can_create_products`
- agent `ID=1` bypasses the permission check

Notes:

- request `price` accepts decimal-string money input
- response `price_amount` is returned as a decimal string

## Realtime notifications

Successful checkout now triggers a BackOffice realtime notification.

Channel:

- `private-backoffice.orders`

Subscription rules:

- authenticated through `/broadcasting/auth`
- uses `auth:backoffice`
- requires `can_view_recent_orders`
- agent `ID=1` bypasses the permission check

Broadcast payload fields:

- `id`
- `customer_name`
- `total_amount`
- `status`
- `remaining_amount`

Notes:

- this is a single global BackOffice channel, not per-site
- notifications are broadcast-only
- there is no persistence, unread state, or replay API
- realtime delivery is implemented through Laravel broadcasting
- Reverb is the preferred runtime for this project
- the broadcaster remains configurable, so a Pusher-compatible runtime can be used without redesigning the feature
