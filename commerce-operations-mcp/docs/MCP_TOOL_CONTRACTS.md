# Proposed MCP Tool Contracts

These are design contracts only. No tools are implemented yet.

## Shared rules

- Every tool is read-only in version one.
- Dates use ISO-8601 `YYYY-MM-DD` format.
- Currency values include an ISO currency code.
- Invalid dates, unknown SKUs, and invalid limits return structured errors.
- Tool output contains facts and data; the LLM is responsible for prose explanations.

## `get_sales_summary`

Returns overall commerce performance for a date range.

```json
{
  "start_date": "2026-10-01",
  "end_date": "2026-10-31"
}
```

```json
{
  "currency": "USD",
  "revenue": 128400.00,
  "orders": 1920,
  "average_order_value": 66.88,
  "refunded_revenue": 2100.00
}
```

## `compare_sales_periods`

Compares two explicit date ranges. The client must supply both ranges; the tool will not guess what “last month” means.

```json
{
  "current_start_date": "2026-10-01",
  "current_end_date": "2026-10-31",
  "previous_start_date": "2026-09-01",
  "previous_end_date": "2026-09-30"
}
```

## `get_top_products`

Returns products ranked by units sold or revenue for a date range.

```json
{
  "start_date": "2026-10-01",
  "end_date": "2026-10-31",
  "metric": "revenue",
  "limit": 5
}
```

## `get_product_stock`

Returns current inventory and reorder information for one SKU.

```json
{
  "sku": "ATL-AERO-X1-BLK"
}
```

## `find_low_stock_products`

Returns active products where current inventory is below a requested threshold or their configured reorder point.

```json
{
  "threshold": 20,
  "limit": 20
}
```

## Later tool: `find_stockout_risks`

This tool will combine current inventory with daily sales velocity. It is deliberately postponed until the base reports and their tests are correct.

```text
Current stock / average daily units sold = estimated days of cover
```

## Example multi-tool question

```text
Why did sales decline in October, and which high-selling products are at risk of stockout?
```

Expected agent behavior:

```text
1. compare_sales_periods
2. get_top_products
3. find_low_stock_products or find_stockout_risks
4. Compare tool outputs
5. Produce an evidence-based management summary
```
