# MCP Testing Guide

Test the Commerce MCP server before connecting it to `ai-agent-be`. The tests progress from fast automated checks to a real HTTP MCP request.

## 1. Automated Laravel tests

Run all application and MCP tests:

```bash
cd commerce-operations-mcp
php artisan test
```

Run only the MCP server tests:

```bash
php artisan test --filter=AtlasCommerceMcpServerTest
```

These tests use Laravel MCP's fake transport. They verify tool discovery, structured outputs, invalid input handling, and unknown-SKU errors without opening an HTTP port.

## 2. Start the local MCP server

```bash
php artisan serve --port=8001
```

The MCP endpoint is:

```text
http://127.0.0.1:8001/mcp/atlas-commerce
```

Every MCP request requires this header:

```http
Authorization: Bearer {MCP_COMMERCE_TOKEN}
```

Keep this terminal running while using the Inspector or `curl`. Stop it with `Control + C`.

## 3. Use the MCP Inspector

In a second terminal:

```bash
cd commerce-operations-mcp
php artisan mcp:inspector mcp/atlas-commerce
```

The Inspector is the easiest manual test. Use it to:

1. Connect to the local server.
2. Confirm the five tools appear.
3. Open a tool to inspect its description and input schema.
4. Call tools with the test inputs below.
5. Inspect `structuredContent` and any validation errors.

Open the Inspector's **Authentication** section and select Bearer token authentication. Enter the value of `MCP_COMMERCE_TOKEN` from the local `.env` file. Do not use `COMMERCE_API_KEY`; that key protects the separate reporting verification API.

## 4. Manual test cases

| Tool | Input | Expected result |
|---|---|---|
| `get_sales_summary` | `start_date: 2026-10-01`, `end_date: 2026-10-31` | Revenue, order count, AOV, and refunded revenue in USD. |
| `compare_sales_periods` | October 2026 as current, September 2026 as previous | Two summaries and percentage changes. |
| `get_top_products` | October 2026, `metric: revenue`, `limit: 3` | At most three ranked products. |
| `get_product_stock` | `sku: ATL-AERO-X1-BLK` | Latest quantity, reorder point, and capture time. |
| `find_low_stock_products` | `threshold: 20`, `limit: 20` | Active products at or below 20 units. |

Negative test cases:

| Tool | Invalid input | Expected result |
|---|---|---|
| `get_sales_summary` | `start_date: not-a-date` | MCP tool error explaining date validation failed. |
| `get_product_stock` | `sku: UNKNOWN-SKU` | Controlled "not found" MCP tool error; no database details. |
| `get_top_products` | `metric: profit` | MCP tool error because only `revenue` and `units_sold` are supported. |
| `find_low_stock_products` | `limit: 500` | MCP tool error because the maximum is 100. |

## 5. HTTP discovery check with curl

This command shows that MCP tool discovery works through the real HTTP endpoint:

```bash
curl --request POST 'http://127.0.0.1:8001/mcp/atlas-commerce' \
  --header 'Content-Type: application/json' \
  --header 'Accept: application/json, text/event-stream' \
  --header 'MCP-Protocol-Version: 2026-07-28' \
  --header 'Authorization: Bearer your_private_mcp_bearer_token' \
  --header 'Mcp-Method: tools/list' \
  --data '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/list",
    "params": {
      "_meta": {
        "io.modelcontextprotocol/protocolVersion": "2026-07-28",
        "io.modelcontextprotocol/clientCapabilities": {}
      }
    }
  }'
```

Confirm the response contains all five tool names and `readOnlyHint: true` for every tool.

## 6. HTTP tool-call check with curl

This calls the MCP tool through HTTP rather than Laravel's test transport:

```bash
curl --request POST 'http://127.0.0.1:8001/mcp/atlas-commerce' \
  --header 'Content-Type: application/json' \
  --header 'Accept: application/json, text/event-stream' \
  --header 'MCP-Protocol-Version: 2026-07-28' \
  --header 'Authorization: Bearer your_private_mcp_bearer_token' \
  --header 'Mcp-Method: tools/call' \
  --header 'Mcp-Name: get_sales_summary' \
  --data '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/call",
    "params": {
      "name": "get_sales_summary",
      "arguments": {
        "start_date": "2026-10-01",
        "end_date": "2026-10-31"
      },
      "_meta": {
        "io.modelcontextprotocol/protocolVersion": "2026-07-28",
        "io.modelcontextprotocol/clientCapabilities": {}
      }
    }
  }'
```

A successful result has:

```json
{
  "result": {
    "isError": false,
    "structuredContent": {
      "currency": "USD"
    }
  }
}
```

The exact sales values depend on the data currently seeded in your local database.

## Why protocol headers exist

MCP is a protocol, not a normal REST API. The headers mirror key request values so Laravel MCP can reject mismatched or incomplete requests before a tool runs:

- `MCP-Protocol-Version`: protocol revision the client uses.
- `Mcp-Method`: MCP operation, such as `tools/list` or `tools/call`.
- `Mcp-Name`: required for a named capability call, such as `get_sales_summary`.

An MCP client and the Laravel MCP Inspector send these automatically. They are shown in the `curl` commands only to make the protocol visible while learning.
