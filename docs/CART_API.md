# Cart API

## Current implemented slice

The implemented cart endpoints are:

- `GET /v1/cart`
- `POST /v1/cart/items`
- `PATCH /v1/cart/items/{product}`
- `DELETE /v1/cart/items/{product}`

All cart endpoints are site-aware through the resolved current site.

## Cart identity

- Cart identity uses the `X-Cart-Token` header.
- The backend issues the token on the first successful add-to-cart request.
- `GET /v1/cart` does not mint a token when no cart exists yet.
- When a cart becomes empty and is removed from cache, the response no longer returns `X-Cart-Token`.

## Storage

- Cart state is stored in cache.
- Cache key shape:
  - `cart:{site_code}:{token}`
- Cart TTL:
  - 3 days
- Stored cart payload remains minimal:
  - `product_id`
  - `quantity`

Current product names and current site prices are resolved at response time rather than stored in cache.

## Response shape

Cart responses currently return:

- `lines`
  - `product_id`
  - `name`
  - `quantity`
  - `unit_price_amount`
  - `line_total_amount`
- `item_count`
- `total_amount`

Public money values are returned as fixed-scale decimal strings such as `"29.99"`.

## Current behavior

### Read cart

- `GET /v1/cart`
- Without a valid cart token:
  - returns an empty cart
  - does not return `X-Cart-Token`

### Add item

- `POST /v1/cart/items`
- Request body:
  - `product_id`
  - `quantity`
- If the cart does not exist yet:
  - a cart is created implicitly
  - `X-Cart-Token` is returned
- If the product already exists in the cart:
  - quantity is incremented
- If the intended final quantity exceeds stock:
  - returns `422`
  - message: `Requested quantity exceeds available stock.`

### Set quantity

- `PATCH /v1/cart/items/{product}`
- Request body:
  - `quantity`
- Sets the absolute quantity for the matching line
- `quantity = 0` removes the line
- If the last line is removed:
  - the cart is forgotten
  - `X-Cart-Token` is not returned

### Remove item

- `DELETE /v1/cart/items/{product}`
- Removes the requested line if present
- If the line is missing:
  - returns `200`
  - cart remains unchanged
- If the last remaining line is removed:
  - the cart is forgotten
  - `X-Cart-Token` is not returned

## Notes

- Cart stock checks are enforced at cart mutation time against shared product stock.
- Cart totals and line enrichment are handled through `CartStateService`.
- Cart math stays internal to the backend in integer cents.
