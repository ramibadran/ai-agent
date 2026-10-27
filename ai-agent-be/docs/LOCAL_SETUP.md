# Local Installation and Setup

This guide runs the AI Knowledge Agent backend on macOS for local development.

## What runs locally

```text
Laravel + Filament admin  ->  PHP application and browser admin
MySQL                     ->  canonical application data
Qdrant (Docker)           ->  derived embedding vectors
OpenAI API                ->  embeddings for knowledge-content chunks
```

MySQL is the source of truth. Qdrant can always be rebuilt from MySQL content.

## Prerequisites

- PHP 8.3 or newer
- Composer
- MySQL running locally on port `3306`
- Docker Desktop running
- An OpenAI Platform account with API billing enabled

Verify the main tools:

```bash
php --version
composer --version
docker --version
```

## 1. Install PHP dependencies

From the backend directory:

```bash
cd /Library/WebServer/Documents/ai-agent/ai-agent-be
composer install
```

## 2. Create the local environment file

Never commit `.env`. It holds local credentials.

```bash
cp .env.example .env
php artisan key:generate
```

Configure MySQL in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ai_knowledge_agent
DB_USERNAME=root
DB_PASSWORD=your_local_mysql_password
```

Create the database once:

```bash
mysql -u root -p -e 'CREATE DATABASE IF NOT EXISTS ai_knowledge_agent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
```

Run migrations and demo seed data:

```bash
php artisan migrate
php artisan db:seed
```

The demo admin account is:

```text
Email: admin@example.test
Password: password
```

Change this password before using any shared or production environment.

## 3. Create an OpenAI API key

1. Sign in to the [OpenAI Platform](https://platform.openai.com/).
2. Create a project named `AI Knowledge Agent`.
3. Add billing and set a low learning/development spend limit.
4. Open **API Keys** within that project.
5. Create a new secret key named `ai-agent-local`.
6. Copy it immediately. The full value is shown only once.

Add it only to the private `.env` file:

```env
OPENAI_API_KEY=your_openai_api_key
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
OPENAI_EMBEDDING_DIMENSIONS=1536
```

Do not put the key in `.env.example`, Git, the browser client, or screenshots.

## 4. Run Qdrant locally with Docker

Qdrant is not installed in Laravel or MySQL. It runs as a separate local Docker container.

The project uses:

- REST API and dashboard: `127.0.0.1:6333`
- gRPC: `127.0.0.1:6334`
- Docker container: `ai-agent-qdrant`
- Persistent Docker volume: `ai_agent_qdrant_storage`

Create and start it once:

```bash
docker volume create ai_agent_qdrant_storage

docker run -d \
  --name ai-agent-qdrant \
  --restart unless-stopped \
  -p 127.0.0.1:6333:6333 \
  -p 127.0.0.1:6334:6334 \
  -v ai_agent_qdrant_storage:/qdrant/storage \
  qdrant/qdrant:v1.19.2
```

The port binding is deliberately limited to `127.0.0.1`, so Qdrant is not reachable from other devices on the network.

Set matching Laravel values in `.env`:

```env
QDRANT_URL=http://127.0.0.1:6333
QDRANT_API_KEY=
QDRANT_COLLECTION=knowledge_chunks
QDRANT_VECTOR_SIZE=1536
QDRANT_CHUNK_SIZE=800
QDRANT_CHUNK_OVERLAP=200
```

`QDRANT_VECTOR_SIZE` must match `OPENAI_EMBEDDING_DIMENSIONS`.

Useful Qdrant commands:

```bash
# Check whether it is running
docker ps --filter name=ai-agent-qdrant

# Start it after it has been stopped
docker start ai-agent-qdrant

# Stop it without deleting vectors
docker stop ai-agent-qdrant

# View logs
docker logs -f ai-agent-qdrant

# Open Qdrant's browser dashboard
open http://127.0.0.1:6333/dashboard
```

Do not run `docker rm -v ai-agent-qdrant` unless you intentionally want to remove the container and its vector storage.

## 5. Start Laravel and access the admin

Clear cached configuration after editing `.env`, then start the application:

```bash
php artisan config:clear
php artisan serve
```

Open the admin panel:

```text
http://127.0.0.1:8000/admin
```

Only users where `is_admin = true` can enter the panel.

## 6. Verify the full content synchronization flow

1. Log in to `/admin`.
2. Open **Knowledge Contents**.
3. Create an article with a title and body.
4. Confirm the article appears in the list and has an indexed timestamp.
5. Open `http://127.0.0.1:6333/dashboard`.
6. Confirm the `knowledge_chunks` collection was created and contains points.
7. Edit the article body, save it, and confirm its Qdrant point count updates.
8. Delete the article and confirm its Qdrant points disappear.

For the MVP, synchronization is direct:

```text
Admin save request -> MySQL -> OpenAI embeddings -> Qdrant -> success response
```

If OpenAI or Qdrant fails, the request reports an error and `content_index_runs` stores the failure. MySQL content remains the source of truth.

## 7. Run automated checks

```bash
vendor/bin/pint --dirty --format agent
php artisan test
```

## Troubleshooting

### `Connection refused` for Qdrant

```bash
docker start ai-agent-qdrant
curl http://127.0.0.1:6333/readyz
```

### Content save fails with OpenAI authentication error

Check that `OPENAI_API_KEY` is present in `.env`, then run:

```bash
php artisan config:clear
```

### Qdrant vector dimension error

Ensure these values are identical:

```env
QDRANT_VECTOR_SIZE=1536
OPENAI_EMBEDDING_DIMENSIONS=1536
```

If you change dimensions after data already exists, delete and recreate the local Qdrant collection before re-indexing content.
