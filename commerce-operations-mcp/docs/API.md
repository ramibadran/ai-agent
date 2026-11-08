# Reporting Verification API

This temporary API verifies the Laravel reporting services before the same capabilities are exposed as MCP tools.

Base URL in local development:

```text
http://127.0.0.1:8001/api/v1/reports
```

Every request requires the local service key:

```http
X-Commerce-Api-Key: {COMMERCE_API_KEY}
```

This API is read-only. It is not the final public MCP interface.

## Shared response contract

The verification API follows the same JSON contract as `ai-agent-be`:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

Authentication failures use `401`; a missing local configuration uses `503`; validation errors use `412`. Error responses have `success: false` and put either a readable message or validation errors in `message`.

For example, an invalid `threshold` query value returns:

```json
{
  "success": false,
  "message": ["The threshold field must be an integer."]
}
```

## Sales summary

```http
GET /sales-summary?start_date=2026-10-01&end_date=2026-10-31
```

Returns completed-order revenue, order count, average order value, and refunded revenue.

## Sales comparison

```http
GET /sales-comparison?current_start_date=2026-10-01&current_end_date=2026-10-31&previous_start_date=2026-09-01&previous_end_date=2026-09-30
```

Returns both sales summaries plus percentage changes. A percentage change is `null` when the previous period has no value, because dividing by zero would be misleading.

## Top products

```http
GET /top-products?start_date=2026-10-01&end_date=2026-10-31&metric=revenue&limit=5
```

`metric` is `revenue` or `units_sold`. `limit` is between 1 and 25.

## Product stock

```http
GET /products/ATL-AERO-X1-BLK/stock
```

Returns the most recent inventory snapshot for the requested SKU.

## Low stock products

```http
GET /inventory/low-stock?threshold=20&limit=20
```

Returns active products whose latest inventory snapshot is at or below the threshold.

## Local curl example

```bash
curl 'http://127.0.0.1:8001/api/v1/reports/sales-summary?start_date=2026-10-01&end_date=2026-10-31' \
  -H 'X-Commerce-Api-Key: local-commerce-development-key'
```

Never commit a production service key. The local development key lives only in `.env`, which Git ignores.
