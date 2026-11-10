# Local Development Guide

This repository contains three independently runnable applications:

```text
ai-agent-be                 Laravel RAG chat backend and Filament admin
ai-agent-fe                 React and Vite chat client
commerce-operations-mcp    Laravel read-only commerce MCP server
```

Run every command below from the repository root unless the command first changes into an application directory.

## Prerequisites

Install these tools before starting:

```text
PHP 8.3+
Composer 2+
Node.js 20+ and npm
MySQL 8+ (or a compatible local MySQL service)
Docker (for Qdrant)
```

The RAG backend also requires an OpenAI API key. Redis and queue workers are not required for the current MVP.

## Ports used locally

| Service | Default URL or port | Purpose |
|---|---:|---|
| AI Agent backend | `http://127.0.0.1:8000` | Laravel API, Filament admin, and Reverb authorization |
| AI Agent frontend | `http://127.0.0.1:5173` | React/Vite chat interface |
| Laravel Reverb | `ws://127.0.0.1:8080` | Real-time answer streaming |
| Qdrant | `http://127.0.0.1:6333` | Vector database HTTP API and dashboard |
| Commerce MCP server | `http://127.0.0.1:8001` | Commerce reporting API and MCP endpoint |

## 1. AI Agent backend (`ai-agent-be`)

### First-time setup

```bash
cd ai-agent-be
composer install
cp .env.example .env
php artisan key:generate
```

Configure these values in `.env` before continuing:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_agent
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password

OPENAI_API_KEY=your_openai_api_key
QDRANT_URL=http://127.0.0.1:6333
```

Do not commit `.env` files or API keys.

Run migrations, seed local learning data, and create the Qdrant index:

```bash
php artisan migrate
php artisan db:seed
php artisan knowledge:reindex
```

`knowledge:reindex` calls the embedding provider and can incur a small API cost.

### Run the backend

```bash
cd ai-agent-be
php artisan serve --port=8000
```

The API is available at `http://127.0.0.1:8000/api/v1`.

### Run Reverb streaming

Open another terminal:

```bash
cd ai-agent-be
php artisan reverb:start
```

### Open the admin panel

Visit:

```text
http://127.0.0.1:8000/admin
```

The seeded local administrator is:

```text
Email: admin@example.test
Password: password
```

Use this account only for local development. Change or remove it outside local environments.

### Test the backend

```bash
cd ai-agent-be
php artisan test
```

