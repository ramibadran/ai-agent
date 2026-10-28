# API Reference

Base URL for local development: `http://localhost:8000/api/v1`.

Every API response uses this envelope:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

## Authentication

### Login

`POST /auth/login`

```json
{
  "email": "admin@example.test",
  "password": "password"
}
```

Save `authorization.token` from the response. All chat endpoints require it:

```http
Authorization: Bearer {token}
```

## Chat

Chat requests are limited to 10 requests per authenticated user per minute.

### List conversations

`GET /chat/conversations`

Returns only the authenticated user's conversations, newest activity first.

### Create a conversation and send the first message

`POST /chat/conversations`

```json
{
  "message": "What was Northstar Nexus revenue in 2024?"
}
```

The backend saves the user message, retrieves relevant Qdrant chunks, generates an answer when sources exist, then saves the assistant message.

### Read a conversation

`GET /chat/conversations/{conversationId}`

Returns the full ordered message history. A user cannot read another user's conversation.

### Send a follow-up message

`POST /chat/conversations/{conversationId}/messages`

```json
{
  "message": "What was the growth rate?"
}
```

The LLM receives recent conversation history, the new question, and fresh Qdrant sources.

### Delete a conversation

`DELETE /chat/conversations/{conversationId}`

Deletes the conversation and its messages.

## Assistant message shape

```json
{
  "id": 42,
  "role": "assistant",
  "content": "Northstar Nexus reported 18.4 million USD in FY2024 revenue.",
  "sources": [
    {
      "content_id": 7,
      "chunk_number": 2,
      "title": "Northstar Nexus: Fictional Company Profile",
      "source_url": "https://northstar-nexus.example/company-profile",
      "score": 0.91
    }
  ],
  "observability": {
    "model": "gpt-5.4-mini",
    "prompt_tokens": 1000,
    "completion_tokens": 20,
    "total_tokens": 1020,
    "latency_ms": 500,
    "cost_usd": "0.00084000",
    "status": "completed"
  }
}
```

For normal users, render `content` and `sources`. Keep `observability` for an internal/debug view.

## No-result behavior

If Qdrant returns no relevant chunks, the API saves and returns this assistant response without calling the LLM:

```text
I could not find this information in the available knowledge base.
```

It does not invoke the OpenAI chat model, so its answer-generation cost is zero. The question still uses one small embedding request before Qdrant can determine that no source matches.

Qdrant chunks with a similarity score below `QDRANT_MINIMUM_SCORE` (default: `0.30`) are treated as not relevant.
