# MCP Architecture and Boundaries

## Goal

The future MCP server gives an AI application controlled access to read-only commerce reporting capabilities.

```text
AI Agent / MCP host
        ↓ MCP protocol
Commerce Operations MCP server (Laravel)
        ↓ domain services
MySQL: commerce_operations_mcp
```

## What MCP does here

MCP standardizes tool discovery and execution. The AI host can discover approved tools, call one or more tools with structured input, receive structured results, and use those results to write an answer.

MCP does not replace MySQL, Laravel authorization, reporting queries, or the LLM.

```text
Laravel service  → calculates trusted business facts
MCP tool         → exposes a narrow, documented service capability
LLM              → explains tool results in natural language
```

## Relationship with the existing RAG application

```text
RAG application
Question → embedding → Qdrant → source text → LLM answer

Commerce MCP application
Question → MCP tool call → MySQL aggregate query → structured facts → LLM answer
```

The first integration will use either RAG or Commerce MCP tools, not both in one turn. Combined RAG/MCP workflows are a later learning step.

## Security boundary

The MCP server must never expose:

```text
raw SQL execution
database credentials
unrestricted customer lists
write operations
administrative Laravel routes
```

The server will expose only narrowly scoped, validated read-only tools. Every future tool invocation should be auditable.

## Transport direction

The learning implementation will use an MCP-compatible HTTP transport. Local development will keep it private on the developer machine. A public remote MCP server will not be created until authentication, authorization, audit logging, and deployment requirements are designed.
