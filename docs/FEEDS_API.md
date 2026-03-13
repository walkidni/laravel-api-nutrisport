# Feeds API

## Scope

This note documents the implemented public catalog feeds slice.

The feeds surface is:

- public
- site-scoped
- machine-readable
- limited to the catalog fields required by the spec

Implemented endpoints:

- `GET /v1/feeds`
- `GET /v1/feeds/{format}`

Supported formats:

- `json`
- `xml`

## Site scope

Feeds use the same resolved-site model as the storefront catalog.

That means:

- the current site is resolved from the request host
- feed links are different per site host
- the feed includes only products visible on the resolved site

Product visibility in the feed is driven by site pricing:

- a product belongs in the feed only if it has a `product_site_prices` row for the resolved site

## `GET /v1/feeds`

Returns the list of available feed links for the resolved site.

Response shape:

- `formats`

`formats[*]` fields:

- `format`
- `url`

Notes:

- URLs are absolute
- URLs are generated from named routes in the current request context
- the response contains one entry for `json` and one for `xml`

Example:

```json
{
  "formats": [
    {
      "format": "json",
      "url": "https://fr.api.nutri-core.com/v1/feeds/json"
    },
    {
      "format": "xml",
      "url": "https://fr.api.nutri-core.com/v1/feeds/xml"
    }
  ]
}
```

## `GET /v1/feeds/{format}`

Returns the full visible product feed for the resolved site in the requested format.

Supported `format` values:

- `json`
- `xml`

Behavior:

- no authentication required
- no pagination
- returns all visible products for the resolved site
- returns `404` for an unsupported format
- returns an empty successful feed if the site has no visible products

Feed item fields:

- `id`
- `name`
- `in_stock`

Stock availability rule:

- `in_stock = product.stock > 0`

The feed does not expose:

- price
- stock quantity
- product detail fields beyond the required export contract

## JSON feed

Content type:

- `application/json`

Response shape:

```json
{
  "products": [
    {
      "id": 1,
      "name": "Whey Protein",
      "in_stock": true
    }
  ]
}
```

## XML feed

Content type:

- `application/xml`

Response shape:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<products>
  <product>
    <id>1</id>
    <name>Whey Protein</name>
    <in_stock>true</in_stock>
  </product>
</products>
```

Notes:

- XML is produced with `DOMDocument`
- the XML response is returned as a raw feed document, not a JSON-style API resource

## Format support

The feeds slice supports format growth without changing the public route shape.

Current components:

- one feed-product query
- one normalized DTO
- one renderer interface
- one renderer per format
- one format registry
- one feed index action
- one feed show action

Adding a new format can be handled as a feed-domain extension rather than a route redesign.
