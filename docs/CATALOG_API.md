# Catalog API

## Current implemented slice

The implemented catalog endpoints are:

- `GET /v1/products`
- `GET /v1/products/{product}`

Both endpoints are site-aware.

## Site resolution

- Primary source: request host/domain
- Optional fallback: `X-Site-Code`, but only when enabled by configuration

At this stage, the host is the normal contract. The header path exists as a controlled fallback for later work.

The header fallback is controlled by `SITE_HEADER_FALLBACK_ENABLED`.

The current canonical site hosts are:

- `fr.api.nutri-core.com`
- `it.api.nutri-core.com`
- `be.api.nutri-core.com`

These hosts are defaults. They can be overridden per environment through `config/sites.php`.

## Response shape

Both the listing items and the detail response currently return:

- `id`
- `name`
- `price_amount`
- `in_stock`

`price_amount` is returned as a fixed-scale decimal string such as `"29.99"`.

`in_stock` is derived from shared product stock using `stock > 0`.

## Notes

- Product stock is shared across sites.
- Product prices are site-specific through `product_site_prices`.
- Product prices are stored internally as integer cents in `product_site_prices.price_amount_cents`.
- Product detail only resolves a product when that product has a price for the resolved site.
- Catalog query and action classes use explicit role suffixes, for example `ListProductsQuery` and `FindProductForSiteQuery`.
