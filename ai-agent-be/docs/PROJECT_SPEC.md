# AI Knowledge Agent

## Purpose

AI Knowledge Agent is a learning-focused, open-source Retrieval-Augmented Generation (RAG) chat application. An administrator adds and publishes knowledge-base content. Laravel indexes published content into Qdrant. Authenticated users chat with an assistant that retrieves relevant internal content and cites its sources.

The public project demonstrates Laravel, queues, real-time chat, vector search, RAG, authorization, and custom AI observability.

## Goals

- Admin content creation, publication, editing, deletion, and re-indexing.
- Reliable MySQL-to-Qdrant synchronization.
- Persisted user conversations and streamed chat answers.
- Internal-content answers with citations.
- Custom records for token usage, cost, duration, sources, tools, and errors.
- A controlled web-search fallback after internal RAG is tested.

## First-release non-goals

- Training an LLM.
- Allowing the LLM direct access to SQL, Qdrant, the filesystem, or admin actions.
- Autonomous multi-agent workflows.
- A Node.js backend, LangChain, or a separate admin repository.

## Repositories

```text
agent-ai-web       React/Vite chat client
agent-ai-backend   Laravel API, admin, jobs, RAG, integrations, observability
```

This repository is `agent-ai-backend`. Browser code never receives database, Qdrant, or AI-provider credentials.

## Stack

| Area | Choice | Purpose |
| --- | --- | --- |
| Application and API | Laravel / PHP | Domain logic, API, authorization, RAG, and integration boundaries |
| Admin | Laravel admin panel | Content, indexing status, and observability workflows |
| Database | MySQL | Canonical source for business data, content, conversations, and traces |
| Vector database | Qdrant | Semantic similarity search over derived content chunks |
| AI provider | Configured through Laravel AI SDK | Chat, embeddings, tools, and streaming |
| Asynchronous work | Laravel Queues + Redis | Indexing and retries |
| Real-time delivery | Laravel Reverb + Echo | Private conversation and indexing events |
| Client | React + Vite | Chat interface, history sidebar, citations |

## Architecture

```text
React client
  | HTTPS API + private WebSocket channels
Laravel backend
  |--- MySQL: source of truth
  |--- Redis: queues and real-time scaling
  |--- Qdrant: vector index
  `--- LLM provider: model inference and embeddings
```

## Content indexing

MySQL is the source of truth. Qdrant stores only derived embeddings and metadata.

```text
draft -> pending_index -> indexed
                       -> failed
published -> pending_index -> indexed
unpublished -> removed_from_index
```

1. An admin creates or edits content in MySQL.
2. Publishing, re-indexing, or changing published content dispatches an `IndexKnowledgeContent` job.
3. Laravel converts rich text to clean text, then chunks it.
4. Initial chunking uses **800 tokens per chunk and 200 tokens overlap**.
5. Laravel generates embeddings and upserts Qdrant points in batches.
6. MySQL stores index timing, content checksum, version, vector count, and any error.
7. Unpublishing or deletion queues removal of all points matching `content_id`.

Overlap keeps context at chunk boundaries. Accuracy also depends on retrieval thresholds, source filtering, prompt instructions, and evaluation.

Each Qdrant point uses a stable ID such as `content:{content_id}:version:{version}:chunk:{number}` and a payload like:

```json
{
  "content_id": 45,
  "content_version": 7,
  "chunk_number": 3,
  "title": "Refund Policy",
  "source_url": "/knowledge/refund-policy",
  "status": "published",
  "checksum": "..."
}
```

## Chat and RAG flow

```text
User message
  -> Laravel authorizes and rate-limits the request
  -> Laravel stores the message in MySQL
  -> Laravel creates an embedding for the question
  -> Qdrant returns permitted similar chunks
  -> Laravel applies a relevance threshold and composes source context
  -> Laravel calls the LLM with instructions, history, and retrieved sources
  -> Laravel streams updates to the private conversation channel
  -> Laravel saves the final answer, citations, usage, cost, and timings
```

The first assistant tool is `searchKnowledgeBase(query)`. Laravel executes it, applies authorization and metadata filters, and returns excerpts plus citations. Future `searchWeb(query)` will be rate-limited and clearly label web sources. The model does not have direct infrastructure access.

## Core records

| Table | Purpose |
| --- | --- |
| `users` | Authentication and ownership |
| `knowledge_contents` | Original content, publication state, version, and source URL |
| `content_index_runs` | Index status, checksum, timings, counts, and failures |
| `conversations` | User-owned chat sessions and titles |
| `messages` | User and assistant messages, citations, model metadata |
| `ai_runs` | Provider, model, token usage, cost, duration, and result |
| `ai_run_steps` | Embedding, Qdrant, LLM, and future web-search steps |

## Observability

User chat history and admin AI-run history are separate. An AI trace includes provider, model, start/end time, duration, input/output/total tokens, estimated cost, retrieved source IDs and scores, tool calls, status, and sanitized errors.

Example:

```text
Conversation: Refund policy
  Qdrant search: 185 ms, 5 results
  LLM generation: 1.8 s, 1,240 input tokens, 210 output tokens
  Total: 2.1 s, estimated cost: $0.00xx
```

Full prompt and response logging must be configurable because it may contain sensitive data. Secrets are never logged.

## Delivery plan

1. **Foundation:** Laravel, MySQL, Redis, Qdrant, Reverb, authentication, CI, models, migrations, policies.
2. **Knowledge base:** content CRUD, publishing, queue-driven vector synchronization, retries, tests.
3. **Grounded chat:** conversation API, retrieval, citations, model generation, React client, streaming.
4. **Quality:** observability UI, evaluation set, source and authorization tests.
5. **Web search:** a constrained fallback tool with separate citations.

## Security and reliability rules

- Keys live in backend environment variables only.
- Authorize all conversations, admin actions, routes, and private channels.
- Validate content and message sizes; rate-limit chat and indexing endpoints.
- Use idempotent indexing jobs and stable Qdrant point IDs.
- Retry transient queue failures and expose safe admin retries.
- Delete vectors when source content is unpublished or deleted.
- Treat retrieved documents and web content as untrusted input that cannot override application instructions.

## Public-repository checklist

- Publish `.env.example`, never actual credentials or private business content.
- Use demo seed data, screenshots, setup instructions, and a short video/GIF.
- Document provider costs and local development commands.
- Test authorization, vector synchronization, indexing, and answer citations.
- Include a license and architecture decision records for material decisions.
