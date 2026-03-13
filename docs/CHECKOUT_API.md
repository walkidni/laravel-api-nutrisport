# Checkout API

## Endpoints

### `POST /v1/checkout`

Creates an order from the authenticated customer's current cart on the resolved site.

Request fields:

- `full_name`
- `full_address`
- `city`
- `country`
- optional `payment_method`
  - defaults to `BANK_TRANSFER`
  - if present, must equal `BANK_TRANSFER`
- optional `delivery_method`
  - defaults to `HOME_DELIVERY`
  - if present, must equal `HOME_DELIVERY`

Success behavior:

- requires customer access JWT auth
- creates the order and order-line snapshots
- decrements stock
- clears the cart
- returns `201`
- dispatches `OrderPlacedEvent`

Failure behavior:

- returns `422` for:
  - empty cart
  - insufficient stock at checkout
  - cart lines that are no longer available/priced for the current site
- failed checkout is all-or-nothing:
  - no partial order
  - no partial stock change
  - cart remains unchanged

Public money fields are returned as fixed-scale decimal strings:

- `delivery_amount`
- `total_amount`
- `unit_price_amount`
- `line_total_amount`

Internal persistence remains integer cents on `_amount_cents` fields.

### `GET /v1/bank-transfer-details`

Returns the current site's bank-transfer details from config.

Response fields:

- `account_holder`
- `iban`
- `bic`
- `bank_name`

This endpoint exposes the site-specific bank-transfer instructions used by the checkout flow.

## Order model notes

Orders persist:

- customer and site linkage
- locale-prefixed per-site reference
- `PENDING_PAYMENT` initial status
- payment method `BANK_TRANSFER`
- delivery method `HOME_DELIVERY`
- delivery snapshot fields on the order
- snapshot order lines with `product_id` retained for traceability

## Notification flow

Successful checkout dispatches `OrderPlacedEvent`.

Current listeners:

- customer order email
- administrator order email
- BackOffice realtime order notification

The BackOffice first slice now consumes that seam and broadcasts a minimal order summary on the private global `backoffice.orders` channel. See `docs/BACKOFFICE_API.md`.