For complete user-facing RAG test cases, see [Manual acceptance test cases](#manual-acceptance-test-cases) below.

## 2. AI Agent frontend (`ai-agent-fe`)

### First-time setup

```bash
cd ai-agent-fe
npm install
cp .env.example .env
```

Configure the public Reverb connection values in `.env`:

```env
VITE_REVERB_APP_KEY=the_same_value_as_backend_REVERB_APP_KEY
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
```

`VITE_REVERB_APP_KEY` is safe to expose to the browser. Never put `REVERB_APP_SECRET`, database passwords, or OpenAI keys in a Vite environment file.

### Run the frontend

Make sure the backend and Reverb are running first. Then run:

```bash
cd ai-agent-fe
npm run dev
```

Open `http://127.0.0.1:5173`.

### Test the frontend

```bash
cd ai-agent-fe
npm run lint
npm run build
```

For a manual test, sign in with the seeded account, create a conversation, ask a RAG test question, confirm sources appear, refresh the page, and confirm conversation history remains visible.

## 3. Qdrant for RAG

Start Qdrant locally with Docker:

```bash
docker run --name ai-agent-qdrant \
  --publish 6333:6333 \
  --publish 6334:6334 \
  --volume qdrant_storage:/qdrant/storage \
  qdrant/qdrant
```

Open the dashboard at:

```text
http://127.0.0.1:6333/dashboard
```

To start the same container after the first run:

```bash
docker start ai-agent-qdrant
```

## 4. Commerce MCP server (`commerce-operations-mcp`)

The MCP application uses a separate MySQL database. It does not use Qdrant, Reverb, or OpenAI.

### First-time setup

```bash
cd commerce-operations-mcp
composer install
cp .env.example .env
php artisan key:generate
```

Configure the separate database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=commerce_operations_mcp
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password
```

Then create its schema and fictional commerce data:

```bash
php artisan migrate
php artisan db:seed
```

### Run and test the normal reporting API

```bash
cd commerce-operations-mcp
php artisan serve --port=8001
```

Run the automated suite in another terminal:

```bash
cd commerce-operations-mcp
php artisan test
```

The protected verification API lives below:

```text
http://127.0.0.1:8001/api/v1/reports
```

See [`commerce-operations-mcp/docs/API.md`](commerce-operations-mcp/docs/API.md) for endpoints and the required local API-key header.

### Test the MCP server manually

Keep the Laravel server running on port `8001`. In another terminal:

```bash
cd commerce-operations-mcp
php artisan mcp:inspector mcp/atlas-commerce
```

The Inspector opens in a browser. Select **Streamable HTTP** if it is not selected automatically, enter:

```text
http://localhost:8001/mcp/atlas-commerce
```

and click **Connect**.

Open the **Tools** tab, list the tools, then test `get_sales_summary`:

```json
{
  "start_date": "2026-10-01",
  "end_date": "2026-10-31"
}
```

Expected result: `Tool Result: Success` and structured sales facts from the commerce database.

Then test an invalid input:

```json
{
  "start_date": "not-a-date",
  "end_date": "2026-10-31"
}
```

Expected result: a controlled MCP validation error, not a server crash.

For all MCP test cases and raw HTTP examples, read [commerce-operations-mcp/docs/MCP_TESTING.md](commerce-operations-mcp/docs/MCP_TESTING.md).

## Recommended local terminal layout

```text
Terminal 1  ai-agent-be              php artisan serve --port=8000
Terminal 2  ai-agent-be              php artisan reverb:start
Terminal 3  ai-agent-fe              npm run dev
Terminal 4  commerce-operations-mcp  php artisan serve --port=8001
Terminal 5  commerce-operations-mcp  php artisan mcp:inspector mcp/atlas-commerce
```

The frontend and the commerce MCP server are not integrated yet. Keep them separate until the agent-backend MCP client integration phase begins.

## Manual acceptance test cases

Use these cases after the local services are running. They are intentionally split into RAG and MCP because the two systems retrieve facts differently.

### RAG chat test cases

Use the AI Agent frontend at `http://127.0.0.1:5173`. Select **RAG** mode before asking each question.

| # | User question | Expected result |
|---:|---|---|
| 1 | `What was Northstar Nexus revenue in 2024?` | States that 2024 revenue was **18.4 million USD** and shows an internal Northstar Nexus source. |
| 2 | `What was the revenue growth rate in 2024?` | States **31% year-over-year growth** and shows a source. |
| 3 | `How many employees did Northstar Nexus have in 2024?` | States **168 employees** and shows a source. |
| 4 | `How many people does the company plan to hire in 2025?` | States **22 roles** and the target year-end headcount of **190**. |
| 5 | `What is OrbitFlow?` | Explains that OrbitFlow is the supply-planning workspace and identifies its intended users. |
| 6 | Ask `What is OrbitFlow?`, then ask `Who uses it?` in the same conversation. | Uses conversation history and answers supply planners and operations managers. |
| 7 | `Prepare a detailed executive briefing on Northstar Nexus covering products, strategy, technology, regional growth, 2026 plan, and risks.` | Produces a structured, longer grounded answer. For a follow-up message, text should appear incrementally through Reverb. |
| 8 | `Who is Rami?` | Does not invent an answer; explains that the internal knowledge base has no relevant information. |
| 9 | `What is the capital of France?` | Does not answer from general knowledge in RAG mode; explains that the information is unavailable in the internal knowledge base. |

For every relevant answer, confirm that source cards appear below the answer. Refresh the browser after one or more conversations and confirm history remains visible.

### MCP commerce test cases

Use the MCP Inspector connected to `http://localhost:8001/mcp/atlas-commerce`. These are direct tool tests for the standalone commerce server; the AI Agent does not call these tools yet.

| # | Business question | Tool and Inspector input | Expected result |
|---:|---|---|---|
| 1 | How did Atlas Commerce perform in October 2026? | `get_sales_summary` with `start_date: 2026-10-01`, `end_date: 2026-10-31` | `Tool Result: Success` with revenue, orders, average order value, refunded revenue, and currency. |
| 2 | Did October perform better than September? | `compare_sales_periods` with October as the current period and September as the previous period. | Both periods and percentage changes are returned as structured facts. |
| 3 | Which products generated the most revenue in October? | `get_top_products` with `start_date: 2026-10-01`, `end_date: 2026-10-31`, `metric: revenue`, `limit: 3` | At most three products ranked by revenue. |
| 4 | Which products sold the most units in October? | `get_top_products` with the same dates, `metric: units_sold`, `limit: 3` | At most three products ranked by units sold. |
| 5 | What is the current stock for AeroRun X1 Black? | `get_product_stock` with `sku: ATL-AERO-X1-BLK` | Latest stock quantity, reorder point, and capture time. |
| 6 | Which products need inventory attention? | `find_low_stock_products` with `threshold: 20`, `limit: 20` | Active products whose latest quantity is at or below 20. |
| 7 | Test safe unknown-product handling. | `get_product_stock` with `sku: UNKNOWN-SKU` | Controlled MCP tool error saying stock was not found; no database or stack-trace details. |
| 8 | Test date validation. | `get_sales_summary` with `start_date: not-a-date`, `end_date: 2026-10-31` | Controlled MCP validation error; no crash. |

For successful MCP results, confirm the Inspector shows `Structured Content` rather than only plain prose. That proves the server is returning facts that a future AI agent can safely use to generate an answer.
