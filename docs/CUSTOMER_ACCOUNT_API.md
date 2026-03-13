# Customer Account API

## Scope

This note documents the implemented authenticated customer account surface for the NutriSport backend.

All endpoints:

- require authenticated customer access JWT
- are scoped to the current resolved site
- are customer-owned only

## Endpoints

### `GET /v1/me`

Returns the authenticated customer's profile.

Response fields:

- `id`
- `first_name`
- `last_name`
- `email`

### `PATCH /v1/me`

Partially updates the authenticated customer's profile.

Allowed request fields:

- `first_name`
- `last_name`
- `email`

Rules:

- updates are partial
- email changes are allowed
- email uniqueness remains scoped by site

Response fields:

- `id`
- `first_name`
- `last_name`
- `email`

### `PUT /v1/me/password`

Changes the authenticated customer's password.

Request body:

- `current_password`
- `password`
- `password_confirmation`

Response:

- `204`

Rules:

- the current password must match the stored password hash
- wrong `current_password` returns validation-style `422`

### `GET /v1/me/orders`

Returns newest-first order summaries for the authenticated customer on the resolved site.

Response fields per order:

- `id`
- `reference`
- `total_amount`
- `status`
- `created_at`

Notes:

- the first slice is unpaginated
- `total_amount` is returned as a decimal string

### `GET /v1/me/orders/{order}`

Returns the authenticated customer's site-scoped order detail.

Response fields:

- `id`
- `reference`
- `total_amount`
- `status`
- `payment_method`
- `delivery_method`
- `delivery_amount`
- `created_at`
- `full_name`
- `full_address`
- `city`
- `country`
- `lines`

Line fields:

- `product_id`
- `product_name`
- `quantity`
- `unit_price_amount`
- `line_total_amount`

Notes:

- detail is `404` when the order is not owned by the authenticated customer
- detail is also `404` when the order belongs to the same customer on another site
- public money fields are returned as decimal strings
