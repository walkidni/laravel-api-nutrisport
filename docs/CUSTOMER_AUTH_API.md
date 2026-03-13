# Customer Auth API

## Scope

This note documents the implemented customer-auth API for the NutriSport backend.

Auth is site-scoped through the resolved current site. Requests do not carry an explicit site field.

## Endpoints

### `POST /v1/auth/register`

Registers a customer for the resolved site.

Request body:

- `email`
- `password`

Response:

- `201`
- created customer summary only
- no access token
- no refresh token

Notes:

- uniqueness is scoped by `(site_id, email)`
- the same email may exist on another site

### `POST /v1/auth/login`

Authenticates a customer for the resolved site.

Request body:

- `email`
- `password`

Response:

- `200`
- `access_token`
- `refresh_token`

Notes:

- valid credentials on the wrong site are rejected
- the access JWT includes customer identity and site context claims

### `POST /v1/auth/refresh`

Rotates the current customer session.

Request body:

- `refresh_token`

Response:

- `200`
- new `access_token`
- rotated `refresh_token`

Rules:

- the old refresh token becomes unusable after a successful refresh
- refresh is site-scoped
- refresh cannot extend the absolute session beyond 6 hours from login
- refresh-token consumption is atomic, so one old token cannot create multiple new sessions under overlapping requests

### `POST /v1/auth/logout`

Logs out the current session only.

Request body:

- `refresh_token`

Response:

- `204`

Rules:

- only the provided refresh-token session is revoked
- other active sessions for the same customer remain usable
- logout is idempotent for an already-consumed token

## Token model

- access token: short-lived JWT
- refresh token: opaque random token
- refresh tokens are stored hashed in `customer_refresh_tokens`
- refresh tokens rotate on every successful refresh
- total customer session lifetime is capped at 6 hours from login

This implementation uses short-lived access JWTs plus rotating refresh tokens rather than a single long-lived JWT covering the entire 6-hour window.

## Notes

- access-token issuance and validation use `tymon/jwt-auth`
- refresh-token storage and rotation are managed by the application, not by the package
- customer auth uses the dedicated `customer` JWT guard in `config/auth.php`
