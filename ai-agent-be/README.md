# AI Knowledge Agent — Backend

A learning-focused, open-source Laravel backend for a Retrieval-Augmented Generation (RAG) chat application.

Administrators add and publish knowledge-base content. Laravel synchronizes that content to Qdrant for semantic search. Authenticated users can chat with an AI assistant that retrieves relevant internal content, returns cited answers, and saves their conversation history.

> This repository is in active development. Content indexing and the synchronous RAG chat foundation are in place; richer observability and real-time streaming are future milestones.

## Project goals

- Laravel API and admin workflows for knowledge-base content.
- MySQL as the source of truth for content, conversations, and AI-run data.
- Qdrant for embedding storage and similarity search.
- Redis and Laravel queues for future asynchronous processing.
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

For a complete macOS local setup guide covering MySQL, OpenAI, Qdrant, Filament, verification, and troubleshooting, read [docs/LOCAL_SETUP.md](docs/LOCAL_SETUP.md).

The frontend-facing endpoint contract is documented in [docs/API.md](docs/API.md).

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

## Run the React chat client

The React/Vite client is in the sibling `ai-agent-fe` directory. Start the Laravel API first:

```bash
cd ai-agent-be
php artisan serve
```

In a second terminal, install the frontend dependencies and start Vite:

```bash
cd ai-agent-fe
npm install
npm run dev
```

Open the local URL printed by Vite, normally `http://localhost:5173`.

For the local seeded administrator account, sign in with:

```text
Email: admin@example.test
Password: password
```

The Vite development server proxies `/api` requests to Laravel at `http://127.0.0.1:8000`.

## Real-time answer streaming with Reverb

The chat uses Laravel Reverb to broadcast OpenAI text deltas over private WebSocket channels. This allows follow-up answers to appear incrementally instead of waiting for the full answer.

### Configure the React client

Create the frontend environment file:

```bash
cd ai-agent-fe
cp .env.example .env
```

Set `VITE_REVERB_APP_KEY` to the same value as `REVERB_APP_KEY` in the backend `.env`. The app key is safe for the browser; never copy `REVERB_APP_SECRET` to the frontend.

```env
VITE_REVERB_APP_KEY=your_backend_reverb_app_key
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
```

### Start all local processes

Use three terminals:

```bash
# Terminal 1: Laravel HTTP API
cd ai-agent-be
php artisan serve
```

```bash
# Terminal 2: Reverb WebSocket server
cd ai-agent-be
php artisan reverb:start
```

```bash
# Terminal 3: React/Vite client
cd ai-agent-fe
npm run dev -- --host 127.0.0.1
```

Open `http://127.0.0.1:5173/`.

### Streaming data flow

```text
React subscribes to private conversation.{id}
→ Laravel authorizes the JWT user at /api/broadcasting/auth
→ Laravel calls OpenAI Responses API with stream=true
→ OpenAI emits response.output_text.delta events
→ Laravel broadcasts AssistantTextDelta through Reverb
→ React appends each delta to the assistant message
→ Laravel saves the completed answer and observability data in MySQL
```

Private channel authorization checks that the conversation belongs to the authenticated user, so users cannot subscribe to another user's chat.

The first message in a brand-new conversation currently uses the normal completed-response display because the browser does not know the new conversation ID until Laravel creates it. Follow-up messages stream through Reverb. A future two-step create-then-send API will enable streaming for the first message too.

## Manual RAG chat test cases

After seeding and indexing the fictional Northstar Nexus document, use the React chat client to verify the full flow: embedding, Qdrant retrieval, OpenAI answer generation, source display, and saved history.

| Test type | Question | Expected answer or behavior |
| --- | --- | --- |
| Exact fact | `What was Northstar Nexus revenue in 2024?` | Revenue was 18.4 million USD. |
| Growth | `What was the revenue growth rate in 2024?` | 31% year-over-year growth. |
| Employees | `How many employees did Northstar Nexus have in 2024?` | 168 employees. |
| Hiring plan | `How many people does the company plan to hire in 2025?` | 22 roles, with a target year-end headcount of 190. |
| Product | `What is OrbitFlow?` | A supply-planning workspace for supply planners and operations managers. |
| Product comparison | `What is the difference between OrbitFlow and PulseSight?` | OrbitFlow supports planning; PulseSight provides operations analytics. |
| Financial forecast | `Is the company expected to be profitable in 2026?` | A fictional 0.6 million USD operating profit is forecast. |
| Follow-up context | Ask `What is OrbitFlow?`, then ask `Who uses it?` | The answer should identify supply planners and operations managers. |
| Follow-up context | Ask `What was revenue in 2024?`, then ask `What was the growth rate?` | The answer should understand the follow-up and return 31%. |
| Unknown information | `Who is Rami?` | The assistant should say the indexed knowledge does not contain that information. |
| Outside knowledge | `What is the capital of France?` | The assistant should not answer from general knowledge; it should say the information is unavailable in the knowledge base. |

For relevant answers, verify that a source from `Northstar Nexus: Fictional Company Profile` appears below the response, and refresh the page to confirm the conversation remains in history.

## Development principles

- MySQL is canonical; Qdrant is a derived index.
- MVP indexing is direct and idempotent; it will move to asynchronous processing after the MVP.
- The LLM receives only controlled tools; it never has direct infrastructure access.
- Grounded answers cite their retrieved sources.
- Secrets, private prompts, and real business content never enter the public repository.

## License

License selection is pending before the first public release.
