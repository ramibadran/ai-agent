# Local Setup

## Purpose

`commerce-operations-mcp` is a standalone Laravel 13 application. It will use its own MySQL database and must not share the `ai_agent` database used by the RAG application.

## Required services

```text
PHP 8.3+
Composer
MySQL 8+ or compatible local MySQL service
```

Redis, queues, Qdrant, and OpenAI credentials are not required for this standalone MCP server.

The temporary reporting verification API also requires a local `COMMERCE_API_KEY`. It is a service key for local testing, not the final MCP authentication design.

## Database

The intended local database name is:

```text
commerce_operations_mcp
```

Create it with the MySQL client provided by your local MySQL installation:

```sql
CREATE DATABASE commerce_operations_mcp
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

If your MySQL client is available on the terminal, the equivalent command is:

```bash
mysql -u root -p -e "CREATE DATABASE commerce_operations_mcp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Do not run this if the database already exists.

## Environment

Copy the example file only when `.env` does not already exist:

```bash
cp .env.example .env
```

The project uses this non-secret local configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=commerce_operations_mcp
DB_USERNAME=root
DB_PASSWORD=
```

Set `DB_USERNAME` and `DB_PASSWORD` to match the local MySQL installation. Never commit `.env`.

## Verify Laravel and MySQL

After creating the database and configuring `.env`:

```bash
cd commerce-operations-mcp
php artisan config:clear
php artisan migrate
php artisan db:seed
php artisan test
```

The migration and seeder create the commerce reporting tables and fictional Atlas Commerce data used by the API and MCP tools.

## Run locally

```bash
cd commerce-operations-mcp
php artisan serve --port=8001
```

The application will be available at `http://127.0.0.1:8001`.

Port `8001` avoids colliding with `ai-agent-be`, which normally runs on port `8000`.

The MCP endpoint is `http://127.0.0.1:8001/mcp/atlas-commerce`. See the [MCP testing guide](MCP_TESTING.md) for Inspector, automated-test, and HTTP testing steps.
