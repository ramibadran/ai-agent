# Atlas Commerce Domain Model

Atlas Commerce is a fictional direct-to-consumer ecommerce company. All products, customers, orders, inventory, and financial values will be invented for learning and demonstration.

## Business questions the future MCP server must answer

```text
What was total revenue last month?
How did October sales compare with September?
Which products produced the most revenue?
Which products have low stock?
Which top-selling products may stock out soon?
```

## Planned data model

```text
products
  └── inventory_snapshots

customers
  └── orders
        └── order_items

daily_product_sales (derived reporting data for fast analysis)
```

## Planned tables

| Table | Purpose | Important fields |
| --- | --- | --- |
| `products` | Sellable catalogue products | SKU, name, category, unit price, active state |
| `inventory_snapshots` | Inventory available at a point in time | product ID, quantity on hand, reorder point, captured date |
| `customers` | Fictional purchasers | name, email, country, created date |
| `orders` | Customer purchases | order number, customer ID, status, subtotal, tax, total, ordered date |
| `order_items` | Products inside each order | order ID, product ID, quantity, unit price, line total |
| `daily_product_sales` | Pre-aggregated product reporting | product ID, date, units sold, gross revenue, order count |

## Data rules

- All seed data is fictional.
- Money will be stored as integer cents or fixed-precision decimals, never floating-point values.
- Every order has one or more order items.
- An order item belongs to one product.
- Reporting dates use UTC and are stored as dates or timestamps consistently.
- The first MCP release is read-only: no order, stock, customer, or product mutation tools.

## Seed-data design

Seed data should cover at least twelve months and include realistic patterns:

- Seasonal sales changes.
- One month with a measurable sales decline.
- Several high-selling products.
- Products with low stock.
- At least one high-selling product that is near its reorder point.
- Completed, cancelled, and refunded orders.

This lets an agent answer multi-tool questions using evidence rather than static text.
