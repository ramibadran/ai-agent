# Delivery Plan

## Stage 0 — Foundation (current)

- Separate Laravel 13 application.
- Separate MySQL database: `commerce_operations_mcp`.
- Documentation of the domain, architecture, and tool contracts.
- No MCP tools, commerce models, migrations, or agent integration.

## Stage 1 — Commerce domain

- Add migrations, Eloquent models, factories, and fictional seed data.
- Add normal Laravel reporting services.
- Add feature tests for sales and inventory calculations.

## Stage 2 — Reporting API for verification

- Add internal, API-key-protected Laravel endpoints for validating report services.
- Compare responses with seeded database values.
- Do not expose a public MCP server yet.

## Stage 3 — Read-only MCP server

- Add MCP transport and tool discovery.
- Implement the approved read-only tools from `MCP_TOOL_CONTRACTS.md`.
- Validate inputs and return structured outputs.
- Log tool calls and errors.

## Stage 4 — AI Agent integration

- Register the Commerce MCP server as a controlled tool source for `ai-agent-be`.
- Add a dedicated Commerce mode in the chat UI.
- Display tool sources separately from RAG and web citations.

## Stage 5 — Security and production readiness

- Add MCP authentication and authorization.
- Add audit records for tool calls.
- Add per-tool rate limits and timeouts.
- Add evaluation cases for tool selection and factual answer quality.
- Keep write tools disabled unless explicit human approval is designed and tested.
