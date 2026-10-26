# AI Knowledge Agent — Backend

A learning-focused, open-source Laravel backend for a Retrieval-Augmented Generation (RAG) chat application.

Administrators add and publish knowledge-base content. Laravel synchronizes that content to Qdrant for semantic search. Authenticated users can chat with an AI assistant that retrieves relevant internal content, returns cited answers, and saves their conversation history.

> This repository is in active development. The foundation is in place; content indexing, chat, and observability are the next milestones.

## Project goals

- Laravel API and admin workflows for knowledge-base content.
- MySQL as the source of truth for content, conversations, and AI-run data.
- Qdrant for embedding storage and similarity search.
- Redis and Laravel queues for reliable background indexing.
- Laravel Reverb for private, real-time chat events.
- Custom observability for tokens, estimated cost, latency, retrieved sources, and errors.
- A separate React/Vite chat client in the `ai-agent-fe` repository.

## Architecture

```text
React chat client
  | HTTPS API + private WebSocket channels
Laravel backend
  |--- MySQL: users, content, conversations, AI traces
  |--- Redis: queues and real-time infrastructure
  |--- Qdrant: derived vector index
  `--- LLM provider: text generation and embeddings
```

## Documentation

The full product and technical design is in [docs/PROJECT_SPEC.md](docs/PROJECT_SPEC.md). It explains the data flow, chunking strategy, Qdrant model, RAG process, security rules, observability plan, and delivery phases.

## Local setup

1. Copy the example environment file:

   ```bash
   cp .env.example .env
   ```

2. Generate an application key:

   ```bash
   php artisan key:generate
   ```

3. Configure MySQL, Redis, Qdrant, and AI-provider credentials in `.env`.

4. Install PHP dependencies:

   ```bash
   composer install
   ```

5. Run migrations:

   ```bash
   php artisan migrate
   ```

6. Run the test suite:

   ```bash
   php artisan test
   ```

## Development principles

- MySQL is canonical; Qdrant is a derived index.
- Indexing is asynchronous, retryable, and idempotent.
- The LLM receives only controlled tools; it never has direct infrastructure access.
- Grounded answers cite their retrieved sources.
- Secrets, private prompts, and real business content never enter the public repository.

## License

License selection is pending before the first public release.
